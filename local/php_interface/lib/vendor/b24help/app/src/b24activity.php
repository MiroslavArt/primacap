<?php

namespace B24help\App;

use Bitrix24\Bitrix24;
use Monolog\Handler\BrowserConsoleHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

/**
 * Класс для создания действий (activity) бизнес-процессов и "роботов воронок".
 * Не сохраняет авторизацию (использует передаваемую при вызове).
 */
abstract class B24Activity
{
    public static $DELETE_ENABLED = true;

    protected $log;
    protected $obB24App;
    protected $arRequest;
    protected $appFile;

    protected $lang;

    /**
     * B24Activity constructor.
     *
     * @param string $client_id
     * @param string $client_secret
     * @param Logger $logger
     *
     * @throws B24Exception
     */
    protected function __construct($appFile, $client_id, $client_secret, $logger = null, &$arRequest = null)
    {
        $this->log = $logger;
        $this->arRequest = null === $arRequest ? $_REQUEST : $arRequest;
        $this->appFile = $appFile;

        $obB24App = new Bitrix24(false, $this->log);
        $this->obB24App = $obB24App;

        $obB24App->setApplicationId($client_id);
        $obB24App->setApplicationSecret($client_secret);

        $this->lang = 'ru'; // Слегка костыль, по-уму надо передавать в run
        if (false !== strpos($_SERVER['REQUEST_URI'], '/en/')) {
            $this->lang = 'en';
        }

        $obB24App->setCustomCurlOptions([
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_TIMEOUT => 5,
        ]);

        if (!isset($this->arRequest['auth'])) {
            if (empty($this->arRequest['member_id']) || empty($this->arRequest['AUTH_ID'])) {
                throw new B24Exception('auth not set in request');
            }

            $doaminDefault = empty($this->config['LOCAL']['domain'])
                ? null
                : $this->config['LOCAL']['domain'];

            if (empty($doaminDefault) && !empty($_SERVER['HTTP_ORIGIN'])) {
                $url = parse_url($_SERVER['HTTP_ORIGIN']);
                $doaminDefault = $url['host'];
            }

            $this->arRequest['auth'] = [
                'domain' => empty($this->arRequest['DOMAIN'])
                    ? $doaminDefault
                    : $this->arRequest['DOMAIN'],
                'member_id' => $this->arRequest['member_id'],
                'access_token' => $this->arRequest['AUTH_ID'],
                'refresh_token' => $this->arRequest['REFRESH_ID'],
            ];
        }

        $obB24App->setDomain($this->arRequest['auth']['domain']);
        $obB24App->setAccessToken($this->arRequest['auth']['access_token']);
        $obB24App->setMemberId($this->arRequest['auth']['member_id']);
    }

    // Определите в классе реализации для логирования
    public static $SLACK_URL = null;

    /**
     * Пишет сообщение в канал slack
     * curl -X POST -H 'Content-type: application/json' --data '{"text":"Hello, World!"}' SLACK_URL.
     *
     * @param string $message
     * @param [any]  $context
     */
    protected static function slack($message, $context = null, $slackUrl = null)
    {
        if (empty($slackUrl)) {
            $slackUrl = static::$SLACK_URL;
        }
        if (empty($slackUrl) || !function_exists('curl_init')) {
            return;
        }
        $ch = curl_init();
        curl_setopt(
            $ch,
            CURLOPT_URL,
            $slackUrl
        );
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type:application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'text' => $_SERVER['SERVER_NAME'].' ['.__CLASS__.']: '.$message.
                (null === $context ? '' : "\n\n".print_r($context, true)),
        ]));
        curl_exec($ch);
        curl_close($ch);
    }

    /**
     * Исполняется при обращении к приложению из Б24
     * или ручном запуске приложения.
     */
    abstract protected function execute();

    /**
     * Возвращает путь к файлу.
     *
     * @return string
     */
    abstract protected function getSelfFile();

    /**
     * Дополнительная обработка описания активити
     * (например, динамически ).
     *
     * @param array $arActivity
     */
    protected function prepareActivity(&$arActivity)
    {
        // реализуется в конкретных имплементациях
    }

    /**
     * Выполненяется при событии установки приложения
     * Создается действие БП
     * Может быть перекрыт, для дополнительный действий (например, подписки на события).
     */
    protected function install($handler = null)
    {
        $jsonFile = str_replace('.php', '.json', $this->getSelfFile());

        $this->log->info('install: '.$jsonFile);

        if (file_exists($jsonFile)) {
            $arActivity = json_decode(file_get_contents($jsonFile), true);
            if (!array_key_exists('HANDLER', $arActivity)) {
                $arActivity['HANDLER'] = null === $handler
                    ? $_SERVER['SCRIPT_URI']
                    : $handler;
            }
            if (!array_key_exists('CODE', $arActivity)) {
                // Подменяем CODE на имя файла из HANDLER, если не задан
                $ar = explode('/', $arActivity['HANDLER']);
                $last = array_pop($ar);
                $code = empty($last)
                    ? array_pop($ar)
                    : str_replace('.php', '', $last);
                if (!empty($code)) {
                    $arActivity['CODE'] = $code;
                }
            }
            $this->prepareActivity($arActivity);
            $this->addBizprocActivity($arActivity);
        }
    }

    /**
     * Добавляет/обновляет действие бизнес-процесса.
     *
     * @param $arActivityParams array описание действия
     */
    protected function addBizprocActivity($arActivityParams)
    {
        $code = $arActivityParams['CODE'];
        if (!empty($arActivityParams['BIZPROC_TARGET']) && is_array($arActivityParams['BIZPROC_TARGET'])) {
            $arTarget = $arActivityParams['BIZPROC_TARGET'];
            unset($arActivityParams['BIZPROC_TARGET']);
            if (count($arTarget) > 1) { // Оказывается, роботы видны и как активити
                $arTarget = \array_filter($arTarget, function ($a) {
                    return 'activity' != $a;
                });
            }

            foreach ($arTarget as $target) {
                $arActivityParams['CODE'] = "{$code}_{$target}";
                $this->delete($target, $arActivityParams['CODE']);
                $this->obB24App->call("bizproc.{$target}.add", $arActivityParams);
            }
        } else {
            $this->delete('activity', $code);
            $this->delete('robot', $code);
            $this->obB24App->call('bizproc.robot.add', $arActivityParams);
        }
    }

    /**
     * Удаляет активити/робота.
     *
     * @param string $target [activity|robot]
     * @param string $code
     *
     * @return array
     */
    protected function delete($target, $code)
    {
        if (!static::$DELETE_ENABLED) {
            return;
        }

        $result = $this->obB24App->call("bizproc.{$target}.list");
        $this->log->info("delete[{$target}]", $result['result']);

        foreach ($result['result'] as $activity) {
            if ($activity == $code) {
                return $this->obB24App->call("bizproc.{$target}.delete", ['code' => $activity]);
            }
        }
    }

    /**
     * Отправка результат выполнения активити.
     *
     * @param $message string Сообщение в журнал бизнес-процесса
     * @param $arResult array Выходные параметры активити
     */
    protected function sendBizprocResult($message, $arResult)
    {
        $arResult = [
            'auth' => $this->arRequest['auth']['access_token'],
            'event_token' => $this->arRequest['event_token'],
            'log_message' => $message,
            'return_values' => $arResult,
        ];

        $this->obB24App->call('bizproc.event.send', $arResult);
        $this->log->debug('sendBizprocResult: '.$message);
    }

    /**
     * Запись сообщения в лог бизнес-процесса.
     *
     * @param $message string текст сообщения в лог
     */
    protected function sendBizprocLog($message)
    {
        if (isset($this->arRequest['event_token'])) {
            $arResult = [
                'event_token' => $this->arRequest['event_token'],
                'log_message' => $message,
            ];
            $this->obB24App->call('bizproc.activity.log', $arResult);
            $this->log->info('sendBizprocLog: '.$message);
        } else {
            $this->log->error('sendBizprocLog [non auth]: '.$message);
        }
    }

    /**
     * Выполняет приложение.
     *
     * @return self|bool
     */
    public static function run($appFile, $clientId, $clientSecret, $logLevel, $handler = null, $arRequest = null)
    {
        $appFile = str_replace('.php', '', $appFile);
        $logger = new Logger(array_pop(explode('/', $appFile)));
        $logger->pushHandler(Logger::DEBUG == $logLevel
            ? new BrowserConsoleHandler()
            : new StreamHandler($appFile.'.log', $logLevel)
        );

        if (null === $arRequest) {
            $arRequest = $_REQUEST;
        }

        try {
            $instance = new static($appFile, $clientId, $clientSecret, $logger, $arRequest);

            if (array_key_exists('install', $arRequest) && 'Y' == $arRequest['install']) {
                $instance->install($handler);
            } else {
                $instance->execute();
            }

            return $instance;
        } catch (B24Exception $e) {
            $logger->error($e->getMessage());

            return null;
        }
    }
}

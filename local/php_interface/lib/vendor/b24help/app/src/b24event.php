<?php

namespace B24help\App;

use Bitrix24\Bitrix24;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

/**
 * Класс для создания обработчиков событий (веб-хуков) Б24
 * Не сохраняет авторизацию (использует передаваемую при вызове).
 */
abstract class B24Event
{
    const ONAPPINSTALL = 'ONAPPINSTALL';

    protected $log;
    protected $obB24App;
    protected $arRequest;
    protected $appFile;
    protected $config;

    /**
     * B24Event constructor.
     *
     * @param string $appFile    полный путь к текущему файлу без расширения
     * @param Logger $logger
     * @param bool   $bAuthSaved true - сохранить полученную авторизацию (для приложений с cron)
     *
     * @throws B24Exception
     */
    public function __construct($appFile, $logger = null, $bAuthSaved = false)
    {
        $this->log = $logger;
        $this->arRequest = $_REQUEST;
        $this->appFile = $appFile;

        $config = \json_decode(\file_get_contents($appFile.'.json'), true);
        $this->log->info(print_r("top", true));
        if (\array_key_exists('LOCAL', $config)) {
            $this->config = $config;
        } else {
            if (array_key_exists('marketplace', $_REQUEST)) {
                $mp = $_REQUEST['marketplace'];
            } elseif (!empty($_REQUEST['LANG']) && in_array($_REQUEST['LANG'], ['ru', 'ua'])) {
                $mp = $_REQUEST['LANG'];
            } else {
                $mp = 'ru';
            }
            $this->arRequest['marketplace'] = $mp;
            if (\array_key_exists($mp, $config)) {
                $this->config = $config[$mp];
            } else {
                throw new B24Exception('incorrect config: '.$appFile.'.json');
            }
        }

        if (empty($this->config)) {
            throw new B24Exception('config not found: '.$appFile.'.json');
        }

        $obB24App = new Bitrix24(false, $this->log);
        $this->obB24App = $obB24App;

        $obB24App->setApplicationId($this->config['LOCAL']['client_id']);
        $obB24App->setApplicationSecret($this->config['LOCAL']['client_secret']);

        $obB24App->setCustomCurlOptions([
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
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

        if ($bAuthSaved) {
            $this->authLoad();
        }
    }

    /**
     * Получение авторизации
     * и продление токена (при необходимости).
     */
    protected function authLoad()
    {
        $result = $this->obB24App->call('app.option.get', []);
        if (!empty($result['result']) && !empty($result['result']['AUTH_DATA'])) {
            $authData = \unserialize($result['result']['AUTH_DATA']);

            $this->obB24App->setDomain($authData['domain']);
            $this->obB24App->setAccessToken($authData['access_token']);
            $this->obB24App->setMemberId($authData['member_id']);

            if ($this->obB24App->isAccessTokenExpire()) {
                $this->obB24App->setApplicationScope(explode(',', $authData['scope']));

                // $this->obB24App->setRedirectUri($this->config['HANDLER']);
                $this->obB24App->setRedirectUri(isset($_SERVER['SCRIPT_URI'])
                    ? $_SERVER['SCRIPT_URI']
                    : 'https://'.$_SERVER['SERVER_NAME'].$_SERVER['SCRIPT_NAME']);

                $this->obB24App->setRefreshToken($authData['refresh_token']);
                $response = $this->obB24App->getNewAccessToken();

                $this->obB24App->setAccessToken($response['access_token']);
                $this->obB24App->setRefreshToken($response['refresh_token']);

                $authData['access_token'] = $response['access_token'];
                $authData['refresh_token'] = $response['refresh_token'];

                $this->obB24App->call('app.option.set', [
                    'AUTH_DATA' => \serialize($authData),
                ]);
            }
        }
    }

    /**
     * Сохраняет авторизацию в параметры приложения.
     */
    protected function authSave($authData)
    {
        $this->obB24App->call('app.option.set', [
            'AUTH_DATA' => \serialize($authData),
        ]);
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
            text => $_SERVER['SERVER_NAME'].' ['.__CLASS__.']: '.$message.
                (null === $context ? '' : "\n\n".print_r($context, true)),
        ]));
        $result = curl_exec($ch);
        curl_close($ch);
    }

    /**
     * Исполняется при обращении к приложению из Б24
     * или ручном запуске приложения.
     */
    abstract protected function execute();

    /**
     * Выполняется при событии установки приложения.
     */
    //abstract protected function install();

    /**
     * Выполняет приложение.
     *
     * @param string $appFile    полный путь к классу приложения
     * @param int    $logLevel   уровень логирования
     * @param bool   $bAuthSaved если true, сохраняется и используется авторизация установщика приложения
     *
     * @return self|bool
     */
    public static function run($appFile, $logLevel, $bAuthSaved = false)
    {
        $appFile = str_replace('.php', '', $appFile);
        $logger = new Logger(array_pop(explode('/', $appFile)));
        $logger->pushHandler(new StreamHandler($appFile.'.log', $logLevel)
        );
        //$logger->notice(print_r($_REQUEST, true));
        try {
            $instance = new static($appFile, $logger, $bAuthSaved);

            if (
                (\array_key_exists('install', $_REQUEST) && 'Y' == $_REQUEST['install']) ||
                (\array_key_exists('event', $_REQUEST) && self::ONAPPINSTALL == $_REQUEST['event'])
            ) {
                $logger->notice(print_r("install", true));
                $instance->install();

                if ($bAuthSaved && !empty($_REQUEST['auth'])) {
                    $instance->authSave($_REQUEST['auth']);
                }
            } else {
                $instance->execute();
            }

            return $instance;
        } catch (B24Exception $e) {
            $logger->error($e->getMessage());

            return null;
        } catch (\Bitrix24\Exceptions\Bitrix24ApiException $e) {
            $logger->error($e->getMessage());

            return null;
        }
    }
}

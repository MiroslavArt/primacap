<?php

namespace B24help\App;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;

/**
 * Класс для создания обработчиков событий (исходящих веб-хуков) Б24
 * Не сохраняет авторизацию (упрощенный вызов входящих веб-хуков).
 */
abstract class B24Hook
{
    protected $log;
    protected $arRequest;
    protected $appFile;
    protected $hookUrl;
    protected $config;

    /**
     * Hook constructor.
     *
     * @param string $hookUrl
     * @param array  $config
     * @param Logger $logger
     *
     * @throws B24Exception
     */
    public function __construct($hookUrl, $config, $logger = null)
    {
        $this->log = $logger;
        $this->arRequest = $_REQUEST;
        $this->hookUrl = $hookUrl;
        $this->config = $config;
    }

    protected static $SLACK_URL = null;

    /**
     * Пишет сообщение в канал slack
     * curl -X POST -H 'Content-type: application/json' --data '{"text":"Hello, World!"}' SLACK_URL.
     *
     * @param string $message
     * @param [any]  $context
     */
    protected static function slack($message, $context = null)
    {
        if (null === static::$SLACK_URL) {
            return;
        }

        $ch = curl_init();
        curl_setopt(
            $ch,
            CURLOPT_URL,
            static::$SLACK_URL
        );
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type:application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            text => $_SERVER['SERVER_NAME'].' [BA\Hook]: '.$message.
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

    protected function callEx($hook, $method, $params)
    {
        $queryUrl = $hook.$method.'.json';
        $queryData = http_build_query($params);
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_POST => 1,
            CURLOPT_HEADER => 0,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => $queryUrl,
            CURLOPT_POSTFIELDS => $queryData,
        ]);

        $result = json_decode(curl_exec($curl), true);
        curl_close($curl);

        return $result;
    }

    /**
     * Выполняет запрос к REST API Б24 через входящий веб-хук
     * или параметры переданной авторизации.
     *
     * @return mixed
     */
    protected function call($method, $params)
    {
        $url = null === $this->hookUrl
            ? $_REQUEST['auth']['client_endpoint']
            : $this->hookUrl.'/';

        if (null === $this->hookUrl && !empty($_REQUEST['auth']['access_token'])) {
            $params['auth'] = $_REQUEST['auth']['access_token'];
        }

        return $this->callEx($url, $method, $params);
    }

    /**
     * Выполняет приложение.
     *
     * @param string $appFile    полный путь к классу приложения
     * @param string $configFile
     * @param int    $logLevel   уровень логирования
     *
     * @return self|bool
     */
    public static function run($appFile, $configFile, $logLevel = Logger::WARNING)
    {
        $appFile = str_replace('.php', '', $appFile);
        $logger = new Logger(array_pop(explode('/', $appFile)));
        $logger->pushHandler(new StreamHandler($appFile.'.log', $logLevel));

        try {
            $config = \json_decode(\file_get_contents($configFile), true);

            if (\array_key_exists('slack', $config['URL'])) {
                static::$SLACK_URL = $config['URL']['slack'];
            }

            $instance = new static($config['URL']['hook'], $config, $logger);
            $instance->execute();

            return $instance;
        } catch (Exception $e) {
            $logger->error($e->getMessage());

            return null;
        }
    }
}

<?php

namespace B24help\App;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\BrowserConsoleHandler;
use Bitrix24\Bitrix24;

abstract class B24Application
{
    const JSON_APP = 'app.json';
    const JSON_OAUTH = 'oauth.json';

    protected $log;
    protected $obB24App;

    protected $arApp;
    protected $arOAuth;

    private $jsonOAuth;

    protected $arRequest;

    /**
     * B24Application constructor.
     *
     * @param $logger Logger
     * @param $jsonApp string путь к файлу настроек приложения
     * @param $jsonOAuth string путь к файлу oauth
     * @param $scope string
     *
     * @throws B24Exception
     */
    public function __construct($logger, $jsonApp, $jsonOAuth, $scope)
    {
        $this->log = $logger;
        $this->jsonOAuth = $jsonOAuth;

        $this->arRequest = $_REQUEST;

        if (!file_exists($jsonApp)) {
            throw new B24Exception("app config not found ({$jsonApp}), please open application with ?install=Y");
        }
        if (!file_exists($jsonOAuth)) {
            throw new B24Exception("oauth config not found ({$jsonOAuth}), please install app in your Bitrix24 account");
        }
        $this->arApp = json_decode(file_get_contents($jsonApp), true);
        $this->arOAuth = json_decode(file_get_contents($jsonOAuth), true);

        $obB24App = new Bitrix24(false, $this->log);
        $this->obB24App = $obB24App;

        $obB24App->setApplicationScope(explode(',', $scope));
        $obB24App->setApplicationId($this->arApp['client_id']);
        $obB24App->setApplicationSecret($this->arApp['client_secret']);

        $obB24App->setCustomCurlOptions(array(
            CURLOPT_FOLLOWLOCATION => false,
        ));

        if (isset($_REQUEST['auth'])) { // Если авторизация пришла из Б24, используем ее
            $obB24App->setDomain($_REQUEST['auth']['domain']);
            $obB24App->setAccessToken($_REQUEST['auth']['access_token']);
            $obB24App->setMemberId($_REQUEST['auth']['member_id']);
        } else {
            $obB24App->setDomain(isset($this->arApp['domain']) ? $this->arApp['domain'] : $this->arOAuth['domain']);
            $obB24App->setAccessToken($this->arOAuth['access_token']);
            $obB24App->setMemberId($this->arOAuth['member_id']);

            if ($obB24App->isAccessTokenExpire()) {
                $this->refreshToken();
            }
        }
    }

    /**
     * Обновление токена.
     */
    protected function refreshToken()
    {
        $this->obB24App->setRedirectUri($this->arApp['redirect_uri']);
        $this->obB24App->setRefreshToken($this->arOAuth['refresh_token']);
        $response = $this->obB24App->getNewAccessToken();
        $response['member_id'] = $this->arOAuth['member_id'];

        file_put_contents($this->jsonOAuth, json_encode($response));

        $this->obB24App->setAccessToken($response['access_token']);
        $this->obB24App->setRefreshToken($response['refresh_token']);
    }

    /**
     * Исполняется при обращении к приложению из Б24
     * или ручном запуске приложения.
     */
    abstract protected function execute();

    /**
     * Выполненяется после установки приложения
     * Применяется, например, для создания действия БП и других разовых действий.
     */
    protected function afterInstall()
    {
        $this->log->debug('afterInstall');
    }

    /**
     * Добавляет/обновляет действие бизнес-процесса.
     *
     * @param $arActivityParams array описание действия
     */
    protected function addBizprocActivity($arActivityParams)
    {
        $result = $this->obB24App->call('bizproc.activity.list');

        foreach ($result['result'] as $activity) {
            if ($activity == $arActivityParams['CODE']) {
                $this->obB24App->call('bizproc.activity.delete', array('code' => $activity));
            }
        }

        $this->obB24App->call('bizproc.activity.add', $arActivityParams);
    }

    /**
     * Отправка результат выполнения активити.
     *
     * @param $message string Сообщение в журнал бизнес-процесса
     * @param $arResult array Выходные параметры активити
     */
    protected function sendBizprocResult($message, $arResult)
    {
        $arResult = array(
            'auth' => $this->arRequest['auth']['access_token'],
            'event_token' => $this->arRequest['event_token'],
            'log_message' => $message,
            'return_values' => $arResult,
        );

        $this->obB24App->call('bizproc.event.send', $arResult);
        $this->log->debug('BizprocResult: '.$message);
    }

    /**
     * Запись сообщения в лог бизнес-процесса.
     *
     * @param $message string текст сообщения в лог
     */
    protected function sendBizprocLog($message)
    {
        if (isset($this->arRequest['event_token'])) {
            $arResult = array(
                'event_token' => $this->arRequest['event_token'],
                'log_message' => $message,
            );
            $this->obB24App->call('bizproc.activity.log', $arResult);
            $this->log->info('BizprocLog: '.$message);
        } else {
            $this->log->error('BizprocLog (non auth): '.$message);
        }
    }

    public static function createLogger($name, $level, $logPath)
    {
        $log = new Logger($name);
        $log->pushHandler(Logger::DEBUG == $level ? new BrowserConsoleHandler() : new StreamHandler($logPath, $level));

        return $log;
    }

    /**
     * Выполняет приложение.
     *
     * @param $scope string
     * @param $level int уровень логирования
     * @param $appPath string путь к приложению (если не указан, определяется по $_SERVER['PHP_SELF']
     *
     * @return static|bool
     */
    public static function run($scope, $level, $appPath = '')
    {
        if (strlen($appPath) > 0) {
            $appName = str_replace('/', '.', substr($appPath, strlen($_SERVER['DOCUMENT_ROOT']) + 1));
            $jsonApp = $appPath.'/'.static::JSON_APP;
            $jsonOAuth = $appPath.'/'.static::JSON_OAUTH;
            $log = static::createLogger($appName, $level, $_SERVER['DOCUMENT_ROOT'].'/log/'.$appName.'.log');
        } else {
            $appName = explode('/', $_SERVER['PHP_SELF'])[1];
            $jsonApp = $_SERVER['DOCUMENT_ROOT'].'/'.$appName.'/'.static::JSON_APP;
            $jsonOAuth = $_SERVER['DOCUMENT_ROOT'].'/'.$appName.'/'.static::JSON_OAUTH;
            $log = static::createLogger($appName, $level, $_SERVER['DOCUMENT_ROOT'].'/log/'.explode('/', $_SERVER['PHP_SELF'])[1].'.log');
        }

        if ((isset($_REQUEST['b24action']) && 'install' == strtolower($_REQUEST['b24action']))
            || (isset($_REQUEST['state']) && 'b24action' == $_REQUEST['state'])) {
            ?>
			<html>
			<head>
				<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
				<style>
					body {font-family: sans-serif}
					.error {color: red}
				</style>
			</head>
			<body>
			<h3>Настройка приложения <?=$appName; ?></h3>
			<?php

            if (isset($_REQUEST['code'])) {
                $arApp = json_decode(file_get_contents($jsonApp), true);

                $token_uri = 'https://oauth.bitrix.info/oauth/token/?client_id='
                    .$arApp['client_id'].'&grant_type=authorization_code&client_secret='
                    .$arApp['client_secret'].'&code='.$_REQUEST['code'];

                $response = file_get_contents($token_uri);

                $arResponse = json_decode($response, true);

                if (isset($arResponse['error'])) {
                    ?><p class="error">Ошибка: <?=$arResponse['error']; ?></p><?php
                } else {
                    file_put_contents($jsonOAuth, $response);

                    $instance = new static($log, $jsonApp, $jsonOAuth, $scope);
                    $instance->afterInstall(); ?><p>Приложение настроено (по полной авторизации)</p><?php
                }
            } elseif ('POST' == $_SERVER['REQUEST_METHOD']) {
                $_REQUEST['redirect_uri'] = isset($_SERVER['SCRIPT_URI']) ?
                    $_SERVER['SCRIPT_URI'] : 'https://'.$_SERVER['SERVER_NAME'].$_SERVER['SCRIPT_NAME'];

                unset($_REQUEST['b24action']);

                file_put_contents($jsonApp, json_encode($_REQUEST));

                if (file_exists($jsonOAuth)) {
                    $instance = new static($log, $jsonApp, $jsonOAuth, $scope);
                    $instance->afterInstall(); ?>Приложение настроено (по быстрой авторизации)<?php
                } else {
                    if (empty($_REQUEST['domain'])) {
                        ?><p class="error">Не найден <b>oauth.json</b> и не указан домен. Повторите установку.</p><?php
                    } else {
                        ?><a href="https://<?=$_REQUEST['domain']; ?>/oauth/authorize/?client_id=<?=$_REQUEST['client_id']; ?>&response_type=code&redirect_uri=<?=rawurlencode($_REQUEST['redirect_uri']); ?>&state=b24action">Авторизовать приложение в <b><?=$_REQUEST['domain']; ?></b></a><?php
                    }
                }
            } else {
                ?>
				<form method="post">
					<input type="hidden" name="install" value="Y">
					<label for="client_id"><b>Код приложения</b> (client_id):</label><br/>
					<input type="text" name="client_id" id="client_id" size="100"><br/><br/>
					<label for="client_secret"><b>Ключ приложения</b> (client_secret):</label><br/>
					<input type="text" name="client_secret" id="client_secret" size="100"><br/><br/>
					<label for="domain">Домен (адрес портала, для полного сценария установки):</label><br/>
					<input type="text" name="domain" id="domain" size="100"><br/><br/>
					<input type="submit" value="Сохранить">
				</form>
				<?php
            } ?>
			</body>
			</html>
			<?php

            return null;
        } elseif (isset($_REQUEST['b24action']) && 'afterinstall' == strtolower($_REQUEST['b24action'])) {
            /* Принудительное выполнение процедуры установки */

            $instance = new static($log, $jsonApp, $jsonOAuth, $scope);
            $instance->afterInstall();
        } else {
            if (isset($_REQUEST['auth']) && isset($_REQUEST['auth']['access_token']) && isset($_REQUEST['auth']['refresh_token'])) {
                /* Сохраняем авторизацию (приходит с важными событиями), не отменяем дальнейшую обработку */
                file_put_contents($jsonOAuth, json_encode($_REQUEST['auth']));
                $log->info('oAuth data saved');
            }

            try {
                $instance = new static($log, $jsonApp, $jsonOAuth, $scope);
                $instance->execute();

                return $instance;
            } catch (B24Exception $e) {
                $log->error($e->getMessage());

                return null;
            }
        }

        return null;
    }
}

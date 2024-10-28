<?php

namespace PrimocapitalReport;

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
require_once($_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/lib/vendor/autoload.php');

use B24help\App\B24Event;
use B24help\App\B24Exception;
use Bitrix24\Bitrix24;
use Monolog\Logger;

class Handler extends B24Event
{
    const IS_QUEUE = true;
    const PATH_ACTIVITIES = '/local/app/activities';
    const TARGETS = ['robot', 'activity']; // Сначала роботы, так как они включают активити
    const EN_DISABLED = [];  // Cписок активити для исключения в EN

    private static $XDEBUG_ENABLED = false;
    private $mp = null;


    /**
     * BA constructor.
     *
     * Поддерживает виртуальные приложения
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

        $obB24App = new Bitrix24(false, $this->log);
        $this->obB24App = $obB24App;

        static::$XDEBUG_ENABLED = extension_loaded('xdebug');

        $config = \json_decode(\file_get_contents('index.json'), true);

        if($config) {
            /*if (array_key_exists('marketplace', $_REQUEST)) {
                $mp = $_REQUEST['marketplace'];
            } elseif (!empty($_REQUEST['LANG']) && in_array($_REQUEST['LANG'], ['ru', 'en'])) {
                $mp = $_REQUEST['LANG'];
            } else {
                $mp = 'en';
            }
            $this->mp = $mp;
            $_REQUEST['marketplace'] = $mp;

            if (\array_key_exists($mp, $config)) {
                $this->config = $config[$mp];
            } else {
                throw new B24Exception('incorrect config: '.$appFile.'.json');
            }*/
            $this->config = $config;
            if (empty($this->config)) {
                throw new B24Exception('config not found: '.$appFile.'.json');
            }

            // region: Обработка виртуальных приложений
            /*if (!empty($_REQUEST['appId']) && is_numeric($_REQUEST['appId'])) {
                $configEx = $this->internalCall('ba.virtual.config.get', ['ID' => $_REQUEST['appId']]);
                if (empty($configEx['result'])) {
                    throw new B24Exception("config for appId #{$_REQUEST['appId']} not found");
                }
                $this->config = array_replace_recursive($this->config, $configEx['result']);
                $this->config['LOCAL']['redirect_uri'] = str_replace(
                    '/local/app/',
                    "/local/app/{$_REQUEST['appId']}/",
                    $this->config['LOCAL']['redirect_uri']
                );
            }*/
            // end region

            $obB24App = new Bitrix24(false, $this->log);
            $this->obB24App = $obB24App;

            $obB24App->setApplicationId($this->config['client_id']);
            $obB24App->setApplicationSecret($this->config['client_secret']);

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
    }

    private $isAdmin = false;


    /**
     * Устанавливает (переустанавливает) роботы и активити приложения.
     */
    protected function install()
    {
        //if ('ONAPPINSTALL' === $this->arRequest['event']) {
        // Игнорируем событие, так как установка идет через интерфейс
        //return;
        //}

        //$this->log->notice("[{$this->arRequest['auth']['domain']}] install start");

        //$result = $this->internalCall('ba.instance.install', ['INSTALL_REQUEST' => $this->arRequest]);
        //$arActivityList = $result['result'];
        $this->log->notice("[{$this->arRequest}] install start");
        $isSuccess = true;

        if ($isSuccess) {
            $arInstalled = [];
            $this->log->notice("[{$this->arRequest}] installed: ");
            $this->log->notice("[{$this->arRequest['auth']['domain']}] installed: ".join(', ', $arInstalled)); ?>
            <html>
            <head>
                <script src="//api.bitrix24.com/api/v1/"></script>
                <script>
                    BX24.installFinish();
                </script>
            </head>
            <body>
            installed...</body>
            </html>
            <?php
        } else {
            header("Location: {$this->config['ERROR_URL']}");
        }
    }

    /**
     * Создает задачу о критической ошибке.
     *
     * @return void
     */
    protected function addErrorTask($arErrorTask)
    {
        $date = new \DateTime();
        $date->add(new \DateInterval('P1D'));

        if (empty($arErrorTask['USER_ID'])) {
            $result = $this->obB24App->call('user.current', []);
            $arErrorTask['USER_ID'] = $result['result']['ID'];
        }

        $result = $this->obB24App->call('tasks.task.add', ['fields' => [
            'TITLE' => $arErrorTask['TITLE'],
            'DESCRIPTION' => $arErrorTask['DESCRIPTION'],
            'DEADLINE' => $date->format("Y-m-d\TH:i:sP"),
            'CREATED_BY' => $arErrorTask['USER_ID'],
            'RESPONSIBLE_ID' => $arErrorTask['USER_ID'],
        ]]);
    }

    /**
     * Выполнение активити.
     */
    protected function execute()
    {
        $result = $this->obB24App->call('user.get', ["ACTIVE" => true]);
        $commands = [];
        $names = [];
        foreach ($result['result'] as $item) {
            $names[$item['ID']] = $item['NAME'].' '.$item['LAST_NAME'];
            $commands['timeman_'.$item['ID']] = 'timeman.status?'
                .http_build_query(array(
                    'USER_ID' => $item['ID']
                ));
        }
        $result = $this->obB24App->call('batch', ['halt' => 0, 'cmd' => $commands]);

        echo "Availability status at ".date('l jS \of F Y h:i:s A');
        echo "<br/>";
        ?>
        <table style="text-align: left; border: black 1px solid;">
            <th>Agent</th>
            <th>Status</th>
            <?php
            foreach ($result['result']['result'] as $key => $item) {
                ?>
                <tr style="border: darkslateblue 1px">
                    <td><?= $names[preg_replace("/[^0-9]/", '', $key)]?></td>
                    <td><?= $item['STATUS']?></td>
                </tr>
                <?
            }
            ?>
        </table>
        <?php
    }

    protected function internalCall($method, $arParams)
    {
        $url = 'https://'.$_SERVER['HTTP_HOST'].$this->config['BA_INTERNAL_REST_API'];

        return $this->call($url, $method, $arParams);
    }

    /**
     * Отправляет POST-запрос в JSON.
     *
     * @param string $url
     * @param string $method
     * @param array  $arParams
     *
     * @return array
     *
     * @throws B24Exception
     */
    protected static function call($url, $method, $arParams)
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_USERAGENT, 'Opera/9.80 (Windows NT 5.1; U; ru) Presto/2.9.168 Version/11.51');
        curl_setopt($ch, CURLOPT_URL, $url.$method);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($arParams));
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        if (static::$XDEBUG_ENABLED) {
            curl_setopt($ch, CURLOPT_COOKIE, 'XDEBUG_SESSION=PHPSTORM');
        }

        $result = curl_exec($ch);

        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);
        $result = json_decode($result, true);

        if ($httpcode >= 400) {
            throw new B24Exception(is_array($result) && array_key_exists('error_description', $result) ? "INTERNAL_API_ERROR ({$url}): {$result['error_description']}" : 'INTERNAL_API_ERROR: '.print_r($result, true));
        }

        return $result;
    }
}


Handler::run(
    __FILE__,
    extension_loaded('xdebug')
        ? \Monolog\Logger::INFO
        : \Monolog\Logger::NOTICE,
    false
);


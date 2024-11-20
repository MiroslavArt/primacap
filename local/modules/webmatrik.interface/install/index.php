<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\EventManager;
use Exception;

Loc::loadMessages(__FILE__);

class webmatrik_interface extends \CModule
{
    /**
     * @return string
     */
    public static function getModuleId()
    {
        return basename(dirname(__DIR__));
    }

    /**
     *
     */
    public function __construct()
    {
        $arModuleVersion = [];
        include(dirname(__FILE__) . "/version.php");
        $this->MODULE_ID = self::getModuleId();
        $this->MODULE_VERSION = $arModuleVersion["VERSION"];
        $this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
        $this->MODULE_NAME = Loc::getMessage("WEBMATRIK_INTERFACE_MODULE_NAME");
        $this->MODULE_DESCRIPTION = Loc::getMessage("WEBMATRIK_INTERFACE_MODULE_DESC");
        $this->PARTNER_NAME = Loc::getMessage("WEBMATRIK_INTERFACE_PARTNER_NAME");
        $this->PARTNER_URI = Loc::getMessage("WEBMATRIK_INTERFACE_PARTNER_URI");
    }

    /**
     * @return void
     */
    public function installEvents(): void
    {
        $eventManager = EventManager::getInstance();
        $eventManager->registerEventHandler('main','OnProlog', $this->MODULE_ID, '\Webmatrik\Interface\Events\MainEvents','onProlog');
    }

    /**
     * @return void
     */
    public function uninstallEvents(): void
    {
        $eventManager = EventManager::getInstance();
        $eventManager->unRegisterEventHandler('main','OnProlog', $this->MODULE_ID, '\Webmatrik\Interface\Events\MainEvents','onProlog');

    }

    /**
     * @param array $arParams
     * @return void
     */
    public function installFiles(array $arParams = []): void
    {
        CopyDirFiles(__DIR__ . '/js/', $_SERVER['DOCUMENT_ROOT'] . '/local/js', true, true);
        //CopyDirFiles(__DIR__ . '/css/', $_SERVER['DOCUMENT_ROOT'] . '/local/css', true, true);
    }

    /**
     * @return void
     */
    public function uninstallFiles(): void
    {
    }

    /**
     * @return bool
     */
    function InstallDB(): bool
    {
        global $APPLICATION, $DB;
        $bInstall = true;

        return $bInstall;
    }

    /**
     * @return bool
     */
    function UnInstallDB(): bool
    {
        global $APPLICATION, $DB;
        $bInstall = true;

        if ($errors !== false) {
            $APPLICATION->throwException(is_array($errors) ? implode('', $errors) : $errors);
            $bInstall = false;
        }

        return $bInstall;
    }

    /**
     * @return bool
     */
    public function doInstall(): bool
    {
        try {
            $this->InstallDB();
            $this->installFiles();
            $this->installEvents();
            Main\ModuleManager::registerModule($this->MODULE_ID);
        } catch (Exception $e) {
            global $APPLICATION;
            $APPLICATION->ThrowException($e->getMessage());

            return false;
        }

        return true;
    }

    /**
     * @return bool
     */
    public function doUninstall(): bool
    {
        try {
            $this->uninstallEvents();
            Main\ModuleManager::unRegisterModule($this->MODULE_ID);
            $this->uninstallFiles();
            $this->UnInstallDB();
        } catch (Exception $e) {
            global $APPLICATION;
            $APPLICATION->ThrowException($e->getMessage());

            return false;
        }

        return true;
    }
}
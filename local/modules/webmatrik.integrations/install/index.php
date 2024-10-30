<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class webmatrik_integrations extends \CModule
{
    /**
     * @return string
     */
    public static function getModuleId()
    {
        return basename(dirname(__DIR__));
    }

    public function __construct()
    {
        $arModuleVersion = array();
        include(dirname(__FILE__) . "/version.php");
        $this->MODULE_ID = self::getModuleId();
        $this->MODULE_VERSION = $arModuleVersion["VERSION"];
        $this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
        $this->MODULE_NAME = Loc::getMessage("WEBMATRIK_INTEGRATIONS_MODULE_NAME");
        $this->MODULE_DESCRIPTION = Loc::getMessage("WEBMATRIK_INTEGRATIONS_MODULE_DESC");

        $this->PARTNER_NAME = Loc::getMessage("WEBMATRIK_INTEGRATIONS_PARTNER_NAME");
        $this->PARTNER_URI = Loc::getMessage("WEBMATRIK_INTEGRATIONS_PARTNER_URI");
    }

    public function installEvents()
    {
        $eventManager = \Bitrix\Main\EventManager::getInstance();
    }

    public function uninstallEvents()
    {
        $eventManager = \Bitrix\Main\EventManager::getInstance();
    }

    public function installFiles($arParams = array())
    {
    }

    public function uninstallFiles()
    {
    }

    function InstallDB()
    {
    }

    function UnInstallDB()
    {
    }

    public function doInstall()
    {
        try {
            $this->InstallDB();
            $this->installFiles();
            $this->installEvents();
            Main\ModuleManager::registerModule($this->MODULE_ID);
        } catch (\Exception $e) {
            global $APPLICATION;
            $APPLICATION->ThrowException($e->getMessage());

            return false;
        }

        return true;
    }

    public function doUninstall()
    {
        try {
            $this->uninstallEvents();
            Main\ModuleManager::unRegisterModule($this->MODULE_ID);
            $this->uninstallFiles();
            $this->UnInstallDB();
        } catch (\Exception $e) {
            global $APPLICATION;
            $APPLICATION->ThrowException($e->getMessage());

            return false;
        }

        return true;
    }
}
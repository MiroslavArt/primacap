<?php

use Bitrix\Main\Application;
use Bitrix\Main\Config\Option as Option;
use Bitrix\Main\Loader as Loader;
use Bitrix\Main\Localization\Loc as Loc;
use Bitrix\Main\ModuleManager;

Loc::loadMessages(__FILE__);

class bit_notification_activity extends CModule
{
    const MODULE_ID = 'bit.notification.activity';

    var $MODULE_ID = self::MODULE_ID;
    var $MODULE_VERSION;
    var $MODULE_VERSION_DATE;
    var $MODULE_NAME;
    var $MODULE_DESCRIPTION;

    public function __construct()
    {
        $arModuleVersion = array();
        include(dirname(__FILE__) . '/version.php');
        $this->MODULE_VERSION = $arModuleVersion['VERSION'];
        $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];

        $this->MODULE_NAME = Loc::getMessage('BIT_NOTE_ACT_MODULE_NAME');
        $this->MODULE_DESCRIPTION = Loc::getMessage('BIT_NOTE_ACT_MODULE_DESC');

        $this->PARTNER_NAME = Loc::getMessage('BIT_NOTE_ACT_PARTNER_NAME');
        $this->PARTNER_URI =  Loc::getMessage('BIT_NOTE_ACT_PARTNER_URI');
    }

    public function DoInstall()
    {
        ModuleManager::registerModule(self::MODULE_ID);
        $this->installDB();
        $this->installAgent();
        $this->installEvent();
    }

    public function installDB()
    {
        if(Loader::includeModule($this->MODULE_ID))
        {
            \Bit\Notification\Activity\CheckActivityTable::getEntity()->createDbTable();
        }
    }
    public function installAgent()
    {
        \CAgent::AddAgent(
            'Bit\Notification\Activity\Agent::runAgent();',
            self::MODULE_ID,
            'N',
            60, // каждые 30 минут
            '',
            "Y",
            '',
            100
        );

        \CAgent::AddAgent(
            'Bit\Notification\Activity\Agent::runClearTable();',
            self::MODULE_ID,
            'N',
            60*60*24, // каждый 1 день
            '',
            "Y",
            '',
            100
        );

    }
    public function installEvent()
    {
        if (Loader::includeModule($this->MODULE_ID))
        {
            \Bit\Notification\Activity\Event::installEvent();
        }
    }

    public function DoUninstall()
    {
        $this->unInstallDB();
        $this->unInstallAgent();
        $this->unInstallEvent();
        Option::delete(self::MODULE_ID);

        ModuleManager::unRegisterModule(self::MODULE_ID);
    }

    public function unInstallDB()
    {
        if (CModule::includeModule($this->MODULE_ID))
        {
            if (Application::getConnection()->isTableExists(\Bit\Notification\Activity\CheckActivityTable::getTableName()))
            {
                $connection = Application::getInstance()->getConnection();
                $connection->dropTable(\Bit\Notification\Activity\CheckActivityTable::getTableName());
            }
        }
    }
    public function unInstallAgent()
    {
        \CAgent::RemoveAgent(
            'Bit\Notification\Activity\Agent::runAgent();',
            self::MODULE_ID
        );
        \CAgent::RemoveAgent(
            'Bit\Notification\Activity\Agent::runClearTable();',
            self::MODULE_ID
        );
    }
    public function unInstallEvent()
    {
        if (Loader::includeModule($this->MODULE_ID))
        {
            \Bit\Notification\Activity\Event::unInstallEvent();
        }
    }

}
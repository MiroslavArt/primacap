<?php

use Bitrix\Main\Application;
use Bitrix\Main\Config\Option as Option;
use Bitrix\Main\Loader as Loader;
use Bitrix\Main\Localization\Loc as Loc;
use Bitrix\Main\ModuleManager;

Loc::loadMessages(__FILE__);

class bit_distribution_lead extends CModule
{
    const MODULE_ID = 'bit.distribution.lead';

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

        $this->MODULE_NAME = Loc::getMessage('BIT_DISTRIBUTION_LEAD_MODULE_NAME');
        $this->MODULE_DESCRIPTION = Loc::getMessage('BIT_DISTRIBUTION_LEAD_MODULE_DESC');

        $this->PARTNER_NAME = Loc::getMessage('BIT_DISTRIBUTION_LEAD_PARTNER_NAME');
        $this->PARTNER_URI =  Loc::getMessage('BIT_DISTRIBUTION_LEAD_PARTNER_URI');
    }

    public function DoInstall()
    {
        ModuleManager::registerModule(self::MODULE_ID);
        $this->installDB();
        $this->installAgent();
    }

    public function installDB()
    {
        if(Loader::includeModule($this->MODULE_ID))
        {
            \Bit\Distribution\Lead\DistributionTable::getEntity()->createDbTable();
            \Bit\Distribution\Lead\DistributionLeadTable::getEntity()->createDbTable();
        }
    }
    public function installAgent()
    {
        \CAgent::AddAgent(
            'Bit\Distribution\Lead\Agent::runAgent();',
            self::MODULE_ID,
            'N',
            86400, // каждые 30 минут
            date('d.m.Y 05:00:00'),
            "Y",
            date('d.m.Y 05:00:00'),
            100
        );
    }

    public function DoUninstall()
    {
        $this->unInstallDB();
        $this->unInstallAgent();
        Option::delete(self::MODULE_ID);

        ModuleManager::unRegisterModule(self::MODULE_ID);
    }

    public function unInstallDB()
    {
        if (CModule::includeModule($this->MODULE_ID))
        {
            if (Application::getConnection()->isTableExists(\Bit\Distribution\Lead\DistributionTable::getTableName()))
            {
                $connection = Application::getInstance()->getConnection();
                $connection->dropTable(\Bit\Distribution\Lead\DistributionTable::getTableName());
            }
            if (Application::getConnection()->isTableExists(\Bit\Distribution\Lead\DistributionLeadTable::getTableName()))
            {
                $connection = Application::getInstance()->getConnection();
                $connection->dropTable(\Bit\Distribution\Lead\DistributionLeadTable::getTableName());
            }
        }
    }
    public function unInstallAgent(){
        \CAgent::RemoveAgent(
            'Bit\Distribution\Lead\Agent::runAgent();',
            self::MODULE_ID
        );
    }

}
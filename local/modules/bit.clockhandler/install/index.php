<?php

use Bitrix\Main\Application;
use Bitrix\Main\Config\Option as Option;
use Bitrix\Main\Loader as Loader;
use Bitrix\Main\Localization\Loc as Loc;
use Bitrix\Main\ModuleManager;

Loc::loadMessages(__FILE__);

class bit_clockhandler extends CModule
{
    const MODULE_ID = 'bit.clockhandler';


//    const USER_GROUP_ID = 'bit_clockhaldler';
//    const EMAIL_EVENT_NAME = 'BIT_CLOCK_HANDLER_REQUEST';

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

        $this->MODULE_NAME = Loc::getMessage('BIT_CLOCKHANDLER_MODULE_NAME');
        $this->MODULE_DESCRIPTION = Loc::getMessage('BIT_CLOCKHANDLER_MODULE_DESC');

        $this->PARTNER_NAME = Loc::getMessage('BIT_CLOCKHANDLER_PARTNER_NAME');
        $this->PARTNER_URI =  Loc::getMessage('BIT_CLOCKHANDLER_PARTNER_URI');
    }

    public function DoInstall()
    {
        ModuleManager::registerModule(self::MODULE_ID);
//        $this->installEvent();
//        $this->installUserGroup();
//        $this->installEmailHandler();
        $this->installAgent();
    }
//    public function installEvent(){
//        if (Loader::includeModule($this->MODULE_ID)) {
//            \Bit\Clockhandler\Event::installEvent();
//        }
//    }
//    public function installUserGroup(){
//        $rsRes = \CGroup::GetList(
//            $by = "c_sort",
//            $order = "asc",
//            [
//                'STRING_ID' => self::USER_GROUP_ID,
//            ]
//        );
//        if(!$ar = $rsRes->Fetch()){
//            $group = new \CGroup;
//            $arFields = Array(
//                "ACTIVE"       => "Y",
//                "C_SORT"       => 100,
//                "NAME"         => Loc::getMessage('BIT_CLOCKHANDLER_USER_GROUP_NAME'),
//                "DESCRIPTION"  => Loc::getMessage('BIT_CLOCKHANDLER_USER_GROUP_DESC'),
//                "STRING_ID"      => self::USER_GROUP_ID,
//            );
//            $group->Add($arFields);
//        }
//    }
//    public function installEmailHandler(){
//        $arFilter = array(
//            "TYPE_ID" => self::EMAIL_EVENT_NAME,
//        );
//        $rsET = \CEventType::GetList($arFilter);
//
//        $ET_ID = '';
//        if ($arET = $rsET->Fetch())
//        {
//            $ET_ID = $arET['ID'];
//        }
//        else{
//            $et = new CEventType;
//            $ET_ID = $et->Add(array(
//                "LID"           => 's1',
//                "EVENT_NAME"    => self::EMAIL_EVENT_NAME,
//                "NAME"          => Loc::getMessage('BIT_CLOCKHANDLER_EVENT_TYPE_NAME'),
//                "DESCRIPTION"   => Loc::getMessage('BIT_CLOCKHANDLER_EVENT_TYPE_DESC'),
//                "EVENT_TYPE"	=> 'email'
//            ));
//        }
//
//        if($ET_ID > 0)
//        {
//            $arr["ACTIVE"] = "Y";
//            $arr["EVENT_NAME"] = self::EMAIL_EVENT_NAME;
//            $arr["LID"] = 's1';
//            $arr["EMAIL_FROM"] = "#DEFAULT_EMAIL_FROM#";
//            $arr["EMAIL_TO"] = "#EMAIL_TO#";
//            $arr["LANGUAGE_ID"] = "en";
//            $arr["SUBJECT"] = "Clock out";
//            $arr["MESSAGE"] = "#MESSAGE_TEXT#";
//
//            $emess = new CEventMessage;
//            $EMAIL_ID = $emess->Add($arr);
//
//            if($EMAIL_ID > 0)
//                Option::set(self::MODULE_ID, 'email_template', $EMAIL_ID);
//        }
//
//
//
//
//    }
    public function installAgent(){
        \CAgent::AddAgent(
            'Bit\Clockhandler\Agent::runAgent();',
            self::MODULE_ID,
            'N',
            60*30, // каждые 30 минут
            "",
            "Y",
            "",
            100
        );
    }

    public function DoUninstall()
    {
//        $this->unInstallEvent();
//        $this->unInstallUserGroup();
        $this->unInstallAgent();

        Option::delete(self::MODULE_ID);

        ModuleManager::unRegisterModule(self::MODULE_ID);
    }
//    public function unInstallEvent(){
//        if (Loader::includeModule($this->MODULE_ID)) {
//            \Bit\Clockhandler\Event::unInstallEvent();
//        }
//    }
//    public function unInstallUserGroup(){
//        $rsRes = \CGroup::GetList(
//            $by = "c_sort",
//            $order = "asc",
//            [
//                'STRING_ID' => self::USER_GROUP_ID,
//            ]
//        );
//        if($ar = $rsRes->Fetch()){
//            $group = new \CGroup;
//            $group->Delete($ar['ID']);
//        }
//    }
    public function unInstallAgent(){
        \CAgent::RemoveAgent(
            'Bit\Clockhandler\Agent::runAgent();',
            self::MODULE_ID
        );
    }

}
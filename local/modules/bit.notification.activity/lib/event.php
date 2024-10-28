<?php

namespace Bit\Notification\Activity;
use \Bitrix\Main\EventManager;
use Bitrix\Main\Loader as Loader;
use Bitrix\Main\Config\Option as Option;
use Bitrix\Main\Type\DateTime as DT;



class Event{

    const MODULE_ID = "bit.notification.activity";


    static function installEvent()
    {
		//EventManager::getInstance()->registerEventHandler(
		//    "timeman",
		//    "OnAfterTMDayStart",
		//    self::MODULE_ID,
		//   "Bit\Clockhandler\Event",
		//   "OnAfterTMDayStartHandler"
		// );
    }
    static function unInstallEvent()
    {
		// EventManager::getInstance()->unRegisterEventHandler(
		//     "timeman",
		//     "OnAfterTMDayStart",
		//     self::MODULE_ID,
		//    "Bit\Clockhandler\Event",
		//    "OnAfterTMDayStartHandler"
		//);
    }

}
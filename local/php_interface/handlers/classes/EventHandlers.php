<?php
namespace Bit\Custom;

use Bitrix\Main\Loader;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Page\Asset;

use Bitrix\Highloadblock as HL;
use Bitrix\Main\Web\Cookie;

class EventHandlers{

    public static function registerEventHandler(){
        $eventManager = \Bitrix\Main\EventManager::getInstance();
		\Bit\Custom\Voximplant\leadCallControl::registerEventHandlers($eventManager);
    }
}

<?php
defined('B_PROLOG_INCLUDED') || die;

Bitrix\Main\Loader::registerAutoloadClasses(
    "bit.notification.activity",
    array(
        "Bit\Notification\Activity\CheckActivityTable" => "lib/checkactivity.php",
        "Bit\Notification\Activity\Agent" => "lib/agent.php",
        "Bit\Notification\Activity\Main"  => "lib/main.php",
        "Bit\Notification\Activity\Event" => "lib/event.php",
    )
);
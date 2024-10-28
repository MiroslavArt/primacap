<?php

Bitrix\Main\Loader::registerAutoloadClasses(
    "bit.clockhandler",
    array(
        "Bit\Clockhandler\Agent" => "lib/Agent.php",
//        "Bit\Clockhandler\Event" => "lib/Event.php",
    )
);

global $APPLICATION;
<?php
defined('B_PROLOG_INCLUDED') || die;

Bitrix\Main\Loader::registerAutoloadClasses(
    "bit.distribution.lead",
    array(
        "Bit\Distribution\Lead\DistributionTable" => "lib/distribution.php",
        "Bit\Distribution\Lead\DistributionLeadTable" => "lib/distributionlead.php",
        "Bit\Distribution\Lead\Agent" => "lib/agent.php",
        "Bit\Distribution\Lead\Main"  => "lib/main.php",
    )
);
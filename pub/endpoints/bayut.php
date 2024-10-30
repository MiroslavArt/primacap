<?php
define("PUBLIC_AJAX_MODE", true);
define("NO_KEEP_STATISTIC", "Y");
define("NO_AGENT_STATISTIC","Y");
define("NOT_CHECK_PERMISSIONS", true);
define("DisableEventsCheck", true);
define("NO_AGENT_CHECK", true);
define("HOST", 'oiaproperties.bitrix24.com');
define("USER", '73');
define("TOKENID", 'lyd7ixec7fqelp2p');

global $APPLICATION;

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

$ev = @file_get_contents("php://input");
$leaddata = json_decode($ev, true);

if(!$leaddata) {
    $leaddata = [
        'enquirer' => [
            'name' => 'Jijin B',
            'phone_number' => '971505085870'
        ],
        'listing' => [
            'url' => 'https://www.bayut.com/pm/9653153/ec901ef2-909d-430c-a7ed-79182ba04c4a',
            'reference' => 'MHR-BV2477'
        ]
    ];
}

\Bitrix\Main\Diag\Debug::writeToFile($leaddata, 'bayut '.date('Y-m-d H:i:s'), "bayutanddubizzle.log");

if(\Bitrix\Main\Loader::includeModule('webmatrik.integrations')) {
    $int = new \Webmatrik\Integrations\Bayut($leaddata);
    $int->createLead();
}








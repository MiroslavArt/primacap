<?php
require_once("common.php");

global $APPLICATION;

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

$request = \Bitrix\Main\HttpApplication::getInstance()->getContext()->getRequest();

$wfid = preg_replace("/[^0-9]/", '', $request->get('wfid'));
$agentid = preg_replace("/[^0-9]/", '', $request->get('agentid'));

\Bitrix\Main\Diag\Debug::writeToFile($wfid, "п3 ".date('H:i:s'), "test5.log");
\Bitrix\Main\Diag\Debug::writeToFile($agentid, "п3 ".date('H:i:s'), "test5.log");

$params = http_build_query(array(
    'halt' => 0,
    'cmd' => array(
        'get_list' => 'lists.element.get?'
            .http_build_query(array(
                'IBLOCK_TYPE_ID' => 'lists_socnet',
                'IBLOCK_ID' => '35',
                'SOCNET_GROUP_ID' => '1',
                'FILTER' => [
                    'NAME' => $wfid
                ]
            )),
    )));

$out = opt($params, HOST, USER, TOKENID);

//\Bitrix\Main\Diag\Debug::writeToFile($out, "п3 ".date('H:i:s'), "test5.log");

$listprj = current($out['result']['result']['get_list']);
\Bitrix\Main\Diag\Debug::writeToFile($listprj['PROPERTY_113'], "п3 ".date('H:i:s'), "test5.log");
$listprj = explode(",", $listprj['PROPERTY_113']);

\Bitrix\Main\Diag\Debug::writeToFile($listprj, "п3 ".date('H:i:s'), "test5.log");

$params = http_build_query(array(
    'halt' => 0,
    'cmd' => array(
        'get_list' => 'lists.element.get?'
            .http_build_query(array(
                'IBLOCK_TYPE_ID' => 'lists_socnet',
                'SOCNET_GROUP_ID' => '1',
                'IBLOCK_ID' => 29,
                'FILTER' => [
                    '=ID' => $listprj
                ]
            )),
    )));
$out = opt($params, HOST, USER, TOKENID);

\Bitrix\Main\Diag\Debug::writeToFile($out, "п30 ".date('H:i:s'), "test5.log");

foreach ($out['result']['result']['get_list'] as $item) {
    if(empty($item['PROPERTY_99'])) {
        \Bitrix\Main\Diag\Debug::writeToFile($item, "п31 ".date('H:i:s'), "test5.log");
        $params = http_build_query(array(
            'halt' => 0,
            'cmd' => array(
                'add_to_list' => 'lists.element.update?'
                    . http_build_query(array(
                        'IBLOCK_TYPE_ID' => 'lists_socnet',
                        'IBLOCK_ID' => 29,
                        'SOCNET_GROUP_ID' => '1',
                        'ELEMENT_ID' => $item['ID'],
                        'FIELDS' => [
                            'NAME' => $item['NAME'],
                            'PROPERTY_99' => $agentid
                        ]
                    )),
            )));
    } else {
        \Bitrix\Main\Diag\Debug::writeToFile($item, "п32 ".date('H:i:s'), "test5.log");
        $params = http_build_query(array(
            'halt' => 0,
            'cmd' => array(
                'add_to_list' => 'lists.element.update?'
                    . http_build_query(array(
                        'IBLOCK_TYPE_ID' => 'lists_socnet',
                        'IBLOCK_ID' => 29,
                        'SOCNET_GROUP_ID' => '1',
                        'ELEMENT_ID' => $item['ID'],
                        'FIELDS' => [
                            'NAME' => $item['NAME'],
                            'PROPERTY_99' => array_push($item['PROPERTY_99'], $agentid)
                        ]
                    )),
            )));
    }
    //$out = opt($params, HOST, USER, TOKENID);
    \Bitrix\Main\Diag\Debug::writeToFile($out, "п3444 ".date('H:i:s'), "test5.log");
}



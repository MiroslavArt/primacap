<?php
require_once("common.php");

global $APPLICATION;

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

$request = \Bitrix\Main\HttpApplication::getInstance()->getContext()->getRequest();

$wfid = preg_replace("/[^0-9]/", '', $request->get('wfid'));
$agentid = preg_replace("/[^0-9]/", '', $request->get('agentid'));
$exclusionprj = $request->get('exclusionprj');
//\Bitrix\Main\Diag\Debug::writeToFile($exclusionprj, "п3 ".date('H:i:s'), "test1.log");

if($exclusionprj) {
    $exclusionprj  = explode(",", $exclusionprj);
}

//\Bitrix\Main\Diag\Debug::writeToFile($exclusionprj , "п3 ".date('H:i:s'), "test6.log");

if($exclusionprj) {
    $params = http_build_query(array(
        'halt' => 0,
        'cmd' => array(
            'get_list' => 'lists.element.get?'
                .http_build_query(array(
                    'IBLOCK_TYPE_ID' => 'bitrix_processes',
                    'IBLOCK_ID' => 37,
                    'FILTER' => [
                        '!ID' => $exclusionprj
                    ]
                )),
        )));
} else {
    $params = http_build_query(array(
        'halt' => 0,
        'cmd' => array(
            'get_list' => 'lists.element.get?'
                .http_build_query(array(
                    'IBLOCK_TYPE_ID' => 'bitrix_processes',
                    'IBLOCK_ID' => 37
                )),
        )));
}

$out = opt($params, HOST, USER, TOKENID);
//\Bitrix\Main\Diag\Debug::writeToFile($out, "п3 ".date('H:i:s'), "test6.log");

$projectnames = [];

foreach ($out['result']['result']['get_list'] as $item) {
    $projectnames[] = $item['NAME'];
}

//\Bitrix\Main\Diag\Debug::writeToFile($projectnames, "п3 ".date('H:i:s'), "test6.log");

$params = http_build_query(array(
    'halt' => 0,
    'cmd' => array(
        'get_list' => 'lists.element.get?'
            .http_build_query(array(
                'IBLOCK_TYPE_ID' => 'lists_socnet',
                'SOCNET_GROUP_ID' => '1',
                'IBLOCK_ID' => 29,
                'FILTER' => [
                    'NAME' => $projectnames,
                    'PROPERTY_RESPONSIBLE_PERSONS' => $agentid
                ]
            )),
    )));

$out = opt($params, HOST, USER, TOKENID);

//\Bitrix\Main\Diag\Debug::writeToFile($out, "п3 ".date('H:i:s'), "test6.log");

$project = [];
$responsible = [];

foreach ($out['result']['result']['get_list'] as $item) {
    $project[] = $item['ID'];
    $array = array_flip($item['PROPERTY_99']); //Меняем местами ключи и значения
    //\Bitrix\Main\Diag\Debug::writeToFile($array, "п3 ".date('H:i:s'), "test4.log");
    unset($array[$agentid]);
    $responsible[$item['ID']]['name'] = $item['NAME'];
    $responsible[$item['ID']]['resp'] = array_flip($array);
}
\Bitrix\Main\Diag\Debug::writeToFile($project, "п3 ".date('H:i:s'), "test6.log");
//\Bitrix\Main\Diag\Debug::writeToFile($responsible, "п3 ".date('H:i:s'), "test4.log");

if($project) {
    //\Bitrix\Main\Diag\Debug::writeToFile($project, "п3 ".date('H:i:s'), "test.log");
    $initialproject = implode(',', $project);
    //\Bitrix\Main\Diag\Debug::writeToFile($initialproject, "п3 ".date('H:i:s'), "test.log");
    $params = http_build_query(array(
        'halt' => 0,
        'cmd' => array(
            'add_to_list' => 'lists.element.add?'
                . http_build_query(array(
                    'IBLOCK_TYPE_ID' => 'lists_socnet',
                    'IBLOCK_ID' => '35',
                    'SOCNET_GROUP_ID' => '1',
                    'ELEMENT_CODE' => date('Y-m-d H:i:s'). 'update',
                    'FIELDS' => [
                        'NAME' => $wfid,
                        'PROPERTY_113' => $initialproject
                    ]
                )),
        )));
    $out = opt($params, HOST, USER, TOKENID);
    foreach($responsible as $key => $item) {
        //\Bitrix\Main\Diag\Debug::writeToFile($item, "п3 ".date('H:i:s'), "test4.log");
        $params = http_build_query(array(
            'halt' => 0,
            'cmd' => array(
                'add_to_list' => 'lists.element.update?'
                    . http_build_query(array(
                        'IBLOCK_TYPE_ID' => 'lists_socnet',
                        'IBLOCK_ID' => 29,
                        'SOCNET_GROUP_ID' => '1',
                        'ELEMENT_ID' => $key,
                        'FIELDS' => [
                            'NAME' => $item['name'],
                            'PROPERTY_99' => $item['resp']
                        ]
                    )),
            )));
        $out = opt($params, HOST, USER, TOKENID);
        //\Bitrix\Main\Diag\Debug::writeToFile($out, "п3 ".date('H:i:s'), "test4.log");
    }
}





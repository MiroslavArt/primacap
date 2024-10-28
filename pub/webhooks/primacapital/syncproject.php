<?php
require_once("common.php");

global $APPLICATION;

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

$request = \Bitrix\Main\HttpApplication::getInstance()->getContext()->getRequest();

$name = translit($request->get('name'));

//\Bitrix\Main\Diag\Debug::writeToFile($data, "п3 ".date('H:i:s'), "test.log");

if($name) {
    $params = http_build_query(array(
        'halt' => 0,
        'cmd' => array(
            'get_list' => 'lists.element.get?'
                .http_build_query(array(
                    'IBLOCK_TYPE_ID' => 'bitrix_processes',
                    'IBLOCK_ID' => 37,
                    'FILTER' => [
                        'CODE' => $name
                    ]
                )),

        )));

    $out = opt($params, HOST, USER, TOKENID);

    if(empty($out['result']['result']['get_list'])) {
        $params = http_build_query(array(
            'halt' => 0,
            'cmd' => array(
                'get_list' => 'lists.element.add?'
                    .http_build_query(array(
                        'IBLOCK_TYPE_ID' => 'bitrix_processes',
                        'IBLOCK_ID' => '37',
                        'ELEMENT_CODE' => $name,
                        'FIELDS' => [
                            'NAME' => $name
                        ]
                    )),
            )));

        $out = opt($params, HOST, USER, TOKENID);
    }
}
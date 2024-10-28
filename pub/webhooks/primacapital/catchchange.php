<?php
require_once("common.php");

global $APPLICATION;

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

$request = \Bitrix\Main\HttpApplication::getInstance()->getContext()->getRequest();

$event = $request->get('event');
$data = $request->get('data');

//c

if($data['FIELDS']['ID']) {
    $params = http_build_query(array(
        'halt' => 0,
        'cmd' => array(
            'get_lead' => 'crm.lead.get?'
                .http_build_query(array(
                    'ID' => $data['FIELDS']['ID']

                )),
        )));
    $out = opt($params, HOST, USER, TOKENID);

    $data = $out['result']['result']['get_lead'];

    //\Bitrix\Main\Diag\Debug::writeToFile($data, "п3 ".date('H:i:s'), "test.log");

    if($data['STATUS_ID']=='NEW' && $data['ASSIGNED_BY_ID'] != $data['UF_CRM_1650255133'] &&
        preg_match("/WhatsApp/i", $data['TITLE']))
    //if($data['STATUS_ID']=='NEW' && $data['ASSIGNED_BY_ID'] != $data['UF_CRM_1650370601'])
    {
        //\Bitrix\Main\Diag\Debug::writeToFile("inside", "п3 ".date('H:i:s'), "test.log");
        $params = http_build_query(array(
            'halt' => 0,
            'cmd' => array(
                'update_lead' => 'crm.lead.update?'
                    . http_build_query(array(
                        'id' => $data['ID'],
                        'fields' => array('STATUS_ID' => 'UC_TCX0EY'))
                    )
            )));
        $out = opt($params, HOST, USER, TOKENID);
        //\Bitrix\Main\Diag\Debug::writeToFile($out, "п3 ".date('H:i:s'), "test.log");
    }
}



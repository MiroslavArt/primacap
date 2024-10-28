<?php
require_once("common.php");

$request = \Bitrix\Main\HttpApplication::getInstance()->getContext()->getRequest();

$leadID = preg_replace("/[^0-9]/", '', $request->get('LEAD_ID'));
//$callID = preg_replace("/[^0-9]/", '', $request->get('CALL_ID'));

if($leadID) {
    $params = http_build_query(array(
        'halt' => 0,
        'cmd' => array(
            'update_lead' => 'crm.timeline.comment.list?'
                . http_build_query(array(
                        'ENTITY_ID' => $leadID,
                        'ENTITY_TYPE' => "lead"
                        )
                )
        )));
    $out = opt($params, HOST, USER, TOKENID);

    if($out['result']['result']['update_lead']['ID'] > 0){

    }
}
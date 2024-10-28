<?php
require_once("common.php");

global $APPLICATION;

$date = date('G');

echo $date;

$leadid = 12233;

/*$params = http_build_query(array(
    'halt' => 0,
    'cmd' => array(
        'get_list' => 'crm.lead.get?'
            .http_build_query(array(
                'ID' => $leadid

            ))
    )));*/

$params = http_build_query(array(
    'halt' => 0,
    'cmd' => array(
        'update_lead' => 'crm.lead.update?'
            . http_build_query(array(
                    'id' => $leadid,
                    'fields' => array('UF_CRM_1653542199'=>[11]))
            ),
    )));

$out = opt($params, HOST, USER, TOKENID);

echo "<pre>";
print_r($out);
echo "</pre>";


/*require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

$request = \Bitrix\Main\HttpApplication::getInstance()->getContext()->getRequest();

$agentid = preg_replace("/[^0-9]/", '', $request->get('agentid'));

$params = http_build_query(array(
    'halt' => 0,
    'cmd' => array(
        'get_list' => 'lists.element.get?'
            .http_build_query(array(
                'IBLOCK_TYPE_ID' => 'lists_socnet',
                'SOCNET_GROUP_ID' => '1',
                'IBLOCK_ID' => 29,
                'FILTER' => [
                    'PROPERTY_RESPONSIBLE_PERSONS' => $agentid
                ]
            )),
    )));

$out = opt($params, HOST, USER, TOKENID);

echo "<pre>";
print_r($out);
echo "</pre>";



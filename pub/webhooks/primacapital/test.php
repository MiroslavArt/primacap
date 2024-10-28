<?php

require_once("common.php");

$ownerid = 9589;
$date = strtotime();
define("CHECKDATE",ConvertTimeStamp($date, "FULL"));

/*$params = http_build_query(array(
    'halt' => 0,
    'cmd' => array(
        'get_acts' => 'crm.activity.list?'
            . http_build_query(array(
                    'order' => array('ID' => "ASC"),
                    'filter' => array('COMPLETED'=>'N',
                        'OWNER_TYPE_ID'=>1, 'OWNER_ID'=>$ownerid, '<DEADLINE'=>CHECKDATE,
                        'TYPE_ID' => '2'),
                    'select' => array("*")
                )
            ))));
$out = opt($params, HOST, USER, TOKENID);
$calls = $out['result']['result']['get_acts'];*/


/*$params = http_build_query(array(
    'halt' => 0,
    'cmd' => array(
        'get_act' => 'crm.activity.get?'
            . http_build_query(array(
                    'ID' => 34711
                )
            ))));

$out = opt($params, HOST, USER, TOKENID);

/*$params = http_build_query(array(
    'halt' => 0,
    'cmd' => array(
        'list_users' => 'lists.element.get?'
            . http_build_query(array(
                    'IBLOCK_TYPE_ID' => 'lists_socnet',
                    'SOCNET_GROUP_ID' => 1,
                    'IBLOCK_ID' => 29,
                    'FILTER' => ['=NAME'=>$prj]
                )
            ),
    )));
$out = opt($params, HOST, USER, TOKENID);*/

/*$params = http_build_query(array(
    'halt' => 0,
    'cmd' => array(
        'get_act' => 'crm.activity.get?'
            . http_build_query(array(
                    'ID' => 34711
                )
            ))));*/


/*$params = http_build_query(array(
    'halt' => 0,
    'cmd' => array(
        'get_list' => 'crm.lead.list?'
            .http_build_query(array(
                'order' => array('ID' => "ASC"),
                'filter' => array('ID'=>11619),
                'select' => array("ID", "OPENED", "UF_CRM_1653277531")
            )),

    )));*/


$params = http_build_query(array(
    'halt' => 0,
    'cmd' => array(
        'get_list' => 'lists.element.get?'
            .http_build_query(array(
                'IBLOCK_TYPE_ID' => 'lists_socnet',
                'SOCNET_GROUP_ID' => '1',
                'IBLOCK_ID' => 29
            )),

    )));

$out = opt($params, HOST, USER, TOKENID);

//echo "<pre>";
//print_r($out);
//echo "</pre>";

foreach ($out['result']['result']['get_list'] as $item) {
    $name = $item['NAME'];
    $params = http_build_query(array(
        'halt' => 0,
        'cmd' => array(
            'get_list' => 'lists.element.add?'
                .http_build_query(array(
                    'IBLOCK_TYPE_ID' => 'bitrix_processes',
                    'IBLOCK_ID' => '37',
                    'ELEMENT_CODE' => translit($name),
                    'FIELDS' => [
                        'NAME' => $name
                    ]
                )),
        )));

    $out = opt($params, HOST, USER, TOKENID);
    echo "<pre>";
    print_r($out);
    echo "</pre>";
}




/*$params = http_build_query(array(
    'halt' => 0,
    'cmd' => array(
        'get_act' => 'crm.activity.get?'
            . http_build_query(array(
                    'ID' => 34681
                )
            ))));

$out = opt($params, HOST, USER, TOKENID);

echo "<pre>";
print_r($out);
echo "</pre>";



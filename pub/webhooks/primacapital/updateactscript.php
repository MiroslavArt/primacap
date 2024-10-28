<?php
require_once("common.php");

$params = http_build_query(array(
    'halt' => 0,
    'cmd' => array(
        'no_respnotify' => 'crm.activity.list?'
            . http_build_query(array(
                    'ORDER' => array(
                        'ID' => 'ASC'
                    ),
                    'FILTER' => array(
                        'SUBJECT' => 'call',
                        'RESPONSIBLE_ID' => 7,
                        'OWNER_TYPE_ID' => 1,
                        'COMPLETED' => 'Y'
                        //'ID' => 6809
                    ),
                    'SELECT' => array(
                        '*',
                        //'ID',
                        //'START_TIME',
                        //'DIRECTION', // Направление активности: 1 = Входящее, 2 = Исходящее
                        //'COMPLETED'
                    )
                )
            ),


    )));

$out = opt($params, HOST, USER, TOKENID);

echo "<pre>";
print_r($out);
echo "</pre>";

$resarr = $out['result']['result']['no_respnotify'];



foreach($resarr as $item) {
    $params = http_build_query(array(
        'halt' => 0,
        'cmd' => array(
            'get_lead' => 'crm.lead.get?'
                .http_build_query(array(
                    'ID' => $item['OWNER_ID']

                )),
        )));

    $out = opt($params, HOST, USER, TOKENID);
    $name = $out['result']['result']['get_lead']['NAME'].' '.$out['result']['result']['get_lead']['LAST_NAME'];

    if($name) {
        $params = http_build_query(array(
            'halt' => 0,
            'cmd' => array(
                'upd_act' => 'crm.activity.update?'
                    . http_build_query(array(
                            'ID' => $item['ID'],
                            'FIELDS' => array(
                                'SUBJECT' => 'call ' . $name
                            )
                        )
                    ))));

        $out = opt($params, HOST, USER, TOKENID);
        echo "<pre>";
        print_r($out);
        echo "</pre>";
    }
}



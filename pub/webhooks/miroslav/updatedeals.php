<?php
define("PUBLIC_AJAX_MODE", true);
define("NO_KEEP_STATISTIC", "Y");
define("NO_AGENT_STATISTIC","Y");
define("NOT_CHECK_PERMISSIONS", true);
define("DisableEventsCheck", true);
define("NO_AGENT_CHECK", true);
define("HOST", 'metrirent.bitrix24.ru');
define("USER", '16258');
define("TOKENID", '61z0960j5rbewckz');

global $APPLICATION;

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

$finish = false;
$dealid = 1;
while (!$finish) {
    $params = http_build_query(array(
            'halt' => 0,
            'cmd' => array(
                'get_deals' => 'crm.deal.list?'
                    . http_build_query(array(
                        'order' => array(
                            'ID' => 'ASC'
                        ),
                        'filter' => array(
                            'STAGE_ID' => 'C2:UC_M0KJMP', // Тип активности: 2 = Звонок, 4 = Письмо
                            '>ID' => $dealid
                        ),
                        'select' => array(
                            'ID',
                            'TITLE'
                        ),
                        'start' => -1
                    ))
            )
        )
    );

    $out = opt($params, HOST, USER, TOKENID);

    $res = $out['result']['result']['get_deals'];

    echo "<pre>";
    print_r($res);
    echo "</pre>";
    $rawpar = [];
    foreach ($res as $deal)
    {
        $dealid = $deal['ID'];
        $rawpar['get_paym_'.$dealid] =  'crm.item.payment.list?'
                . http_build_query(array(
                    'entityId'=> $dealid,
                    'entityTypeId'=>2
                )
        );
    }
    $params = http_build_query(array(
        'halt' => 0,
        'cmd' =>$rawpar
    ));
    $out = opt($params, HOST, USER, TOKENID);

    $succest = 'C2:WON';
    $unsuccest = 'C2:1';

    $stage = $unsuccest;

    $res = $out['result']['result'];
    echo "<pre>";
    print_r($res);
    echo "</pre>";
    $rawparn = [];
    foreach ($res as $key => $paym) {
        $dealid = preg_replace('/[^0-9]/', '', $key);;
        if (is_array($paym)) {
            foreach ($paym as $p) {
                if($p['paid']=='Y') {
                    $stage = $succest;
                    break;
                }
            }
        }
        $rawparn['get_upd_' . $dealid] = 'crm.deal.update?'
            . http_build_query(array(
                'ID' => $dealid,
                'fields' => [
                    'STAGE_ID' => $stage
                ]
            ));
    }
    $params = http_build_query(array(
        'halt' => 0,
        'cmd' =>$rawparn
    ));
    $out = opt($params, HOST, USER, TOKENID);
    $finish = true;
}

function opt($appParams, $domain, $user, $auth)
{
    $appRequestUrl = 'https://'.$domain.'/rest/'.$user.'/'.$auth.'/batch';
    $curl=curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_SSL_VERIFYPEER => 0,
        CURLOPT_POST => 1,
        CURLOPT_HEADER => 0,
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_URL => $appRequestUrl,
        CURLOPT_POSTFIELDS => $appParams
    ));
    $out=curl_exec($curl);

    return json_decode($out, 1);
}






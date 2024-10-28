<?php
require_once("common.php");

$request = \Bitrix\Main\HttpApplication::getInstance()->getContext()->getRequest();

$leadid = $request->get('lead');
$name = $request->get('name');
$resp = preg_replace("/[^0-9]/", '', $request->get('resp'));

//$name = 'WhatsApp';

if($resp!=USER) {
    $params = http_build_query(array(
        'halt' => 0,
        'cmd' => array(
            'chat_list' => 'im.search.chat.list?'
                . http_build_query(array(
                        'FIND' => $name
                    )
                ))));
    $out = opt($params, HOST, USER, TOKENID);

    foreach ($out['result']['result']['chat_list'] as $item) {
        if($item['entity_type']=='LINES') {
            if(strpos($item['entity_data_1'], 'LEAD')!=false && strpos($item['entity_data_1'], $leadid)) {
                $openlineid =  $item['entity_id'];
                break;
            }
        }
    }

    if($openlineid) {
        $params = http_build_query(array(
            'halt' => 0,
            'cmd' => array(
                'chat_get' => 'im.chat.get?'
                    . http_build_query(array(
                            'ENTITY_TYPE' => 'LINES',
                            'ENTITY_ID' => $openlineid
                        )
                    ))));

        $out = opt($params, HOST, USER, TOKENID);
        $chatid = $out['result']['result']['chat_get']['ID'];
        if($chatid) {
            echo $chatid;
            $params = http_build_query(array(
                'halt' => 0,
                'cmd' => array(
                    'chat_share' => 'im.chat.user.add?'
                        . http_build_query(array(
                                'CHAT_ID' => $chatid,
                                'USERS' => $resp
                            )
                        ))));
            $out = opt($params, HOST, USER, TOKENID);

        }
    }
}

/*function sendpostRequest($http, $method, $par) {
    echo "sendpost";
    $json = $http->post(
        'https://'.HOST.'/rest/'.USER.'/'.TOKENID.'/'.$method.'/',
        $par
    );
    return \Bitrix\Main\Web\Json::decode($json);
}
*/


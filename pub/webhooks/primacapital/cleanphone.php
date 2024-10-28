<?php
require_once("common.php");

$request = \Bitrix\Main\HttpApplication::getInstance()->getContext()->getRequest();

$leadid = preg_replace("/[^0-9]/", '', $request->get('lead'));
$phone = preg_replace("/[^0-9]/", '', $request->get('phone'));

//\Bitrix\Main\Diag\Debug::writeToFile($phone, "phone ".date('H:i:s'), "test.log");

$ccode = [1,7,20,21,21,21,21,27,30,31,32,33,34,36,39,39,39,40,41,41,
    43,44,45,46,47,47,48,49,51,53,54,55,56,57,58,60,61,62,62,63,64,65,66,81,
    82,84,86,90,91,92,93,94,95,98,212,220,221,222,223,224,225,226,227,228,229,
    230,231,232,233,234,235,236,237,238,239,240,241,242,243,244,245,246,247,248,249,250,
    251,252,253,254,255,256,257,258,260,261,262,263,264,265,266,267,268,269,298,299,350,
    351,352,353,354,355,356,357,358,359,370,371,372,374,375,376,377,378,380,381,385,386,
    387,389,420,421,500,501,502,503,504,505,506,507,508,509,590,591,592,593,594,595,596,
    597,598,599,670,672,672,672,673,674,675,676,677,678,679,681,682,683,684,685,686,687,
    688,689,690,691,692,850,852,853,855,856,880,
    886,960,961,962,963,964,965,966,967,968,969,971,972,973,974,976,977,992,993,994,995,996,998];

//if(preg_match("/[+]/", $phone)) {
if($leadid && $phone) {
    $newphone = preg_replace("/[^0-9]/", '', $phone);
    //echo $newphone;
    $found = 0;
    for ($i = 1; $i <= 3; $i++) {
        $code = substr($newphone, 0, $i);
        if(in_array($code, $ccode)) {
            $found = $i;
            $corcode = $code;
        }
    }
    if($found!=0 && $corcode) {
        $ifzero = substr($newphone, $found, 1);
        if($ifzero == 0) {
            $corphone = '+'.$corcode.substr($newphone, $found+1);
            //echo $corphone;
            //\Bitrix\Main\Diag\Debug::writeToFile($corphone, "phone ".date('H:i:s'), "test.log");
            $params = http_build_query(array(
                'halt' => 0,
                'cmd' => array(
                    'update_lead' => 'crm.lead.get?'
                        . http_build_query(array(
                                'id' => $leadid
                                )
                        )
                )));
            $out = opt($params, HOST, USER, TOKENID);
            //\Bitrix\Main\Diag\Debug::writeToFile($out, "phone ".date('H:i:s'), "test.log");
            foreach ($out['result']['result']['update_lead']['PHONE'] as $item) {
                if(preg_replace("/[^0-9]/", '', $item['VALUE']) == $phone) {
                    $arUpdatePhone = [
                        [//change
                            'ID' => $item['ID'],
                            'VALUE' => $corphone
                        ]
                    ];
                    $params = http_build_query(array(
                        'halt' => 0,
                        'cmd' => array(
                            'update_lead' => 'crm.lead.update?'
                                . http_build_query(array(
                                        'id' => $leadid ,
                                        'fields' => array('PHONE' => $arUpdatePhone
                                        ))
                                )
                        )));
                    $out = opt($params, HOST, USER, TOKENID);
                }
            }

        }
    }
}
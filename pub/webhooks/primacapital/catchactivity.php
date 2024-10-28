<?php
require_once("common.php");
define("TEMPLATE", '87');
$request = \Bitrix\Main\HttpApplication::getInstance()->getContext()->getRequest();

$data = $request->get('data');
//\Bitrix\Main\Diag\Debug::writeToFile($data, "activity ".date('H:i:s'), "test.log");

$id = $data['FIELDS']['ID'];

//\Bitrix\Main\Diag\Debug::writeToFile($id, "activity ".date('H:i:s'), "test.log");

$date = strtotime();

define("CHECKDATE",ConvertTimeStamp($date, "FULL"));

if($id) {
    $params = http_build_query(array(
        'halt' => 0,
        'cmd' => array(
            'get_act' => 'crm.activity.get?'
                . http_build_query(array(
                        'ID' => $id
                    )
                ))));

    $out = opt($params, HOST, USER, TOKENID);

    $subject = $out['result']['result']['get_act']['SUBJECT'];
    $ownerid = $out['result']['result']['get_act']['OWNER_ID'];
    $ownertypeid = $out['result']['result']['get_act']['OWNER_TYPE_ID'];
    $completed = $out['result']['result']['get_act']['COMPLETED'];
    $result = $out['result']['result']['get_act']['RESULT_STREAM'];

    if($ownertypeid==1) {
        $params = http_build_query(array(
            'halt' => 0,
            'cmd' => array(
                'get_lead' => 'crm.lead.get?'
                    .http_build_query(array(
                        'ID' => $ownerid

                    )),
            )));
        $outlead = opt($params, HOST, USER, TOKENID);

        if($subject == 'call') {
            //\Bitrix\Main\Diag\Debug::writeToFile($out, "activity ".date('H:i:s'), "test.log");
            $name = $outlead['result']['result']['get_lead']['NAME'].' '.$outlead['result']['result']['get_lead']['LAST_NAME'];

            if($name) {
                $params = http_build_query(array(
                    'halt' => 0,
                    'cmd' => array(
                        'upd_act' => 'crm.activity.update?'
                            . http_build_query(array(
                                    'ID' => $id,
                                    'FIELDS' => array(
                                        'SUBJECT' => $subject.' '.$name
                                    )
                                )
                            ))));

                $out = opt($params, HOST, USER, TOKENID);
            }
        } elseif(preg_match("/submitted/", $subject)) {
            $len = strlen($subject);
            $kov = strpos($subject, '"');
            $subject = substr($subject, $kov+1);
            $kov = strpos($subject, '"');
            $prjname = substr($subject, 0, $kov);
            $params = http_build_query(array(
                'halt' => 0,
                'cmd' => array(
                    'get_acts' => 'crm.activity.list?'
                        . http_build_query(array(
                                'order' => array('ID' => "ASC"),
                                'filter' => array('!ID'=>$id, 'COMPLETED'=>'Y',
                                    'OWNER_TYPE_ID'=>1, 'OWNER_ID'=>$ownerid),
                                'select' => array("*")
                            )
                        ))));
            $out = opt($params, HOST, USER, TOKENID);

            if($out['result']['result']['get_acts']) {
                $params = http_build_query(array(
                    'halt' => 0,
                    'cmd' => array(
                        'start_wf' => 'bizproc.workflow.start?'
                            .http_build_query(array(
                                'TEMPLATE_ID' => TEMPLATE,
                                'DOCUMENT_ID' => ['crm', 'CCrmDocumentLead', 'LEAD_'.$ownerid],
                                'PARAMETERS' => ['prjname'=>$prjname]
                            ))
                    )));
                $out = opt($params, HOST, USER, TOKENID);
            }
        } elseif(preg_match("/Outgoing to/", $subject)) {
            if($outlead['result']['result']['get_lead']['STATUS_ID']=='UC_9WUJ49') {
                \Bitrix\Main\Diag\Debug::writeToFile('f1', "activity ".date('H:i:s'), "test.log");
                if($result==4) {
                    $newstatus = 'UC_23YNYD';
                } elseif($result==2) {
                    $newstatus = 'UC_TCX0EY';
                }
                if($newstatus) {
                    \Bitrix\Main\Diag\Debug::writeToFile($newstatus, "activity ".date('H:i:s'), "test.log");
                    $params = http_build_query(array(
                        'halt' => 0,
                        'cmd' => array(
                            'update_lead' => 'crm.lead.update?'
                                . http_build_query(array(
                                        'id' => $ownerid,
                                        'fields' => array('STATUS_ID' => $newstatus))
                                )
                        )));
                    $out = opt($params, HOST, USER, TOKENID);
                }
            }

            $params = http_build_query(array(
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
            $calls = $out['result']['result']['get_acts'];

            foreach($calls as $call) {
                $params = http_build_query(array(
                    'halt' => 0,
                    'cmd' => array(
                        'upd_acts' => 'crm.activity.update?'
                            . http_build_query(array(
                                    'ID' => $call['ID'],
                                    'FIELDS' => array(
                                        // 'DESCRIPTION' => $desc,
                                        'COMPLETED' => 'Y'
                                    )
                                )
                            ))));
                $out = opt($params, HOST, USER, TOKENID);
            }
        }
    }
}




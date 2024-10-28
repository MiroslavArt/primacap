<?php
require_once("common.php");

define("NOLEADS", 100);
define("NOOVERDUE",100);
define("TEMPLATE",77);

$request = \Bitrix\Main\HttpApplication::getInstance()->getContext()->getRequest();

$leadid = preg_replace("/[^0-9]/", '', $request->get('lead'));
$oldresp = preg_replace("/[^0-9]/", '', $request->get('oldresp'));
$newresp = preg_replace("/[^0-9]/", '', $request->get('newresp'));
$cuser = preg_replace("/[^0-9]/", '', $request->get('cuser'));
$cid = preg_replace("/[^0-9]/", '', $request->get('contid'));
$full = preg_replace("/[^0-9]/", '', $request->get('fullmode'));

$date = strtotime('-2 days');

define("CHECKDATE",ConvertTimeStamp($date, "FULL"));
//$newdate = ConvertTimeStamp($date, "FULL");

if($leadid && $oldresp && $newresp && $cuser) {
    if($full=='1') {
        checkUser($newresp, $leadid, $oldresp, $cuser, $cid);
    } elseif($full=='0') {
        lightcheckUser($newresp, $leadid, $oldresp, $cuser, $cid);
    }
}

function checkUser($newresp, $leadid, $oldresp, $cuser, $cid) {
    $fail = true;
    $params = http_build_query(array(
        'halt' => 0,
        'cmd' => array(
            'user_status' => 'timeman.status?'
                . http_build_query(array(
                        'USER_ID' => $newresp
                    )
                ),
            'get_list' => 'crm.lead.list?'
                .http_build_query(array(
                    'order' => array('ID' => "ASC"),
                    'filter' => array('ASSIGNED_BY_ID'=>$newresp, "OPENED"=>'Y'),
                    'select' => array("ID", "OPENED")
                )),
            'get_activities' => 'crm.activity.list?'
                .http_build_query(array(
                    'order' => array('ID' => "ASC"),
                    'filter' => array('RESPONSIBLE_ID'=>$newresp, 'COMPLETED'=>'N', '<DEADLINE'=>CHECKDATE),
                    'select' => array("*")
                )),

        )));
    $out = opt($params, HOST, USER, TOKENID);

    if (!$out['result_error']) {

        $errmessage = '';
        if ($out['result']['result']['user_status']['STATUS'] != 'OPENED') {
            $errmessage .= "User is not active inside CRM.";
        }
        if ($out['result']['result_total']['get_list'] > NOLEADS) {
            $errmessage .= "User has more than ".NOLEADS." open leads.";
        }
        if ($out['result']['result_total']['get_activities'] > NOOVERDUE) {
            $errmessage .= "User has more than ".NOOVERDUE." overdue activities.";
        }
        if ($errmessage) {
            $params = http_build_query(array(
                'halt' => 0,
                'cmd' => array(
                    'no_respnotify' => 'im.notify.personal.add?'
                        . http_build_query(array(
                                'USER_ID' => $cuser,
                                'MESSAGE' => $errmessage."Change of responsible person for the lead ".$leadid." can't be processed."
                            )
                        ),
                    'update_lead' => 'crm.lead.update?'
                        . http_build_query(array(
                                'id' => $leadid,
                                'fields' => array('ASSIGNED_BY_ID' => $oldresp))
                        )
                )));

        } else {
            $fail = false;
            if($cid) {
                $params = http_build_query(array(
                    'halt' => 0,
                    'cmd' => array(
                        'no_respnotify' => 'im.notify.personal.add?'
                            . http_build_query(array(
                                    'USER_ID' => $cuser,
                                    'MESSAGE' => 'Responsible '.$newresp.' changed for the lead '.$leadid
                                )
                            ),
                        'update_lead' => 'crm.lead.update?'
                            . http_build_query(array(
                                    'id' => $leadid,
                                    'fields' => array('ASSIGNED_BY_ID' => $newresp, 'UF_CRM_1650255133'=>$newresp))
                            ),
                        /*'start_wf' => 'bizproc.workflow.start?'
                            .http_build_query(array(
                                'TEMPLATE_ID' => TEMPLATE,
                                'DOCUMENT_ID' => ['crm', 'CCrmDocumentLead', 'LEAD_'.$leadid],
                                'PARAMETERS' => ['newresp'=>$newresp, 'oldresp'=>$oldresp]
                            )),*/
                        'update_contact' => 'crm.contact.update?'
                            . http_build_query(array(
                                    'id' => $cid,
                                    'fields' => array('ASSIGNED_BY_ID' => $newresp))
                            )
                    )));
            } else {
                $params = http_build_query(array(
                    'halt' => 0,
                    'cmd' => array(
                        'no_respnotify' => 'im.notify.personal.add?'
                            . http_build_query(array(
                                    'USER_ID' => $cuser,
                                    'MESSAGE' => 'Responsible changed'
                                )
                            ),
                        'update_lead' => 'crm.lead.update?'
                            . http_build_query(array(
                                    'id' => $leadid,
                                    'fields' => array('ASSIGNED_BY_ID' => $newresp, 'UF_CRM_1650255133'=>$newresp))
                            )
                        /*'start_wf' => 'bizproc.workflow.start?'
                            .http_build_query(array(
                                'TEMPLATE_ID' => TEMPLATE,
                                'DOCUMENT_ID' => ['crm', 'CCrmDocumentLead', 'LEAD_'.$leadid],
                                'PARAMETERS' => ['newresp'=>$newresp, 'oldresp'=>$oldresp]
                            )),*/

                    )));
            }


        }
        $out = opt($params, HOST, USER, TOKENID);
        return $fail;
    }


}

function lightcheckUser($newresp, $leadid, $oldresp, $cuser, $cid) {
    //\Bitrix\Main\Diag\Debug::writeToFile('inside', "п3 ".date('H:i:s'), "test.log");
    if($cid) {
        $params = http_build_query(array(
            'halt' => 0,
            'cmd' => array(

                'update_lead' => 'crm.lead.update?'
                    . http_build_query(array(
                            'id' => $leadid,
                            'fields' => array('ASSIGNED_BY_ID' => $newresp, 'UF_CRM_1650255133'=>$newresp))
                    ),
                'update_contact' => 'crm.contact.update?'
                    . http_build_query(array(
                            'id' => $cid,
                            'fields' => array('ASSIGNED_BY_ID' => $newresp))
                    )
            )));
        $out = opt($params, HOST, USER, TOKENID);
        //\Bitrix\Main\Diag\Debug::writeToFile($out, "п3 ".date('H:i:s'), "test.log");
    }
}


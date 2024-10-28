<?php
require_once("common.php");
define("NOLEADS", 100);
define("TEMPLATE", 93);

$date = date('G');

if($date==23 OR ($date>0 && $date<8)) {
    echo true;
}

$request = \Bitrix\Main\HttpApplication::getInstance()->getContext()->getRequest();

$leadid = preg_replace("/[^0-9]/", '', $request->get('lead'));
$prj = $request->get('project');
$cid = preg_replace("/[^0-9]/", '', $request->get('contid'));

if($leadid && $prj) {
    $params = http_build_query(array(
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
            'get_lead' => 'crm.lead.get?'
                .http_build_query(array(
                    'ID' => $leadid

                ))
        )));
    $out = opt($params, HOST, USER, TOKENID);

    $agents = current($out['result']['result']['list_users']);
    $agenttslist = $agents['PROPERTY_99'];
    $prevagents = $out['result']['result']['get_lead']['UF_CRM_1653542199'];
    if(empty($prevagents)) {
        $prevagents = [];
    }

    if($agenttslist) {
        shuffle($agenttslist);
        $wasupdated = false;
        foreach ($agenttslist as $agent) {
            if(!in_array($agent, $prevagents)) {
                $wasupdated= checkUser($agent, $leadid, $cid, $prevagents);
            }
            if($wasupdated)
                break;
        }
        if(!$wasupdated) {
            // здесь также обнуляем поле
            $params = http_build_query(array(
                'halt' => 0,
                'cmd' => array(
                    'start_wf' => 'bizproc.workflow.start?'
                        .http_build_query(array(
                            'TEMPLATE_ID' => TEMPLATE,
                            'DOCUMENT_ID' => ['crm', 'CCrmDocumentLead', 'LEAD_'.$leadid],
                            'PARAMETERS' => []
                        )),
                )));
            $out = opt($params, HOST, USER, TOKENID);

            /*$params = http_build_query(array(
                'halt' => 0,
                'cmd' => array(
                    'no_respnotify' => 'im.notify.personal.add?'
                        . http_build_query(array(
                                'USER_ID' => 1,
                                'MESSAGE' => 'System was not be able to find responsible for the lead '.$leadid
                            )
                        ),
                )));*/
            //$out = opt($params, HOST, USER, TOKENID);
        }
    }
}

function checkUser($resp, $leadid, $cid, $prevagents) {
    $params = http_build_query(array(
        'halt' => 0,
        'cmd' => array(
            'user_status' => 'timeman.status?'
                . http_build_query(array(
                        'USER_ID' => $resp
                    )
                ),
            'get_list' => 'crm.lead.list?'
                .http_build_query(array(
                    'order' => array('ID' => "ASC"),
                    'filter' => array('ASSIGNED_BY_ID'=>$resp, "OPENED"=>'Y',
                        '!=STATUS_ID'=>'UC_ZCVQ0B'),
                    'select' => array("ID", "OPENED")
                )),

        )));
    $out = opt($params, HOST, USER, TOKENID);

    if (!$out['result_error']) {
        $cannotbeupdated = false;
        if ($out['result']['result']['user_status']['STATUS'] != 'OPENED') {
            $cannotbeupdated = true;
        }
        if ($out['result']['result_total']['get_list'] > NOLEADS) {
            $cannotbeupdated = true;
        }

        if(!$cannotbeupdated) {
            $prevagents[] = $resp;
            if($cid) {
                // тут дополнительно пишем историю
                $params = http_build_query(array(
                    'halt' => 0,
                    'cmd' => array(
                        'update_lead' => 'crm.lead.update?'
                            . http_build_query(array(
                                    'id' => $leadid,
                                    'fields' => array('ASSIGNED_BY_ID' => $resp, 'UF_CRM_1650255133'=>$resp,
                                        'STATUS_ID'=>'UC_9WUJ49', 'UF_CRM_1653542199'=>$prevagents))
                            ),
                        'update_contact' => 'crm.contact.update?'
                            . http_build_query(array(
                                    'id' => $cid,
                                    'fields' => array('ASSIGNED_BY_ID' => $resp))
                            )
                    )));
            } else {
                $params = http_build_query(array(
                    'halt' => 0,
                    'cmd' => array(
                        'update_lead' => 'crm.lead.update?'
                            . http_build_query(array(
                                    'id' => $leadid,
                                    'fields' => array('ASSIGNED_BY_ID' => $resp, 'UF_CRM_1650255133'=>$resp,
                                        'STATUS_ID'=>'UC_9WUJ49', 'UF_CRM_1653542199'=>$prevagents))
                            )
                    )));
            }
            $out = opt($params, HOST, USER, TOKENID);
            return true;
        }
        return false;
    }
}






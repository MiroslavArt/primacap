<?php

namespace Bit\Distribution\Lead;

use \Bitrix\Main\Config\Option as Option;
use Bitrix\Main\Loader as Loader;

class Agent
{
    const MODULE_ID = "bit.distribution.lead";

    public static function runAgent()
    {
        Loader::IncludeModule("bizproc");
        Loader::IncludeModule("crm");

        $enable = Option::get(self::MODULE_ID, 'enabled', 'N');
        if($enable == 'N')
        {
            return __CLASS__.'::'.__FUNCTION__.'();';
        }

        $status = Option::get(self::MODULE_ID, 'check_lead_status', 'NEW');
        $bp_ID = Option::get(self::MODULE_ID, 'agent_workflow_id', '0');
        if($bp_ID > 0)
        {
            $rs = \CCrmLead::GetList(
                [],
                [
                    'STATUS_ID' => $status,
                ]
            );
            while ($ar = $rs->fetch())
            {
                $leadID = $ar['ID'];
                $params = [];
                $documentID = ['crm', 'CCrmDocumentLead', 'LEAD_' . $leadID];

                \CBPDocument::StartWorkflow(
                    $bp_ID,
                    $documentID,
                    $params,
                    $arErrorsTmp = []
                );
            }
        }
        return __CLASS__.'::'.__FUNCTION__.'();';
    }

    public static function runClearTable()
    {
        $enable = Option::get(self::MODULE_ID, 'enabled', 'N');
        if($enable == 'N')
        {
            return __CLASS__.'::'.__FUNCTION__.'();';
        }

        $rs = DistributionLeadTable::getList();
        while ($ar = $rs->fetch())
        {
            DistributionLeadTable::delete($ar['ID']);
        }

        return __CLASS__.'::'.__FUNCTION__.'();';
    }
}

/***/


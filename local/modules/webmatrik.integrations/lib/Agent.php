<?php

namespace Webmatrik\Integrations;

class Agent
{
    public static function fetchLeads()
    {
        $obj = new BayutEmail();
        $obj->fetchEmailLeads();

        $obj = new BayutCalls();
        $obj->fetchPhoneLeads();

        return '\\' . __METHOD__ . '();';
    }
}
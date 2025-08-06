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

    public static function makeBayutXML()
    {
        $feed = new Feed();

        $feed->makeBayutFeed();

        return '\\' . __METHOD__ . '();';
    }

}
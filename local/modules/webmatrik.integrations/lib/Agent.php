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
        $feed = new FeedBayut();

        $feed->makeNewFeed();

        return '\\' . __METHOD__ . '();';
    }

    public static function syncPfLocations()
    {
        $obj = new FeedPf();
        $cities = ['Dubai', 'Abu Dhabi', 'Sharjah', 'Ajman'];

        foreach ($cities as $city) {
            $obj->syncLocations($city);
        }

        return '\\' . __METHOD__ . '();';
    }

    public static function syncPfUsers()
    {
        $obj = new FeedPf();
        $obj->getPfUsers();

        return '\\' . __METHOD__ . '();';
    }
}

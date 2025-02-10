<?php

namespace Webmatrik\Interface\Events;

class CrmEvents
{
    public static function onMenuBuild(&$items)
    {
        // build new menu
        define("SEARCHVALUES",
            [
                'LEAD',
                'DEAL',
                'crm_clients',
                //'menu_crm_contact',
                //'menu_crm_company',
                'crm_analytics',
                /*'ANALYTICS_SALES_FUNNEL',
                'ANALYTICS_MANAGERS',
                'ANALYTICS_CALLS',
                'ANALYTICS_DIALOGS',
                'CRM_TRACKING',
                'REPORT',
                'ANALYTICS_BI'*/
            ]
        );

        global $USER;

        $userid = $USER->GetID();

        $usergroup = \CUser::GetUserGroup(
            $userid
        );

        if(!in_array(1, $usergroup) && !in_array(21, $usergroup)) {
            $analyzeitems = $items;
            $checkitems = [];
            foreach ($analyzeitems as $menuitem) {
                if(in_array($menuitem['ID'], SEARCHVALUES )) {
                    $checkitems[] = $menuitem;
                }
            }
            $items = $checkitems;
        }
        //\Bitrix\Main\Diag\Debug::writeToFile($items,"items", '__miros.log');
    }
}
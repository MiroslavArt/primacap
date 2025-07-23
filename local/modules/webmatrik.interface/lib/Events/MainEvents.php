<?php

namespace Webmatrik\Interface\Events;

use Bitrix\Main\Diag\Debug;
use Bitrix\Main\EventManager;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Page\Asset;
use Bitrix\Main\Loader;
use CJSCore;

/**
 * Class for Main events
 **/
class MainEvents
{

    /**
     * @return void
     */
    public static function onProlog()
    {
        $eventManager = EventManager::getInstance();

        $eventManager->addEventHandler(
            'main',
            'OnEpilog',
            [MainEvents::class,'onEpilog']
        );

        $eventManager->addEventHandler(
            'tasks',
            'OnTaskAdd',
            [TaskEvents::class,'onTaskAdd']
        );

        $eventManager->addEventHandler(
            'socialnetwork',
            'onSocNetGroupAdd',
            [TaskEvents::class,'onSocNetGroupAdd']
        );

        $eventManager->addEventHandler(
            'crm',
            'OnAfterCrmControlPanelBuild',
            [CrmEvents::class,'onMenuBuild']
        );

        $eventManager->addEventHandler(
            'main',
            'OnUserTypeBuildList',
            ['\Webmatrik\Interface\Property\UFLocations','GetUserTypeDescription']
        );

        \CJSCore::RegisterExt('select2', [
            "js" => "/local/js/select2/script.js",
            "css" => "/local/css/select2/style.css"
        ]);

        \CJSCore::RegisterExt('webmatrik_interface_leads', [
            "js" => "/local/js/webmatrik.interface/crm/leads/script.js",
        ]);
    }

    public static function onEpilog()
    {
        global $USER;

        $urlTemplates = [
            'lead_kanban' => ltrim(Option::get('crm', 'path_to_lead_kanban', '', SITE_ID), '/'),
            'lead_list' => ltrim(Option::get('crm', 'path_to_lead_list', '', SITE_ID), '/'),
        ];
        \Bitrix\Main\UI\Extension::load("ui.buttons");
        $asset = Asset::getInstance();

        $page = \CComponentEngine::parseComponentPath('/', $urlTemplates, $arVars);
        $type = '';
        if ($page !== false) {
            switch ($page) {
                case 'lead_kanban':
                    $type = 'leadkanban';
                    break;
                case 'lead_list':
                    $type = 'leadlist';
                    break;
            }
        }
        \CJSCore::init('jquery3');
        \CJSCore::init('select2');

        if($type =='leadkanban' || $type =='leadlist') {
            \CJSCore::init(['webmatrik_interface_leads']);
            $asset->addString('<script>BX.ready(function () {BX.Webmatrik.Interface.Crm.Leads.init
                ();});</script>');

        }
    }
}
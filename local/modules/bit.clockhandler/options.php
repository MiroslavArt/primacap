<?php

global $APPLICATION;

use Bitrix\Main\Localization\Loc as Loc;
use Bitrix\Main\Application;
use Bitrix\Main\Loader as Loader;
use Bitrix\Main\Config\Option as Option;


$module_id = 'bit.clockhandler';
Loader::includeModule($module_id);
Loader::includeModule('crm');

$request = Application::getInstance()->getContext()->getRequest();
$request = $request->getPostList();

foreach ($request as $key => $arParams)
{
    if($key === 'main')
    {
        foreach ($arParams as $key_param => $value_param){
            switch ($key_param)
            {
                case 'skip_check':
                    $value_param = intval($value_param);
                    if($value_param > 100)
                        $value_param = 100;
                    if($value_param < 0)
                        $value_param = 0;
                    break;
                case 'stage_deal' :
                case 'ignore_activity' :
                    $value_param = implode(',' , $value_param);
                    break;
                default:
                    break;
            }
            Option::set($module_id, $key_param, $value_param);
        }
        break;
    }
}

/*
$arStage = ['-' => 'ALL'];
$Stages = CAllCrmDeal::GetStages();
foreach ($Stages as $stage) {
    $arStage[$stage['STATUS_ID']] = $stage['NAME'];
}

$arEmail = ['-' => 'Not send email'];
$arFilter = array(
    "TYPE_ID" => "BIT_CLOCK_HANDLER_REQUEST",
);
$rsET = \CEventMessage::GetList(
    $by="site_id",
    $order="desc",
    $arFilter
);
while ($arET = $rsET->Fetch())
{
    $arEmail[$arET['ID']] = 'Template # '.$arET['ID'];
}

$arUserGroup = ['-' => 'Not check'];
$rsUG = \CGroup::GetList(
    $by="name",
    $order="asc"
);
while ($arUG = $rsUG->Fetch())
{
    $arUserGroup[$arUG['ID']] = $arUG['NAME'];
}

$arUserGroup = ['-' => 'Not check'];
$rsUG = \CGroup::GetList(
    $by="name",
    $order="asc"
);
while ($arUG = $rsUG->Fetch())
{
    $arUserGroup[$arUG['ID']] = $arUG['NAME'];
}

$arActivity = ['-' => 'None'];
$arProvides = \CAllCrmActivity::GetProviders();
foreach ($arProvides as $provider)
{
    $id = $provider::getTypeId([]);
    $name = $provider::getName();
    if(empty($id) || empty($name))
        continue;

    $arActivity[$id] = $name;
}

$arTimes = [
    '- 1 seconds' => 'Do not include',
    '- 30 minutes' => '30 minutes',
    '- 1 hour' => '1 hour',
    '- 2 hours' => '2 hours',
    '- 3 hours' => '3 hours',
    '- 4 hours' => '4 hours',
    '- 5 hours' => '5 hours',
    '- 6 hours' => '6 hours',
    '- 8 hours' => '8 hours',
    '- 12 hours' => '12 hours',
    '- 1 day' => '1 day',
    '- 2 days' => '2 days',
    '- 1 weeks' => '1 week',
];

/*
$arDeadline = [
    '- 1 seconds' => '--Now--',
    '- 1 day' => 'Yesterday',
    '- 2 days' => '2 days ago',
    '- 3 days' => '3 days ago',
    '- 4 days' => '4 days ago',
    '- 5 days' => '5 days ago',
    '- 1 weeks' => '1 week ago',
    '- 2 weeks' => '2 week ago',
];
$arActivityUpdate = [
    '- 1 seconds' => '--Now--',
    '- 30 minutes' => '30 minutes',
    '- 1 hour' => '1 hour',
    '- 2 hours' => '2 hours',
    '- 3 hours' => '3 hours',
    '- 4 hours' => '4 hours',
    '- 5 hours' => '5 hours',
    '- 6 hours' => '6 hours',
    '- 12 hours' => '12 hours',
    '- 1 day' => '1 day',
];
$arResponsibleChange = [
    '- 1 seconds' => 'Not check',
    '- 1 day' => 'Yesterday',
    '- 2 days' => '2 days ago',
    '- 3 days' => '3 days ago',
    '- 4 days' => '4 days ago',
    '- 5 days' => '5 days ago',
    '- 1 weeks' => '1 week ago',
    '- 2 weeks' => '2 week ago',
];
// */

$arTimeBefore = [
    '02:00' => '06:00',
    '02:30' => '06:30',
    '03:00' => '07:00',
    '03:30' => '07:30',
    '04:00' => '08:00',
    '04:30' => '08:30',
    '05:00' => '09:00',
    '05:30' => '09:30',
    '06:00' => '10:00',
    '06:30' => '10:30',
];
$arTime = [
    '14:00' => '18:00',
    '14:30' => '18:30',
    '15:00' => '19:00',
    '15:30' => '19:30',
    '16:00' => '20:00',
    '16:30' => '20:30',
    '17:00' => '21:00',
    '17:30' => '21:30',
    '18:00' => '22:00',
    '18:30' => '22:30',
    '19:00' => '23:00',
    '19:30' => '23:30',
];
$aTabs = array(
    array(
        "DIV"       => "main",
        "TAB"       => Loc::getMessage("TAB_BASE_NAME"),
        "TITLE"     => Loc::getMessage("TAB_BASE_TITLE"),
        "OPTIONS"   => array(
            Loc::getMessage("TAB_BASE_GROUPFIELD_NAME"),
            array(
                "main[enabled]",
                Loc::getMessage("TAB_BASE_FIELD_ENABLED"),
                Option::get($module_id, 'enabled', 'N'),
                array("selectbox", ['N'=> 'No', 'Y' => 'Yes'])
            ),
            array(
                "main[time_clockout_before]",
                Loc::getMessage("TAB_BASE_CLOCKOUT_BEFORE"),
                Option::get($module_id, 'time_clockout_before', '08:00'),
                array("selectbox", $arTimeBefore),
            ),
            array(
                "main[time_clockout]",
                Loc::getMessage("TAB_BASE_CLOCKOUT_AFTER"),
                Option::get($module_id, 'time_clockout', '18:00'),
                array("selectbox", $arTime),
            ),
            /*
            array(
                "main[skip_check]",
                Loc::getMessage("TAB_BASE_FIELD_SKIP_CHECK"),
                Option::get($module_id, 'skip_check', '75'),
                array("text", 3),
            ),
            array(
                "main[email_template]",
                Loc::getMessage("TAB_BASE_FIELD_EMAIL_TEMPLATE"),
                Option::get($module_id, 'email_template', '-'),
                array("selectbox", $arEmail),
            ),
            array(
                "main[user_group]",
                Loc::getMessage("TAB_BASE_FIELD_USER_GROUP"),
                Option::get($module_id, 'user_group', '-'),
                array("selectbox", $arUserGroup),
            ),
            array(
                "main[ignore_activity]",
                Loc::getMessage("TAB_BASE_FIELD_IGNORE_ACTIVITY"),
                Option::get($module_id, 'ignore_activity', '-'),
                array("multiselectbox", $arActivity),
            ),
            array(
                "main[deadline_activity]",
                Loc::getMessage("TAB_BASE_FIELD_DEADLINE_ACTIVITY"),
                Option::get($module_id, 'deadline_activity', '- 30 minutes'),
                array("selectbox", $arTimes),
            ),
            array(
                "main[update_activity]",
                Loc::getMessage("TAB_BASE_FIELD_UPDATE_ACTIVITY"),
                Option::get($module_id, 'update_activity', '- 30 minutes'),
                array("selectbox", $arTimes),
            ),
            array(
                "main[update_responsible]",
                Loc::getMessage("TAB_BASE_FIELD_UPDATE_RESPONSIBLE"),
                Option::get($module_id, 'update_responsible', '- 30 minutes'),
                array("selectbox", $arTimes),
            ), //*/
        )
    ),
);


$tabControl = new CAdminTabControl(
    "tabControl",
    $aTabs
);

$tabControl->Begin(); ?>
    <form class="rules_form" action="<? echo($APPLICATION->GetCurPage()); ?>?mid=<? echo($module_id); ?>&lang=<? echo(LANG); ?>" method="post">
        <? foreach($aTabs as $aTab) :
            if($aTab["OPTIONS"])
            {
                $tabControl->BeginNextTab();
                __AdmSettingsDrawList($module_id, $aTab["OPTIONS"]);
            }
        endforeach;
        $tabControl->Buttons();
        ?>
        <input type="submit" value="Apply" class="adm-btn-save" />
        <? echo(bitrix_sessid_post()); ?>
    </form>
<?php $tabControl->End(); ?>
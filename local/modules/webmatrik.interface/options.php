<?php

$MODULE_ID = 'webmatrik.interface';

use Bitrix\Main\Config\Option;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Application;
use Bitrix\Main\Loader;

$app = Application::getInstance();
$context = $app->getContext();
$request = $context->getRequest();
Loc::loadMessages($context->getServer()->getDocumentRoot()."/bitrix/modules/main/options.php");
Loc::loadMessages(__FILE__);

global $USER, $APPLICATION;
if (!$USER->CanDoOperation($MODULE_ID . '_settings')) {
    $APPLICATION->AuthForm(Loc::getMessage("ACCESS_DENIED"));
}

$arAllOptions = [
    'main' => [
        [
            'IBLOCK_CITY',
            Loc::getMessage($MODULE_ID . '_IBLOCK_CITY'),
            Option::get($MODULE_ID, '_IBLOCK_CITY'),
            ['text'],
        ],
        [
            'IBLOCK_COMMUNITY',
            Loc::getMessage($MODULE_ID . '_IBLOCK_COMMUNITY'),
            Option::get($MODULE_ID, '_IBLOCK_COMMUNITY'),
            ['text'],
        ],
        [
            'IBLOCK_SUB_COMMUNITY',
            Loc::getMessage($MODULE_ID . '_IBLOCK_SUB_COMMUNITY'),
            Option::get($MODULE_ID, '_IBLOCK_SUB_COMMUNITY'),
            ['text'],
        ],
        [
            'IBLOCK_TOWER_NAME',
            Loc::getMessage($MODULE_ID . '_IBLOCK_TOWER_NAME'),
            Option::get($MODULE_ID, '_IBLOCK_TOWER_NAME'),
            ['text'],
        ],
    ],
];

if(isset($request["save"]) && check_bitrix_sessid()) {
    foreach ($arAllOptions as $part) {
        foreach($part as $arOption) {
            if(is_array($arOption)) {
                __AdmSettingsSaveOption($MODULE_ID, $arOption);
            }
        }
    }
}

$arTabs = [
    [
        "DIV" => "main",
        "TAB" => Loc::getMessage($MODULE_ID.'_main'),
        "ICON" => $MODULE_ID . '_settings',
        "TITLE" => Loc::getMessage($MODULE_ID.'_main'),
        'TYPE' => 'options', //options || rights || user defined
    ]
];

$tabControl = new CAdminTabControl("tabControl", $arTabs);

$tabControl->Begin();
?>
<form method="POST" action="<?= $APPLICATION->GetCurPage() ?>?mid=<?= htmlspecialcharsbx($mid) ?>&amp;lang=<?= LANG ?>"
      name="<?= $MODULE_ID ?>_settings">
    <?= bitrix_sessid_post(); ?>
    <?
    foreach ($arTabs as $tab) {
        $tabControl->BeginNextTab();
        __AdmSettingsDrawList($MODULE_ID, $arAllOptions[$tab['DIV']]);
    }?>
    <?$tabControl->Buttons();?>
    <input type="submit" class="adm-btn-save" name="save" value="<?=Loc::getMessage($MODULE_ID.'_save');?>">
    <?=bitrix_sessid_post();?>
    <? $tabControl->End(); ?>
</form>
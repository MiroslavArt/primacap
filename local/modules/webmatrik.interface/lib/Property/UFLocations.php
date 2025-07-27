<?php

namespace Webmatrik\Interface\Property;

use Bitrix\Main\UserField\TypeBase;
use Bitrix\Main\Config\Option;

class UFLocations extends TypeBase
{
    const USER_TYPE_ID = 'proplocations';

    /**
     * @var string
     */
    protected static $MODULE_ID;

    public static function GetUserTypeDescription()
    {
        self::$MODULE_ID = 'webmatrik.interface';
        return array(
            'USER_TYPE_ID' => static::USER_TYPE_ID,
            'CLASS_NAME' => __CLASS__,
            'DESCRIPTION' => 'Property Locations',
            'BASE_TYPE' => \CUserTypeManager::BASE_TYPE_STRING,
            'EDIT_CALLBACK' => array(__CLASS__, 'GetPublicEdit'),
            'VIEW_CALLBACK' => array(__CLASS__, 'GetPublicView')
        );
    }

    public static function GetDBColumnType($arUserField)
    {
        global $DB;
        switch (strtolower($DB->type)) {
            case "mysql":
                return "text";
            case "oracle":
                return "varchar2(2000 char)";
            case "mssql":
                return "varchar(2000)";
        }
    }

    public static function GetPublicView($arUserField, $arAdditionalParameters = array()) {
        //return StringType::renderView($userField, $additionalParameters);
        return '<p>' . $arUserField["VALUE"] . '</p>';

    }

    public static function GetPublicEdit($arUserField, $arAdditionalParameters = array())
    {
        $arCities = self::GetCity();
        $arCommunities = self::GetCommunity();
        $arSubCommunities = self::GetSubCommunity();
        $arTowerNames = self::GetTowerNames();

        if($arUserField["VALUE"]) {
            $arValues = json_decode($arUserField["VALUE"], 1);
            //\Bitrix\Main\Diag\Debug::writeToFile($arValues, "bayutcall ".date('Y-m-d H:i:s'), "test3.log");
        } else {
            $arValues = [
                'CITY'=> '',
                'COMMUNITY' => '',
                'SUB_COMMUNITY' => '',
                'TOWER_NAME' => ''
            ];
        }

        ob_start();
        ?>
        <script>
            $(document).ready(function() {
                var _idCity = "#<?=$arUserField["FIELD_NAME"]?>_CITY";
                var _idCommunity = "#<?=$arUserField["FIELD_NAME"]?>_COMMUNITY";
                var _idSubCommunity = "#<?=$arUserField["FIELD_NAME"]?>_SUB_COMMUNITY";
                var _idTowerName = "#<?=$arUserField["FIELD_NAME"]?>_TOWER_NAME";
                var _idResult = "#<?=$arUserField["FIELD_NAME"]?>";

                var _loc_city = <?=\CUtil::PhpToJSObject($arCities)?>;
                var _loc_communities = <?=\CUtil::PhpToJSObject($arCommunities)?>;
                var _loc_sub_communities = <?=\CUtil::PhpToJSObject($arSubCommunities)?>;
                var _loc_tower_names = <?=\CUtil::PhpToJSObject($arTowerNames)?>;

                if (window.AUTOLOCATION != 'y') {
                    window.AUTOLOCATION = 'y';

                    function clearSelectLocation(id) {
                        $(id).find('option').each(function () {
                            if ($(this).val()) {
                                $(this).remove();
                            }
                        });
                    }

                    function SetLocation(_idCity, _idCommunity, _idSubCommunity, _idTowerName) {
                        let result = {
                            CITY: '',
                            COMMUNITY: '',
                            SUB_COMMUNITY: '',
                            TOWER_NAME: ''
                        };
                        if ($(_idCity).val()) {
                            result['CITY'] = $(_idCity).val();
                        }
                        if ($(_idCommunity).val()) {
                            result['COMMUNITY'] = $(_idCommunity).val();
                        }
                        if ($(_idSubCommunity).val()) {
                            result['SUB_COMMUNITY'] = $(_idSubCommunity).val();
                        }
                        if ($(_idTowerName).val()) {
                            result['TOWER_NAME'] = $(_idTowerName).val();
                        }
                        $(_idResult).val(JSON.stringify(result));
                    }
                }

                $('body').on('change', _idCity, function () {
                    clearSelectLocation(_idCommunity);
                    console.log($(this).val());
                    if ($(this).val()) {
                        for (_i in _loc_communities) {
                            if ($(this).val() != _loc_communities[_i].CITY) {
                                continue;
                            }
                            $(_idCommunity).append('<option value="' + _loc_communities[_i].XML_ID + '">' +
                                _loc_communities[_i].NAME + '</option>');
                        }
                    }
                    clearSelectLocation(_idSubCommunity);
                    clearSelectLocation(_idTowerName);
                    SetLocation(_idCity, _idCommunity, _idSubCommunity, _idTowerName);
                });

                $('body').on('change', _idCommunity, function () {
                    clearSelectLocation(_idSubCommunity);
                    console.log($(this).val());
                    if ($(this).val()) {
                        for (_i in _loc_sub_communities) {
                            if ($(this).val() != _loc_sub_communities[_i].COMMUNITY) {
                                continue;
                            }
                            $(_idSubCommunity).append('<option value="' + _loc_sub_communities[_i].XML_ID + '">' +
                                _loc_sub_communities[_i].NAME + '</option>');

                        }
                    }
                    clearSelectLocation(_idTowerName);
                    SetLocation(_idCity, _idCommunity, _idSubCommunity, _idTowerName);
                });

                $('body').on('change', _idSubCommunity, function () {
                    clearSelectLocation(_idTowerName);
                    if ($(this).val()) {
                        for (_i in _loc_tower_names) {
                            if ($(this).val() != _loc_tower_names[_i].SUB_COMMUNITY) {
                                continue;
                            }
                            $(_idTowerName).append('<option value="' + _loc_tower_names[_i].XML_ID + '">' +
                                _loc_tower_names[_i].NAME + '</option>');

                        }
                    }
                    SetLocation(_idCity, _idCommunity, _idSubCommunity, _idTowerName);
                });
                $('body').on('change', _idTowerName, function () {
                    SetLocation(_idCity, _idCommunity, _idSubCommunity, _idTowerName);
                });
                BX.onCustomEvent(window, 'onCrmEntityEditorUserFieldExternalChanged', ['<?= $arUserField["FIELD_NAME"] ?>']);
                $(_idCity).select2(
                    {
                        width: '100%'
                    });
                $(_idCommunity).select2(
                    {
                        width: '100%'
                    });
                $(_idSubCommunity).select2(
                    {
                        width: '100%'
                    });
                $(_idTowerName).select2(
                    {
                        width: '100%'
                    });
            });
        </script>
        <div class="crm-entity-widget-resourcebook-container">
            <input type="hidden" name="<?= htmlspecialcharsbx($arUserField["FIELD_NAME"]) ?>"
                   value='<?= json_encode($arValues) ?>'
                   id="<?= $arUserField["FIELD_NAME"] ?>">
            <div>
                <div class="name">City:</div>
                <div class="value">
                    <select name="<?= htmlspecialcharsbx($arUserField["FIELD_NAME"]) ?>_CITY"
                            id="<?= $arUserField["FIELD_NAME"] ?>_CITY">
                        <option value="">not select</option>
                        <?
                        foreach ($arCities as $arItem) {
                            $selected = '';
                            if ($arItem['XML_ID'] == $arValues['CITY']) {
                                $selected = 'selected';
                            }
                            ?>
                            <option <?= $selected ?> value="<?= $arItem['XML_ID'] ?>"><?= $arItem['NAME'] ?></option><?
                        }
                        ?>
                    </select>
                </div>
            </div>
            <div>
                <div class="name">Community:</div>
                <div class="value">
                    <select name="<?= htmlspecialcharsbx($arUserField["FIELD_NAME"]) ?>_COMMUNITY"
                            id="<?= $arUserField["FIELD_NAME"] ?>_COMMUNITY">
                        <option value="">not select</option>
                        <?
                        if (!empty($arValues['CITY'])) {
                            foreach ($arCommunities as $arItem) {
                                if ($arItem['CITY'] != $arValues['CITY']) {
                                    continue;
                                }
                                $selected = '';
                                if ($arItem['XML_ID'] == $arValues['COMMUNITY']) {
                                    $selected = 'selected';
                                }
                                ?>
                                <option <?= $selected ?> value="<?= $arItem['XML_ID'] ?>"><?= $arItem['NAME'] ?></option>
                                <?
                            }
                        } ?>
                    </select>
                </div>
            </div>
            <div>
                <div class="name">Sub-Community:</div>
                <div class="value">
                    <select name="<?= htmlspecialcharsbx($arUserField["FIELD_NAME"]) ?>_SUB_COMMUNITY"
                            id="<?= $arUserField["FIELD_NAME"] ?>_SUB_COMMUNITY">
                        <option value="">not select</option>
                        <?
                        if (!empty($arValues['COMMUNITY'])) {
                            foreach ($arSubCommunities as $arItem) {
                                if ($arItem['COMMUNITY'] != $arValues['COMMUNITY']) {
                                    continue;
                                }
                                $selected = '';
                                if ($arItem['XML_ID'] == $arValues['SUB_COMMUNITY']) {
                                    $selected = 'selected';
                                }
                                ?>
                                <option <?= $selected ?> value="<?= $arItem['XML_ID'] ?>"><?= $arItem['NAME'] ?></option>
                                <?
                            }
                        } ?>
                    </select>
                </div>
            </div>
            <div>
                <div class="name">Sub-Sub-Community (If Applicable):</div>
                <div class="value">
                    <select name="<?= htmlspecialcharsbx($arUserField["FIELD_NAME"]) ?>_TOWER_NAME"
                            id="<?= $arUserField["FIELD_NAME"] ?>_TOWER_NAME">
                        <option value="">not select</option>
                        <?
                        if (!empty($arValues['SUB_COMMUNITY'])) {
                            foreach ($arTowerNames as $arItem) {
                                if ($arItem['SUB_COMMUNITY'] != $arValues['SUB_COMMUNITY']) {
                                    continue;
                                }
                                $selected = '';
                                if ($arItem['XML_ID'] == $arValues['TOWER_NAME']) {
                                    $selected = 'selected';
                                }
                                ?>
                                <option <?= $selected ?> value="<?= $arItem['XML_ID'] ?>"><?= $arItem['NAME'] ?></option>
                                <?
                            }
                        } ?>
                    </select>
                </div>
            </div>
         </div>
        <?php

        $strResult = ob_get_contents();
        ob_end_clean();
        return $strResult;

    }

    public static function GetPublicEditTest($arUserField, $arAdditionalParameters = array())
    {
        $options = [
            '1' => 'Dubai',
            '2' => 'Abu Dhabi',
            '3' => 'Sharjah',
            '4' => 'Al Ain',
        ];

        $selectedValues = [$arUserField["VALUE"]];
        $controlId = $arUserField['FIELD_NAME'].'_'.rand();

        ob_start();
        ?>
        <div id="<?= $controlId ?>" class="crm-entity-widget-resourcebook-container">
            <select id="LocSelect" class="form-control">
                <?php foreach ($options as $value => $label): ?>
                    <option value="<?= $value ?>"
                        <?= in_array($value, $selectedValues) ? 'selected="selected"' : '' ?>>
                        <?= htmlspecialchars($label) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <input name='<?= $arUserField["FIELD_NAME"] ?>' value='<?= $arUserField["VALUE"] ?>' id="<?= $arUserField["FIELD_NAME"] ?>"
                   type="hidden">

        </div>

        <script>
            $(document).ready(function() {
                /*$('#LocSelect').selectize({
                    persist: false,
                    create: true
                });*/
                $('#LocSelect').select2({
                    placeholder: "Search for a location",
                    allowClear: true
                });
                var selectedValue = $('#LocSelect').val();
                BX.onCustomEvent(window, 'onCrmEntityEditorUserFieldExternalChanged', ['<?= $arUserField["FIELD_NAME"] ?>']);
                $('#LocSelect').on('change', function() {
                    // Get the selected value
                    selectedValue = $(this).val();
                    // Set the value to the hidden field
                    $('#<?= $arUserField["FIELD_NAME"] ?>').val(selectedValue);

                });
            });
        </script>

        <?
        $strResult = ob_get_clean();


        return $strResult;
        //return '<input type="text" name="UF_CRM_5_1752834036" placeholder="">';
    }

    /**
     * @return array
     */
    public static function GetCity(): array
    {
        $arResult = [];
        $iblock_id = Option::get(self::$MODULE_ID, 'IBLOCK_CITY');
        $iblock_id = intval($iblock_id);
        if($iblock_id == 0)
        {
            return $arResult;
        }
        $rsData = \CIBlockElement::GetList(
            ['SORT' => 'ASC'],
            ['IBLOCK_ID' => $iblock_id],
            false,
            false,
            ['ID', 'NAME', 'XML_ID']
        );
        while ($arData = $rsData->GetNext()) {
            $arResult[] = [
                'ID' => $arData['ID'],
                'NAME' => $arData['NAME'],
                'XML_ID' => $arData['XML_ID']
            ];
        }
        return $arResult;
    }

    /**
     * @return array
     */
    public static function GetCommunity(): array
    {
        $arResult = [];
        $iblock_id = Option::get(self::$MODULE_ID, 'IBLOCK_COMMUNITY');
        $iblock_id = intval($iblock_id);
        if($iblock_id == 0)
        {
            return $arResult;
        }
        $rsData = \CIBlockElement::GetList(
            ['SORT' => 'ASC'],
            ['IBLOCK_ID' => $iblock_id],
            false,
            false,
            ['ID', 'NAME', 'PROPERTY_CITY', 'XML_ID']
        );
        while ($arData = $rsData->GetNext()) {
            $arResult[] = [
                'ID' => $arData['ID'],
                'NAME' => $arData['NAME'],
                'CITY' => $arData['PROPERTY_CITY_VALUE'],
                'XML_ID' => $arData['XML_ID']
            ];
        }
        return $arResult;
    }

    /**
     * @return array
     */
    public static function GetSubCommunity(): array
    {
        $arResult = [];
        $iblock_id = Option::get(self::$MODULE_ID, 'IBLOCK_SUB_COMMUNITY');
        $iblock_id = intval($iblock_id);
        if($iblock_id == 0)
        {
            return $arResult;
        }
        $rsData = \CIBlockElement::GetList(
            ['SORT' => 'ASC'],
            ['IBLOCK_ID' => $iblock_id],
            false,
            false,
            ['ID', 'NAME', 'PROPERTY_COMMUNITY', 'XML_ID']
        );
        while ($arData = $rsData->GetNext()) {
            $arResult[] = [
                'ID' => $arData['ID'],
                'NAME' => $arData['NAME'],
                'COMMUNITY' => $arData['PROPERTY_COMMUNITY_VALUE'],
                //'COMMUNITY_ID' => $arData['PROPERTY_COMMUNITY_VALUE_ID'],
                'XML_ID' => $arData['XML_ID']
            ];
        }
        return $arResult;
    }

    /**
     * @return array
     */
    public static function GetTowerNames(): array
    {
        $arResult = [];
        $iblock_id = Option::get(self::$MODULE_ID, 'IBLOCK_TOWER_NAME');
        $iblock_id = intval($iblock_id);
        if($iblock_id == 0)
        {
            return $arResult;
        }
        $rsData = \CIBlockElement::GetList(
            ['SORT' => 'ASC'],
            ['IBLOCK_ID' => $iblock_id],
            false,
            false,
            ['ID', 'NAME', 'PROPERTY_SUB_COMMUNITY', 'XML_ID']
        );
        while ($arData = $rsData->GetNext()) {
            $arResult[] = [
                'ID' => $arData['ID'],
                'NAME' => $arData['NAME'],
                'SUB_COMMUNITY' => $arData['PROPERTY_SUB_COMMUNITY_VALUE'],
                //'SUB_COMMUNITY_ID' => $arData['PROPERTY_SUB_COMMUNITY_VALUE_ID'],
                'XML_ID' => $arData['XML_ID']
            ];
        }
        return $arResult;
    }
}
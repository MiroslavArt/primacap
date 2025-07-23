<?php

namespace Webmatrik\Interface\Property;

use Bitrix\Main\UserField\TypeBase;
use Bitrix\Main\Config\Option;

class UFLocations extends TypeBase
{
    const USER_TYPE_ID = 'proplocations';

    public static function GetUserTypeDescription()
    {
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
}
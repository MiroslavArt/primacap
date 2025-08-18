<?php

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;
use Bitrix\Iblock\UserField\Types\ElementType;
use Bitrix\Main\Component\BaseUfComponent;
use Bitrix\Iblock\Component\UserField\Catalog\CheckAccessTrait;
Loc::loadMessages(__FILE__);
//require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/components/bitrix/iblock.field.element/class.php");
/**
 * Class ElementUfComponent
 * @see bitrix/components/bitrix/iblock.field.element
 */
class CrmDependFieldsElementUfComponent  extends BaseUfComponent
{
    use CheckAccessTrait;

    protected static
        $iblockIncluded = null;

    public function __construct($component = null)
    {
        if(Loader::includeModule('bestrank.crmdependentfields'))
        {
            parent::__construct($component);
        }
    }

    /**
     * @return bool
     */
    public function isIblockIncluded():bool
    {
        return (static::$iblockIncluded !== null);
    }

    protected static function getUserTypeId(): string
    {
        return ElementType::USER_TYPE_ID;
    }

    /**
     * @inheritDoc
     */
    protected function prepareResult(): void
    {
        parent::prepareResult();

        if ($this->arResult['userField']['SETTINGS']['DISPLAY']) {
            if (!in_array(
                $this->arResult['userField']['SETTINGS']['DISPLAY'],
                [ElementType::DISPLAY_UI, ElementType::DISPLAY_DIALOG]
            )) {
                if (empty($this->arResult['userField']['USER_TYPE']['FIELDS'])) {
                    $this->arResult['userField']['USER_TYPE']['FIELDS'] = [
                        '' => Loc::getMessage('CRMDEPNDENTFIELDS_IBLOCK_FIELD_ELEMENT_NOT_SELECT'),
                    ];
                }
            }

            if ($this->arResult['userField']['SETTINGS']['DISPLAY'] === ElementType::DISPLAY_DIALOG)
            {
                $crmHelper = new \Bestrank\CrmDependentFields\Helpers\Crm();

                $filterIds = $crmHelper->getDependentFieldsValuesFilterElements(
                    $this->arResult['userField']['ENTITY_ID'],
                    $this->arResult['userField']['ENTITY_VALUE_ID'],
                    $this->arResult['userField']['FIELD_NAME'],
                    (int)$this->arResult['userField']["SETTINGS"]["IBLOCK_ID"],
                    $this->arResult['userField']["SETTINGS"]["BR_CATEGORY_ID"]
                );

                if (!empty($filterIds)) {
                    $this->arResult['DISPLAY_FILTER_ELEMENTS'] = ["=ID" => $filterIds];
                }
            }
        }

        $this->arResult['hasAccessToCatalog'] = $this->hasAccessToCatalog();
    }
}

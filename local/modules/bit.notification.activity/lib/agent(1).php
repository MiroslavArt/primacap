<?php

namespace Bit\Notification\Activity;

use \Bitrix\Main\Config\Option as Option;
use Bitrix\Main\Loader as Loader;
use Bitrix\Main\Type\DateTime as DT;

class Agent
{
    const MODULE_ID = "bit.notification.activity";

    static $userPhoneData = [];


    public static function runAgent()
    {
        $enable = Option::get(self::MODULE_ID, 'enabled', 'N');
        if($enable == 'N')
        {
            return __CLASS__.'::'.__FUNCTION__.'();';
        }

        $arOptions = self::getNoteActOptions();

        if(count($arOptions) > 0)
        {
            Loader::includeModule('crm');

            $sendingActivityID = [];

            $rs = CheckActivityTable::getList();
            while($arData = $rs->fetch())
            {
                $sendingActivityID[] = $arData['ACT_ID'];
            }

            foreach ($arOptions as $optionData)
            {
                $optionData = unserialize($optionData);

                $endDate = new DT();
                $modifyTime = $optionData[1] . ' minutes';
                $endDate->add($modifyTime);

                $filterOption = [
                    'PROVIDER_ID' => $optionData[0],
                    '<DEADLINE' => $endDate,
                ];

                $currDate = new DT();
                $filter = [
                    'COMPLETED' => 'N',
                    '>=DEADLINE' => $currDate,
                ];
                $filter = array_merge($filter, $filterOption);

                if(count($sendingActivityID) > 0)
                {
                    $filter['!ID'] = $sendingActivityID;
                }

                $rs = \CCrmActivity::GetList(['ID' => 'ASC'], $filter);

                while ($arActivity = $rs->fetch())
                {
                    $newRow = [
                        'ACT_ID' => $arActivity['ID'],
                    ];
                    CheckActivityTable::add($newRow);
                    self::sendNote($arActivity, $optionData[2]);
                }
            }
        }

         return __CLASS__.'::'.__FUNCTION__.'();';
    }

    public static function runClearTable()
    {
        $rs = CheckActivityTable::getList();
        $arData = [];
        $arActId = [];
        while($ar = $rs->fetch())
        {
            $arData[$ar['ACT_ID']] = $ar;
            $arActId[] = $ar['ACT_ID'];
        }
        if(count($arActId) > 0)
        {
            Loader::includeModule('crm');

            $currDate = new DT();
            $currDate->add('- 1 day');

            $filter = [
                'ID' => $arActId,
                '<DEADLINE' => $currDate,
            ];
            $rs = \CCrmActivity::GetList(['ID' => 'ASC'], $filter);
            while ($ar = $rs->fetch())
            {
                $actID = $ar['ID'];
                $rowID = $arData[$actID]['ID'];
                CheckActivityTable::Delete($rowID);
            }
        }

         return __CLASS__.'::'.__FUNCTION__.'();';
    }



    public function test($phone)
    {
        $arOptions = self::getNoteActOptions();
        if(count($arOptions) > 0)
        {
            Loader::includeModule('crm');

            $sendingActivityID = [];
            $rs = CheckActivityTable::getList();
            if($arData = $rs->fetch())
            {
                $sendingActivityID[] = $arData['ACT_ID'];
            }
            foreach ($arOptions as $optionData)
            {
                $optionData = unserialize($optionData);

                $endDate = new DT();
                $modifyTime = $optionData[1] . ' minutes';
                $endDate->add($modifyTime);

                $filterOption = [
                    'PROVIDER_ID' => $optionData[0],
                    '<DEADLINE' => $endDate,
                ];

                $currDate = new DT();
                $filter = [
                    'COMPLETED' => 'N',
                    '>=DEADLINE' => $currDate,
                ];
                $filter = array_merge($filter, $filterOption);

                if(count($sendingActivityID) > 0)
                {
                    $filter['!ID'] = $sendingActivityID;
                }

                $rs = \CCrmActivity::GetList(['ID' => 'ASC'], $filter);
                while ($arActivity = $rs->fetch())
                {
                    $newRow = [
                        'ACT_ID' => $arActivity['ID'],
                    ];
					//CheckActivityTable::add($newRow);
                    self::sendNote($arActivity, $optionData[2], $phone);
                    break;
                }
            }
        }

		//var_dump('work end');
      return __CLASS__.'::'.__FUNCTION__.'();';
    }
    public static function sendNote($actData, $textNote, $phone = '')
    {
        Loader::includeModule('crm');

        if(!$actData['ID'])
        {
            return;
        }

        $entityID = $actData['OWNER_ID'];
        $entityType = $actData['OWNER_TYPE_ID'];

        $entityClass = null;
        switch ($entityType)
        {
            case \CCrmOwnerType::Lead:
                $entityClass = '\CCrmLead';
                break;
            case \CCrmOwnerType::Deal:
                $entityClass = '\CCrmDeal';
                break;
            case \CCrmOwnerType::Contact:
                $entityClass = '\CCrmContact';
                break;
            case \CCrmOwnerType::Company:
                $entityClass = '\CCrmCompany';
                break;
        }

        if($entityClass)
        {
            $rsEntity = $entityClass::getList([],['ID' => $entityID]);
            if ($arEntity = $rsEntity->fetch())
            {
                $userPhone = self::getUserPhone($actData['RESPONSIBLE_ID']);
                if(empty($userPhone))
                {
                    return;
                }

                preg_match_all('/(\#[0-9a-zA-Z_]+\#)/', $textNote, $findPattern);
                $findPattern = array_shift($findPattern);
                foreach ($findPattern as $pattern)
                {
                    $patternValue = Main::getValueByPattern($pattern, $arEntity, $actData);
                    $textNote = str_replace($pattern, $patternValue, $textNote);
                }



                if (!empty($phone))
                {
                    $userPhone = $phone;
                    var_dump([
                        'USER ID' => $actData['RESPONSIBLE_ID'],
                        'USER PHONE' => $userPhone,
                        'ENTITY ID' => $entityID,
                        'NOTE TEXT' => $textNote,
                    ]);

                }

                $prop = [
                    'NOTE_TEXT' => $textNote
                ];


                $data = [
                    'IBLOCK_ID' => 40,
                    'NAME' => $userPhone,
                    'PROPERTY_VALUES' => $prop,
                    'ACTIVE' => 'Y',
                ];
                $el = new \CIBlockElement;
                $res = $el->add($data);

                if($res > 0)
                {
                    $arErrorsTmp = [];

                    Loader::includeModule('bizproc');

                    $templateID = '129';

                    $resBP = \CBPDocument::StartWorkflow(
                        $templateID,
                        ["lists", "	BizprocDocument", $res],
                        [],
                        $arErrorsTmp
                    );

                    if (!empty($phone))
                    {
                        var_dump($resBP);
                    }
                }
            }
        }

    }

    private static function getUserPhone($user_id)
    {
        $result = '';

        if(isset(self::$userPhoneData[$user_id]))
        {
            $result = self::$userPhoneData[$user_id];
        }
        else
        {
            $rs = \CUser::GetByID($user_id);
            if ($ar = $rs->Fetch())
            {
                $arReplace = [' ', '-'];
                $phone = str_replace($arReplace, '', $ar['WORK_PHONE']);


                self::$userPhoneData[$user_id] = $phone;
                $result = $phone;
            }
            else
            {
                self::$userPhoneData[$user_id] = '';
            }
        }


        return $result;
    }

    public static function getNoteActOptions()
    {
        $arOptions = Option::getForModule(self::MODULE_ID);
        foreach ($arOptions as $key => $option)
        {
            if(strpos($key, 'note_act_') === false)
            {
                unset($arOptions[$key]);
            }
        }
        return $arOptions;
    }


}
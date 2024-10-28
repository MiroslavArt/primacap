<?php

namespace Bit\Distribution\Lead;

use Bitrix\Main\Loader as Loader;
use \Bitrix\Main\Config\Option as Option;

class Main
{
    const MODULE_ID = "bit.distribution.lead";

    const IBLOCK_PROJECT_ID = 29;

    /**
     * @param $projectName
     * @return array
     */
    public static function getUserByProjectName($projectName)
    {
        $arUserId = [];
        $rs = \CIBlockElement::GetList(
            [
                'name' => 'ASC'
            ],
            [
                'IBLOCK_ID' => self::IBLOCK_PROJECT_ID,
                'NAME' => $projectName,
            ],
            false,
            false,
            ['ID','NAME', 'PROPERTY_RESPONSIBLE_PERSONS']
        );
        while($ar = $rs->Fetch())
        {
            $userID = $ar['PROPERTY_RESPONSIBLE_PERSONS_VALUE'];
            $arUserId[] = $userID;
        }

        return $arUserId;
    }
    public static function getDistributionLimits($projectName, $arUserId, $source)
    {
        $arLimit = [];

        $defaultCountPerUser = Option::get(self::MODULE_ID, 'count_per_user', '10');
        $defaultCountPerProject = Option::get(self::MODULE_ID, 'count_per_project', '5');

        $rsDistribution = DistributionTable::getList([
            'order' => [
                'TYPE' => 'ASC',
                'USER_ID' => 'ASC',
            ],
            'filter' => [
                'LOGIC' => 'OR',
                [
                    'TYPE' => DistributionTable::TYPE_PROJECT,
                    'VALUE'=> $projectName,
                ],
                [
                    'TYPE' => DistributionTable::TYPE_USER,
                    'USER_ID' => $arUserId,
                ],
                [
                    'TYPE' => DistributionTable::TYPE_SOURCE,
                    'VALUE'=> $source,
                    'USER_ID' => $arUserId,
                ],

            ]
        ]);
        while ($arRow = $rsDistribution->fetch())
        {
            $type = $arRow['TYPE'];
            $userID = $arRow['USER_ID'];

            if($userID > 0)
            {
                if(!isset($arLimit[$userID]))
                {
                    $arLimit[$userID] = [];
                }
            }

            switch ($type)
            {
                case DistributionTable::TYPE_PROJECT:
                    if($userID > 0)
                    {
                        $arLimit[$userID]['MAX_LIMIT_PROJECT'] = $arRow['COUNT'];
                    }
                    else
                    {
                        $arLimit['PROJECT'] = $arRow['COUNT'];
                    }
                    break;
                case DistributionTable::TYPE_USER:
                    $arLimit[$userID]['MAX_LIMIT'] = $arRow['COUNT'];
                    break;
                case DistributionTable::TYPE_SOURCE:
                    $arLimit[$userID]['MAX_LIMIT_SOURCE'] = $arRow['COUNT'];
                    break;
                default:
                    break;
            }
        }

        $arLimit['PROJECT'] = $arLimit['PROJECT'] ? $arLimit['PROJECT'] : $defaultCountPerProject;
        foreach ($arUserId as $userID)
        {
            $arLimit[$userID]['MAX_LIMIT_PROJECT'] = isset($arLimit[$userID]['MAX_LIMIT_PROJECT']) ? $arLimit[$userID]['MAX_LIMIT_PROJECT'] : $arLimit['PROJECT'];
            $arLimit[$userID]['MAX_LIMIT'] = isset($arLimit[$userID]['MAX_LIMIT']) ? $arLimit[$userID]['MAX_LIMIT'] : $defaultCountPerUser;
        }
        return $arLimit;
    }
    public static function getCurrDistribution($projectName, $arUserId)
    {
        $arLimit = [];

        $rsDistribution = DistributionLeadTable::getList([
            'order' => [
                'USER_ID' => 'ASC',
            ],
            'filter' => [
                'LOGIC' => 'OR',
                [
                    'USER_ID'=> $arUserId,
                ],
                [
                    'PROJECT' => $projectName,
                ],
            ]
        ]);

        foreach ($arUserId as $userID)
        {
            $arLimit[$userID] = [
                'ALL_COUNT' => 0,
                'COUNT_PROJECT' => 0,
                'SOURCE' => [

                ],
            ];
        }

        while ($arRow = $rsDistribution->fetch())
        {
            $source = $arRow['SOURCE'];
            $project = $arRow['PROJECT'];
            $userID = $arRow['USER_ID'];
            $count  = $arRow['COUNT'];

            if ($project == $projectName)
            {
                $arLimit[$userID]['COUNT_PROJECT'] += $count;
            }
            if(!isset($arLimit[$userID]['SOURCE'][$source]))
            {
                $arLimit[$userID]['SOURCE'][$source] = 0;
            }
            $arLimit[$userID]['SOURCE'][$source] += $count;
            $arLimit[$userID]['ALL_COUNT'] += $count;

        }
        return $arLimit;
    }

    public static function getNewResponsibleForLead($projectName, $source, &$isNewResponsible, &$newResponsibleID)
    {
        $isNewResponsible = false;
        $newResponsibleID = false;

        if(strlen($projectName) == 0 || empty($projectName))
        {
            return false;
        }

        $arUserId = self::getUserByProjectName($projectName);
        if(count($arUserId) == 0) // нет пользователей у проекта
        {
            return false;
        }

        $connection = \Bitrix\Main\Application::getConnection();
        $currDay = new \Bitrix\Main\Type\DateTime();
        $formatTime = 'Y-m-d 00:00:01';
        $currDay = $currDay->format($formatTime);

        $sql = "SELECT ID, USER_ID
            FROM b_timeman_entries
            WHERE DATE_START > '%s' 
              AND DATE_FINISH IS NULL ";
        $sql = sprintf($sql, $currDay);
        $rsUser = $connection->query($sql);
        $clockInUserId = [];
        while ($arUser = $rsUser->Fetch())
        {
            $USER_ID = $arUser['USER_ID'];
            $clockInUserId[] = $USER_ID; // $arUser['USER_ID'];
        }

        if(count($clockInUserId) == 0) // нет активных пользователей
        {
            return false;
        }

        foreach ($arUserId as $userKey => $userID)
        {
            if(in_array($userID, $clockInUserId))
            {

            }
            else
            {
                unset($arUserId[$userKey]);
            }
        }
        if(count($arUserId) == 0) // нет пользователей у проекта
        {
            return false;
        }



        $arDistributionLimits = self::getDistributionLimits($projectName, $arUserId, $source);
        $arCurrDistribution = self::getCurrDistribution($projectName, $arUserId);


        $selectResponsible = 0;
        $selectResponsibleMaxCount = 0;

        foreach ($arUserId as $userID)
        {
		/*---Boost Lead User Select---*/
          global $DB;
         \Bitrix\Main\Loader::includeModule('crm');

		$sql_bst="SELECT UF_BOOST_LEAD,UF_BOOST_VALID,VALUE_ID from b_uts_user where VALUE_ID = $userID and UF_BOOST_LEAD ='675' and UF_BOOST_VALID >= NOW()";
  		$rsUser_bst = $DB->query($sql_bst);
		$arUser_bst = $rsUser_bst->Fetch();

         $filter = [
            'ASSIGNED_BY_ID' => $userID,
            'STATUS_ID' => 'UC_9WUJ49',
            'UF_CRM_1646721978' => $projectName, 
        ];

        $rs = \CCrmLead::GetList([], $filter);
        $ar = $rs->fetch();

            $limit = $arDistributionLimits[$userID];
            $currLimit = $arCurrDistribution[$userID];

            if(($limit['MAX_LIMIT'] > $currLimit['ALL_COUNT']) && empty($ar['ID']))
            {

             if($arUser_bst['VALUE_ID'] > 0)
			    {
						$selectResponsible = $arUser_bst['VALUE_ID'];
						} else {
                if($limit['MAX_LIMIT_PROJECT'] > $currLimit['COUNT_PROJECT'])
                {
                  if(isset($limit['MAX_LIMIT_SOURCE']))
                    {
                        $currSource = isset($currLimit['SOURCE'][$source]) ? $currLimit['SOURCE'][$source] : 0;
                        if($limit['MAX_LIMIT_SOURCE'] > $currSource)
                        {
                            if($selectResponsible > 0)
                            {
                                if($currLimit['ALL_COUNT'] < $selectResponsibleMaxCount)
                                {
                                    $selectResponsible = $userID;
                                    $selectResponsibleMaxCount = $currLimit['ALL_COUNT'];
                                }
                            }
                            else
                            {
                                $selectResponsible = $userID;
                                $selectResponsibleMaxCount = $currLimit['ALL_COUNT'];
                            }
                        }
                    }
                    else
                    {
                        if($selectResponsible > 0)
                        {
                            if($currLimit['ALL_COUNT'] < $selectResponsibleMaxCount)
                            {
                                $selectResponsible = $userID;
                                $selectResponsibleMaxCount = $currLimit['ALL_COUNT'];
                            }
                        }
                        else
                        {
                            $selectResponsible = $userID;
                            $selectResponsibleMaxCount = $currLimit['ALL_COUNT'];
                        }
                    }

                }
				} 
            }
            else
            {
                continue;
            }

      }

        if($selectResponsible > 0)
        {
            $isNewResponsible = true;
            $newResponsibleID = $selectResponsible;
            DistributionLeadTable::addCount($projectName, $source, $selectResponsible);
        }


        return true;
    }

    public static function getLeadSourceID($leadSource)  //$leadID)
    {
        Loader::includeModule('crm');

        $source = '';

        /*$filter = [
            'ID' => $leadID,
        ];
        $rs = \CCrmLead::GetList(
            [],
            $filter
        );
        if($ar = $rs->fetch())
        {
            $source = $ar['SOURCE_ID'];
        }


        //*/

        $rs = \CCrmStatus::GetList(
            [
                'SORT' => 'ASC'
            ],
            [
                'ENTITY_ID' => 'SOURCE'
            ]
        );
        while($ar = $rs->fetch())
        {
            if($ar['NAME'] == $leadSource)
            {
                $source = $ar['STATUS_ID'];
                break;
            }
        }

        $leadData = [
            $leadSource,
            $source
        ];

        \Bitrix\Main\Diag\Debug::dumpToFile($leadData, 'leadData', '/Bit_AgentLead.log');

        return $source;
    }

}
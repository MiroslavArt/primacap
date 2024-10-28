<?php

namespace Bit\Distribution\Lead;

use Bitrix\Main\Entity;
use Bitrix\Main\Type;
use \Bitrix\Main\Config\Option as Option;

class DistributionLeadTable extends Entity\DataManager
{
    public static function getTableName()
    {
        return 'bit_distribution_lead_count';
    }

    public static function getMap()
    {
        return array(
            'ID' => array(
                'data_type'=>'integer',
                'primary'=>true,
                'autocomplete'=>true,
                'title' => 'ID',
            ),
            'USER_ID' => array(
                'data_type'=>'integer',
                'title' => 'USER ID',
            ),
            'PROJECT' => array(
                'data_type'=>'string',
                'title' => 'PROJECT NAME',
            ),
            'SOURCE' => array(
                'data_type'=>'string',
                'title' => 'SOURCE',
            ),
            'COUNT' => array(
                'data_type'=>'integer',
                'title' => 'LEAD COUNT',
            ),
        );
    }

    public static function addCount($projectName, $source, $user_id)
    {
        $rs = self::getList([
            'filter' => [
                'PROJECT' => $projectName,
                'SOURCE' => $source,
                'USER_ID' => $user_id,
            ]
        ]);
        if($ar = $rs->fetch())
        {
            $count = $ar['COUNT'] + 1;

            $updateData = [
                'COUNT' => $count,
            ];

            self::Update($ar['ID'], $updateData);
        }
        else
        {
            $data = [
                'PROJECT' => $projectName,
                'SOURCE' => $source,
                'USER_ID' => $user_id,
                'COUNT' => 1,
            ];
            self::add($data);
        }
    }

}
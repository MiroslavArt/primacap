<?php

namespace Bit\Distribution\Lead;

use Bitrix\Main\Entity;
use Bitrix\Main\Type;
use \Bitrix\Main\Config\Option as Option;

class DistributionTable extends Entity\DataManager
{
    const TYPE_PROJECT = 'PROJECT';
    const TYPE_SOURCE = 'SOURCE';
    const TYPE_USER = 'USER';

    public static function getTableName()
    {
        return 'bit_distribution_lead';
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
            'TYPE' => array(
                'data_type'=>'string',
                'title' => 'TYPE DISTRIBUTION',
            ),
            'VALUE' => array(
                'data_type'=>'string',
                'title' => 'VALUE DISTRIBUTION',
            ),
            'USER_ID' => array(
                'data_type'=>'integer',
                'title' => 'USER ID',
            ),
            'COUNT' => array(
                'data_type'=>'integer',
                'title' => 'MAX LEAD COUNT',
            ),
        );
    }
}
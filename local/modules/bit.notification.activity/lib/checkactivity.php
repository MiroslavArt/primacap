<?php

namespace Bit\Notification\Activity;

use Bitrix\Main\Entity;
use Bitrix\Main\Type;
use \Bitrix\Main\Config\Option as Option;

class CheckActivityTable extends Entity\DataManager
{

    public static function getTableName()
    {
        return 'bit_note_act_checked';
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
            'ACT_ID' => array(
                'data_type'=>'integer',
                'title' => 'ACT_ID',
            ),
        );
    }
}
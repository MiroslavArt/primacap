<?php

namespace Bit\Notification\Activity;

use Bitrix\Main\Loader as Loader;
use Bitrix\Main\Config\Option as Option;

class Main
{
    const MODULE_ID = "bit.notification.activity";

    static $calendarEvent = [];

    public static function getValueByPattern($pattern, $entityData, $actData)
    {
        Loader::includeModule('crm');

        $value = $pattern;
        switch ($pattern)
        {
            case '#USER_FULLNAME#':
                $defValue = $actData['RESPONSIBLE_ID'];
                if($defValue > 0)
                {
                    $rs = \CUser::getByID($defValue);
                    if($ar = $rs->Fetch())
                    {
                        $name = $ar['NAME'] . ' ' . $ar['LAST_NAME'];
                        $value = $name;
                    }
                }
                break;
            case '#ACT_ENTITY_NAME#':
                $value = $entityData['TITLE'];
                break;
            case '#ACT_ENTITY_LINK#':
                $href = \CCrmOwnerType::GetShowUrl($actData['OWNER_TYPE_ID'], $entityData['ID']);
//                $href = $_SERVER['HTTP_ORIGIN'] . $href;
                $href = 'https://primocapitalcrm.ae' . $href;
                $value = $href;
                break;
            case '#ACT_DATE_START#' :
                $value = self::getEventStartDate($actData['CALENDAR_EVENT_ID']);
                break;
            case '#ACT_NAME#' :
                $value = $actData['SUBJECT'];
                break;
            default:
                break;
        }

        return $value;
    }

    public static function getEventStartDate($event_id)
    {
        global $USER;

        $result = '';

        if($event_id > 0)
        {
            $event = [];

            if(isset(self::$calendarEvent[$event_id]))
            {
                $event = self::$calendarEvent[$event_id];
            }
            else
            {
                Loader::includeModule('calendar');

                $event = \CCalendarEvent::GetById($event_id , false);
                self::$calendarEvent[$event_id] = $event;

            }

            if(!empty($event) && count($event) > 0)
            {
                $result = $event['DATE_FROM'];
            }

        }



        return $result;
    }

    public static function getNoteActPatterns()
    {
        $data = [
            [
                'PATTERN' => 'Responsible',
                'TITLE' => 'Responsible user name',
                'INSERT' => '#USER_FULLNAME#',
            ],
            [
                'PATTERN' => 'Entity title',
                'TITLE' => 'Title entity (lead, contact ...)',
                'INSERT' => '#ACT_ENTITY_NAME#',
            ],
            [
                'PATTERN' => 'Entity link',
                'TITLE' => 'Link to the entity (lead, contant ...)',
                'INSERT' => '#ACT_ENTITY_LINK#',
            ],
            [
                'PATTERN' => 'Event start time',
                'TITLE' => 'Event start time',
                'INSERT' => '#ACT_DATE_START#',
            ],
            [
                'PATTERN' => 'Event title',
                'TITLE' => 'Event title',
                'INSERT' => '#ACT_NAME#',
            ],
        ];
        return $data;
    }
}
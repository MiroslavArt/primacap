<?php

namespace Bit\Clockhandler;
use Bitrix\Main\Loader as Loader;
use Bitrix\Main\Config\Option as Option;

class Agent
{

    const MODULE_ID = "bit.clockhandler";

    public static function runAgent()
    {

        //$t1 = time();
        //AddMessage2Log('Start');

//        \Bitrix\Main\Diag\Debug::writeToFile(date('Y-m-d H:i:s'),"START","Bit_clockhandler_Agent.txt");

        if (Option::get(self::MODULE_ID, 'enabled', 'N') !== 'Y')
        {
            Option::set(self::MODULE_ID, 'runAgent', 'N');
            return __CLASS__ . '::' . __FUNCTION__ . '();';
        }

        if (Option::get(self::MODULE_ID, 'runAgent', 'N') == 'N')
            Option::set(self::MODULE_ID, 'runAgent', 'Y');
        else
            return __CLASS__.'::'.__FUNCTION__.'();';

        if (Loader::includeModule('timeman'))
        {
            if (!$GLOBALS['USER'] || !is_object($GLOBALS['USER'])) {
                $GLOBALS['USER'] = new \CUser();
            }
            $GLOBALS['USER']->Authorize(1);

            $connection = \Bitrix\Main\Application::getConnection();
            $lastDay = new \Bitrix\Main\Type\DateTime();
            $lastDay->add('- 1 day');

            $dateLastEnd = $lastDay->format('Y-m-d 23:59:59');

            $sql = "SELECT ID, USER_ID
            FROM b_timeman_entries
            WHERE DATE_START < '%s' 
              AND DATE_FINISH IS NULL ";
            $sql = sprintf($sql, $dateLastEnd);
            $rsUser = $connection->query($sql);
            $arUserId = [];
            while ($arUser = $rsUser->Fetch())
            {
                $USER_ID = $arUser['USER_ID'];
                $tmUser = new \CTimeManUser($USER_ID);


                $res = $tmUser->closeDay(
                    time() % 86400,
                    'Auto_exit_day'
                );

                if(!$res)
                {
                    $arUserId[__LINE__][] = $USER_ID; // $arUser['USER_ID'];
                }
            }


            $currDay = new \Bitrix\Main\Type\DateTime();
            $currDayEnd = new \Bitrix\Main\Type\DateTime();
            $timeClockout = Option::get(self::MODULE_ID, 'time_clockout', '18:00');
            $timeClockout = explode(':', $timeClockout);
            $formatTime = 'Y-m-d %s:%s:00';
            $formatTime = sprintf($formatTime, $timeClockout[0], $timeClockout[1]);
            $currDayEnd = $currDayEnd->format($formatTime);
            $currDayEnd = new \Bitrix\Main\Type\DateTime($currDayEnd , "Y-m-d H:i:s");
            if($currDay > $currDayEnd)
            {
                $dateLastEnd = $currDayEnd->format('Y-m-d H:i:s');
            }
            $sql = "SELECT ID, USER_ID
            FROM b_timeman_entries
            WHERE DATE_START < '%s' 
              AND DATE_FINISH IS NULL ";
            $sql = sprintf($sql, $dateLastEnd);
            $rsUser = $connection->query($sql);
            while ($arUser = $rsUser->Fetch())
            {
                $USER_ID = $arUser['USER_ID'];
                $tmUser = new \CTimeManUser($USER_ID);

//                $arUserId[] = $USER_ID; // $arUser['USER_ID'];

                $res = $tmUser->closeDay(
                    time() % 86400,
                    'Auto_exit'
                );

                if(!$res)
                {
                    $arUserId[__LINE__][] = $USER_ID; // $arUser['USER_ID'];
                }
            }


            $currDay = new \Bitrix\Main\Type\DateTime();
            $currDayEnd = new \Bitrix\Main\Type\DateTime();
            $timeClockout = Option::get(self::MODULE_ID, 'time_clockout_before', '08:00');
            $timeClockout = explode(':', $timeClockout);
            $formatTime = 'Y-m-d %s:%s:00';
            $formatTime = sprintf($formatTime, $timeClockout[0], $timeClockout[1]);
            $currDayEnd = $currDayEnd->format($formatTime);
            $currDayEnd = new \Bitrix\Main\Type\DateTime($currDayEnd , "Y-m-d H:i:s");
            if($currDay < $currDayEnd)
            {
                $dateLastEnd = $currDayEnd->format('Y-m-d H:i:s');
                $dateStart = $currDay->format('Y-m-d 00:00:01');

                $sql = "SELECT ID, USER_ID
                    FROM b_timeman_entries
                    WHERE DATE_START < '%s' 
                      AND DATE_START > '%s'
                      AND DATE_FINISH IS NULL ";
                $sql = sprintf($sql, $dateStart, $dateLastEnd);
                $rsUser = $connection->query($sql);
                while ($arUser = $rsUser->Fetch())
                {
                    $USER_ID = $arUser['USER_ID'];
                    $tmUser = new \CTimeManUser($USER_ID);

//                    $arUserId[] = $USER_ID; // $arUser['USER_ID'];

                    $res = $tmUser->closeDay(
                        time() % 86400,
                        'Auto_exit_before'
                    );

                    if(!$res)
                    {
                        $arUserId[__LINE__][] = $USER_ID; // $arUser['USER_ID'];
                    }
                }
            }
            if(count($arUserId) > 0)
            {
                $arUserId[] = [
                    date('Y-m-d H:i:s'),
                ];
                \Bitrix\Main\Diag\Debug::writeToFile($arUserId,"arUserListId","Bit_clockhandler_Agent.txt");
            }

            $GLOBALS['USER'] = null;
        }

        Option::set(self::MODULE_ID, 'runAgent', 'N');

//        \Bitrix\Main\Diag\Debug::writeToFile(date('Y-m-d H:i:s'),"END","Bit_clockhandler_Agent.txt");

        return __CLASS__.'::'.__FUNCTION__.'();';

    }
}
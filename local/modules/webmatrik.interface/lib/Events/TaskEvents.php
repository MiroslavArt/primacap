<?php

namespace Webmatrik\Interface\Events;

class TaskEvents
{
    public static function onTaskAdd($taskId)
    {
        \Bitrix\Main\Loader::includeModule('tasks');
        $task = new \Bitrix\Tasks\Item\Task($taskId);

        $arTask = $task->export(array("AUDITORS"));

        if(!in_array(27, $arTask['AUDITORS'])) {
            array_push($arTask['AUDITORS'], 27);

            $obTask = new \CTasks;

            $obTask->Update(
                $taskId,
                array(
                    "AUDITORS" => $arTask['AUDITORS']
                ),
                array('USER_ID' => 1)
            );
        }
    }

    public static function onSocNetGroupAdd($ID, &$arFields)
    {
        if (\CModule::IncludeModule("socialnetwork")) {
            $dbGroups = \CSocnetGroup::GetList(
                array("ID" => "ASC"),
                ['ID'=>$ID],
                false,
                false,
                array("*")
            );

            while ($arGroup = $dbGroups->GetNext())
            {
                $groupid = $arGroup['ID'];
                $ownerid = $arGroup['OWNER_ID'];
                if($ownerID!=21) {
                    $dbUserGroups = \CSocNetUserToGroup::getList(
                        array("GROUP_NAME" => "ASC"),
                        ["GROUP_ID"=>$arGroup['ID'], '=USER_ID'=>21],
                        false,
                        false,
                        array("*")
                    )->fetch();

                    if(!$dbUserGroups) {
                        $res = \CSocNetUserToGroup::Add(
                            array(
                                "USER_ID" => 27,
                                "GROUP_ID" => $arGroup['ID'],
                                "ROLE" => SONET_ROLES_MODERATOR,
                                "=DATE_CREATE" => $GLOBALS["DB"]->CurrentTimeFunction(),
                                "=DATE_UPDATE" => $GLOBALS["DB"]->CurrentTimeFunction(),
                                "INITIATED_BY_TYPE" => SONET_INITIATED_BY_USER,
                                "INITIATED_BY_USER_ID" => $ownerid,
                                "MESSAGE" => false,
                            )
                        );
                    }
                }
            }
        }
    }
}
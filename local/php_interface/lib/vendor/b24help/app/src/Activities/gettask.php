<?php

namespace B24help\App\Activities;

use B24help\App\B24Activity;
use Bitrix24\Exceptions\Bitrix24ApiException;

class GetTask extends B24Activity
{
    protected function getSelfFile()
    {
        return __FILE__;
    }

    private function getOutputProps(&$arTask)
    {
        return [
            'OutputAssigned' => "user_{$arTask['RESPONSIBLE_ID']}",
            'OutputCreated' => $arTask['CREATED_DATE'],
            'OutputDeadline' => $arTask['DEADLINE'],
            'OutputClosedDate' => $arTask['CLOSED_DATE'],
            'OutputClosedBy' => intval($arTask['CLOSED_BY']) > 0 ? "user_{$arTask['CLOSED_BY']}" : '',
            'OutputClosed' => 5 == $arTask['REAL_STATUS'] ? 'Y' : 'N',
            'OutputIsDeadline' => $arTask['DEADLINE'] <= date(DATE_ISO8601) ? 'Y' : 'N',
        ];
    }

    const WAIT = [
        'CLOSED' => 'завершения',
        'DEADLINE' => 'крайнего срока',
    ];

    /*
        TODO:
        "InputTaskWait": {
            "Name": {
                "ru": "Ожидание",
                "en": "Waiting"
            },
            "Description": {
                "ru": "Что ждем в статусе задачи",
                "en": "What to expect in the task status"
            },
            "Type": "select",
            "Required": "Y",
            "Multiple": "N",
            "Default": "NO",
            "Options": {
                "NO": "-",
                "CLOSED": "Close",
                "DEADLINE": "Overdue"
            }
        }
    */

    const MESS = [
        'ru' => [
            'RESULT' => 'Поля задачи №{{taskID}} загружены',
            'NOT_FOUND' => 'Задача №{{taskID}} не найдена',
        ],
        'en' => [
            'RESULT' => 'Tasks №{{taskID}} fields loaded',
            'NOT_FOUND' => 'Task N{{taskID}} not found',
        ],
    ];

    protected function execute()
    {
        if (isset($this->arRequest['workflow_id'])) { // Activity call
            $this->lang = 'en' == $this->lang ? 'en' : 'ru';

            $taskID = $this->arRequest['properties']['InputTaskID'];
            try {
                $result = $this->obB24App->call('task.item.getdata', [$taskID]);
                if (isset($result['result']) && count($result['result']) > 0) {
                    if (in_array($this->arRequest['properties']['InputTaskWait'], ['CLOSED', 'DEADLINE'])) {
                        /* TODO: Ожидание завершения задачи сделать на веб-хуках (вопрос, где хранить event_token?)
                        $this->sendBizprocLog(
                            'Ожидание '.static::WAIT[$this->arRequest['properties']['InputTaskWait']]." задачи №{$taskID} не реализовано"
                        ); */
                    }
                    $this->sendBizprocResult(
                        \str_replace('{{taskID}}', $taskID, static::MESS[$this->lang]['RESULT']),
                        $this->getOutputProps($result['result'])
                    );
                } else {
                    $this->sendBizprocResult(\str_replace('{{taskID}}', $taskID, static::MESS[$this->lang]['NOT_FOUND']), []);
                }
            } catch (Bitrix24ApiException $e) {
                $this->sendBizprocResult($e->getMessage(), []);
            }
        }
    }
}

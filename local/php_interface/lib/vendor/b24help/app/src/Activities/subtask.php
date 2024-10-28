<?php

namespace B24help\App\Activities;

use B24help\App\B24Activity;

class SubTask extends B24Activity
{
    const PARENT_ID = 'PARENT_ID'; // Поле "Родительская задача"

    protected function getSelfFile()
    {
        return __FILE__;
    }

    const MESS = [
        'ru' => [
            'RESULT' => 'Задача №{{subTaskID}} привязана к родительской №{{taskID}}',
            'RESULT_ALL' => 'Все задачи привязаны к родительской №{{taskID}}',
            'NOT_FOUND' => 'Задача №{{taskID}} не найдена или доступ запрещен',
        ],
        'en' => [
            'RESULT' => 'Task N{{subTaskID}} added to parent N{{taskID}}',
            'RESULT_ALL' => 'All subtasks added to parent task N{{taskID}}',
            'NOT_FOUND' => 'Task N{{taskID}} not found or access denied',
        ],
    ];

    protected function execute()
    {
        if (isset($this->arRequest['workflow_id'])) {
            $this->lang = 'en' == $this->lang ? 'en' : 'ru';

            $taskID = $this->arRequest['properties']['InputTaskID'];
            $subTasks = $this->arRequest['properties']['InputSubTasks'];
            $result = $this->obB24App->call('task.item.getdata', [$taskID]);
            if (isset($result['result']) && count($result['result']) > 0) {
                foreach ($subTasks as $subTaskID) {
                    $result = $this->obB24App->call(
                        'task.item.update',
                        [$subTaskID, [self::PARENT_ID => $taskID],
                    ]);
                    $this->sendBizprocLog(
                        \str_replace('{{subTaskID}}', $subTaskID,
                        \str_replace('{{taskID}}', $taskID, static::MESS[$this->lang]['RESULT']))
                    );
                }
                $this->sendBizprocResult(\str_replace('{{taskID}}', $taskID, static::MESS[$this->lang]['RESULT_ALL']), []);
            } else {
                $this->sendBizprocResult(
                    \str_replace('{{taskID}}', $taskID, static::MESS[$this->lang]['NOT_FOUND']),
                    []);
            }
        }
    }
}

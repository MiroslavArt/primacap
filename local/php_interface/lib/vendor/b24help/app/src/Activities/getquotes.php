<?php

namespace B24help\App\Activities;

use B24help\App\B24Activity;

/**
 * Активити "Получить предложения"
 * Возвращает массив идентификаторов предложений, отобранных согласно входным параметрам отбора.
 */
class GetQuotes extends B24Activity
{
    const InputDealID = 'InputDealID';
    const InputStatusID = 'InputStatusID';
    const InputDateInsert = 'InputDateInsert';
    const InputPrice = 'InputPrice';
    const InputDatePayBefore = 'InputDatePayBefore';
    const OutputList = 'OutputList';

    const MESS = [
        'ru' => [
            'RESULT' => 'Найдено предложений',
            'EMPTY' => 'Предложения не найдены',
        ],
        'en' => [
            'RESULT' => 'Found quotes',
            'EMPTY' => 'No quotes found',
        ],
    ];

    protected function getSelfFile()
    {
        return __FILE__;
    }

    protected function prepareActivity(&$arActivity)
    {
        $result = $this->obB24App->call('crm.status.list', [
            'order' => ['SORT' => 'ASC'],
            'filter' => ['ENTITY_ID' => 'QUOTE_STATUS'],
        ]);

        $arOptions = [];
        foreach ($result['result'] as $arStatus) {
            $arOptions[$arStatus['STATUS_ID']] = $arStatus['NAME'];
        }

        $this->log->debug('QUOTE_STATUS', $arOptions);

        $arActivity['PROPERTIES'][static::InputStatusID]['Options'] = $arOptions;
    }

    protected function execute()
    {
        if (isset($this->arRequest['workflow_id'])) {
            $arFilter = [
                '=DEAL_ID' => $this->arRequest['properties'][static::InputDealID],
                'STATUS_ID' => $this->arRequest['properties'][static::InputStatusID],
            ];

            $this->lang = 'en' == $this->lang ? 'en' : 'ru';

            if (!empty($this->arRequest['properties'][static::InputPrice])) {
                $arFilter['>=OPPORTUNITY'] = $this->arRequest['properties'][static::InputPrice];
            }

            if (!empty($this->arRequest['properties'][static::InputDateInsert])) {
                $arFilter['<=BEGINDATA'] = $this->arRequest['properties'][static::InputDateInsert];
            }

            if (!empty($this->arRequest['properties'][static::InputDatePayBefore])) {
                $arFilter['<=CLOSEDATE'] = $this->arRequest['properties'][static::InputDatePayBefore];
            }

            $result = $this->obB24App->call('crm.quote.list', [
                'order' => ['ID' => 'ASC'],
                'filter' => $arFilter,
                'select' => ['ID'],
            ]);
            if (!empty($result['result'])) {
                $arIDs = [];
                foreach ($result['result'] as $arItem) {
                    $arIDs[] = $arItem['ID'];
                }
                $count = count($arIDs);
                $this->sendBizprocResult(static::MESS[$this->lang]['RESULT'].": {$count}", [static::OutputList => $arIDs]);
            } else {
                $this->sendBizprocResult(static::MESS[$this->lang]['EMPTY'], []);
            }
        }
    }
}

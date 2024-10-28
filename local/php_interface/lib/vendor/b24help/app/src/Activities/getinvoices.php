<?php

namespace B24help\App\Activities;

use B24help\App\B24Activity;

/**
 * Активити "Получить счета"
 * Возвращает массив идентификаторов счетов, отобранных согласно входным параметрам отбора.
 */
class GetInvoices extends B24Activity
{
    const InputDealID = 'InputDealID';
    const InputStatusID = 'InputStatusID';
    const InputDateInsert = 'InputDateInsert';
    const InputPrice = 'InputPrice';
    const InputDatePayBefore = 'InputDatePayBefore';
    const OutputInvoiceList = 'OutputInvoiceList';

    protected function getSelfFile()
    {
        return __FILE__;
    }

    const MESS = [
        'ru' => [
            'RESULT' => 'Найдено счетов',
            'EMPTY' => 'Счета не найдены',
        ],
        'en' => [
            'RESULT' => 'Found invoices',
            'EMPTY' => 'No invoices found',
        ],
    ];

    protected function prepareActivity(&$arActivity)
    {
        $result = $this->obB24App->call('crm.invoice.status.list', [
            'order' => ['SORT' => 'ASC'],
            'filter' => [],
            'select' => ['ID', 'STATUS_ID', 'NAME'],
        ]);

        $arOptions = [];
        foreach ($result['result'] as $arStatus) {
            $arOptions[$arStatus['STATUS_ID']] = $arStatus['NAME'];
        }

        $this->log->debug('INVOICE_STATUS', $arOptions);

        $arActivity['PROPERTIES'][static::InputStatusID]['Options'] = $arOptions;
    }

    protected function execute()
    {
        if (isset($this->arRequest['workflow_id'])) {
            $this->lang = 'en' == $this->lang ? 'en' : 'ru';

            $arFilter = [
                '=UF_DEAL_ID' => $this->arRequest['properties'][static::InputDealID],
                'STATUS_ID' => $this->arRequest['properties'][static::InputStatusID],
            ];

            if (!empty($this->arRequest['properties'][static::InputPrice])) {
                $arFilter['>=PRICE'] = $this->arRequest['properties'][static::InputPrice];
            }

            if (!empty($this->arRequest['properties'][static::InputDateInsert])) {
                $arFilter['<=DATE_INSERT'] = $this->arRequest['properties'][static::InputDateInsert];
            }

            if (!empty($this->arRequest['properties'][static::InputDatePayBefore])) {
                $arFilter['<=DATE_PAY_BEFORE'] = $this->arRequest['properties'][static::InputDatePayBefore];
            }

            $result = $this->obB24App->call('crm.invoice.list', [
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
                $this->sendBizprocResult(static::MESS[$this->lang]['RESULT'].": {$count}", [static::OutputInvoiceList => $arIDs]);
            } else {
                $this->sendBizprocResult(static::MESS[$this->lang]['EMPTY'], []);
            }
        }
    }
}

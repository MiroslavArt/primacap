<?php

namespace Webmatrik\Integrations;

class BayutCalls extends AbstractIntegration
{
    protected $title;
    protected $phone;
    protected $comment;
    protected $url;
    protected $method;

    public function __construct()
    {
        parent::__construct();
        $this->url = static::getModuleOption('main_Bayut_API_URL', '');
        $this->source = static::getModuleOption('main_Bayut_Source', '');
        $this->method = 'call_logs';
    }

    public function fetchPhoneLeads()
    {
        try {
            $data = $this->sendCurlRequest($this->method, $this->url);
        } catch (\Throwable $e) {
            \Bitrix\Main\Diag\Debug::writeToFile(
                $e->getMessage(),
                'BayutCalls API Error ' . date('Y-m-d H:i:s'),
                'bayutdubizzlepull.log'
            );
            return;
        }

        if (empty($data) || !is_array($data)) {
            \Bitrix\Main\Diag\Debug::writeToFile(
                $data,
                'BayutCalls Empty/Invalid Response ' . date('Y-m-d H:i:s'),
                'bayutdubizzlepull.log'
            );
            return;
        }

        \Bitrix\Main\Diag\Debug::writeToFile(
            $data,
            'BayutCalls Response ' . date('Y-m-d H:i:s'),
            'bayutdubizzlepull.log'
        );

        foreach ($data as $value) {
            try {
                $this->phone   = $value['caller_number'] ?? '';
                $this->comment = $value['call_recordingurl'] ?? '';
                $this->title   = 'BayutCall_' . ($this->phone ?: 'Unknown');

                $this->createDeal('phone');
            } catch (\Throwable $e) {
                \Bitrix\Main\Diag\Debug::writeToFile(
                    [
                        'error'     => $e->getMessage(),
                        'call_data' => $value
                    ],
                    'BayutCalls Deal Create Error ' . date('Y-m-d H:i:s'),
                    'bayutdubizzlepull.log'
                );
                continue;
            }
        }
    }
}

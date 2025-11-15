<?php

namespace Webmatrik\Integrations;

class BayutEmail extends AbstractIntegration
{
    protected $title;
    protected $name;
    protected $email;
    protected $phone;
    protected $proprefufval;
    protected $comment;
    protected $url;
    protected $method;

    public function __construct()
    {
        parent::__construct();
        $this->url = static::getModuleOption('main_Bayut_API_URL', '');
        $this->source = static::getModuleOption('main_Bayut_Source', '');
        $this->method = 'leads';
    }

    public function fetchEmailLeads()
    {
        try {
            $data = $this->sendCurlRequest($this->method, $this->url);
        } catch (\Throwable $e) {
            \Bitrix\Main\Diag\Debug::writeToFile(
                $e->getMessage(),
                'BayutEmail API Error ' . date('Y-m-d H:i:s'),
                'bayutdubizzlepull.log'
            );
            return;
        }

        if (empty($data) || !is_array($data)) {
            \Bitrix\Main\Diag\Debug::writeToFile(
                $data,
                'BayutEmail Empty/Invalid Response ' . date('Y-m-d H:i:s'),
                'bayutdubizzlepull.log'
            );
            return;
        }

        \Bitrix\Main\Diag\Debug::writeToFile(
            $data,
            'BayutEmail Response ' . date('Y-m-d H:i:s'),
            'bayutdubizzlepull.log'
        );

        foreach ($data as $value) {
            try {
                $this->proprefufval = $value['property_reference'] ?? '';
                $this->email        = $value['client_email'] ?? '';
                $this->name         = $value['client_name'] ?? '';
                $this->phone        = $value['client_phone'] ?? '';
                $this->comment      = $value['message'] ?? '';
                $this->title        = 'BayutEmail_' . $this->name . '_' . $this->email;

                $this->createDeal('email');
            } catch (\Throwable $e) {
                \Bitrix\Main\Diag\Debug::writeToFile(
                    [
                        'error' => $e->getMessage(),
                        'lead_data' => $value
                    ],
                    'BayutEmail Deal Create Error ' . date('Y-m-d H:i:s'),
                    'bayutdubizzlepull.log'
                );
                continue;
            }
        }
    }
}

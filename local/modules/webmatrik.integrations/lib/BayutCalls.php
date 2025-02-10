<?php

namespace Webmatrik\Integrations;

class BayutCalls extends AbstractIntegration
{
    protected $title;
    // protected $name;
    // protected $email;
    protected $phone;
    // protected $proprefufval;
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
        $data = $this->sendCurlRequest($this->method, $this->url);
        if($data) {
            \Bitrix\Main\Diag\Debug::writeToFile($data, "bayutcall ".date('Y-m-d H:i:s'), "bayutdubizzlepull.log");
        }
        //print_r($data);
        foreach ($data as $value) {
            // $this->proprefufval = $value['property_reference'];
            // $this->email = $value['client_email'];
            // $this->name = $value['client_name'];
            $this->phone = $value['caller_number'];
            $this->comment = $value['call_recordingurl]'];
            $this->title = 'BayutCall_'.$this->name.'_'.$this->email;
            $this->createDeal('phone');
        }
    }
}
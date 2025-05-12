<?php

namespace Webmatrik\Integrations;

class DubizzleEmail extends AbstractIntegration
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
        $this->url = static::getModuleOption('main_Dubizzle_API_URL', '');
        $this->source = static::getModuleOption('main_Dubizzle_Source', '');
        $this->method = 'leads';
    }

    public function fetchEmailLeads()
    {
        $data = $this->sendCurlRequest($this->method, $this->url);
        if($data) {
            \Bitrix\Main\Diag\Debug::writeToFile($data, "dubizzleemail ".date('Y-m-d H:i:s'), "bayutdubizzlepull.log");
        }
        //print_r($data);
        foreach ($data as $value) {
            $this->proprefufval = $value['property_reference'];
            $this->email = $value['client_email'];
            $this->name = $value['client_name'];
            $this->phone = $value['client_phone'];
            $this->comment = $value['message'];
            $this->title = 'DubizzleEmail_'.$this->name.'_'.$this->email;
            $this->createDeal('email');
        }
    }
}
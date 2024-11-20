<?php

namespace Webmatrik\Integrations;

class Dubizzle extends AbstractIntegration
{
    protected $name;
    protected $phone;
    protected $title;
    protected $source;
    protected $proplinkufval;
    protected $proprefufval;
    protected $contactlinkval;

    public function __construct($leadData)
    {
        parent::__construct();
        $this->name = static::translateToEnglish($leadData['enquirer']['name']);
        $this->phone = $leadData['enquirer']['phone_number'];
        $this->title = 'Dubizzle_'.$this->name.'_'.$this->phone;
        $this->source = static::getModuleOption('main_Dubizzle_Source', '');
        $this->proplinkufval = $leadData['listing']['url'];
        $this->proprefufval = $leadData['listing']['reference'];
        $this->contactlinkval = $leadData['enquirer']['contact_link'];
    }
}

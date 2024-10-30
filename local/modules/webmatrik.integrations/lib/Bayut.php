<?php

namespace Webmatrik\Integrations;

class Bayut extends AbstractIntegration
{
    protected $name;
    protected $phone;
    protected $title;
    protected $assigned;
    protected $source;
    protected $proplinkuf;
    protected $proprefuf;
    protected $proplinkufval;
    protected $proprefufval;


    public function __construct($leadData)
    {
        parent::__construct();
        $this->name = static::translateToEnglish($leadData['enquirer']['name']);
        $this->phone = $leadData['enquirer']['phone_number'];
        $this->title = 'Bayut_'.$this->name.'_'.$this->phone;
        $this->assigned = static::getModuleOption('main_Lead_AssignedTo', '');
        $this->source = static::getModuleOption('main_Bayut_Source', '');
        $this->proplinkuf = static::getModuleOption('main_Bayut_Property_Link_UF', '');
        $this->proprefuf = static::getModuleOption('main_Bayut_Property_Ref_UF', '');
        $this->proplinkufval = $leadData['listing']['url'];
        $this->proprefufval = $leadData['listing']['reference'];
    }
}
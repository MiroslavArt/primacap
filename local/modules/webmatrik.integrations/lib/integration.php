<?php
namespace Webmatrik\Integrations;

interface Integration
{

    //public function processWebhook();

    public static function dump($data) ;

    public static function getModuleOption($optionName, $default = null);

    public static function setModuleOption($optionName, $optionValue);
}
<?php

namespace Webmatrik\Integrations;

use Bitrix\Crm\Service\Container;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Loader;
use Bitrix\Main\Web\HttpClient;
use Bitrix\Main\Config\Option;
use Bitrix\Crm\Service;
use Bitrix\Main\Application;

class FeedBayut extends Feed
{
    protected static $root;
    protected static $mask;

    public function __construct()
    {
        $server = Application::getInstance()->getContext()::getCurrent()->getServer();
        static::$root = $server->getDocumentRoot().'/pub/feed';
        static::$mask = [
            'TITLE' => 'Property_Title',
            'UF_CRM_5_1752571265' => 'Property_Ref_No',
            'UF_CRM_5_1752755510' => 'Permit_Number',
            'UF_CRM_5_1752755567' => 'Property_purpose',
            'UF_CRM_5_1752571572' => 'Property_Type',
            'UF_CRM_5_1752571276' => 'Property_Size',
            'UF_CRM_5_1752755685' => 'Property_Size_Unit',
            'UF_CRM_5_1752569108' => 'plotArea',
            'UF_CRM_5_1752508051' => 'Bedrooms',
            'UF_CRM_5_1752507949' => 'Bathrooms',
            'UF_CRM_5_1754495503' => 'Features',
            'UF_CRM_5_1754553774' => 'Off_plan',
            'UF_CRM_5_1752569141' => 'Portals',
            'UF_CRM_5_1752508408' => 'Property_Description',
            'UF_CRM_5_1752571489' => 'Property_Title_AR',
            'UF_CRM_5_1752508464' => 'Property_Description_AR',
            'UF_CRM_5_1752569908' => 'Rent_Frequency',
            'UF_CRM_5_1752570167' => 'Price',
            'UF_CRM_5_1752508563' => 'Furnished',
            'UF_CRM_5_1752755788' => 'offplanDetails_saleType',
            'UF_CRM_5_1752755825' => 'offplanDetails_dldWaiver',
            'UF_CRM_5_1752755888' => 'offplanDetails_originalPrice',
            'UF_CRM_5_1752755913' => 'offplanDetails_amountPaid'
        ];
        parent::__construct();
    }

    public function makeNewFeed() {
        self::cleanDir(static::$root);

        $filter = [
            'STAGE_ID' => 'DT1036_8:SUCCESS',
            '@UF_CRM_5_1752569141' => [1297, 1298]
        ];

        $data = static::retrieveDate($filter, static::$mask, 'bayut');


        /*$data = [
            ['ref' => 111],
            ['ref' => 222]
        ];
        self::packtoXML($data);*/
    }

    protected static function packtoXML($data) {
        $inputUTF8 = <<<INPUT
            <?xml version="1.0" encoding="UTF-8"?>
            <Properties>
            </Properties>    
            INPUT;
        $root = simplexml_load_string($inputUTF8);
        foreach ($data as $key => $item) {
            $property = $root->addChild('Property');
            $property->Property_Ref_No = '<![CDATA['.$item['ref'].']]';

        }
        $root->asXML(static::$root."/bayutdubizzle.xml");
    }

    protected static function cleanDir($dir) {
        $files = glob($dir."/*");
        $c = count($files);
        if (count($files) > 0) {
            foreach ($files as $file) {
                if (file_exists($file)) {
                    unlink($file);
                }
            }
        }
    }

}
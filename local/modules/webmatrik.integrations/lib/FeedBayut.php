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
    protected static $furnmap;
    protected static $extraAmenities;

    public function __construct()
    {
        $server = Application::getInstance()->getContext()::getCurrent()->getServer();
        static::$root = $server->getDocumentRoot().'/pub/feed';
        static::$mask = [
            'TITLE' => 'Property_Title',
            'UF_CRM_5_1752571265' => 'Property_Ref_No',
            'UF_CRM_5_1752508269' => 'Permit_Number',
            'UF_CRM_5_1752755567' => 'Property_purpose',
            'UF_CRM_5_1754561389' => 'Property_Type',
            'UF_CRM_5_1752571276' => 'Property_Size',
            'UF_CRM_5_1752755685' => 'Property_Size_Unit',
            'UF_CRM_5_1752569108' => 'plotArea',
            'UF_CRM_5_1752508051' => 'Bedrooms',
            'UF_CRM_5_1752507949' => 'Bathrooms',
            'UF_CRM_5_1754495503' => 'Features',
            'UF_CRM_5_1752571194' => 'Off_plan',
            'UF_CRM_5_1752569141' => 'Portals',
            'UF_CRM_5_1752508408' => 'Property_Description',
            'UF_CRM_5_1752571489' => 'Property_Title_AR',
            'UF_CRM_5_1752508464' => 'Property_Description_AR',
            'UF_CRM_5_1752569908' => 'Rent_Frequency',
            'UF_CRM_5_1754555234' => 'Price',
            'UF_CRM_5_1752508563' => 'Furnished',
            'UF_CRM_5_1752755788' => 'offplanDetails_saleType',
            'UF_CRM_5_1752755825' => 'offplanDetails_dldWaiver',
            'UF_CRM_5_1754555417' => 'offplanDetails_originalPrice',
            'UF_CRM_5_1754555396' => 'offplanDetails_amountPaid',
            'UF_CRM_5_1752569021' => 'Parking Spaces',
            'UF_CRM_5_1755236272' => 'View',
            'UF_CRM_5_1755238439' => 'Pet policy',
            'UF_CRM_5_1752508720' => 'Floor',
            'UF_CRM_5_1755238866' => 'Other Main Features',
            'UF_CRM_5_1755238928' => 'Other Rooms',
            'UF_CRM_5_1755238978' => 'Other Facilities',
            'UF_CRM_5_1755239127' => 'Land Area',
            'UF_CRM_5_1755239186' => 'Nearby Schools',
            'UF_CRM_5_1755239275' => 'Nearby Hospitals',
            'UF_CRM_5_1755239336' => 'Nearby Shopping Malls',
            'UF_CRM_5_1755239384' => 'Distance From Airport (kms)',
            'UF_CRM_5_1755239445' => 'Nearby Public Transport',
            'UF_CRM_5_1755239531' => 'Other Nearby Places',
            'UF_CRM_5_1755239591' => 'Total Floors',
            'UF_CRM_5_1755239671' => 'Elevators in Building',
            'UF_CRM_5_1755239741' => 'Completion Year',
            'UF_CRM_5_1755239886' => 'Flooring'
        ];

        static::$extraAmenities = [
            'Parking Spaces',
            'View',
            'Pet policy',
            'Floor',
            'Other Main Features',
            'Other Rooms',
            'Other Facilities',
            'Land Area',
            'Nearby Schools',
            'Nearby Hospitals',
            'Nearby Hospitals',
            'Nearby Shopping Malls',
            'Distance From Airport (kms)',
            'Nearby Public Transport',
            'Other Nearby Places',
            'Total Floors',
            'Elevators in Building',
            'Completion Year',
            'Flooring'
        ];

        static::$furnmap = [
            'furnished' => 'Yes',
            'semi-furnished' => 'Partly',
            'unfurnished' => 'No'
        ];

        parent::__construct();
    }

    public function makeNewFeed() {
        self::cleanDir(static::$root);

        $filter = [
            'STAGE_ID' => 'DT1036_8:SUCCESS',
            '@UF_CRM_5_1752569141' => [1298, 1299]
        ];

        $data = static::retrieveDate($filter,  'bayut');

        $data = self::prepareData($data);
        print_r($data);

        if($data) {
            self::packtoXML($data, 'No');
            self::packtoXML($data, 'Yes');
        }
    }

    protected static function prepareData($data) {
        foreach ($data as $key => &$item) {
            if(is_array($item['Features'])) {
                foreach (static::$extraAmenities as $kitem) {
                    if($item[$kitem]) {
                        $item['Features'][] = $kitem.':'.$item[$kitem];
                    }
                }
            }
            switch ($item['Off_plan']) {
                case 'off_plan':
                case 'off_plan_primary':
                    $item['Off_plan'] = 'Yes';
                    break;
                case 'completed':
                case 'completed_primary':
                    $item['Off_plan'] = 'No';
                    break;
            }
        }
        return $data;
    }

    protected static function packtoXML($data, string $offPlan = 'No') {
        $fileName = 'bayutdubizzlesec.xml';
        if($offPlan=='Yes') {
            $fileName = 'bayutdubizzleoffp.xml';
        }
        $inputUTF8 = <<<INPUT
            <?xml version="1.0" encoding="UTF-8"?>
            <Properties>
            </Properties>    
            INPUT;
        $root = simplexml_load_string($inputUTF8);
        foreach ($data as $key => $item) {
            if($item['Off_plan'] == $offPlan) {
                $property = $root->addChild('Property');
                $property->Property_Ref_No = '<![CDATA['.$item['Property_Ref_No'].']]>';
                $property->Property_purpose = '<![CDATA['.$item['Property_purpose'].']]>';
                $property->Property_Type = '<![CDATA['.$item['Property_Type'].']]>';
                $property->Property_Status = '<![CDATA['.$item['Property_Status'].']]>';
                $property->City = '<![CDATA['.$item['location']['City'].']]>';
                $property->Locality = '<![CDATA['.$item['location']['Locality'].']]>';
                $property->Sub_Locality = '<![CDATA['.$item['location']['Sub_Locality'].']]>';
                $property->Tower_Name = '<![CDATA['.$item['location']['Tower_Name'].']]>';
                $property->Property_Title = '<![CDATA['.$item['Property_Title'].']]>';
                $property->Property_Title_AR = '<![CDATA['.$item['Property_Title_AR'].']]>';
                $property->Property_Description = '<![CDATA['.$item['Property_Description'].']]>';
                $property->Property_Description_AR = '<![CDATA['.$item['Property_Description_AR'].']]>';
                $property->Property_Size = '<![CDATA['.$item['Property_Size'].']]>';
                $property->Property_Size_Unit = $item['Property_Size_Unit'] ?
                    '<![CDATA['.$item['Property_Size_Unit'].']]>' : '<![CDATA[SQFT]]>';
                $property->Bedrooms = '<![CDATA['.$item['Bedrooms'].']]>';
                $property->Bathroom = '<![CDATA['.$item['Bathrooms'].']]>';
                $property->Price = '<![CDATA['.$item['Price'].']]>';
                $property->Listing_Agent = '<![CDATA['.$item['assignedTo']['Listing_Agent'].']]>';
                $property->Listing_Agent_Phone = '<![CDATA['.$item['assignedTo']['Listing_Agent_Phone'].']]>';
                $property->Listing_Agent_Email = '<![CDATA['.$item['assignedTo']['Listing_Agent_Email'].']]>';
                $features = $property->addChild('Features');
                foreach($item['Features'] as $key => $val) {
                    $features->Feature[$key] = '<![CDATA['.$val.']]>';
                }
                $images = $property->addChild('Images');
                foreach($item['Photos'] as $key => $val) {
                    $images->Image[$key] = '<![CDATA['.$val.']]>';
                }
                $videos = $property->addChild('Videos');
                foreach($item['Videos'] as $key => $val) {
                    $videos->Video[$key] = '<![CDATA['.$val.']]>';
                }
                $property->Last_Updated = '<![CDATA['.$item['Last_Updated'].']]>';
                $property->Permit_Number = '<![CDATA['.$item['Permit_Number'].']]>';
                if($item['Property_purpose'] == 'Rent') {
                    $property->Rent_Frequency = '<![CDATA['.$item['Rent_Frequency'].']]>';
                }
                $property->Off_plan = '<![CDATA['.$item['Off_plan'].']]>';
                if($item['Off_plan'] == 'Yes') {
                    $property->offplanDetails_saleType = '<![CDATA['.$item['offplanDetails_saleType'].']]>';
                    $property->offplanDetails_dldWaiver = '<![CDATA['.$item['offplanDetails_dldWaiver'].']]>';
                    $property->offplanDetails_originalPrice = '<![CDATA['.$item['offplanDetails_originalPrice'].']]>';
                    $property->offplanDetails_amountPaid = '<![CDATA['.$item['offplanDetails_amountPaid'].']]>';
                }
                $property->Furnished = '<![CDATA['.static::$furnmap[$item['Furnished']].']]>';
                $portals = $property->addChild('Portals');
                foreach($item['Portals'] as $key => $val) {
                    $portals->Portal[$key] = '<![CDATA['.$val.']]>';
                }
            }
        }
        $root->asXML(static::$root."/".$fileName);
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
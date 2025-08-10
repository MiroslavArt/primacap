<?php

namespace Webmatrik\Integrations;

use Bitrix\Crm\Service\Container;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Loader;
use Bitrix\Main\Web\HttpClient;
use Bitrix\Main\Config\Option;
use Bitrix\Crm\Service;
use Bitrix\Main\Application;

abstract class Feed
{
    protected static $entityTypeId;
    protected static $locentityTypeId;
    protected static $photoentityTypeId;
    protected static $videoentityTypeId;

    public function __construct()
    {
        Loader::includeModule('crm');
        static::$entityTypeId = 1036;
        static::$locentityTypeId = 1054;
        static::$photoentityTypeId = 1040;
        static::$videoentityTypeId = 1044;
    }

    protected function retrieveDate(array $filter, string $mode ='Pf') {
        $enums = static::getEnumVal();
        print_r($enums);
        $container = Container::getInstance();

        $factory = $container->getFactory(static::$entityTypeId);
        $rellocfactory = $container->getFactory(static::$locentityTypeId);
        $relphotofactory = $container->getFactory(static::$photoentityTypeId);
        $relvideofactory = $container->getFactory(static::$videoentityTypeId);

        if (!$factory) {
            throw new Exception('Factory not found');
        }

        $params = [
            'select' => ['ID', 'TITLE', 'UPDATED_TIME',
                'PARENT_ID_1054', 'CREATED_BY',
                'ASSIGNED_BY_ID', 'UF_*'], // Все поля, включая пользовательские
            'filter' => $filter,
            'order' => ['ID' => 'ASC']
        ];

        // Получаем элементы
        $items = $factory->getItems($params);
        $result = [];
        $locations = [];
        $users = [];
        foreach ($items as $item) {
            $res = [];
            $data = $item->getData();
            print_r($data);
            $lisid = $data['ID'];
            $locations[] = $data['PARENT_ID_1054'];
            if($mode = 'Pf') {
                $users[] = $data['CREATED_BY'];
            }
            $users[] = $data['ASSIGNED_BY_ID'];
            $res['Location'] = $data['PARENT_ID_1054'];
            $res['Created'] = $data['CREATED_BY'];
            $res['Assigned'] = $data['ASSIGNED_BY_ID'];
            $res['Last_Updated'] = $data['UPDATED_TIME']->format("Y-m-d H:i:s");
            // sale amount
            // get main info
            foreach (static::$mask as $key => $item) {
                if(array_key_exists($key, $data)) {
                    if(is_array($data[$key])) {
                        if(array_key_exists($key, $enums)) {
                            $arr1 = $enums[$key];
                            $arr2 = $data[$key];
                            $arr2 = array_map(function($key) use ($arr1) {
                                return $arr1[$key] ?? $key; // Если ключа нет в $arr1, оставляем исходное значение
                            }, $arr2);

                            $val = $arr2;
                        }
                    } else {
                        if($data[$key]) {
                            if(array_key_exists($key, $enums)) {
                                $val = $enums[$key][$data[$key]];
                            } else {
                                $val = $data[$key];
                            }
                        } else {
                            $val = '';
                        }
                    }
                    $itemarr = explode( ',', $item);
                    if(count($itemarr)==1) {
                        $res[$itemarr[0]] = $val;
                    } else {
                        $itemtemp = $itemarr;
                        $itemtemp0 = array_shift($itemtemp);
                        $res[$itemtemp0] = self::arrayToNestedKeys($itemtemp, $val);
                    }


                }
            }
            if($mode='bayut') {
                $res['Property_Status'] = 'Live';
            }
            print_r($res);
            $result[$lisid] = $res;

        }
        // get locations
        $locations = array_unique($locations);

        $params = [
            'select' => ['ID', 'TITLE', 'UF_CRM_9_1753773914'],
            'filter' => [
                '@ID'=>$locations
            ],
            'order' => ['ID' => 'ASC'],
        ];

        $locobj = $rellocfactory->getItems($params);

        $locresult = [];

        foreach ($locobj as $item) {
            $data = $item->getData();
            if($mode='bayut') {
                $titles = array_reverse(explode(",", $data['TITLE']));
                $locresult[$data['ID']] = [
                    'City' => $titles[0],
                    'Locality' => $titles[1],
                    'Sub_Locality' => $titles[2],
                    'Tower_Name' => $titles[3]
                ];
            } elseif($mode='Pf') {

            }
        }
        // get users
        $users = array_unique($users);
        if($mode='bayut') {
            $select = ['ID', 'NAME', 'LAST_NAME', 'WORK_PHONE', 'EMAIL'];
        } elseif($mode='Pf') {
            $select = ['ID', 'UF_PFID'];
        }

        $userlist = \Bitrix\Main\UserTable::getList(array(
            'filter' => array(
                '@ID' => $users,
                ),
            'select'=>$select
        ))->fetchAll();

        $userresult = [];

        foreach ($userlist as $item) {
            if($mode='bayut') {
                $userresult[$item['ID']] = [
                    'Listing_Agent' => $item['NAME'].' '.$item['LAST_NAME'],
                    'Listing_Agent_Phone' => $item['WORK_PHONE'],
                    'Listing_Agent_Email' => $item['EMAIL']
                ];
            } elseif($mode='Pf') {

            }
        }
        // get photos
        $params = [
            'select' => ['*', 'UF_*'], // Все поля, включая пользовательские
            'filter' => [
                '@PARENT_ID_'.static::$entityTypeId => array_keys($result),
            ],
            'order' => ['ID' => 'ASC'],
            //'limit' => 100,
        ];

        // Получаем элементы
        $photoobj = $relphotofactory->getItems($params);
        $photoresult = [];
        foreach ($photoobj as $item) {
            $data = $item->getData();
            if($mode='bayut') {
                if($item['UF_CRM_6_1752590335']) {
                    $videoarr = \CFile::GetFileArray($item['UF_CRM_6_1752590335']);
                    $photoresult[$item['PARENT_ID_'.static::$entityTypeId]][] =
                        'https://primocapitalcrm.ae/'.$videoarr['SRC'];
                }
            } elseif($mode='Pf') {

            }
        }

        // get videos
        $params = [
            'select' => ['*', 'UF_*'], // Все поля, включая пользовательские
            'filter' => [
                '@PARENT_ID_'.static::$entityTypeId => array_keys($result),
            ],
            'order' => ['ID' => 'ASC'],
            //'limit' => 100,
        ];

        // Получаем элементы
        $videoobj = $relvideofactory->getItems($params);
        $videoresult = [];
        foreach ($videoobj as $item) {
            $data = $item->getData();
            if($mode='bayut') {
                if($item['UF_CRM_7_1752575795']) {
                    $videoresult[$item['PARENT_ID_'.static::$entityTypeId]][] =
                        $item['UF_CRM_7_1752575795'];
                }
            } elseif($mode='Pf') {

            }
        }
        //print_r($locresult);

        foreach ($result as $key=>&$item) {
            $item['Location'] = $locresult[$item['Location']];
            $item['Assigned'] = $userresult[$item['Assigned']];
            $item['Photos'] = $photoresult[$key];
            $item['Videos'] = $videoresult[$key];
        }

        //print_r($result);

        return $result;
    }

    private function arrayToNestedKeys(array $keys, $value = null) {
        $result = [];
        $current = &$result;

        foreach ($keys as $key) {
            $current[$key] = [];
            $current = &$current[$key];
        }

        // Если нужно установить значение в последний ключ
        if (func_num_args() > 1) {
            $current = $value;
        }

        return $result;
    }

    /*public static function getUser() {

        $user = \Bitrix\Main\UserTable::getList(array(
            'filter' => array(
                '!UF_PFID' => false,
            ),

            //'limit'=>1,

            'select'=>array('*','UF_*'),

        ))->fetchAll();

        print_r($user);
    }*/


    public static function getEnumVal() {
        $rsUserFields = \Bitrix\Main\UserFieldTable::getList(array(
            'filter'=>array('ENTITY_ID'=> 'CRM_5', 'USER_TYPE_ID'=>'enumeration'),
        ));
        $resval = [];
        while($arUserField=$rsUserFields->fetch())
        {
            $enumList = \CUserFieldEnum::getList([], [
                'USER_FIELD_ID' => $arUserField['ID'],
            ]);
            $resval[$arUserField['FIELD_NAME']] = [];
            while ($enumValue = $enumList->fetch()) {
                $resval[$arUserField['FIELD_NAME']][$enumValue['ID']] = $enumValue['VALUE'];
            }
        }
        return $resval;
    }

    /*public static function makeFeeds() {
        Loader::includeModule("crm");
        $entityTypeId = '1036';
// Получаем фабрику для работы с сущностью videos
        $container = Container::getInstance();
        $relationManager = $container->getRelationManager();
        $factory = $container->getFactory($entityTypeId);

        if (!$factory) {
            throw new Exception('Factory not found');
        }

// Подготовка параметров запроса
        $params = [
            'select' => ['*', 'UF_*'], // Все поля, включая пользовательские
            'filter' => [
                'STAGE_ID' => 'DT1036_8:SUCCESS',
            ],
            'order' => ['ID' => 'ASC'],
            //'limit' => 100,
        ];

// Получаем элементы
        $items = $factory->getItemsFilteredByPermissions($params);

// Обработка результатов

        foreach ($items as $item) {
            $result = [];
            $id = $item->getId();
            $result[] = [
                //'id' => $item->getId(),
                'data' => $item->getData(),
                //'userFields' => $item->getUserFields(),
            ];
            $childs = [];
            $itemIdentifier = new \Bitrix\Crm\ItemIdentifier($entityTypeId, $id);
            $childElements = $relationManager->getChildElements($itemIdentifier);
            foreach ($childElements as $child) {
                $childs[$child->getEntityTypeId()] = [
                    //'id' => $item->getId(),
                    //'entTypeId' => $child->getEntityTypeId(),
                    $child->getEntityId(),
                    //'data' => $child->toArray()
                    //'userFields' => $item->getUserFields(),
                ];
            }

            print_r($result);
            print_r($childs);
            foreach($childs as $ckey => $citem) {
                $cfactory = $container->getFactory($ckey);
                $params = [
                    'select' => ['*', 'UF_*'], // Все поля, включая пользовательские
                    'filter' => [
                        'ID' => $citem,
                    ],
                    'order' => ['ID' => 'ASC'],
                    //'limit' => 100,
                ];
                $chresult = [];
                // Получаем элементы
                $chitems = $cfactory->getItemsFilteredByPermissions($params);
                foreach ($chitems as $chitem) {

                    $chresult[] = [
                        //'id' => $item->getId(),
                        'data' => $chitem->getData(),
                        //'userFields' => $item->getUserFields(),
                    ];
                }
                print_r($chresult);


            }


            // альтернатива
            $listingId = '1036';
            $videoId = '1044';
            $photoId = '1040';
            // Получаем фабрику для работы с сущностью videos
            $container = Container::getInstance();
            $relationManager = $container->getRelationManager();
            $factory = $container->getFactory($photoId);


            // Подготовка параметров запроса
            $params = [
                'select' => ['*', 'UF_*'], // Все поля, включая пользовательские
                'filter' => [
                    'PARENT_ID_'.$listingId => 1,
                ],
                'order' => ['ID' => 'ASC'],
                //'limit' => 100,
            ];

            // Получаем элементы
            $items = $factory->getItemsFilteredByPermissions($params);

            foreach ($items as $item) {
                $result = [];
                $id = $item->getId();
                $result[] = [
                    //'id' => $item->getId(),
                    'data' => $item->getData(),
                    //'userFields' => $item->getUserFields(),
                ];

            }

            print_r($result);

        }




    }*/

}
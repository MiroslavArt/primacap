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

    protected function retrieveDate(array $filter, array $mask, string $mode ='Pf') {
        $enums = static::getEnumVal();
        print_r($enums);
        $container = Container::getInstance();

        $factory = $container->getFactory(static::$entityTypeId);
        $rellocfactory = $container->getFactory(static::$locentityTypeId);

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
        foreach ($items as $item) {
            $res = [];
            $data = $item->getData();
            print_r($data);
            $lisid = $data['ID'];
            $locations[] = $data['PARENT_ID_1054'];
            $res['LOCATION'] = $data['PARENT_ID_1054'];
            $res['CREATED_BY'] = $data['CREATED_BY'];
            $res['ASSIGNED_BY_ID'] = $data['ASSIGNED_BY_ID'];
            $res['Last_Updated'] = $data['UPDATED_TIME']->format("Y-m-d H:i:s");
            // sale amount

            foreach ($mask as $key => $item) {
                if(array_key_exists($key, $data)) {
                    if(is_array($data[$key])) {
                        if(array_key_exists($key, $enums)) {
                            $arr1 = $enums[$key];
                            $arr2 = $data[$key];
                            $arr2 = array_map(function($key) use ($arr1) {
                                return $arr1[$key] ?? $key; // Если ключа нет в $arr1, оставляем исходное значение
                            }, $arr2);

                            $res[$item] = $arr2;
                        }

                    } else {
                        if($data[$key]) {
                            if(array_key_exists($key, $enums)) {
                                $res[$item] = $enums[$key][$data[$key]];
                            } else {
                                $res[$item] = $data[$key];
                            }
                        } else {
                            $res[$item] = '';
                        }
                    }
                }
            }
            if($mode='bayut') {
                $res['Property_Status'] = 'Live';
            }

            print_r($res);
            $result[$lisid] = $res;

        }
        return $result;
    }



    public static function getUser() {

        $user = \Bitrix\Main\UserTable::getList(array(
            'filter' => array(
                '!UF_PFID' => false,
            ),

            //'limit'=>1,

            'select'=>array('*','UF_*'),

        ))->fetchAll();

        print_r($user);
    }


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

    public static function makeFeeds() {
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




    }

}
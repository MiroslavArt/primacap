<?php

namespace Webmatrik\Integrations;

use Bitrix\Crm\Service\Container;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Loader;
use Bitrix\Main\Web\HttpClient;
use Bitrix\Main\Config\Option;
use Bitrix\Crm\Service;

class Feed
{

    public function __construct()
    {
        Loader::includeModule('crm');
    }


    public static function makeAuth() {
        $data = [
            'apiKey' => 'ZbtqB.S9LtCW4yuloB7HLOp9P12wr3YzponeZIaC',
            'apiSecret' => '5qWrfodfthVtL2e0YG2r9WvRPXKWAk5U'
        ];

        $httpClient = new HttpClient([
            "socketTimeout" => 10,
            "streamTimeout" => 15
        ]);

        $httpClient->setHeader('Content-Type', 'application/json', true);
        $httpClient->setHeader('Accept', 'application/json', true);
        $httpClient->setHeader('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/114.0 Safari/537.36', true); // mimic real browser

        $response = $httpClient->post(
            'https://atlas.propertyfinder.com/v1/auth/token',
            json_encode($data)
        );

        $status = $httpClient->getStatus();

        if ($status == 200) {
            $responseData = json_decode($response, true);
            //print_r($responseData);
            return $responseData['accessToken'];
            //echo '✅ Token: ' . $responseData['accessToken'];
        } else {
            echo "❌ HTTP Error: $status\n";
            echo "Response Body: " . $response . "\n";
        }
    }

    private static function getCurLocations($factory, $city) {
        $params = [
            'select' => ['ID', 'TITLE', 'UF_CRM_9_1753773914'],
            'filter' => [
                '%TITLE'=>$city
            ],
            'order' => ['ID' => 'ASC'],
        ];

        $items = $factory->getItemsFilteredByPermissions($params);

        $result = [];

        foreach ($items as $item) {
            $data = $item->getData();
            $result[$data['UF_CRM_9_1753773914']][] = $data['ID'];
        }

        return $result;
    }

    private static function getPfLocations($city) {
        $httpClient = new HttpClient([
            "socketTimeout" => 10,
            "streamTimeout" => 15
        ]);
        $httpClient->setHeader('Content-Type', 'application/json', true);
        $httpClient->setHeader('Accept', 'application/json', true);
        $httpClient->setHeader('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/114.0 Safari/537.36', true); // mimic real browser
        $httpClient->setHeader('Authorization', 'Bearer '.self::makeAuth(), true);
        $url = 'https://atlas.propertyfinder.com/v1/locations'; // Adjust endpoint as needed

        $queryParams = [
            'search' => $city,
            'page' => 1, // Example additional parameter,
            'perPage' => 100
        ];

        $fullUrl = $url . '?' . http_build_query($queryParams);

        $response = $httpClient->get(
            $fullUrl
        );

        $status = $httpClient->getStatus();

        $pflocations = [];

        if ($status == 200) {
            $responseData = json_decode($response, true);
            //print_r($responseData);
            //self::processLocations($responseData['data'], $factory);
            //return $responseData['accessToken'];
            //echo '✅ Token: ' . $responseData['accessToken'];
            $pages = $responseData['pagination']['totalPages'];
            foreach ($responseData['data'] as $item) {
                $pflocations[$item['id']] = $item['tree'];
            }
            $startpage = 2;
            if($pages>1) {
                while($startpage<=$pages) {
                    $queryParams = [
                        'search' => $city,
                        'page' => $startpage, // Example additional parameter,
                        'perPage' => 100
                    ];
                    $startpage++;
                    $fullUrl = $url . '?' . http_build_query($queryParams);

                    $response = $httpClient->get(
                        $fullUrl
                    );

                    $status = $httpClient->getStatus();

                    if ($status == 200) {
                        $responseData = json_decode($response, true);
                        foreach ($responseData['data'] as $item) {
                            $pflocations[$item['id']] = $item['tree'];
                        }
                    }
                }
            }
        } else {
            echo "❌ HTTP Error: $status\n";
            echo "Response Body: " . $response . "\n";
        }

        return $pflocations;
    }

    public function getPfUsers() {
        $httpClient = new HttpClient([
            "socketTimeout" => 10,
            "streamTimeout" => 15
        ]);
        $httpClient->setHeader('Content-Type', 'application/json', true);
        $httpClient->setHeader('Accept', 'application/json', true);
        $httpClient->setHeader('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/114.0 Safari/537.36', true); // mimic real browser
        $httpClient->setHeader('Authorization', 'Bearer '.self::makeAuth(), true);
        $url = 'https://atlas.propertyfinder.com/v1/users/'; // Adjust endpoint as needed

        $queryParams = [
            'status' => 'active',
            'page' => 1, // Example additional parameter,
            'perPage' => 100
        ];

        $fullUrl = $url . '?' . http_build_query($queryParams);

        $response = $httpClient->get(
            $fullUrl
        );

        $status = $httpClient->getStatus();

        if ($status == 200) {
            $responseData = json_decode($response, true);
            //print_r($responseData);
            $res = [];
            $data = $responseData['data'];

            foreach ($data as $item) {
                $res[mb_strtolower($item['email'])] = $item['id'];
            }

            $emails = array_keys($res);

            print_r($res);

            $user = \Bitrix\Main\UserTable::getList(array(
                'filter' => array(
                    '@EMAIL' => $emails,
                    'UF_PFID' => false
                ),
                'select'=>array('ID', 'EMAIL','UF_PFID'),
            ))->fetchAll();

            print_r($user);

            $usero = new \CUser;
            foreach ($user as $us) {
                $update = false;
                $email = mb_strtolower($us['EMAIL']);
                echo "<pre>";
                print_r($res[$email]);
                echo "</pre>";

                if($us['UF_PFID'] != $res[$email]) {
                    $update = true;
                }
                if($update) {
                    $fields = Array(
                        "UF_PFID" => $res[$email]
                    );
                    $usero->Update($us['ID'], $fields);
                }
            }
        }
    }

    public function syncLocations($city) {
        $factory = Service\Container::getInstance()->getFactory(1054);
        $curloc = static::getCurLocations($factory, $city);
        $pfloc = static::getPfLocations($city);
        print_r($pfloc);
        print_r(count($pfloc));
        foreach($pfloc as $key=>$item) {
            if(!array_key_exists($key, $curloc)) {
                $newtree = array_reverse($item, true);
                $titles = [];
                foreach ($newtree as $item) {
                    $titles[] = $item['name'];
                }
                $title = implode(',', $titles);
                $item = $factory->createItem(['TITLE'=>$title, 'ASSIGNED_BY_ID'=>1013,
                    'UF_CRM_9_1753773914'=>$key]);
                $operation = $factory->getAddOperation($item);
                $operation
                    ->disableCheckFields()
                    ->disableBizProc()
                    ->disableCheckAccess()
                ;
                $addResult = $operation->launch();

                $errorMessages = $addResult->getErrorMessages();

                if ($addResult->isSuccess())
                {
                    // получаем ID новой записи СП
                    $newId = $item->getId();
                    //echo $newId;

                }
            }
        }

    }

    public function setLocations()
    {
        //$token = self::makeAuth();
        $factory = Service\Container::getInstance()->getFactory(1054);

        $httpClient = new HttpClient([
            "socketTimeout" => 10,
            "streamTimeout" => 15
        ]);
        $httpClient->setHeader('Content-Type', 'application/json', true);
        $httpClient->setHeader('Accept', 'application/json', true);
        $httpClient->setHeader('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/114.0 Safari/537.36', true); // mimic real browser
        $url = 'https://atlas.propertyfinder.com/v1/locations'; // Adjust endpoint as needed

        $startpage = 2;
        while($startpage<=86) {
            $queryParams = [
                'search' => 'Dubai',
                'page' => $startpage, // Example additional parameter,
                'perPage' => 100
            ];
            $startpage++;
            $fullUrl = $url . '?' . http_build_query($queryParams);
            $httpClient->setHeader('Authorization', 'Bearer '.self::makeAuth(), true);

            $response = $httpClient->get(
                $fullUrl
            );
            $status = $httpClient->getStatus();

            if ($status == 200) {
                $responseData = json_decode($response, true);
                //print_r($responseData);
                self::processLocations($responseData['data'], $factory);
                //return $responseData['accessToken'];
                //echo '✅ Token: ' . $responseData['accessToken'];
            } else {
                echo "❌ HTTP Error: $status\n";
                echo "Response Body: " . $response . "\n";
            }
        }
    }

    private static function processLocations($data, $factory) {
        foreach ($data as $key => $item) {
            if($key == 99) {
                print_r($item);
                $locid = $item['id'];
                $newtree = array_reverse($item['tree'], true);
                $titles = [];
                foreach ($newtree as $item) {
                    $titles[] = $item['name'];
                }
                $title = implode(',', $titles);
                $item = $factory->createItem(['TITLE'=>$title, 'ASSIGNED_BY_ID'=>1013,
                    'UF_CRM_9_1753773914'=>$locid]);
                $operation = $factory->getAddOperation($item);
                $operation
                    ->disableCheckFields()
                    ->disableBizProc()
                    ->disableCheckAccess()
                ;
                $addResult = $operation->launch();

                $errorMessages = $addResult->getErrorMessages();

                if ($addResult->isSuccess())
                {
                    // получаем ID новой записи СП
                    $newId = $item->getId();
                    echo $newId;

                }
                else
                {
                    echo "fail";
                }
            }
        }
    }

    public static function delDupl() {
        $entityTypeId = '1054';
// Получаем фабрику для работы с сущностью videos
        $container = Container::getInstance();
        $relationManager = $container->getRelationManager();
        $factory = $container->getFactory($entityTypeId);

        if (!$factory) {
            throw new Exception('Factory not found');
        }


        $params = [
            'select' => ['ID', 'UF_CRM_9_1753773914'], // Все поля, включая пользовательские
            'filter' => [
            ],
            'order' => ['ID' => 'ASC'],
            //'limit' => 100,
        ];

// Получаем элементы
        $items = $factory->getItemsFilteredByPermissions($params);

// Обработка результатов

        $result = [];

        foreach ($items as $item) {
            $data = $item->getData();
            $result[$data['UF_CRM_9_1753773914']][] = $data['ID'];
        }

        $cleanresult = [];

        foreach ($result as $item) {
            if(count($item)>1) {
                $cleanresult[] = $item;
            }
        }


        $cleanresult = [];

        foreach ($result as $key=>$item) {
            if(count($item)>1) {
                $pop = array_pop($item);
                //$cleanresult[$key] = $item;
                //if($key==3033) {
                    foreach($item as $it) {
                        $fit = $factory->getItem($it);
                        $operation = $factory->getDeleteOperation($fit);
                        $operation
                            ->disableCheckFields()
                            ->disableBizProc()
                            ->disableCheckAccess()
                        ;
                        $addResult = $operation->launch();
                    }
                //}
            }
        }

    }

    public function makedraftPfFeed($id) {
        $entityTypeId = '1036';
        $locentityTypeId = '1054';
        $enums = static::getEnumVal();
        print_r($enums);
        $container = Container::getInstance();

        $factory = $container->getFactory($entityTypeId);
        $rellocfactory = $container->getFactory($locentityTypeId);

        if (!$factory) {
            throw new Exception('Factory not found');
        }

        /*$entityClass = $factory->getDataClass();
        $result = $entityClass::query()
            ->setSelect(['*', 'UF_*'])
            ->where('ID', $id)
            ->fetch();

        print_r($result);*/
// Подготовка параметров запроса
        $params = [
            'select' => ['*', 'UF_*'], // Все поля, включая пользовательские
            'filter' => [
                'ID' => $id,
            ],
            'order' => ['ID' => 'ASC']
            //'limit' => 100,
        ];

        // Получаем элементы
        $items = $factory->getItems($params);

        foreach ($items as $item) {
            $data = $item->getData();
            print_r($data);
        }
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
<?php

namespace Webmatrik\Integrations;

use Bitrix\Crm\Service\Container;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Loader;
use Bitrix\Main\Web\HttpClient;
use Bitrix\Main\Config\Option;
use Bitrix\Crm\Service;
use Bitrix\Main\Application;

class FeedPf extends Feed
{
    protected static $token;
    protected static $mask;
    protected static $offplan;

    public function __construct(bool $gettoken = true, bool $offPlan = false)
    {
        static::$offplan = $offPlan;
        if($gettoken) {
            static::$token = self::makeAuth();
        }
        static::$mask = [
            'UF_CRM_5_1752506832' => 'age',
            'UF_CRM_5_1752506857' => 'amenities',
            'UF_CRM_5_1752508051' => 'bedrooms',
            'UF_CRM_5_1752507949' => 'bathrooms',
            'UF_CRM_5_1752508146' => 'category',
            'UF_CRM_5_1752508197' => 'compliance,advertisementLicenseIssuanceDate',
            'UF_CRM_5_1752508269' => 'compliance,listingAdvertisementNumber',
            'UF_CRM_5_1752570656' => 'compliance,type',
            'UF_CRM_5_1752508386' => 'compliance,userConfirmedDataIsCorrect',
            'UF_CRM_5_1752508408' => 'description,en',
            'UF_CRM_5_1752508464' => 'description,ar',
            'UF_CRM_5_1752508545' => 'developer',
            'UF_CRM_5_1752577914' => 'finishingType',
            'UF_CRM_5_1752508563' => 'furnishingType',
            'UF_CRM_5_1752508720' => 'floorNumber',
            'UF_CRM_5_1752508637' => 'hasGarden',
            'UF_CRM_5_1752508654' => 'hasKitchen',
            'UF_CRM_5_1752578322' => 'hasParkingOnSite',
            'UF_CRM_5_1752508685' => 'landNumber',
            'UF_CRM_5_1752568955' => 'mojDeedLocationDescription',
            'UF_CRM_5_1752568971' => 'numberOfFloors',
            'UF_CRM_5_1752569001' => 'ownerName',
            'UF_CRM_5_1752569021' => 'parkingSlots',
            'UF_CRM_5_1752569049' => 'plotNumber',
            'UF_CRM_5_1752569108' => 'plotSize',
            // to be changed
            'UF_CRM_5_1754555234' => 'price,amounts,sum',
            //
            'UF_CRM_5_1754891719' => 'price,downpayment',
            'UF_CRM_5_1752569355' => 'price,minimalRentalPeriod',
            'UF_CRM_5_1752569384' => 'price,mortgage,comment',
            'UF_CRM_5_1752579812' => 'price,mortgage,enabled',
            'UF_CRM_5_1752569413' => 'price,numberOfCheques',
            'UF_CRM_5_1752569581' => 'price,numberOfMortgageYears',
            'UF_CRM_5_1752579686' => 'price,obligation,enabled',
            'UF_CRM_5_1752569649' => 'price,obligation,comment',
            'UF_CRM_5_1752569673' => 'price,onRequest',
            'UF_CRM_5_1754893298' => 'price,paymentMethods',
            'UF_CRM_5_1752569908' => 'price,type',
            'UF_CRM_5_1752569772' => 'price,utilitiesInclusive',
            'UF_CRM_5_1752570481' => 'price,valueAffected,comment',
            'UF_CRM_5_1752570503' => 'price,valueAffected,enabled',
            'UF_CRM_5_1752571194' => 'projectStatus',
            'UF_CRM_5_1752571265' => 'reference',
            'UF_CRM_5_1752571276' => 'size',
            'UF_CRM_5_1752571294' => 'street,direction',
            'UF_CRM_5_1752571434' => 'street,width',
            'UF_CRM_5_1752571489' => 'title,ar',
            'TITLE' => 'title,en',
            'UF_CRM_5_1752571572' => 'type',
            'UF_CRM_5_1752509816' => 'uaeEmirate',
            'UF_CRM_5_1752571865' => 'unitNumber'
        ];
        parent::__construct();
    }

    protected function getHttpClient() {
        $httpClient = new HttpClient([
            "socketTimeout" => 10,
            "streamTimeout" => 15
        ]);

        $httpClient->setHeader('Content-Type', 'application/json', true);
        $httpClient->setHeader('Accept', 'application/json', true);
        $httpClient->setHeader('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/114.0 Safari/537.36', true);
        if(static::$token) {
            $httpClient->setHeader('Authorization', 'Bearer '.static::$token, true);
        }
        return $httpClient;
    }

    protected function makeAuth() {
        if(static::$offplan) {
            $data = [
                'apiKey' => 'ZbtqB.S9LtCW4yuloB7HLOp9P12wr3YzponeZIaC',
                'apiSecret' => '5qWrfodfthVtL2e0YG2r9WvRPXKWAk5U'
            ];
        } else {
            $data = [
                'apiKey' => 'BlDyE.Fmy2YImN9zFqLgqEr3QTobXDxXHtGUUGPk',
                'apiSecret' => 'CoI2eARQkVfLYxz50q0b2NzVe0bULDZT'
            ];
        }
        $httpClient = self::getHttpClient();

        $response = $httpClient->post(
            'https://atlas.propertyfinder.com/v1/auth/token',
            json_encode($data)
        );

        $status = $httpClient->getStatus();

        if ($status == 200) {
            $responseData = json_decode($response, true);
            //print_r($responseData);
            $token = $responseData['accessToken'];
            if(!$token) {
                throw new \Exception('no token');
            } else {
                return $token;
            }
            //echo '✅ Token: ' . $responseData['accessToken'];
        } else {
            echo "❌ HTTP Error: $status\n";
            echo "Response Body: " . $response . "\n";
            throw new \Exception('no token');

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
        $httpClient = self::getHttpClient();
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
        $httpClient = self::getHttpClient();
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
                    //'UF_PFID' => false
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
                        "UF_PFID" => $res[$email],
                        "UF_PFOP" => static::$offplan
                    );
                    $usero->Update($us['ID'], $fields);
                }
            }
        }
    }

    public function syncLocations($city) {
        $factory = Service\Container::getInstance()->getFactory(static::$locentityTypeId);
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
        $entityTypeId = static::$locentityTypeId;
// Получаем фабрику для работы с сущностью videos
        $container = Container::getInstance();
        $relationManager = $container->getRelationManager();
        $factory = $container->getFactory($entityTypeId);

        if (!$factory) {
            throw new \Exception('Factory not found');
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

    public function sendListingDraft($lisId) {
        $filter = [
            'ID' => $lisId,
            '@UF_CRM_5_1752569141' => [1297]
        ];
        $data = static::retrieveDate($filter, 'Pf');
        if(!$data) {
            throw new \Exception('No data for export. Please check portals field');
        } else {
            $data = self::prepareListing(current($data));
            $lisid = self::deliverListing($data);
            return $lisid;
        }
    }

    protected function prepareListing(array $data) {
        $reserr = [];
        $resdescr = [];
        if(!$data['price']['amounts']['sum']) {
            $reserr[] = 'price in AED';
        } else {
            $sum = $data['price']['amounts']['sum'];
            unset($data['price']['amounts']['sum']);
            switch ($data['price']['type']) {
                case 'sale':
                    $data['price']['amounts']['sale'] = $sum;
                    break;
                case 'daily':
                    $data['price']['amounts']['daily'] = $sum;
                    break;
                case 'monthly':
                    $data['price']['amounts']['monthly'] = $sum;
                    break;
                case 'weekly':
                    $data['price']['amounts']['weekly'] = $sum;
                    break;
                case 'yearly':
                    $data['price']['amounts']['yearly'] = $sum;
                    break;
            }
        }
        if($data['price']['amounts']['type']=='sale') {
            if(!$data['price']['downpayment']) {
                $reserr[] = 'downpayment';
            }
        }
        if(!$data['uaeEmirate']) {
            $reserr[] = 'uaeEmirate';
        } else {
            if($data['uaeEmirate'] == 'dubai' || $data['uaeEmirate'] == 'abu_dhabi') {
                if(!$data['compliance']['listingAdvertisementNumber']) {
                    $reserr[] = 'listingAdvertisementNumber';
                }
                if(!$data['compliance']['type']) {
                    $reserr[] = 'compliance type';
                } else {
                    if($data['uaeEmirate'] == 'dubai') {
                        if(!in_array($data['compliance']['type'], ['rera','dtcm'])) {
                            $reserr[] = 'compliance type';
                        }
                    } elseif ($data['uaeEmirate'] == 'abu_dhabi') {
                        if(!in_array($data['compliance']['type'], ['adrec'])) {
                            $reserr[] = 'compliance type';
                        }
                    }
                }
            }
        }
        if(!$data['type']) {
            $reserr[] = 'type';
        } else {
            if($data['type'] != 'farm' || $data['type'] != 'land') {
                if(!$data['bedrooms']) {
                    $reserr[] = 'bedrooms';
                }
                if(!$data['bathrooms']) {
                    $reserr[] = 'bathrooms';
                }
            }
            if($data['type'] == 'co-working-space') {
                if(!$data['hasParkingOnSite']) {
                    $reserr[] = 'hasParkingOnSite';
                }
            }
        }
        if($data['location']) {
            $loc = $data['location'];
            unset($data['location']);
            $data['location']['id'] = $loc;
        } else {
            $reserr[] = 'location';
        }
        if($data['assignedTo']) {
            $assgn = $data['assignedTo'];
            unset($data['assignedTo']);
            $data['assignedTo']['id'] = $assgn;
            if(!$data['createdBy']) {
                unset($data['createdBy']);
                $data['createdBy']['id'] = $assgn;
            } else {
                $cre = $data['createdBy'];
                unset($data['createdBy']);
                $data['createdBy']['id'] = $cre;
            }
        } else {
            $reserr[] = 'assignedTo';
            $resdescr[] = 'User not present at PropertyFinder account';
        }
        if(!empty($reserr)) {
            throw new \Exception('Errors in fields '.implode(",", $reserr).
                '!'.implode(".", $resdescr));
        } else {
            ksort($data);
            return $data;
        }
    }

    protected function deliverListing(array $data) {
        $httpClient = self::getHttpClient();

        //$dataj =  json_encode($data, JSON_NUMERIC_CHECK | JSON_UNESCAPED_SLASHES);
        $dataj =  json_encode($data, JSON_UNESCAPED_SLASHES);
        file_put_contents(__DIR__.'/data.json', $dataj);
        $response = $httpClient->post(
            'https://atlas.propertyfinder.com/v1/listings',
            $dataj
        );

        $status = $httpClient->getStatus();

        if ($status == 200) {
            $responseData = json_decode($response, true);
            print_r($responseData);
        } else {
            echo "❌ HTTP Error: $status\n";
            echo "Response Body: " . $response . "\n";

        }

    }

    public function searchLocation($search) {
        $httpClient = self::getHttpClient();

        $url = 'https://atlas.propertyfinder.com/v1/locations'; // Adjust endpoint as needed

        $queryParams = [
            'search' => $search,
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
            print_r($responseData);
        }

    }

    /*public function setLocations()
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
    }*/


}
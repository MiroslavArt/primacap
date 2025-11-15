<?php

namespace Webmatrik\Integrations;

use \Webmatrik\Integrations\Integration;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;
use \Bitrix\Main\Data\Cache;
use \Bitrix\Main\Application;

abstract class AbstractIntegration implements Integration
{
    /**
     * @var int
     */
    protected $gmt = 0;
    protected $token;
    protected $subscribed = false;
    protected $assigned;
    protected $proplinkuf;
    protected $proprefuf;
    protected $contactlinkuf;
    protected $startwf;
    protected $wfid;
    protected $apikey;


    public function __construct()
    {
        \Bitrix\Main\Loader::includeModule('crm');
        \Bitrix\Main\Loader::includeModule('bizproc');
        $this->assigned = static::getModuleOption('main_Lead_AssignedTo', '');
        $this->proplinkuf = static::getModuleOption('main_Bayut_Property_Link_UF', '');
        $this->proprefuf = static::getModuleOption('main_Bayut_Property_Ref_UF', '');
        $this->contactlinkuf = static::getModuleOption('main_Bayut_Contact_Link_UF', '');
        $this->startwf = static::getModuleOption('main_Bayut_Start_Deal_WF', '');
        $this->wfid = static::getModuleOption('main_Bayut_Start_Deal_WF_ID', '');
        $this->apikey = static::getModuleOption('main_BayutDubizzle_API_KEY', '');
    }

    protected function isSubscribed()
    {
        $this->subscribed = true;
    }
    /**
     * @return void
     */
    //abstract public function processWebhook();

    /**
     * @param $dateStr
     * @return null
     */
    protected function getDateFromString($dateStr)
    {
        if (empty($dateStr)) {
            return null;
        }
        $dateArr = explode('.', $dateStr);

        $tmstmp = strtotime($dateArr[0]);
        if ($this->gmt > 0) {
            $tmstmp += 3600 * $this->gmt;
        }

        $date = \Bitrix\Main\Type\DateTime::createFromTimestamp($tmstmp);
        $this->isWinter($date);

        return $date;
    }

    /**
     * @param $yourdate
     * @return void
     */
    public function createLead()
    {
        $oLead = new \CCrmLead(false);
        $arFields = array(
            "TITLE" => $this->title,
            "SOURCE_ID" => $this->source,
            "NAME" => $this->name,
            "STATUS_ID" => 'NEW',
            'ASSIGNED_BY_ID' => $this->assigned,
            $this->proplinkuf => $this->proplinkufval,
            $this->proprefuf => $this->proprefufval,
            $this->contactlinkuf => $this->contactlinkval,
            "FM" => array(
                'PHONE' => array(
                    'n0' => array(
                        'VALUE' => $this->phone,
                        'VALUE_TYPE' => 'WORK'
                    )
                ),
            ),
        );

        $newlead_id = $oLead->Add($arFields, true);
        if ($newlead_id > 0) {
            echo $newlead_id;
        }
    }


    public function createDeal(string $type = 'WA')
    {
        if ($type == 'WA' || $type == 'phone') {
            $search = 'phone';
        } else {
            $search = 'email';
        }
        if ($contacts = $this->returnContact($search)) {
            $targetcontact = $contacts[0]['ID'];
        } else {
            $targetcontact = $this->addContact($search);
        }
        if ($type == 'WA') {
            $entityFields = [
                'TITLE'    => $this->title,
                'STAGE_ID' => "C2:NEW",
                'CATEGORY_ID' => 2,
                'CLOSED' => 'N',
                'TYPE_ID' => 'SALE',
                'CONTACT_ID' => $targetcontact,
                'CONTACT_IDS' => [
                    $targetcontact
                ],
                'OPENED' => 'Y',
                'ASSIGNED_BY_ID' => 1,
                'SOURCE_ID' =>  $this->source,
                $this->proplinkuf => $this->proplinkufval,
                $this->proprefuf => $this->proprefufval,
                $this->contactlinkuf => $this->contactlinkval,
            ];
        } elseif ($type == 'email') {
            $entityFields = [
                'TITLE'    => $this->title,
                'STAGE_ID' => "C2:NEW",
                'CATEGORY_ID' => 2,
                'CLOSED' => 'N',
                'TYPE_ID' => 'SALE',
                'CONTACT_ID' => $targetcontact,
                'CONTACT_IDS' => [
                    $targetcontact
                ],
                'OPENED' => 'Y',
                'ASSIGNED_BY_ID' => 1,
                'SOURCE_ID' =>  $this->source,
                $this->proprefuf => $this->proprefufval,
            ];
        } elseif ($type == 'phone') {
            $entityFields = [
                'TITLE'    => $this->title,
                'STAGE_ID' => "C2:NEW",
                'CATEGORY_ID' => 2,
                'CLOSED' => 'N',
                'TYPE_ID' => 'SALE',
                'CONTACT_ID' => $targetcontact,
                'CONTACT_IDS' => [
                    $targetcontact
                ],
                'OPENED' => 'Y',
                'ASSIGNED_BY_ID' => 1,
                'SOURCE_ID' =>  $this->source,
                $this->proprefuf => $this->proprefufval,
            ];
        }

        $entityObject = new \CCrmDeal(false);
        $entityId = $entityObject->Add(
            $entityFields
        );

        if ($entityId) {
            if ($this->startwf == 'Y' and $this->wfid) {
                $deal = 'DEAL_' . $entityId;
                $arWorkflowParameters = [];
                $arErrorsTmp = [];
                $wfId = \CBPDocument::StartWorkflow(
                    $this->wfid, // константа шаблона БП
                    array("crm", "CCrmDocumentDeal", $deal),
                    $arWorkflowParameters,
                    $arErrorsTmp
                );
            }
            if ($type == 'email' || $type == 'phone') {
                if ($this->comment) {
                    $entryId = \Bitrix\Crm\Timeline\CommentEntry::create([
                        'TEXT' => $this->comment,
                        'SETTINGS' => ['HAS_FILES' => 'N'],
                        'AUTHOR_ID' => 1,
                        'BINDINGS' => [['ENTITY_TYPE_ID' => \CCrmOwnerType::Deal, 'ENTITY_ID' => $entityId]]
                    ]);
                }
            }
        } else {
            print_r($entityObject->LAST_ERROR);
        }

        //print_r($targetcontact);
    }

    protected function returnContact($type)
    {
        $searchCondition = '%VALUE';
        if ($type == 'phone') {
            $arFilter = array(
                'FM' => array(
                    array(
                        'TYPE_ID' => 'phone',
                        $searchCondition => $this->phone
                    )
                ),
                'CHECK_PERMISSIONS' => 'N'
            );
        } else {
            $arFilter = array(
                'FM' => array(
                    array(
                        'TYPE_ID' => 'email',
                        $searchCondition => $this->email
                    )
                ),
                'CHECK_PERMISSIONS' => 'N'
            );
        }

        $obCompany = \CCrmContact::GetListEx(
            array('ID' => 'ASC'),
            $arFilter,
            false,
            false,
            array('ID')
        );
        $arResult = [];
        while ($arCompany = $obCompany->Fetch()) {
            $arResult[] = $arCompany;
        }
        return $arResult;
    }


    protected function addContact($type)
    {
        if ($type = 'email') {
            $fm = [
                "PHONE" => [
                    "n0" => [
                        "VALUE"      => $this->phone,
                        "VALUE_TYPE" => "WORK",
                    ]
                ],
                "EMAIL" => [
                    "n0" => [
                        "VALUE"      => $this->email,
                        "VALUE_TYPE" => "WORK",
                    ]
                ],
            ];
        } else {
            $fm = [
                "PHONE" => [
                    "n0" => [
                        "VALUE"      => $this->phone,
                        "VALUE_TYPE" => "WORK",
                    ]
                ]
            ];
        }
        $contactFields = [
            'NAME' => $this->name,
            "FM"  => $fm,
            "OPENED" => "Y", // "Доступен для всех" = Да
            "ASSIGNED_BY_ID" => $this->assigned,
            "SOURCE_ID" => $this->source
        ];
        $contactEntity = new \CCrmContact(false);
        $contactId = $contactEntity->Add(
            $contactFields
        );

        return isset($contactId) ? $contactId : false;
    }
    /**
     * @param $yourdate
     * @return void
     */
    protected function isWinter(\Bitrix\Main\Type\DateTime $yourdate)
    {
        $day = $yourdate->format('d');
        $month = $yourdate->format('m');
        $year = $yourdate->format('Y');

        $cy =  date("Y");

        $winter = false;

        if (($day >= 25 && $month >= 10 && $year == $cy) || ($day <= 29 && $month <= 3 && $year < $cy)) {
            $winter = true;
        }

        if ($winter) {
            $yourdate->modify("-1 hour"); // 1 hour back
        }
    }

    /**
     * @param $type
     * @param $url
     * @return array
     */
    protected function sendCurlRequest($type, $url)
    {
        try {
            // Bayut requires raw timestamp, no URL encoding
            $date = date('Y-m-d H:i:s', strtotime('-1 minutes'));

            // Build query manually
            $url = $url . '?type=' . $type . '&timestamp=' . $date;

            $curl = curl_init();
            $options = [
                CURLOPT_URL            => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING       => "",
                CURLOPT_MAXREDIRS      => 10,
                CURLOPT_TIMEOUT        => 30,
                CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST  => 'GET',
                CURLOPT_HTTPHEADER     => [
                    "Authorization: Bearer " . $this->apikey,
                ],
            ];

            curl_setopt_array($curl, $options);

            $rawResponse = curl_exec($curl);
            $curlErr     = curl_error($curl);
            $httpCode    = curl_getinfo($curl, CURLINFO_HTTP_CODE);

            curl_close($curl);

            if ($curlErr) {
                \Bitrix\Main\Diag\Debug::writeToFile(
                    ['url' => $url, 'error' => $curlErr],
                    'BayutCurl Error ' . date('Y-m-d H:i:s'),
                    'bayutdubizzlepull.log'
                );
                return null;
            }

            if ($httpCode !== 200) {
                \Bitrix\Main\Diag\Debug::writeToFile(
                    ['url' => $url, 'http_code' => $httpCode, 'response' => $rawResponse],
                    'BayutCurl HTTP Error ' . date('Y-m-d H:i:s'),
                    'bayutdubizzlepull.log'
                );
            }

            $response = json_decode($rawResponse, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                \Bitrix\Main\Diag\Debug::writeToFile(
                    ['url' => $url, 'rawResponse' => $rawResponse, 'json_error' => json_last_error_msg()],
                    'BayutCurl JSON Decode Error ' . date('Y-m-d H:i:s'),
                    'bayutdubizzlepull.log'
                );
                return null;
            }

            \Bitrix\Main\Diag\Debug::writeToFile(
                ['requested_url' => $url, 'response' => $response],
                'Bayut Debug ' . date('Y-m-d H:i:s'),
                'bayutdubizzlepull.log'
            );

            return $response;
        } catch (\Throwable $e) {
            \Bitrix\Main\Diag\Debug::writeToFile(
                ['url' => $url, 'error' => $e->getMessage()],
                'BayutCurl Exception ' . date('Y-m-d H:i:s'),
                'bayutdubizzlepull.log'
            );
            return null;
        }
    }

    /**
     * logging. TODO: monologging
     * @param $data
     * @param $sHeader
     * @param $sFilePath
     * @return void
     */
    public static function dump($data, $sHeader = null, $sFilePath = "__mylog_integrations.log")
    {

        $sHeader = ($sHeader != null ? $sHeader . '   ' : '') . date('l jS \of F Y h:i:s A');

        \Bitrix\Main\Diag\Debug::dumpToFile($data, $sHeader, $sFilePath);
    }

    /**
     * @return string
     */
    protected static function translateToEnglish($str)
    {
        $tr = array(
            "А" => "A",
            "Б" => "B",
            "В" => "V",
            "Г" => "G",
            "Д" => "D",
            "Е" => "E",
            "Ж" => "J",
            "З" => "Z",
            "И" => "I",
            "Й" => "Y",
            "К" => "K",
            "Л" => "L",
            "М" => "M",
            "Н" => "N",
            "О" => "O",
            "П" => "P",
            "Р" => "R",
            "С" => "S",
            "Т" => "T",
            "У" => "U",
            "Ф" => "F",
            "Х" => "H",
            "Ц" => "TS",
            "Ч" => "CH",
            "Ш" => "SH",
            "Щ" => "SCH",
            "Ъ" => "",
            "Ы" => "YI",
            "Ь" => "",
            "Э" => "E",
            "Ю" => "YU",
            "Я" => "YA",
            "а" => "a",
            "б" => "b",
            "в" => "v",
            "г" => "g",
            "д" => "d",
            "е" => "e",
            "ж" => "j",
            "з" => "z",
            "и" => "i",
            "й" => "y",
            "к" => "k",
            "л" => "l",
            "м" => "m",
            "н" => "n",
            "о" => "o",
            "п" => "p",
            "р" => "r",
            "с" => "s",
            "т" => "t",
            "у" => "u",
            "ф" => "f",
            "х" => "h",
            "ц" => "ts",
            "ч" => "ch",
            "ш" => "sh",
            "щ" => "sch",
            "ъ" => "y",
            "ы" => "yi",
            "ь" => "",
            "э" => "e",
            "ю" => "yu",
            "я" => "ya",
            "Ё" => "E",
            "Є" => "E",
            "Ї" => "YI",
            "ё" => "e",
            "є" => "e",
            "ї" => "yi",
            " " => "_",
            "/" => "_"
        );
        if (preg_match('/[^A-Za-z0-9_\-]/', $str)) {
            $str = strtr($str, $tr);
            $str = preg_replace('/[^A-Za-z0-9_\-.]/', '', $str);
        }
        return $str;
    }

    /**
     * @return string
     */
    protected static function getModuleId()
    {
        return 'webmatrik.integrations';
    }

    /**
     * @param $optionName
     * @param $default
     * @return mixed
     */
    public static function getModuleOption($optionName, $default = null)
    {
        return Option::get(static::getModuleId(), $optionName);
    }

    /**
     * @param $optionName
     * @param $optionValue
     * @return mixed
     */
    public static function setModuleOption($optionName, $optionValue)
    {
        return Option::set(static::getModuleId(), $optionName, $optionValue);
    }
}

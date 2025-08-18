<?
$eventManager = \Bitrix\Main\EventManager::getInstance();

use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Loader;

//use Bitrix\Main\Page\Asset::getInstance()->addJs('/local/hide-pipeline-popup.js');


//Prohibition of editing lead fields
if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/local/applications/prohibiting_editing_fields/function.php')) {
    require_once $_SERVER['DOCUMENT_ROOT'] . '/local/applications/prohibiting_editing_fields/function.php';
    $eventManager->addEventHandlerCompatible("crm", "OnBeforeCrmLeadUpdate", "control_field_access");
}

if ($_GET['test_whatsapp'] == '1') {
    $result = pf_whatsapp_api();
    file_put_contents($_SERVER["DOCUMENT_ROOT"] . "/local/logs/pf_whatsapp_api_test_log.txt", "Function executed at: " . date("Y-m-d H:i:s") . " - " . $result . "\n", FILE_APPEND);
}

// Bitrix  executing attendance script every 20 minutes
Loader::includeModule("main");

function logMessage($message)
{
    $logDir = $_SERVER['DOCUMENT_ROOT'] . "/local/logs/";
    $logFile = $logDir . "custom_cron_log.txt";

    if (!file_exists($logDir)) {
        mkdir($logDir, 0777, true);
    }

    $date = date("Y-m-d H:i:s");
    file_put_contents($logFile, "[" . $date . "] " . $message . "\n", FILE_APPEND);
}

function AttendanceAdd()
{
    global $DB;

    $logFile = $_SERVER["DOCUMENT_ROOT"] . "/local/logs/attendance_log.txt";
    file_put_contents($logFile, date("Y-m-d H:i:s") . " - AttendanceAdd Agent Ran\n", FILE_APPEND);

    $processed_count = 0;
    $api_call_count = 0;
    $section_not_found = 0;

    $sql = "SELECT ID, USER_ID, PUNCH_DIRECTION, LOG_DATE 
            FROM c_office_attandance 
            WHERE Userlog_status = 0 
            ORDER BY ID DESC LIMIT 200";
    $result = $DB->Query($sql);

    $total_records = $result->SelectedRowsCount();
    logMessage("Total Pending Attendance Records: $total_records");

    if ($total_records == 0) {
        logMessage("No records to process.");
        return "AttendanceAdd();";
    }

    while ($row = $result->Fetch()) {
        $uniqId = (int)$row['ID'];
        $userId = (int)$row['USER_ID'];
        $punchDr = trim($row['PUNCH_DIRECTION']);
        $punchDate = trim($row['LOG_DATE']);

        $color = ($punchDr == 'in') ? '#28a745' : (($punchDr == 'out') ? '#dc3545' : '');
        $name = ($punchDr == 'in') ? 'PUNCH IN' : (($punchDr == 'out') ? 'PUNCH OUT' : '');

        if (empty($name)) {
            logMessage("Invalid PUNCH_DIRECTION '$punchDr' for ID: $uniqId. Skipping record.<br>");
            continue;
        }

        $sql4 = "SELECT VALUE_ID FROM b_uts_user WHERE UF_OFFICE_ATTN = $userId";
        $result4 = $DB->Query($sql4);
        $row4 = $result4->Fetch();
        $valueId = isset($row4['VALUE_ID']) ? (int)$row4['VALUE_ID'] : null;

        if (!$valueId) {
            logMessage("No VALUE_ID found for User ID: $userId");
            continue;
        }

        $sql1 = "SELECT ID FROM b_calendar_section WHERE CAL_TYPE='user' AND OWNER_ID = $valueId LIMIT 1";
        $calendarResult = $DB->Query($sql1);
        $calendarRow = $calendarResult->Fetch();
        $sectionId = isset($calendarRow['ID']) ? (int)$calendarRow['ID'] : null;

        if (!$sectionId) {
            $section_not_found++;
            logMessage("No calendar section found for VALUE_ID: $valueId");
            continue;
        }

        $response = api_call($valueId, $sectionId, $punchDate, $color, $name);
        $processed_count++;
        logMessage("Processing Unique ID: $uniqId - User ID: $valueId - Date: $punchDate");

        if (isset($response['result']) && $response['result'] == true) {
            $api_call_count++;
            $sql3 = "UPDATE c_office_attandance SET Userlog_status = 1 WHERE ID = $uniqId";
            $DB->Query($sql3);
            logMessage("Updated Userlog_status for ID: $uniqId");
        } else {
            logMessage("API call failed for ID: $uniqId");
        }
    }

    logMessage("Total Records Processed: $processed_count");
    logMessage("Total Successful API Calls: $api_call_count");
    logMessage("Total 'Section Not Found' Count: $section_not_found");
    logMessage("Process completed.");

    return "AttendanceAdd();";
}

function api_call($valueId, $sectionId, $from, $color, $name)
{
    $postData = json_encode([
        "type"          => "user",
        "ownerId"       => $valueId,
        "section"       => $sectionId,
        "name"          => $name,
        "description"   => "User Punched In for work.",
        "from"          => $from,
        "to"            => $from,
        "is_meeting"    => false,
        "accessibility" => "free",
        "importance"    => "normal",
        "color"         => $color
    ]);

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL            => 'https://primocapitalcrm.ae/rest/4780/uk1s3te1b1qvhn7n/calendar.event.add',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $postData,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json']
    ]);

    $response = curl_exec($curl);
    if (curl_errno($curl)) {
        logMessage("cURL Error: " . curl_error($curl));
    }
    curl_close($curl);

    return json_decode($response, true);
}

$agentExists = \CAgent::GetList([], ["NAME" => "AttendanceAdd();"])->Fetch();
if (!$agentExists) {
    \CAgent::AddAgent(
        "AttendanceAdd();",
        "main",
        "N",
        1200,
        "",
        "Y",
        DateTime::createFromTimestamp(time() + 60)->toString(),
        30
    );
}


//Registering extensions
$arRegisterConfig = array(
    'hide_stage_lead_kanban' => [
        'js'  => '/local/php_interface/js/hide_stage_lead_kanban.js',
        //'css' => '',
        'rel' => [],
    ],
);
foreach ($arRegisterConfig as $ext => $arExt) {
    \CJSCore::RegisterExt($ext, $arExt);
}

//Initiating changes in crm
if (CSite::InDir('/crm/lead/kanban/')) {
    CUtil::InitJSCore(['hide_stage_lead_kanban']);
}

//Class autoloader and Event logger
if (file_exists(__DIR__ . '/handlers/init.php')) {
    include_once __DIR__ . '/handlers/init.php';
    \Bit\Custom\EventHandlers::registerEventHandler();
}
function Sync_Office_Attandance()
{
    global $DB;

    $fromDate = date('Y-m-d', strtotime("-1 days"));
    $toDate = date('Y-m-d', strtotime("-1 days"));

    $ch = curl_init();
    $url = "http://primocapital.mywire.org:85/api/v2/WebAPI/GetDeviceLogs";
    $queueData = array('APIKey' => "065710072320", 'FromDate' => $fromDate, 'ToDate' => $toDate);
    $data = http_build_query($queueData);
    $getUrl = $url . "?" . $data;
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_URL, $getUrl);
    curl_setopt($ch, CURLOPT_TIMEOUT, 80);
    $response = curl_exec($ch);
    $respArr = json_decode($response, true);

    if ($e = curl_error($ch)) {
        echo $e;
    } else {
        $respArr = json_decode($response, true);
        foreach ($respArr as $data) {
            // Prepare and escape the values to prevent SQL injection
            $userId = $data['EmployeeCode'];
            $punchDirection = $data['PunchDirection'];
            $logDate = $data['LogDate'];

            // Check if the USER_ID already has a record for the same date
            $checkQuery = "SELECT COUNT(*) as count FROM c_office_attandance WHERE USER_ID='$userId' AND LOG_DATE='$logDate'";
            $result = $DB->Query($checkQuery);
            $row = $result->fetch();

            if ($row['count'] == 0) {
                // If no record exists, insert the new record
                $insertQuery = "INSERT INTO c_office_attandance (USER_ID, PUNCH_DIRECTION, LOG_DATE) VALUES ('$userId', '$punchDirection', '$logDate')";
                $DB->Query($insertQuery);
            }
        }
    }
    curl_close($ch);

    return "Sync_Office_Attandance();";
}

function calculateAgentMetrics($agentId)
{
    global $DB;

    $ninetyDaysAgo = date('Y-m-d', strtotime('-90 days'));

    // Lead Conversion
    $leadconvSql = $DB->query("SELECT COUNT(*) as lead_convertion_count FROM b_crm_lead WHERE ASSIGNED_BY_ID = '$agentId' AND STATUS_ID = 'CONVERTED' AND DATE_MODIFY >= '$ninetyDaysAgo'");
    $leadconvRes = $leadconvSql->fetch();

    $leadconvSqltot = $DB->query("SELECT COUNT(*) as lead_convertion_count FROM b_crm_lead WHERE STATUS_ID = 'CONVERTED' AND DATE_MODIFY >= '$ninetyDaysAgo'");
    $leadconvRestot = $leadconvSqltot->fetch();

    $conversionPercentage = ($leadconvRes['lead_convertion_count'] / $leadconvRestot['lead_convertion_count']) * 60;
    $leadconvPercentage = number_format($conversionPercentage, 2);

    // Attendance
    $strtimeSql = $DB->query("SELECT SUM(TIMESTAMPDIFF(SECOND, DATE_START, DATE_FINISH)) AS score_time FROM b_timeman_entries WHERE USER_ID = '$agentId' AND DATE_START >= '$ninetyDaysAgo'");
    $timeRes = $strtimeSql->fetch();

    $strtimeSqltot = $DB->query("SELECT SUM(TIMESTAMPDIFF(SECOND, DATE_START, DATE_FINISH)) AS score_time FROM b_timeman_entries WHERE DATE_START >= '$ninetyDaysAgo'");
    $timeRestot = $strtimeSqltot->fetch();

    $timeResPercentage = ($timeRes['score_time'] / $timeRestot['score_time']) * 10;
    $timePercentage = number_format($timeResPercentage, 2);

    // Office Attendance
    $stroftimeSql = $DB->query("SELECT TIMEDIFF(MAX(CASE WHEN PUNCH_DIRECTION = 'out' THEN LOG_DATE END), MIN(CASE WHEN PUNCH_DIRECTION = 'in' THEN LOG_DATE END)) AS WorkingHours FROM c_office_attandance WHERE USER_ID = '$agentId' AND LOG_DATE >= '$ninetyDaysAgo'");
    $oftimeRes = $stroftimeSql->fetch();

    // Activities
    $stractSql = $DB->query("SELECT COUNT(*) as activities_count FROM b_crm_act WHERE AUTHOR_ID = '$agentId' AND OWNER_TYPE_ID ='1' AND LAST_UPDATED >= '$ninetyDaysAgo'");
    $actRes = $stractSql->fetch();

    $stractSqltot = $DB->query("SELECT COUNT(*) as activities_count FROM b_crm_act WHERE OWNER_TYPE_ID ='1' AND LAST_UPDATED >= '$ninetyDaysAgo'");
    $actRestot = $stractSqltot->fetch();

    $actperPercentage = ($actRes['activities_count'] / $actRestot['activities_count']) * 15;
    $stractper = number_format($actperPercentage, 2);

    // Reactiveness
    $strproactSql = $DB->query("SELECT COUNT(*) as proactivities_count FROM c_distribution_lead_missing WHERE USER_ID = '$agentId' AND MISSED_AT >= '$ninetyDaysAgo'");
    $proactRes = $strproactSql->fetch();

    $strproactSqltot = $DB->query("SELECT COUNT(*) as proactivities_count FROM  c_distribution_lead_missing WHERE  MISSED_AT >= '$ninetyDaysAgo'");
    $proactRestot = $strproactSqltot->fetch();


    $proactPercentage = ($proactRes['proactivities_count'] / $proactRestot['proactivities_count']) * 15;
    $reactivenessPercentage = number_format($proactPercentage, 2);

    // Total Score
    $totalscore = $leadconvPercentage + $timePercentage + $stractper - $reactivenessPercentage;

    return [
        'lead_conversion'            => $leadconvRes['lead_convertion_count'],
        'lead_conversion_percentage' => $leadconvPercentage,
        'attendance'                 => $timeSumper,
        'office_attendance'          => $oftimeRes['WorkingHours'],
        'activities'                 => $stractper,
        'reactiveness'               => $proactRes['proactivities_count'],
        'total_score'                => $totalscore
    ];
}

function auto_lead_boost()
{

    global $DB;
    $strSql = "Select ID,NAME,SECOND_NAME,LAST_NAME,EMAIL,c.UF_DEPARTMENT AS DEP,c.UF_OFFICE_ATTN from b_user a,b_user_group b,b_uts_user c where a.active ='Y' and a.LID ='s1' and a.ID = b.USER_ID and b.GROUP_ID='11' and c.VALUE_ID=a.ID and a.ID NOT IN (1,27,1709,1013,4817,4818)  ORDER BY a.ID ASC";
    $dbRes = $DB->Query($strSql);
    while ($arRes = $dbRes->Fetch()) {
        $agentId = $arRes['ID'];
        $metrics = calculateAgentMetrics($agentId);

        $strBostSql = $DB->Query("Select VALUE_ID  from b_uts_user where VALUE_ID = '" . $agentId . "' and UF_BOOST_LEAD ='675' and UF_BOOST_VALID >= NOW()");
        $dbBostRes = $strBostSql->Fetch();

        if ($metrics['lead_conversion'] > '2' && $dbBostRes['VALUE_ID'] != $agentId) {

            global $USER;

            CModule::IncludeModule("iblock");
            CModule::IncludeModule("bizproc");
            CModule::IncludeModule("crm");

            $PROP = array();
            $PROP['BOOST_REQUESTED_BY'] = $agentId;


            $IblockFields = array(
                "IBLOCK_SECTION_ID" => false,
                "IBLOCK_ID"         => 45,
                "PROPERTY_VALUES"   => $PROP,
                "NAME"              => "Boost Approval #" . $agentId,
                "ACTIVE"            => "Y",
                "PREVIEW_TEXT"      => "",
                "DETAIL_TEXT"       => "",

            );
            $IBLOCKELEMENT = new CIBlockElement();
            $element_id = $IBLOCKELEMENT->Add($IblockFields);
            if ($element_id) {
                $arErrors = array();
                $arParameters = array();

                CBPDocument::StartWorkflow(
                    143,
                    array("lists", "BizprocDocument", $element_id),
                    $arParameters,
                    $arErrors
                );
            }

        }
    }
    return "auto_lead_boost();";
}

function pf_lead_api()
{
    // Step 1: Obtain Access Token
    $api_key = 'SVTPc.SKYATvoLMidbn1ZAaIIENpTkYbib6FsEEY';
    $api_secret = 'hGvwxHgwBOiYbhHKW7F2GXB8jjyQLIyQ';
    $token_url = 'https://atlas.propertyfinder.com/v1/auth/token';

    CModule::IncludeModule("crm");
    $contact = new CCrmContact();
    $deal = new CCrmDeal();
    global $USER;

    function getBitrixUserByEmail($email)
    {
        if (CModule::IncludeModule("intranet")) {
            $userFilter = ["=EMAIL" => $email];
            $dbUsers = CUser::GetList(($by = "ID"), ($order = "ASC"), $userFilter);
            if ($arUser = $dbUsers->Fetch()) {
                $userId = $arUser['ID'];
            }
            return $userId;
        }
        return null;
    }

    function getBitrixDealByLeadId($lead_id)
    {
        $filter = [
            'UF_CRM_1742890231808' => $lead_id
        ];
        $select = ['ID', 'TITLE'];

        $deals = CCrmDeal::GetList([], $filter, $select);
        if ($deal = $deals->Fetch()) {
            return $deal['ID'];
        }
        return null;
    }

    // Get access token
    $data = json_encode([
        'apiKey'    => $api_key,
        'apiSecret' => $api_secret
    ]);

    $ch = curl_init($token_url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Accept: application/json'
        ],
        CURLOPT_POSTFIELDS     => $data,
    ]);

    $token_response = curl_exec($ch);
    if (curl_errno($ch) || !$token_response) {
        die('Token request failed: ' . curl_error($ch));
    }
    curl_close($ch);

    $token_data = json_decode($token_response, true);
    if (empty($token_data['accessToken'])) {
        die('Failed to obtain access token');
    }
    $access_token = $token_data['accessToken'];

    // Step 2: Fetch leads from Property Finder Enterprise API
    $api_url = "https://atlas.propertyfinder.com/v1/leads";

    $curl = curl_init($api_url);
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $access_token,
            'Accept: application/json'
        ],
    ]);

    $response_data = curl_exec($curl);
    curl_close($curl);

    $decoded_data = json_decode($response_data, true);
    if (empty($decoded_data['data'])) {
        die('No leads data retrieved');
    }

    foreach ($decoded_data['data'] as $lead) {
        $lead_id = $lead['id'];
        $existingDealId = getBitrixDealByLeadId($lead_id);

        if (!$existingDealId) {
            // Get public profile information if available
            $publicProfile = $lead['publicProfile'] ?? [];
            $ufname = $publicProfile['firstName'] ?? '';
            $ulname = $publicProfile['lastName'] ?? '';
            $uemail = $publicProfile['email'] ?? '';

            // Get property/location information
            $entity = $lead['entity'] ?? [];
            $property_ref = $entity['reference'] ?? '';
            $property_type = $entity['type'] ?? '';
            $property_category = $entity['category'] ?? '';
            $location = $entity['location']['name'] ?? '';

            // Prepare lead data for Bitrix
            $lead_details = [
                'TITLE'                => "{$lead['sender']['name']}",
                'NAME'                 => $lead['sender']['name'],
                'LAST_NAME'            => $lead['sender']['lastName'] ?? '',
                'EMAIL'                => [['VALUE' => $lead['sender']['email'], 'VALUE_TYPE' => 'WORK']],
                'PHONE'                => [['VALUE' => $lead['sender']['phone'], 'VALUE_TYPE' => 'WORK']],
                'SOURCE_ID'            => 'UC_9LDUWC', // Property Finder source
                'UF_CRM_1722936346902' => $lead['priceRange'] ?? '',
                'UF_CRM_1727321267282' => "{$property_category}-{$property_type}",
                'UF_CRM_1722935088220' => $lead['offeringType'] ?? '',
                'UF_CRM_1724152083075' => $entity['location']['id'] ?? '',
                'COMMENTS'             => $lead['message'] ?? '',
                'UF_CRM_USER_FNAME'    => $ufname,
                'UF_CRM_USER_LNAME'    => $ulname,
                'UF_CRM_USER_EMAIL'    => $uemail
            ];

            // Create contact in Bitrix
            $apicontactFields = [
                'NAME'      => $lead['sender']['name'],
                'LAST_NAME' => $lead['sender']['lastName'] ?? '',
                'EMAIL'     => [['VALUE' => $lead['sender']['email'], 'VALUE_TYPE' => 'WORK']],
                'PHONE'     => [['VALUE' => $lead['sender']['phone'], 'VALUE_TYPE' => 'WORK']],
            ];

            $apicontactId = $contact->Add($apicontactFields);

            // Get assigned user
            $current_user = getBitrixUserByEmail($uemail) ?: $USER->GetID();

            // Create deal in Bitrix
            $apidealFields = [
                'STAGE_ID'             => 'C2:NEW',
                'CONTACT_ID'           => $apicontactId,
                'TYPE_ID'              => 'SALE',
                'CATEGORY_ID'          => 2,
                'TITLE'                => 'PF_' . $lead['sender']['name'] . '_' . ($lead['sender']['lastName'] ?? '') . '_' . $lead['sender']['phone'],
                'NAME'                 => $lead['sender']['name'],
                'LAST_NAME'            => $lead['sender']['lastName'] ?? '',
                'PHONE'                => [['VALUE' => $lead['sender']['phone'], 'VALUE_TYPE' => 'WORK']],
                'EMAIL'                => [['VALUE' => $lead['sender']['email'], 'VALUE_TYPE' => 'WORK']],
                'SOURCE_ID'            => "UC_7LVVAZ",
                'UF_CRM_1645599207002' => $lead['priceRange'] ?? '',
                'UF_CRM_1727321267282' => $lead['offeringType'] ?? '',
                'UF_CRM_1645599456320' => $location . '_' . ($entity['location']['id'] ?? ''),
                'UF_CRM_1730201029300' => $property_ref,
                'UF_CRM_1725942907'    => "{$property_category}-{$property_type}",
                'UF_CRM_1742890231808' => $lead_id,
                'UF_CRM_1724152083075' => $entity['location']['id'] ?? '',
                'COMMENTS'             => $lead['message'] ?? '',
                'ASSIGNED_BY_ID'       => $current_user,
            ];

            $apidealId = $deal->Add($apidealFields);
        }
    }
    return "pf_lead_api();";
}

// function getBitrixUserByEmail($email)
// {
//     if (empty($email)) {
//         return null;
//     }

//     $user = CUser::GetList(
//         ($by = "id"),
//         ($order = "asc"),
//         ["EMAIL" => $email],
//         ["FIELDS" => ["ID"]]
//     );

//     if ($userData = $user->Fetch()) {
//         return $userData['ID'];
//     }

//     return null;
// }


function pf_whatsapp_api()
{
    // Initialize result array
    $result = [
        'status'          => 'started',
        'timestamp'       => date('Y-m-d H:i:s'),
        'leads_received'  => 0,
        'leads_processed' => 0,
        'leads_failed'    => 0,
        'error_details'   => []
    ];

    try {
        // 1. Set execution limits
        set_time_limit(0);
        ini_set('max_execution_time', 300);

        // 2. Load Bitrix CRM module
        if (!CModule::IncludeModule('crm')) {
            throw new Exception('Bitrix CRM module not installed!');
        }
        date_default_timezone_set('Asia/Dubai');

// Aaj ki date Dubai timezone ke hisaab se
        $today = date('Y-m-d');

        $createdAtFrom = $today . 'T00:00:00+04:00';

        $leads_url = 'https://atlas.propertyfinder.com/v1/leads?channel=whatsapp'
            . '&orderBy=id&orderDirection=desc'
            . '&createdAtFrom=' . urlencode($createdAtFrom);
        // 3. API Configuration
        $config = [
            'api_key'    => 'boiwg.tekX8XcyUxXhtbthDxbBS1w72N4hVWZiRy',
            'api_secret' => 'LtMBgdx0AWY1rvwTeID0pXAnmlqeNNJ7',
            'token_url'  => 'https://atlas.propertyfinder.com/v1/auth/token',
            'leads_url'   => $leads_url,


        ];

        // 4. Get Access Token
        $token_response = getApiToken($config['token_url'], $config['api_key'], $config['api_secret']);
        if (empty($token_response['accessToken'])) {
            throw new Exception('Failed to get access token. Response: ' . print_r($token_response, true));
        }
        $access_token = $token_response['accessToken'];

        // 5. Fetch WhatsApp Leads
        $leads_response = fetchWhatsAppLeads($config['leads_url'], $access_token);
//        var_dump($leads_response);
//        die();
        if (empty($leads_response['data'])) {
            $result['status'] = 'success';
            $result['message'] = 'No new leads found';
            return $result;
        }

        $leads = $leads_response['data'];
        $result['leads_received'] = count($leads);

        // 6. Process Leads
        $processed_leads = [];
        foreach ($leads as $lead) {
            try {
                $lead_id = $lead['id'] ?? null;
                if (empty($lead_id)) {
                    throw new Exception('Invalid lead ID');
                }

                // Check if lead already exists
                if (getBitrixDealByLeadId($lead_id)) {
                    continue;
                }
//                echo '<pre>';
//                var_dump($lead);
//                die();
                // Process lead
                $process_result = processSingleLead($lead, $access_token);
                $processed_leads[] = $process_result;
                $result['leads_processed']++;

            } catch (Exception $e) {
                $result['leads_failed']++;
                $result['error_details'][$lead_id] = $e->getMessage();
                continue;
            }
        }

        // 7. Prepare final result
        $result['status'] = 'success';
        $result['message'] = 'Leads processed successfully';
        $result['processed_leads'] = $processed_leads;
        $result['execution_time'] = date('Y-m-d H:i:s');

    } catch (Exception $e) {
        $result['status'] = 'error';
        $result['message'] = $e->getMessage();

        // Log to Bitrix
        CEventLog::Add([
            "SEVERITY"      => "ERROR",
            "AUDIT_TYPE_ID" => "PF_WHATSAPP_API",
            "MODULE_ID"     => "main",
            "DESCRIPTION"   => $e->getMessage(),
        ]);
    }

//    var_dump($result);
//    die();
    return $result;
}


// Helper Functions

function getApiToken($url, $key, $secret)
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'User-Agent: PostmanRuntime/7.44.1'
        ],
        CURLOPT_POSTFIELDS     => json_encode([
            "apiKey"    => $key,
            "apiSecret" => $secret
        ]),
    ]);

    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        throw new Exception("Token API Error: " . curl_error($ch));
    }
    curl_close($ch);

    return json_decode($response, true);
}

function fetchWhatsAppLeads($url, $token)
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 60,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
            'User-Agent: PostmanRuntime/7.44.1'
        ],
    ]);

    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        throw new Exception("Leads API Error: " . curl_error($ch));
    }
    curl_close($ch);

    return json_decode($response, true);
}

function getBitrixUserByEmail($email)
{
    if (empty($email)) return null;

    $user = CUser::GetList(
        ($by = "id"),
        ($order = "asc"),
        ["EMAIL" => $email],
        ["FIELDS" => ["ID"]]
    );
//    return var_dump($user->Fetch());
    return ($userData = $user->Fetch()) ? $userData['ID'] : null;
}

function processSingleLead($lead, $token)
{

    $user = fetchUserDetails($lead['publicProfile']['id'] ?? '', $token);
    $property = fetchPropertyDetails($lead['listing']['id'] ?? '', $token);
    $contact = new CCrmContact(false);
    $contactFields = [
        'NAME'  => $lead['sender']['name'] ?? 'WhatsApp Lead',
        'PHONE' => [['VALUE' => $lead['sender']['contacts'][0]['value'] ?? '', 'VALUE_TYPE' => 'WORK']],
//        'EMAIL' => [['VALUE' => $user['email'] ?? '', 'VALUE_TYPE' => 'WORK']],
    ];

    $result = $property['result']; // renamed to $result
    $contactId = $contact->Add($contactFields, true);
    if (!$contactId) {
        throw new Exception("Failed to create contact");
    }

    $result = $property['result'];

    $locationName = '';
    $locationFull = '';

    if (
        isset($property['location']['data'][0]) &&
        is_array($property['location']['data'][0])
    ) {
        $locationData = $property['location']['data'][0];

        if (isset($locationData['name'])) {
            $locationName = $locationData['name'];
        }

        if (isset($locationData['tree']) && is_array($locationData['tree'])) {
            $names = [];
            foreach ($locationData['tree'] as $item) {
                if (isset($item['name'])) {
                    $names[] = $item['name'];
                }
            }

            $locationFull = implode(' > ', $names);
        }
    }
    $comments =
        "Title: " . ($result['title']['en'] ?? '') . ' | ' .
        "Type: " . ($result['type'] ?? '') . ' | ' .
        "Bedrooms: " . ($result['bedrooms'] ?? '') . ' | ' .
        "Price: AED " . ($result['price']['amounts']['sale'] ?? '') . ' | ' .
        "Reference: " . ($result['reference'] ?? '') . ' | ' .
        'Location: ' . $locationName . ' (' . $locationFull . ')';

// Optional: trim to max 250 for Bitrix
    $comments = substr($comments, 0, 250);

// Convert \n to single-line safe text for Bitrix
    $comments = str_replace("\n", ' | ', $comments);

// Trim to safe length (Bitrix text fields are usually max 250–512 chars)
    $comments = substr($comments, 0, 500);

    $comments = htmlspecialchars($comments, ENT_QUOTES, 'UTF-8');
    $deal = new CCrmDeal(false);
    $dealFields = [
        'STAGE_ID'             => 'C2:NEW',
        'CONTACT_ID'           => $contactId ?? '',
        'TYPE_ID'              => 'SALE',
        'CATEGORY_ID'          => 2,
        'TITLE'                => 'PF Lead - ' . ($lead['sender']['contacts'][0]['value'] ?? ''),
        'SOURCE_ID'            => "UC_7LVVAZ",
        'UF_CRM_1742890231808' => $property['id'],
        'ASSIGNED_BY_ID'       => getBitrixUserByEmail($user['email'] ?? ''),
        'UF_CRM_1727321267282' => $property['offering_type'] ?? '',
        'UF_CRM_1645599456320' => $property['community'] ?? '',
        'UF_CRM_1730201029300' => $property['reference'] ?? '',
        'UF_CRM_1725942907'    => $property['category'] ?? '',
        'COMMENTS'             => $comments

    ];
//    echo '<pre>';
//    var_dump($dealFields);
//    die();
    $dealId = $deal->Add($dealFields, true);
    if (!$dealId) {
        throw new Exception("Failed to create deal");
    }

    return [
        'lead_id'    => $lead['id'],
        'contact_id' => $contactId,
//        'deal_id'    => $dealId,
        'phone'      => $lead['sender']['contacts'][0]['value'] ?? ''
    ];
}

function fetchUserDetails($profileId, $token)
{
    if (empty($profileId)) return [];

    $url = "https://atlas.propertyfinder.com/v1/users?publicProfileId=" . $profileId;
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
            'User-Agent: PostmanRuntime/7.44.1',
        ],
    ]);

    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        throw new Exception("User API Error: " . curl_error($ch));
    }
    curl_close($ch);

//    var_dump($response);
//    die();
    $data = json_decode($response, true);
    return $data['data'][0] ?? [];
}

function fetchPropertyDetails($listingId, $token)
{
    if (empty($listingId)) return [];

    $url = "https://atlas.propertyfinder.com/v1/listings?filter[ids]=" . $listingId;
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
            'User-Agent: PostmanRuntime/7.44.1'
        ],
    ]);

    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        throw new Exception("Property API Error: " . curl_error($ch));
    }
    curl_close($ch);

    $data = json_decode($response, true);
//    echo '<pre>';
//    var_dump($data);
//    exit();

    $result = $data['results'][0] ?? [];
    $locationId = $result['location']['id'];
    $url = "https://atlas.propertyfinder.com/v1/locations?filter[id]=" . $locationId;
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
            'User-Agent: PostmanRuntime/7.44.1'
        ],
    ]);

    $locationresponse = curl_exec($ch);
    if (curl_errno($ch)) {
        throw new Exception("Property API Error: " . curl_error($ch));
    }
    curl_close($ch);

    $locationData = json_decode($locationresponse, true);


    return [
        'location'      => $locationData,
        'result'        => $result,
        'id'            => $result['id'],
        'reference'     => $result['reference'] ?? '',
        'community'     => $locationData['data'][0]['type'] ?? '',
//        'sub_community' => $result['location']['sub_community'] ?? '',
        'category'      => $result['category'] ?? '',
        'type_name'     => $result['type'] ?? '',
        'offering_type' => $result['price']['type'] ?? ''
    ];
}

function getBitrixDealByLeadId($lead_id)
{
    if (empty($lead_id)) return null;

    $filter = ['UF_CRM_1742890231808' => $lead_id];
    $select = ['ID', 'TITLE'];
    $deals = CCrmDeal::GetList([], $filter, $select);
    return ($deal = $deals->Fetch()) ? $deal['ID'] : null;
}


function pf_call_api()
{


    // Step 1: Obtain Access Token
    $api_key = 'boBQv.PIA1BchUxHvah62Wwet7tEnpjlo0ZGeKdb';
    $api_secret = 'b8e1c99c9e2ec8b1d212d9a55ac1a156';
    $token_url = 'https://auth.propertyfinder.com/auth/oauth/v1/token';

    CModule::IncludeModule("crm");
    $contact = new CCrmContact();
    $deal = new CCrmDeal();
    global $USER;

    function getBitrixUserByEmail($email)
    {
        if (CModule::IncludeModule("intranet")) {
            $userFilter = ["=EMAIL" => $email];
            $dbUsers = CUser::GetList(($by = "ID"), ($order = "ASC"), $userFilter);
            if ($arUser = $dbUsers->Fetch()) {
                $userId = $arUser['ID'];
            }
            return $userId;
        }
        return null; // Return null if the intranet module is not included
    }

    function getBitrixDealByLeadId($lead_id)
    {
        $filter = [
            'UF_CRM_1742890231808' => $lead_id
        ];
        $select = ['ID', 'TITLE'];

        $deals = CCrmDeal::GetList([], $filter, $select);
        if ($deal = $deals->Fetch()) {
            return $deal['ID']; // Deal already exists, return ID
        }
        return null; // No existing deal found
    }

    $credentials = base64_encode("$api_key:$api_secret");
    $data = json_encode(['scope' => 'openid', 'grant_type' => 'client_credentials']);

    $ch = curl_init($token_url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Basic ' . $credentials,
            'Content-Type: application/json'
        ],
        CURLOPT_POSTFIELDS     => $data,
    ]);
    $token_response = curl_exec($ch);
    if (curl_errno($ch) || !$token_response) {
        die('Token request failed: ' . curl_error($ch));
    }
    curl_close($ch);

    $token_data = json_decode($token_response, true);

    if (empty($token_data['access_token'])) {
        die('Failed to obtain access token');
    }
    $access_token = $token_data['access_token'];


// Step 2: Make GET request to the API
    $api_url = "https://api-v2.mycrm.com/calltrackings?sort=id&sort_order=DESC";

    $curl = curl_init($api_url);
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $access_token,
            'Content-Type: application/json'
        ],
    ]);
    $response_data = curl_exec($curl);
    curl_close($curl);

    $decoded_data = json_decode($response_data, true);

    if (empty($decoded_data['call_trackings'])) {
        die('No leads data retrieved');
    }

    foreach ($decoded_data['call_trackings'] as $call_trackings) {

        $lead_id = $call_trackings['id'];
        //print_r($lead);
        $existingDealId = getBitrixDealByLeadId($lead_id);

        if ($existingDealId) {
            die();
        } else {
            $createdDate = new DateTime($call_trackings['created_at']);
            $currentDate = new DateTime();
            $minutesDifference = $currentDate->diff($createdDate)->days * 24 * 60
                + $currentDate->diff($createdDate)->h * 60
                + $currentDate->diff($createdDate)->i;


// Fetch user details
            $user_id = $call_trackings['user']['id'];
            $user_api = "https://api-v2.mycrm.com/users/" . $user_id;
            $ucurl = curl_init($user_api);
            curl_setopt_array($ucurl, [
                CURLOPT_CUSTOMREQUEST  => "GET",
                CURLOPT_HTTPHEADER     => [
                    'Authorization: Bearer ' . $access_token,
                    'Content-Type: application/json'
                ],
                CURLOPT_RETURNTRANSFER => true
            ]);
            $uresponse_data = curl_exec($ucurl);
            $udecoded_data = json_decode($uresponse_data, true);
            curl_close($ucurl);

            // Extract user details
            $ufname = $udecoded_data['user']['public']['first_name'] ?? '';
            $ulname = $udecoded_data['user']['public']['last_name'] ?? '';
            $uemail = $udecoded_data['user']['public']['email'] ?? '';


            // Get property details
            $prop_id = $call_trackings['property']['id'];
            $prop_api = "https://api-v2.mycrm.com/properties/" . $prop_id;
            $pcurl = curl_init($prop_api);
            curl_setopt_array($pcurl, [
                CURLOPT_CUSTOMREQUEST  => "GET",
                CURLOPT_HTTPHEADER     => [
                    'Authorization: Bearer ' . $access_token,
                    'Content-Type: application/json'
                ],
                CURLOPT_RETURNTRANSFER => true
            ]);
            $presponse_data = curl_exec($pcurl);
            $pdecoded_data = json_decode($presponse_data, true);
            curl_close($pcurl);


            $propref = $pdecoded_data['property']['reference'];

            $location = ($pdecoded_data['property']['location']['community'] ?? '') . ', ' .
                ($pdecoded_data['property']['location']['sub_community'] ?? '');
            $propertytype = ($pdecoded_data['property']['type']['category'] ?? '') . '-' .
                ($pdecoded_data['property']['type']['name'] ?? '');
            $lookingfor = $pdecoded_data['property']['price']['offering_type'] ?? '';

            $callcontactFields = [
                'NAME'   => $call_trackings['phone'],                      // Contact's first name
                'PHONE'  => [['VALUE' => $call_trackings['phone'], 'VALUE_TYPE' => 'WORK']],
                'MOBILE' => [['VALUE' => $call_trackings['mobile'], 'VALUE_TYPE' => 'MOBILE']],

            ];

            $callcontactId = $contact->Add($callcontactFields);


// Example usage

            $current_user = getBitrixUserByEmail($uemail);

            $calldealFields = [
                'STAGE_ID'             => 'C2:NEW',
                'CONTACT_ID'           => $callcontactId,
                'TYPE_ID'              => 'SALE',
                'CATEGORY_ID'          => 2,
                'TITLE'                => 'PF' . '_' . $call_trackings['phone'],
                'NAME'                 => $call_trackings['phone'],
                'PHONE'                => [['VALUE' => $call_trackings['phone'], 'VALUE_TYPE' => 'WORK']],
                'MOBILE'               => [['VALUE' => $call_trackings['mobile'], 'VALUE_TYPE' => 'MOBILE']],
                'SOURCE_ID'            => "UC_7LVVAZ",
                'UF_CRM_1727321267282' => $lookingfor,
                'UF_CRM_1645599456320' => $location,
                'UF_CRM_1730201029300' => $propref,
                'UF_CRM_1725942907'    => $propertytype,
                'UF_CRM_1742890231808' => $lead_id,
                'UF_CRM_1724152083075' => $call_trackings['preferences'][0]['locations'][0]['id'],
                'COMMENTS'             => $call_trackings['notes'][0] ?? '',
                'ASSIGNED_BY_ID'       => $current_user,
            ];

//            $calldealId = $deal->Add($calldealFields);
        }
    }
    return "pf_call_api();";
}

function WhatsApp_Activity_Notification()
{

    CModule::IncludeModule('bit.notification.activity');

    Bit\Notification\Activity\Agent::runAgent();

    return "WhatsApp_Activity_Notification();";
}

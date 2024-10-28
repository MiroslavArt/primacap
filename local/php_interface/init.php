<?
$eventManager = \Bitrix\Main\EventManager::getInstance();

//Prohibition of editing lead fields
if(file_exists($_SERVER['DOCUMENT_ROOT'] . '/local/applications/prohibiting_editing_fields/function.php')){
	require_once $_SERVER['DOCUMENT_ROOT'] . '/local/applications/prohibiting_editing_fields/function.php';
	$eventManager->addEventHandlerCompatible("crm", "OnBeforeCrmLeadUpdate", "control_field_access");
}

//Registering extensions
$arRegisterConfig = array( 
    'hide_stage_lead_kanban' => [
        'js' => '/local/php_interface/js/hide_stage_lead_kanban.js', 
        //'css' => '', 
        'rel' => [], 
    ], 
); 
foreach ($arRegisterConfig as $ext => $arExt) { 
    \CJSCore::RegisterExt($ext, $arExt); 
}

//Initiating changes in crm
if (CSite::InDir('/crm/lead/kanban/')){
    CUtil::InitJSCore(['hide_stage_lead_kanban']);
}

//Class autoloader and Event logger
if(file_exists(__DIR__ . '/handlers/init.php')){
	include_once __DIR__ . '/handlers/init.php';
	\Bit\Custom\EventHandlers::registerEventHandler();
}
    function Sync_Office_Attandance()
    {
global $DB;

    $fromDate = date('Y-m-d',strtotime("-1 days"));
    $toDate = date('Y-m-d',strtotime("-1 days"));

    $ch = curl_init();
    $url = "http://primocapital.mywire.org:85/api/v2/WebAPI/GetDeviceLogs";
    $queueData = array('APIKey' => "065710072320",'FromDate' => $fromDate ,'ToDate' => $toDate);
    $data = http_build_query($queueData);
    $getUrl = $url."?".$data;
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_URL, $getUrl);
    curl_setopt($ch, CURLOPT_TIMEOUT, 80);
    $response = curl_exec($ch);
    $respArr  = json_decode($response,true);
    
    if($e = curl_error($ch)){
    echo $e;
     } else {
 $respArr  = json_decode($response, true);
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
        'lead_conversion' => $leadconvRes['lead_convertion_count'],
        'lead_conversion_percentage' => $leadconvPercentage,
        'attendance' => $timeSumper,
        'office_attendance' => $oftimeRes['WorkingHours'],
        'activities' => $stractper,
        'reactiveness' => $proactRes['proactivities_count'],
        'total_score' => $totalscore
    ];
}
function auto_lead_boost()
    {

global $DB;
$strSql = "Select ID,NAME,SECOND_NAME,LAST_NAME,EMAIL,c.UF_DEPARTMENT AS DEP,c.UF_OFFICE_ATTN from b_user a,b_user_group b,b_uts_user c where a.active ='Y' and a.LID ='s1' and a.ID = b.USER_ID and b.GROUP_ID='11' and c.VALUE_ID=a.ID and a.ID NOT IN (1,27,1709,1013,4817,4818)  ORDER BY a.ID ASC";
$dbRes = $DB->Query($strSql);
while($arRes = $dbRes->Fetch())
{
$agentId = $arRes['ID'];
$metrics = calculateAgentMetrics($agentId);

$strBostSql = $DB->Query("Select VALUE_ID  from b_uts_user where VALUE_ID = '".$agentId."' and UF_BOOST_LEAD ='675' and UF_BOOST_VALID >= NOW()");
$dbBostRes = $strBostSql->Fetch();

if($metrics['lead_conversion'] > '2' && $dbBostRes['VALUE_ID'] !=$agentId )
{

global $USER;

	CModule::IncludeModule("iblock");
	CModule::IncludeModule("bizproc");
	CModule::IncludeModule("crm");

           $PROP = array();
			$PROP['BOOST_REQUESTED_BY'] = $agentId;


$IblockFields = array(
			  "IBLOCK_SECTION_ID" => false,         
			  "IBLOCK_ID"      => 45,
			"PROPERTY_VALUES"=> $PROP,
			  "NAME"           => "Boost Approval #".$agentId,
			  "ACTIVE"         => "Y",           
			  "PREVIEW_TEXT"   => "",
			  "DETAIL_TEXT"    => "",

			  );
			$IBLOCKELEMENT = new CIBlockElement();
			$element_id = $IBLOCKELEMENT->Add($IblockFields);
			if($element_id)
			{
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
function WhatsApp_Activity_Notification()
    {

CModule::IncludeModule('bit.notification.activity');

Bit\Notification\Activity\Agent::runAgent();

return "WhatsApp_Activity_Notification();";
    }
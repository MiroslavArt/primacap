<?php
include($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");
   \Bitrix\Main\UI\Extension::load("ui.tooltip");
global $DB,$USER;

$current_user=getBitrixUserSubEmployees($USER->GetID());

//if(!$USER->IsAdmin() && empty($current_user)) {echo "Access Denied"; die();}

if(!empty($current_user)) {
 $sub = implode(', ', $current_user);
}
 $cuser=$USER->GetID();

$currentdate=date('m-d-Y');
if($_REQUEST['fromdate']!='')
{
$fromdate=$_REQUEST['fromdate']." 00:00:00";
$todate=$_REQUEST['todate']." 23:59:59";
}

function hoursToSeconds($hours) {
    // Split hours into hours and minutes
    $parts = explode(':', $hours);

    // Extract hours and minutes
    $hours = (int)$parts[0];
    $minutes = (int)$parts[1];

    // Calculate total seconds
    $seconds = ($hours * 3600) + ($minutes * 60);

    return $seconds;
}
function secondsToHoursMinutes($seconds) { 
    $hours = floor($seconds / 3600); 
    $minutes = floor(($seconds % 3600) / 60); 
    return [ 
        'hours'   => $hours, 
        'minutes' => $minutes, 
    ]; 
} 
if(!$USER->IsAdmin() && !empty($current_user)){
$strSql = "Select ID,NAME,SECOND_NAME,LAST_NAME,EMAIL,c.UF_DEPARTMENT AS DEP,c.UF_OFFICE_ATTN from b_user a,b_user_group b,b_uts_user c where a.active ='Y' and a.LID ='s1' and a.ID NOT IN (1,27,1709,1013,4817,4818) and a.ID = b.USER_ID and b.GROUP_ID='11' and c.VALUE_ID=a.ID and a.ID IN (".$sub.")  ORDER BY a.ID ASC";
}
elseif(!$USER->IsAdmin() && empty($current_user)){
$strSql = "Select ID,NAME,SECOND_NAME,LAST_NAME,EMAIL,c.UF_DEPARTMENT AS DEP,c.UF_OFFICE_ATTN from b_user a,b_user_group b,b_uts_user c where a.active ='Y' and a.LID ='s1' and a.ID NOT IN (1,27,1709,1013,4817,4818) and a.ID = b.USER_ID and b.GROUP_ID='11' and c.VALUE_ID=a.ID and a.ID = ".$cuser."  ORDER BY a.ID ASC";
}
else
{
$strSql = "Select ID,NAME,SECOND_NAME,LAST_NAME,EMAIL,c.UF_DEPARTMENT AS DEP,c.UF_OFFICE_ATTN from b_user a,b_user_group b,b_uts_user c where a.active ='Y' and a.LID ='s1' and a.ID NOT IN (1,27,1709,1013,4817,4818) and a.ID = b.USER_ID  and b.GROUP_ID='11' and c.VALUE_ID=a.ID and a.ID ORDER BY a.ID ASC";
}
$dbRes = $DB->Query($strSql);



 global $APPLICATION;

	$GLOBALS['APPLICATION']->SetAdditionalCSS('/local/components/bitrix/agent.score/templates/.default/datatables.min.css');

	$GLOBALS['APPLICATION']->AddHeadScript('/local/components/bitrix/agent.score/templates/.default/datatables.min.js');
?>

<h2>Agent Report</h2>
<style>
table#score tr:hover {background-color: #dadcda;cursor: pointer;}
table#score > tbody > tr.view:nth-child(even) {
  background: #eee;
}
input[type=date] {
  padding: 10px 8px;
  margin: 8px 0;
  display: inline-block;
  border: 1px solid #ccc;
  border-radius: 4px;
  box-sizing: border-box;
}

input[type=submit] {
  background-color: #4CAF50;
  color: white;
  padding: 11px 20px;
  margin: 8px 0;
  border: none;
  border-radius: 4px;
  cursor: pointer;
}

input[type=submit]:hover {
  background-color: #45a049;
}
input[type=reset] {
  background-color: #ff4444;
  color: white;
  padding: 11px 20px;
  margin: 8px 0;
  border: none;
  border-radius: 4px;
  cursor: pointer;
}

input[type=reset]:hover {
  background-color: #ff4e4e;
}
 .tooltiptext {
            visibility: hidden;
  width: 85px;
  background-color: #2263b9;
  color: #fff;
  text-align: left;
    font-size: 13px;
    font-weight: 200;
  padding: 2px 5px 5px;
  border-radius: 5px;

  position: absolute;
  z-index: 1;
        }
 </style>
<form name="frmSearch" method="post" action="">
	 <p class="search_input">
		<input type="date" name="fromdate"  class="input-control" value="<?=$fromdate;?>" />
	    <input type="date"  name="todate" style="margin-left:10px"  class="input-control" max="<?=$currentdate;?>" value="<?=$todate;?>"  />
		<input type="submit" name="go" class="btn" value="Filter" >
        <input type="reset" name="reset" value="Reset" onClick="window.location.reload()">
	</p>
</form>

<table id="score" border='0'  cellpadding='8' cellspacing='0' width='100%'>
<thead>
<tr bgcolor='#8d9089'>
<th align='center' style='border:1px #f5f9f9 solid; color:#fff;'>Employee</th>
<th align='center' style='border:1px #f5f9f9 solid; color:#fff;'>Bitrix Timing</th>
<th align='center' style='border:1px #f5f9f9 solid; color:#fff;'>Office Attendance</th>
<th align='center' style='border:1px #f5f9f9 solid; color:#fff;'>Activities</th>
<th align='center' style='border:1px #f5f9f9 solid; color:#fff;'>Reactiveness</th>
<th align='center' style='border:1px #f5f9f9 solid; color:#fff;'>Won Leads</th>
<th align='center' style='border:1px #f5f9f9 solid; color:#fff;'>Active Leads</th>
<th align='center' style='border:1px #f5f9f9 solid; color:#fff;'>Junk Leads</th>
<th align='center' style='border:1px #f5f9f9 solid; color:#fff;'>Total Leads</th>
<th align='center' style='border:1px #f5f9f9 solid; color:#fff;'>Total Assigned Leads</th>
<th align='center' style='border:1px #f5f9f9 solid; color:#fff;'>Score</th>
</tr>
</thead>
<?
while($arRes = $dbRes->Fetch())
{

if($fromdate!=''){
/*------------------------Convertion Ratio------------------------------*/
$leadconvSql = $DB->Query("select count(*) as lead_convertion_count from b_crm_lead where ASSIGNED_BY_ID = '".$arRes['ID']."' and STATUS_ID = 'CONVERTED' and DATE_MODIFY between '".$fromdate."' and '".$todate."' ");
$leadconvRes = $leadconvSql->Fetch();
if(!$USER->IsAdmin() && !empty($current_user)){
$leadconvSqltot = $DB->Query("select count(*) as lead_convertion_count from b_crm_lead where ASSIGNED_BY_ID IN (".$sub.") and STATUS_ID = 'CONVERTED' and DATE_MODIFY between '".$fromdate."' and '".$todate."'");
}
elseif(!$USER->IsAdmin() && empty($current_user)){
$leadconvSqltot = $DB->Query("select count(*) as lead_convertion_count from b_crm_lead where ASSIGNED_BY_ID = ".$cuser." and STATUS_ID = 'CONVERTED' and DATE_MODIFY between '".$fromdate."' and '".$todate."'");
}
else
{
$leadconvSqltot = $DB->Query("select count(*) as lead_convertion_count from b_crm_lead where STATUS_ID = 'CONVERTED' and DATE_MODIFY between '".$fromdate."' and '".$todate."' ");
}
$leadconvRestot = $leadconvSqltot->Fetch();

if($leadconvRestot['lead_convertion_count'] > 0)
{
$conversionPercentage = ($leadconvRes['lead_convertion_count'] / $leadconvRestot['lead_convertion_count']) * 60;
$leadconv = number_format($conversionPercentage, 2);
}

// Fetch total sum of all lead conversion counts
$totalLeadConversionsSql = $DB->Query("SELECT COUNT(*) AS total_lead_convertion_count FROM b_crm_lead WHERE STATUS_ID = 'CONVERTED' and DATE_MODIFY between '".$fromdate."' and '".$todate."' ");
$totalLeadConversionsRes = $totalLeadConversionsSql->Fetch();
$totalLeadConversions = $totalLeadConversionsRes['total_lead_convertion_count'];



/*------------------------Won Lead Score------------------------------*/
$leadconvnoSql = $DB->Query("select UF_CRM_1720352836,UF_CRM_1720352792,UF_CRM_1720353030 from b_crm_lead a, b_uts_crm_lead b where a.ASSIGNED_BY_ID = '".$arRes['ID']."' and a.STATUS_ID = 'CONVERTED' and a.ID = b.VALUE_ID  and a.DATE_MODIFY between '".$fromdate."' and '".$todate."' ");
$leadconvnoRes = $leadconvnoSql->Fetch();
// Assume $leadconvnoRes is already fetched from the database as shown in your query
$salesAmountAED = $leadconvnoRes['UF_CRM_1720352792'];
$salesAmount = explode('|', $salesAmountAED);
$salesAmountvalue = (float) $salesAmount[0]; // Convert to float for calculations

$googleReviewStatus = $leadconvnoRes['UF_CRM_1720353030'];

$mainTarget = 100000;
$minTarget = 10000;
$extraPointThreshold = 4000;
$positiveReviewBonusFactor = 1.5; 
$maxScore = 50;

// Calculate base score based on new conditions
if ($salesAmountvalue >= $mainTarget) {
    // Total Sales greater than or equal to 100,000
    if ($googleReviewStatus == 690) {
        $baseScore = 50; // Base score 50 if positive review
    } else {
        $baseScore = 25; // Base score 25 if not positive review
    }
} elseif ($salesAmountvalue >= $minTarget) {
    // Total Sales greater than 10,000 and less than 100,000
    if ($googleReviewStatus == 690) {
        $baseScore = 25; // Base score 25 if positive review
    } else {
        $baseScore = 0; // Base score 0 if not positive review
    }
} else {
    // Total Sales less than 10,000
    $baseScore = 0; // Base score 0 regardless of review
}

// Calculate extra points for sales above 10,000 AED
if ($salesAmountvalue > $minTarget) {
    $extraPoints = floor(($salesAmountvalue - $minTarget) / $extraPointThreshold);
    $finalWonScore = $baseScore + $extraPoints;
} else {
    $finalWonScore = $baseScore;
}

// Ensure the final score does not exceed the max score
if ($finalWonScore > $maxScore) {
    $finalWonScore = $maxScore;
}

/*------------------------Won Lead Score------------------------------*/

/*------------------------Attendance------------------------------*/
$strtimeSql = $DB->Query("Select sum(TIMESTAMPDIFF(SECOND,DATE_START,DATE_FINISH)) AS score_time from b_timeman_entries where USER_ID = '".$arRes['ID']."' and DATE_START between '".$fromdate."' and '".$todate."' and DATE_FINISH between '".$fromdate."' and '".$todate."'");
$timeRes = $strtimeSql->Fetch();

if(!$USER->IsAdmin() && !empty($current_user)){
$strtimeSqltot = $DB->Query("Select sum(TIMESTAMPDIFF(SECOND,DATE_START,DATE_FINISH)) AS score_time from b_timeman_entries where USER_ID IN (".$sub.") and DATE_START between '".$fromdate."' and '".$todate."' and DATE_FINISH between '".$fromdate."' and '".$todate."'");
}
elseif(!$USER->IsAdmin() && empty($current_user)){
$strtimeSqltot = $DB->Query("Select sum(TIMESTAMPDIFF(SECOND,DATE_START,DATE_FINISH)) AS score_time from b_timeman_entries where USER_ID = ".$cuser." and DATE_START between '".$fromdate."' and '".$todate."' and DATE_FINISH between '".$fromdate."' and '".$todate."'");
}
else
{
$strtimeSqltot = $DB->Query("Select sum(TIMESTAMPDIFF(SECOND,DATE_START,DATE_FINISH)) AS score_time from b_timeman_entries where  DATE_START between '".$fromdate."' and '".$todate."' and DATE_FINISH between '".$fromdate."' and '".$todate."'");
}
$timeRestot = $strtimeSqltot->Fetch();

$timeduration = secondsToHoursMinutes($timeRes['score_time']); 

if($timeRestot['score_time'] > 0)
{
 $timeResPercentage = ($timeRes['score_time'] / $timeRestot['score_time']) * 10;
$timeSumper = number_format($timeResPercentage, 2);
}

/*------------------------Office Attendance------------------------------*/
$stroftimeSql = $DB->Query("SELECT 
    USER_ID,
    SEC_TO_TIME(SUM(TIMESTAMPDIFF(SECOND, MIN_LOG_DATE, AdjustedPunchOut))) AS WorkingHours
FROM (
    SELECT 
        USER_ID,
        DATE(LOG_DATE) AS WorkDate,
        MIN(LOG_DATE) AS MIN_LOG_DATE,
        MAX(LOG_DATE) AS MAX_LOG_DATE,
        COUNT(LOG_DATE) AS EntryCount,
        CASE
            WHEN COUNT(LOG_DATE) % 2 = 1 THEN MAX(LOG_DATE) -- If count is odd
            ELSE MAX(LOG_DATE) -- If count is even, use logic here if different
        END AS AdjustedPunchOut
    FROM 
        c_office_attandance
    WHERE 
        USER_ID = '".$arRes['UF_OFFICE_ATTN']."' and LOG_DATE between '".$fromdate."' and '".$todate."'
    GROUP BY 
        USER_ID, WorkDate
    HAVING 
        MIN(LOG_DATE) IS NOT NULL AND MAX(LOG_DATE) IS NOT NULL
) AS DailyLogs
GROUP BY 
    USER_ID");
	$oftimeRes = $stroftimeSql->Fetch();

	$ofsec=hoursToSeconds($oftimeRes['WorkingHours']);
	$oftimeduration = secondsToHoursMinutes($ofsec); 



/*----------------Attandance Score--------------------*/
$attendSql = $DB->Query("SELECT 
    DATE(LOG_DATE) AS date,
    MIN(CASE WHEN TIME(LOG_DATE) < '10:00:00' THEN LOG_DATE END) AS punch_in,
     CASE
        WHEN COUNT(LOG_DATE) % 2 = 1 THEN -- If count is odd
            MAX(LOG_DATE)
        ELSE -- If count is even
            MAX(LOG_DATE)
    END AS punch_out
FROM 
    c_office_attandance
    WHERE 
        USER_ID = '".$arRes['UF_OFFICE_ATTN']."'   and LOG_DATE between '".$fromdate."' and '".$todate."'
    GROUP BY 
    DATE(LOG_DATE)
HAVING 
    punch_in IS NOT NULL
    AND TIMEDIFF(MAX(LOG_DATE), punch_in) >= '03:00:00'");

$attendance_days = 0;

while ($attendRes = $attendSql->Fetch()) {
    if ($attendRes['punch_in'] && $attendRes['punch_out']) {
        $attendance_days++;
    }
}

// Calculate the score
$full_points = 15;
$required_days = 15;

if ($attendance_days >= $required_days) {
    $attendscore = $full_points;
} else {
    $attendscore = ($attendance_days / $required_days) * $full_points;
}
/*----------------Attandance Score--------------------*/
/*------------------------Activities------------------------------*/
$stractSql = $DB->Query("Select count(*) as activities_count from b_crm_act where RESPONSIBLE_ID = '".$arRes['ID']."' and OWNER_TYPE_ID ='1' and CREATED between '".$fromdate."' and '".$todate."'");
$actRes = $stractSql->Fetch();
if(!$USER->IsAdmin() && !empty($current_user)){
$stractSqltot = $DB->Query("Select count(*) as activities_count from b_crm_act where RESPONSIBLE_ID IN (".$sub.") and OWNER_TYPE_ID ='1' and CREATED between '".$fromdate."' and '".$todate."'");
}
elseif(!$USER->IsAdmin() && empty($current_user)){
$stractSqltot = $DB->Query("Select count(*) as activities_count from b_crm_act where RESPONSIBLE_ID = ".$cuser." and OWNER_TYPE_ID ='1' and CREATED between '".$fromdate."' and '".$todate."'");
}
else
{
$stractSqltot = $DB->Query("Select count(*) as activities_count  from b_crm_act where OWNER_TYPE_ID ='1' and CREATED between '".$fromdate."' and '".$todate."'");
}

$actRestot = $stractSqltot->Fetch();

if($actRestot['activities_count'] > 0)
{
$stractper = number_format(($actRes['activities_count'] / $actRestot['activities_count'] * 15), 2)."%\n";
}
/*------------------------Activities Score------------------------------*/
$ActiveleadSql = $DB->Query("Select count(*) as act_lead_count from b_crm_lead where ASSIGNED_BY_ID = '".$arRes['ID']."' and STATUS_ID IN ('UC_9WUJ49','UC_23YNYD','UC_TCX0EY','IN_PROCESS','UC_OD8Y57','UC_TB6VLO','UC_6QWK0K',7,'UC_U2UJ60') and DATE_MODIFY between '".$fromdate."' and '".$todate."' ");
$ActiveleadSqlRes = $ActiveleadSql->Fetch();

$activities_count = $actRes['activities_count'];
$act_lead_count = $ActiveleadSqlRes['act_lead_count'];
		// Calculate the target
$target = $act_lead_count * 10;

// Calculate the score
if ($target > 0) {
    $score = ($activities_count / $target) * 20;
    // Ensure the score does not exceed the maximum of 20
    $Activityscore = min($score, 20);
} else {
    $Activityscore = 0; // Avoid division by zero
}

$Activityscore = round($Activityscore);


/*------------------------Activities Score------------------------------*/
/*------------------------Proactiveness------------------------------*/
$strproactSql = $DB->Query("Select count(*) as proactivities_count from c_distribution_lead_missing where USER_ID = '".$arRes['ID']."'  and MISSED_AT between '".$fromdate."' and '".$todate."' ");
$proactRes = $strproactSql->Fetch();
if(!$USER->IsAdmin() && !empty($current_user)){
$strproactSqltot = $DB->Query("Select count(*) as proactivities_count from  c_distribution_lead_missing where USER_ID IN (".$sub.") and MISSED_AT between '".$fromdate."' and '".$todate."'");
}
elseif(!$USER->IsAdmin() && empty($current_user)){
$strproactSqltot = $DB->Query("Select count(*) as proactivities_count from  c_distribution_lead_missing where USER_ID = ".$cuser." and MISSED_AT between '".$fromdate."' and '".$todate."'");
}
else{
$strproactSqltot = $DB->Query("Select count(*) as proactivities_count from  c_distribution_lead_missing where MISSED_AT between '".$fromdate."' and '".$todate."'");
}

$proactRestot = $strproactSqltot->Fetch();

if($proactRestot['proactivities_count'] > 0)
{
$proactper = number_format(($proactRes['proactivities_count'] / $proactRestot['proactivities_count'] * 15), 2)."%\n";
}
/*------------------------Proactiveness Score------------------------------*/
$strtotleadAssignedSql = $DB->Query("Select count(*) as tot_lead_assigned_count from b_crm_lead_status_history  where RESPONSIBLE_ID = '".$arRes['ID']."' and STATUS_ID IN ('UC_9WUJ49') and CREATED_DATE between '".$fromdate."' and '".$todate."'");
$strtotleadAssignedRestot = $strtotleadAssignedSql->Fetch();


 $totAssigned= $strtotleadAssignedRestot['tot_lead_assigned_count'];
$proactivities=$proactRes['proactivities_count'];

		// Calculate the percentage
if ($totAssigned > 0) {
    $percentage = ($proactivities / $totAssigned) * 100;
} else {
    // Handle division by zero or unexpected cases where $totAssigned might be zero
    $percentage = 0;
}
$percentage = round($percentage);
// Determine score based on percentage range
if ($percentage == 100) {
    $proscore = 15; // 100% from $totAssigned: 15 points
} elseif ($percentage >= 90 && $percentage <= 99) {
   $proscore = 11; // 90-99% from $totAssigned: 11 points
} elseif ($percentage >= 75 && $percentage <= 89) {
    $proscore = 7.5; // 75-89% from $totAssigned: 7.5 points
} else {
    $proscore = 0; // Default score if none of the conditions match
}

$proscore = min($proscore, 15);
/*------------------------Proactiveness Score------------------------------*/
/*------------------------Active Leads------------------------------*/
$stractleadSql = $DB->Query("Select count(*) as act_lead_count from b_crm_lead where ASSIGNED_BY_ID = '".$arRes['ID']."' and STATUS_ID IN ('UC_9WUJ49','UC_23YNYD','UC_TCX0EY','IN_PROCESS','UC_OD8Y57','UC_TB6VLO','UC_6QWK0K',7,'UC_U2UJ60') and DATE_MODIFY between '".$fromdate."' and '".$todate."' ");
$stractleadRes = $stractleadSql->Fetch();

$stractleadSqltot = $DB->Query("Select count(*) as act_lead_count from b_crm_lead where STATUS_ID IN ('UC_9WUJ49','UC_23YNYD','UC_TCX0EY','IN_PROCESS','UC_OD8Y57','UC_TB6VLO','UC_6QWK0K',7,'UC_U2UJ60') and DATE_MODIFY between '".$fromdate."' and '".$todate."'");
$stractleadRestot = $stractleadSqltot->Fetch();

if($stractleadRestot['act_lead_count'] > 0)
{
$stractleadper = number_format(($stractleadRes['act_lead_count'] / $stractleadRestot['act_lead_count'] * 10), 2)."%\n";
}
/*------------------------Junk Leads------------------------------*/
$strjunkleadSql = $DB->Query("Select count(*) as junk_lead_count from b_crm_lead where ASSIGNED_BY_ID = '".$arRes['ID']."' and STATUS_ID IN ('JUNK',1,2,3,4,5,6) and DATE_MODIFY between '".$fromdate."' and '".$todate."'");
$strjunkleadRes = $strjunkleadSql->Fetch();

$strjunkleadSqltot = $DB->Query("Select count(*) as junk_lead_count from b_crm_lead where STATUS_ID IN ('JUNK',1,2,3,4,5,6) and DATE_MODIFY between '".$fromdate."' and '".$todate."'");
$strjunkleadRestot = $strjunkleadSqltot->Fetch();


if($strjunkleadRestot['junk_lead_count'] > 0)
{
$strjunkleadper = number_format(($strjunkleadRes['junk_lead_count'] / $strjunkleadRestot['junk_lead_count'] * 10), 2)."%\n";
}
/*------------------------Total Leads------------------------------*/
$strtotleadSql = $DB->Query("Select count(*) as tot_lead_count from b_crm_lead where ASSIGNED_BY_ID = '".$arRes['ID']."' and STATUS_ID IN ('UC_9WUJ49','UC_23YNYD','UC_TCX0EY','IN_PROCESS','UC_OD8Y57','UC_TB6VLO','UC_6QWK0K',7,'UC_U2UJ60','JUNK',1,2,3,4,5,6,'CONVERTED') and DATE_MODIFY between '".$fromdate."' and '".$todate."' ");
$strtotleadRes = $strtotleadSql->Fetch();

$strtotleadSqltot = $DB->Query("Select count(*) as tot_lead_count from b_crm_lead where STATUS_ID IN ('UC_9WUJ49','UC_23YNYD','UC_TCX0EY','IN_PROCESS','UC_OD8Y57','UC_TB6VLO','UC_6QWK0K',7,'UC_U2UJ60','JUNK',1,2,3,4,5,6,'CONVERTED') and DATE_MODIFY between '".$fromdate."' and '".$todate."'");
$strtotleadRestot = $strtotleadSqltot->Fetch();

if($stractleadRestot['act_lead_count'] > 0)
{
$stractleadper = number_format(($stractleadRes['act_lead_count'] / $stractleadRestot['act_lead_count'] * 10), 2)."%\n";
}
/*------------------------Total Score------------------------------*/
$totalscore = $finalWonScore+$attendscore+$Activityscore+$proscore;
	}else
	{
		/*------------------------Convertion Ratio------------------------------*/
$leadconvSql = $DB->Query("select count(*) as lead_convertion_count from b_crm_lead where ASSIGNED_BY_ID = '".$arRes['ID']."' and STATUS_ID = 'CONVERTED'");
$leadconvRes = $leadconvSql->Fetch();
if(!$USER->IsAdmin() && !empty($current_user)){
$leadconvSqltot = $DB->Query("select count(*) as lead_convertion_count from b_crm_lead where ID IN (".$sub.") and STATUS_ID = 'CONVERTED'");
}
elseif(!$USER->IsAdmin() && empty($current_user)){
$leadconvSqltot = $DB->Query("select count(*) as lead_convertion_count from b_crm_lead where ID = ".$cuser." and STATUS_ID = 'CONVERTED'");
}else{
$leadconvSqltot = $DB->Query("select count(*) as lead_convertion_count from b_crm_lead where STATUS_ID = 'CONVERTED'");
}
/*------------------------Won Lead Score------------------------------*/
$leadconvnoSql = $DB->Query("select UF_CRM_1720352836,UF_CRM_1720352792,UF_CRM_1720353030 from b_crm_lead a, b_uts_crm_lead b where a.ASSIGNED_BY_ID = '".$arRes['ID']."' and a.STATUS_ID = 'CONVERTED' and a.ID = b.VALUE_ID");
$leadconvnoRes = $leadconvnoSql->Fetch();
// Assume $leadconvnoRes is already fetched from the database as shown in your query
$salesAmountAED = $leadconvnoRes['UF_CRM_1720352792'];
$salesAmount = explode('|', $salesAmountAED);
$salesAmountvalue = (float) $salesAmount[0]; // Convert to float for calculations

$googleReviewStatus = $leadconvnoRes['UF_CRM_1720353030'];

$mainTarget = 100000;
$minTarget = 10000;
$extraPointThreshold = 4000;
$positiveReviewBonusFactor = 1.5; 
$maxScore = 50;

// Calculate base score based on new conditions
if ($salesAmountvalue >= $mainTarget) {
    // Total Sales greater than or equal to 100,000
    if ($googleReviewStatus == 690) {
        $baseScore = 50; // Base score 50 if positive review
    } else {
        $baseScore = 25; // Base score 25 if not positive review
    }
} elseif ($salesAmountvalue >= $minTarget) {
    // Total Sales greater than 10,000 and less than 100,000
    if ($googleReviewStatus == 690) {
        $baseScore = 25; // Base score 25 if positive review
    } else {
        $baseScore = 0; // Base score 0 if not positive review
    }
} else {
    // Total Sales less than 10,000
    $baseScore = 0; // Base score 0 regardless of review
}

// Calculate extra points for sales above 10,000 AED
if ($salesAmountvalue > $minTarget) {
    $extraPoints = floor(($salesAmountvalue - $minTarget) / $extraPointThreshold);
    $finalWonScore = $baseScore + $extraPoints;
} else {
    $finalWonScore = $baseScore;
}

// Ensure the final score does not exceed the max score
if ($finalWonScore > $maxScore) {
    $finalWonScore = $maxScore;
}

/*------------------------Won Lead Score------------------------------*/


$leadconvRestot = $leadconvSqltot->Fetch();

if($leadconvRestot['lead_convertion_count'] > 0)
{
$conversionPercentage = ($leadconvRes['lead_convertion_count'] / $leadconvRestot['lead_convertion_count']) * 60;
$leadconv = number_format($conversionPercentage, 2);
}
/*------------------------Attendance------------------------------*/
$strtimeSql = $DB->Query("Select sum(TIMESTAMPDIFF(SECOND,DATE_START,DATE_FINISH)) AS score_time from b_timeman_entries where USER_ID = '".$arRes['ID']."'");
$timeRes = $strtimeSql->Fetch();
if(!$USER->IsAdmin() && !empty($current_user)){
$strtimeSqltot = $DB->Query("Select sum(TIMESTAMPDIFF(SECOND,DATE_START,DATE_FINISH)) AS score_time from b_timeman_entries where USER_ID IN (".$sub.") ");
}
elseif(!$USER->IsAdmin() && empty($current_user)){
$strtimeSqltot = $DB->Query("Select sum(TIMESTAMPDIFF(SECOND,DATE_START,DATE_FINISH)) AS score_time from b_timeman_entries where USER_ID = ".$cuser." ");
}
else{
$strtimeSqltot = $DB->Query("Select sum(TIMESTAMPDIFF(SECOND,DATE_START,DATE_FINISH)) AS score_time from b_timeman_entries");
}

$timeRestot = $strtimeSqltot->Fetch();

$timeduration = secondsToHoursMinutes($timeRes['score_time']); 

if($timeRestot['score_time'] > 0)
{
$timeResPercentage = ($timeRes['score_time'] / $timeRestot['score_time']) * 10;
$timeSumper = number_format($timeResPercentage, 2);
}

/*------------------------Office Attendance------------------------------*/
$stroftimeSql = $DB->Query("SELECT 
    USER_ID,
    SEC_TO_TIME(SUM(TIMESTAMPDIFF(SECOND, MIN_LOG_DATE, AdjustedPunchOut))) AS WorkingHours
FROM (
    SELECT 
        USER_ID,
        DATE(LOG_DATE) AS WorkDate,
        MIN(LOG_DATE) AS MIN_LOG_DATE,
        MAX(LOG_DATE) AS MAX_LOG_DATE,
        COUNT(LOG_DATE) AS EntryCount,
        CASE
            WHEN COUNT(LOG_DATE) % 2 = 1 THEN MAX(LOG_DATE) -- If count is odd
            ELSE MAX(LOG_DATE) -- If count is even, use logic here if different
        END AS AdjustedPunchOut
    FROM 
        c_office_attandance
    WHERE 
        USER_ID = '".$arRes['UF_OFFICE_ATTN']."'
    GROUP BY 
        USER_ID, WorkDate
    HAVING 
        MIN(LOG_DATE) IS NOT NULL AND MAX(LOG_DATE) IS NOT NULL
) AS DailyLogs
GROUP BY 
    USER_ID");
$oftimeRes = $stroftimeSql->Fetch();

$ofsec=hoursToSeconds($oftimeRes['WorkingHours']);
$oftimeduration = secondsToHoursMinutes($ofsec); 


/*----------------Attandance Score--------------------*/
$attendSql = $DB->Query("SELECT 
    DATE(LOG_DATE) AS date,
    MIN(CASE WHEN TIME(LOG_DATE) < '10:00:00' THEN LOG_DATE END) AS punch_in,
      CASE
        WHEN COUNT(LOG_DATE) % 2 = 1 THEN -- If count is odd
            MAX(LOG_DATE)
        ELSE -- If count is even
            MAX(LOG_DATE)
    END AS punch_out
FROM 
    c_office_attandance
    WHERE 
        USER_ID = '".$arRes['UF_OFFICE_ATTN']."'
    GROUP BY 
    DATE(LOG_DATE)
HAVING 
    punch_in IS NOT NULL
    AND TIMEDIFF(MAX(LOG_DATE), punch_in) >= '03:00:00'");

$attendance_days = 0;

while ($attendRes = $attendSql->Fetch()) {
    if ($attendRes['punch_in'] && $attendRes['punch_out']) {
        $attendance_days++;
    }
}

// Calculate the score
$full_points = 15;
$required_days = 15;

if ($attendance_days >= $required_days) {
    $attendscore = $full_points;
} else {
    $attendscore = ($attendance_days / $required_days) * $full_points;
}
/*----------------Attandance Score--------------------*/
/*------------------------Activities------------------------------*/
$stractSql = $DB->Query("Select count(*) as activities_count from b_crm_act where RESPONSIBLE_ID = '".$arRes['ID']."' and OWNER_TYPE_ID ='1'");
$actRes = $stractSql->Fetch();
if(!$USER->IsAdmin() && !empty($current_user)){
$stractSqltot = $DB->Query("Select count(*) as activities_count from b_crm_act where RESPONSIBLE_ID IN (".$sub.") and OWNER_TYPE_ID ='1' ");
}
elseif(!$USER->IsAdmin() && empty($current_user)){
$stractSqltot = $DB->Query("Select count(*) as activities_count from b_crm_act where RESPONSIBLE_ID = ".$cuser." and OWNER_TYPE_ID ='1' ");
}
else
{
$stractSqltot = $DB->Query("Select count(*) as activities_count from b_crm_act where OWNER_TYPE_ID ='1'");
}

$actRestot = $stractSqltot->Fetch();

if($actRestot['activities_count'] > 0)
{
$stractper = number_format(($actRes['activities_count'] / $actRestot['activities_count'] * 15), 2)."%\n";
}

/*------------------------Activities Score------------------------------*/
$ActiveleadSql = $DB->Query("Select count(*) as act_lead_count from b_crm_lead where ASSIGNED_BY_ID = '".$arRes['ID']."' and STATUS_ID IN ('UC_9WUJ49','UC_23YNYD','UC_TCX0EY','IN_PROCESS','UC_OD8Y57','UC_TB6VLO','UC_6QWK0K',7,'UC_U2UJ60') ");
$ActiveleadSqlRes = $ActiveleadSql->Fetch();

$activities_count = $actRes['activities_count'];
$act_lead_count = $ActiveleadSqlRes['act_lead_count'];
		// Calculate the target
$target = $act_lead_count * 10;

// Calculate the score
if ($target > 0) {
    $score = ($activities_count / $target) * 20;
    // Ensure the score does not exceed the maximum of 20
    $Activityscore = min($score, 20);
} else {
    $Activityscore = 0; // Avoid division by zero
}

$Activityscore = round($Activityscore);


/*------------------------Activities Score------------------------------*/

/*------------------------Proactiveness------------------------------*/
$strproactSql = $DB->Query("Select count(*) as proactivities_count from c_distribution_lead_missing where USER_ID = '".$arRes['ID']."'");
$proactRes = $strproactSql->Fetch();
if(!$USER->IsAdmin() && !empty($current_user)){
$strproactSqltot = $DB->Query("Select count(*) as proactivities_count from c_distribution_lead_missing where USER_ID IN (".$sub.")");
}
if(!$USER->IsAdmin() && empty($current_user)){
$strproactSqltot = $DB->Query("Select count(*) as proactivities_count from c_distribution_lead_missing where USER_ID IN (".$cuser.")");
}
else{
$strproactSqltot = $DB->Query("Select count(*) as proactivities_count from c_distribution_lead_missing");
}
$proactRestot = $strproactSqltot->Fetch();

if($proactRestot['proactivities_count'] > 0)
{
$proactper = number_format(($proactRes['proactivities_count'] / $proactRestot['proactivities_count'] * 15), 2)."%\n";
}
/*------------------------Proactiveness Score------------------------------*/
$strtotleadAssignedSql = $DB->Query("Select count(*) as tot_lead_assigned_count from b_crm_lead_status_history  where RESPONSIBLE_ID = '".$arRes['ID']."' and STATUS_ID IN ('UC_9WUJ49')");
$strtotleadAssignedRestot = $strtotleadAssignedSql->Fetch();


 $totAssigned= $strtotleadAssignedRestot['tot_lead_assigned_count'];
$proactivities=$proactRes['proactivities_count'];

if ($proactivities == 0) {
    $proscore = 15; // If $proactivities is 0, assign 15 points
} else {
		// Calculate the percentage
if ($totAssigned > 0) {
    $percentage = ($proactivities / $totAssigned) * 100;
} else {

    $percentage = 0;
}
$percentage = round($percentage);

// Determine score based on percentage range
if ($percentage == 100) {
    $proscore = 15; // 100% from $totAssigned: 15 points
} elseif ($percentage >= 90 && $percentage <= 99) {
   $proscore = 11; // 90-99% from $totAssigned: 11 points
} elseif ($percentage >= 75 && $percentage <= 89) {
    $proscore = 7.5; // 75-89% from $totAssigned: 7.5 points
} else {
    $proscore = 0; // Default score if none of the conditions match
}

$proscore = min($proscore, 15);
	}
/*------------------------Proactiveness Score------------------------------*/
/*------------------------Active Leads------------------------------*/
$stractleadSql = $DB->Query("Select count(*) as act_lead_count from b_crm_lead where ASSIGNED_BY_ID = '".$arRes['ID']."' and STATUS_ID IN ('UC_9WUJ49','UC_23YNYD','UC_TCX0EY','IN_PROCESS','UC_OD8Y57','UC_TB6VLO','UC_6QWK0K',7,'UC_U2UJ60') ");
$stractleadRes = $stractleadSql->Fetch();

$stractleadSqltot = $DB->Query("Select count(*) as act_lead_count from b_crm_lead where STATUS_ID IN ('UC_9WUJ49','UC_23YNYD','UC_TCX0EY','IN_PROCESS','UC_OD8Y57','UC_TB6VLO','UC_6QWK0K',7,'UC_U2UJ60')");
$stractleadRestot = $stractleadSqltot->Fetch();

if($stractleadRestot['act_lead_count'] > 0)
{
$stractleadper = number_format(($stractleadRes['act_lead_count'] / $stractleadRestot['act_lead_count'] * 10), 2)."%\n";
}
/*------------------------Junk Leads------------------------------*/
$strjunkleadSql = $DB->Query("Select count(*) as junk_lead_count from b_crm_lead where ASSIGNED_BY_ID = '".$arRes['ID']."' and STATUS_ID IN ('JUNK',1,2,3,4,5,6)");
$strjunkleadRes = $strjunkleadSql->Fetch();

$strjunkleadSqltot = $DB->Query("Select count(*) as junk_lead_count from b_crm_lead where STATUS_ID IN ('JUNK',1,2,3,4,5,6) ");
$strjunkleadRestot = $strjunkleadSqltot->Fetch();

if($strjunkleadRestot['junk_lead_count'] > 0)
{
$strjunkleadper = number_format(($strjunkleadRes['junk_lead_count'] / $strjunkleadRestot['junk_lead_count'] * 10), 2)."%\n";
}
/*------------------------Total Leads------------------------------*/
$strtotleadSql = $DB->Query("Select count(*) as tot_lead_count from b_crm_lead where ASSIGNED_BY_ID = '".$arRes['ID']."' and STATUS_ID IN ('UC_9WUJ49','UC_23YNYD','UC_TCX0EY','IN_PROCESS','UC_OD8Y57','UC_TB6VLO','UC_6QWK0K',7,'UC_U2UJ60','JUNK',1,2,3,4,5,6,'CONVERTED') ");
$strtotleadRes = $strtotleadSql->Fetch();

$strtotleadSqltot = $DB->Query("Select count(*) as tot_lead_count from b_crm_lead where STATUS_ID IN ('UC_9WUJ49','UC_23YNYD','UC_TCX0EY','IN_PROCESS','UC_OD8Y57','UC_TB6VLO','UC_6QWK0K',7,'UC_U2UJ60','JUNK',1,2,3,4,5,6,'CONVERTED')");
$strtotleadRestot = $strtotleadSqltot->Fetch();

if($stractleadRestot['act_lead_count'] > 0)
{
$stractleadper = number_format(($stractleadRes['act_lead_count'] / $stractleadRestot['act_lead_count'] * 10), 2)."%\n";
}
/*------------------------Total Score------------------------------*/

$totalscore = $finalWonScore+$attendscore+$Activityscore+$proscore;
	}
?>

<tr class="view">
<td align='left' style='border:1px #f5f9f9 solid;width:15%;'><a href="/company/personal/user/<?=$arRes['ID'];?>/" bx-tooltip-user-id="<?=$arRes['ID'];?>"><?=$arRes['NAME']." ".$arRes['LAST_NAME']; ?></a></td>
	<td align='center' style='border:1px #f5f9f9 solid;width:5%;'><?php  echo "{$timeduration['hours']}h". " {$timeduration['minutes']}m"; ?></td>	
	<td align='center' style='border:1px #f5f9f9 solid;width:5%;'><?php  echo "{$oftimeduration['hours']}h". " {$oftimeduration['minutes']}m";?></td>	
	<td align='center' style='border:1px #f5f9f9 solid;width:5%;'><a href="/local/custom-reports/agent-score/lead-details/?ASSIGNED_BY_ID=<?=$arRes['ID'];?>&LEAD_TYPE=ALL&fromdate=<?=$_REQUEST['fromdate'];?>&todate=<?=$_REQUEST['todate'];?>"><?=$actRes['activities_count'];?></a></td>
	<td align='center' style="border:1px #f5f9f9 solid;width:5%; <? if($reactRes['proactivities_count']>0){ ?>color:red;<?}?>"><a href="/local/custom-reports/agent-score/lead-details/?ASSIGNED_BY_ID=<?=$arRes['ID'];?>&LEAD_TYPE=Reactiveness&fromdate=<?=$_REQUEST['fromdate'];?>&todate=<?=$_REQUEST['todate'];?>"><?=$proactRes['proactivities_count'];?></a></td>
<td align='center' style='border:1px #f5f9f9 solid;width:5%;'><a href="/local/custom-reports/agent-score/lead-details/?ASSIGNED_BY_ID=<?=$arRes['ID'];?>&LEAD_TYPE=WON&fromdate=<?=$_REQUEST['fromdate'];?>&todate=<?=$_REQUEST['todate'];?>"><?=$leadconvRes['lead_convertion_count'];?></a></td>
<td align='center' style='border:1px #f5f9f9 solid;width:5%;'><a href="/local/custom-reports/agent-score/lead-details/?ASSIGNED_BY_ID=<?=$arRes['ID'];?>&LEAD_TYPE=ACTIVE&fromdate=<?=$_REQUEST['fromdate'];?>&todate=<?=$_REQUEST['todate'];?>"><?=$stractleadRes['act_lead_count'];?></a></td>
<td align='center' style='border:1px #f5f9f9 solid;width:5%;'><a href="/local/custom-reports/agent-score/lead-details/?ASSIGNED_BY_ID=<?=$arRes['ID'];?>&LEAD_TYPE=JUNK&fromdate=<?=$_REQUEST['fromdate'];?>&todate=<?=$_REQUEST['todate'];?>"><?=$strjunkleadRes['junk_lead_count'];?></a></td>
<td align='center' style='border:1px #f5f9f9 solid;width:5%;'><a href="/local/custom-reports/agent-score/lead-details/?ASSIGNED_BY_ID=<?=$arRes['ID'];?>&fromdate=<?=$_REQUEST['fromdate'];?>&todate=<?=$_REQUEST['todate'];?>"><?=$strtotleadRes['tot_lead_count'];?></a></td>
<td align='center' style='border:1px #f5f9f9 solid;width:5%;'><a href="/local/custom-reports/agent-score/lead-details/?ASSIGNED_BY_ID=<?=$arRes['ID'];?>&LEAD_TYPE=ASSIGNED&fromdate=<?=$_REQUEST['fromdate'];?>&todate=<?=$_REQUEST['todate'];?>"><?=$strtotleadAssignedRestot['tot_lead_assigned_count'];?></a></td>
<td align='center' style='border:1px #f5f9f9 solid;width:5%;<? if($totalscore<0){ ?> color:red;<?}?>' class="tooltip-trigger" data-tooltip="Lead Target: <?=$finalWonScore;?><br/>Attendance: <?=$attendscore;?><br/>Activities: <?=$Activityscore;?><br/>Reactiveness: <?=$proscore;?>"><?=$totalscore;?></td>
</tr>

<?
} 
?>

</table>
<div class="tooltiptext" id="tooltip"></div>
<script>
	new DataTable('#score', {
    lengthMenu: [
        [20, 40, 60, -1],
        [20, 40, 60, 'All']
    ]
});
	</script>
 <script>
        const tooltip = document.getElementById('tooltip');
        const triggers = document.querySelectorAll('.tooltip-trigger');

        triggers.forEach(trigger => {
            trigger.addEventListener('mouseenter', (e) => {
                const tooltipText = trigger.getAttribute('data-tooltip');
                tooltip.innerHTML = tooltipText;
                const rect = trigger.getBoundingClientRect();
                tooltip.style.top = `${rect.top + window.scrollY - tooltip.offsetHeight - 10}px`;
                tooltip.style.left = `${rect.left + window.scrollX}px`;
                tooltip.style.visibility = 'visible';
                tooltip.style.opacity = '1';
            });

            trigger.addEventListener('mouseleave', () => {
                tooltip.style.visibility = 'hidden';
                tooltip.style.opacity = '0';
            });
        });
    </script>

<p style="color:red;">
	<span style="color:red;"><b>Notes:</b></span><br/>
    1. Each Employee Individual Score (100) =  Lead Target (50) + Office Attendance (15) + Activities (20) + Reactiveness (15)<br/>
	2. Reactivness lead ID data is showing from <b>9th April 2024</b> <br/>
	3. Attendance data is showing from <b>01st April 2023</b><br/>
</p>
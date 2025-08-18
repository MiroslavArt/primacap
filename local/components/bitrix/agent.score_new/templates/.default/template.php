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
		<input type="date" name="fromdate"  class="input-control" value="<?=$_REQUEST['fromdate'];?>" />
	    <input type="date"  name="todate" style="margin-left:10px"  class="input-control" max="<?=$currentdate;?>" value="<?=$_REQUEST['todate'];?>"  />
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
<th align='center' style='border:1px #f5f9f9 solid; color:#fff;'>Total Working Leads</th>
<th align='center' style='border:1px #f5f9f9 solid; color:#fff;'>Total Assigned Leads</th>
<th align='center' style='border:1px #f5f9f9 solid; color:#fff;'>Score</th>
</tr>
</thead>
<?
function getLeadMetrics($DB, $arRes, $fromdate = null, $todate = null, $USER, $current_user, $sub, $cuser) {
    global $DB, $USER;
    
    $dateCondition = ($fromdate && $todate) ? "AND DATE_MODIFY BETWEEN '".$fromdate."' AND '".$todate."'" : "";

    // Calculate lead conversion count for the specific user
    $leadconvSql = $DB->Query("SELECT COUNT(*) AS lead_convertion_count 
                               FROM b_crm_lead 
                               WHERE ASSIGNED_BY_ID = '".$arRes['ID']."' 
                               AND STATUS_ID = 'CONVERTED' 
                               $dateCondition");
    $leadconvRes = $leadconvSql->Fetch();
    
    // Calculate total lead conversion count based on the user type
    if (!$USER->IsAdmin() && !empty($current_user)) {
        $leadconvSqltot = $DB->Query("SELECT COUNT(*) AS lead_convertion_count 
                                       FROM b_crm_lead 
                                       WHERE ASSIGNED_BY_ID IN (".$sub.") 
                                       AND STATUS_ID = 'CONVERTED' 
                                       $dateCondition");
    } elseif (!$USER->IsAdmin() && empty($current_user)) {
        $leadconvSqltot = $DB->Query("SELECT COUNT(*) AS lead_convertion_count 
                                       FROM b_crm_lead 
                                       WHERE ASSIGNED_BY_ID = ".$cuser." 
                                       AND STATUS_ID = 'CONVERTED' 
                                       $dateCondition");
    } else {
        $leadconvSqltot = $DB->Query("SELECT COUNT(*) AS lead_convertion_count 
                                       FROM b_crm_lead 
                                       WHERE STATUS_ID = 'CONVERTED' 
                                       $dateCondition");
    }
    $leadconvRestot = $leadconvSqltot->Fetch();

    // Calculate the conversion percentage
    if ($leadconvRestot['lead_convertion_count'] > 0) {
        $conversionPercentage = ($leadconvRes['lead_convertion_count'] / $leadconvRestot['lead_convertion_count']) * 60;
        $lead_convertion_count = number_format($conversionPercentage, 2);
    } else {
        $lead_convertion_count = 0;
    }

    // Calculate the won lead score
    $dateCondition = ($fromdate && $todate) ? "AND a.DATE_MODIFY BETWEEN '".$fromdate."' AND '".$todate."'" : "";
    
    $leadconvnoSql = $DB->Query("SELECT UF_CRM_1720352836,UF_CRM_1720352792,UF_CRM_1720353030 
                                 FROM b_crm_lead a 
                                 JOIN b_uts_crm_lead b ON a.ID = b.VALUE_ID 
                                 WHERE a.ASSIGNED_BY_ID = '".$arRes['ID']."' 
                                 AND a.STATUS_ID = 'CONVERTED' 
                                 $dateCondition");
    $leadconvnoRes = $leadconvnoSql->Fetch();
    $salesAmountAED = $leadconvnoRes['UF_CRM_1720352792'];
    $salesAmount = explode('|', $salesAmountAED);
    $salesAmountvalue = (float) $salesAmount[0]; 

    $googleReviewStatus = $leadconvnoRes['UF_CRM_1720353030'];

    $mainTarget = 100000;
    $minTarget = 10000;
    $extraPointThreshold = 4000;
    $positiveReviewBonusFactor = 1.5; 
    $maxScore = 50;

    if ($salesAmountvalue >= $mainTarget) {
        $baseScore = ($googleReviewStatus == 690) ? 50 : 25;
    } elseif ($salesAmountvalue >= $minTarget) {
        $baseScore = ($googleReviewStatus == 690) ? 25 : 0;
    } else {
        $baseScore = 0;
    }

    if ($salesAmountvalue > $minTarget) {
        $extraPoints = floor(($salesAmountvalue - $minTarget) / $extraPointThreshold);
        $finalWonScore = $baseScore + $extraPoints;
    } else {
        $finalWonScore = $baseScore;
    }

    $finalWonScore = min($finalWonScore, $maxScore);

    return [
        'lead_convertion_count' => $leadconvRes['lead_convertion_count'],
        'finalWonScore' => $finalWonScore
    ];
}
function getActiveLead($DB, $arRes, $fromdate = null, $todate = null, $USER, $current_user, $sub, $cuser) {
    $dateCondition = ($fromdate && $todate) ? "AND DATE_MODIFY  BETWEEN '".$fromdate."' AND '".$todate."'" : "";

    $stractSql = $DB->Query("SELECT count(*) as act_lead_count
                             FROM b_crm_lead
                             WHERE ASSIGNED_BY_ID = '".$arRes['ID']."' 
                             AND STATUS_ID IN ('UC_9WUJ49','UC_23YNYD','UC_TCX0EY','IN_PROCESS','UC_OD8Y57','UC_TB6VLO','UC_6QWK0K',7,'UC_U2UJ60')
                             $dateCondition");
    $actRes = $stractSql->Fetch();
	
	return [
        'act_lead_count' => $actRes['act_lead_count']
    ];
	
}
function getJunkLead($DB, $arRes, $fromdate = null, $todate = null, $USER, $current_user, $sub, $cuser) {
    $dateCondition = ($fromdate && $todate) ? "AND DATE_MODIFY  BETWEEN '".$fromdate."' AND '".$todate."'" : "";

    $stractSql = $DB->Query("SELECT count(*) as junk_lead_count
                             FROM b_crm_lead
                             WHERE ASSIGNED_BY_ID = '".$arRes['ID']."' 
                             AND STATUS_ID IN ('JUNK',1,2,3,4,5,6)
                             $dateCondition");
    $actRes = $stractSql->Fetch();
	
	return [
        'junk_lead_count' => $actRes['junk_lead_count']
    ];
	
}
function getTotalLead($DB, $arRes, $fromdate = null, $todate = null, $USER, $current_user, $sub, $cuser) {
    $dateCondition = ($fromdate && $todate) ? "AND DATE_MODIFY  BETWEEN '".$fromdate."' AND '".$todate."'" : "";

    $stractSql = $DB->Query("SELECT count(*) as tot_lead_count
                             FROM b_crm_lead
                             WHERE ASSIGNED_BY_ID = '".$arRes['ID']."' 
                             AND STATUS_ID IN ('UC_9WUJ49','UC_23YNYD','UC_TCX0EY','IN_PROCESS','UC_OD8Y57','UC_TB6VLO','UC_6QWK0K',7,'UC_U2UJ60','JUNK',1,2,3,4,5,6,'CONVERTED')
                             $dateCondition");
    $actRes = $stractSql->Fetch();
	
	return [
        'tot_lead_count' => $actRes['tot_lead_count']
    ];
	
}
function getProactivenessScore($DB, $USER, $arRes, $fromdate = null, $todate = null, $current_user = null, $cuser = null, $sub = null) {
    global $DB, $USER;

    // Define date condition
    $dateCondition = ($fromdate && $todate) ? "AND MISSED_AT BETWEEN '".$fromdate."' AND '".$todate."'" : "";
    $dateConditionadmin = ($fromdate && $todate) ? " WHERE MISSED_AT BETWEEN '".$fromdate."' AND '".$todate."'" : "";

    // Calculate proactivities_count
    $strproactSql = $DB->Query("SELECT COUNT(*) AS proactivities_count 
                                FROM c_distribution_lead_missing 
                                WHERE USER_ID = '".$arRes['ID']."' 
                                $dateCondition");
    $proactRes = $strproactSql->Fetch();

    // Determine user query condition
    if (!$USER->IsAdmin() && !empty($current_user)) {
        $strproactSqltot = $DB->Query("SELECT COUNT(*) AS proactivities_count 
                                       FROM c_distribution_lead_missing 
                                       WHERE USER_ID IN (".$sub.") 
                                       $dateCondition");
    } elseif (!$USER->IsAdmin() && empty($current_user)) {
        $strproactSqltot = $DB->Query("SELECT COUNT(*) AS proactivities_count 
                                       FROM c_distribution_lead_missing 
                                       WHERE USER_ID = ".$cuser." 
                                       $dateCondition");
    } else {
        $strproactSqltot = $DB->Query("SELECT COUNT(*) AS proactivities_count 
                                       FROM c_distribution_lead_missing 
                                      $dateConditionadmin");
    }
    $proactRestot = $strproactSqltot->Fetch();

    // Define lead assigned date condition
    $leadAssignedCondition = ($fromdate && $todate) ? "AND CREATED_DATE BETWEEN '".$fromdate."' AND '".$todate."'" : "";

    // Calculate total lead assigned count
    $strtotleadAssignedSql = $DB->Query("SELECT COUNT(*) AS tot_lead_assigned_count 
                                         FROM b_crm_lead_status_history  
                                         WHERE RESPONSIBLE_ID = '".$arRes['ID']."' 
                                         AND STATUS_ID IN ('UC_9WUJ49') 
                                         $leadAssignedCondition");
    $strtotleadAssignedRestot = $strtotleadAssignedSql->Fetch();

    // Define total active lead date condition
    $leadActiveCondition = ($fromdate && $todate) ? "AND CREATED_DATE BETWEEN '".$fromdate."' AND '".$todate."'" : "";

    // Calculate total active lead count
    $strtotleadActiveSql = $DB->Query("SELECT COUNT(*) AS tot_lead_active_count 
                                         FROM b_crm_lead_status_history 
                                         WHERE RESPONSIBLE_ID = '".$arRes['ID']."' 
                                         AND STATUS_ID IN ('UC_9WUJ49','UC_23YNYD','UC_TCX0EY','IN_PROCESS','UC_OD8Y57','UC_TB6VLO','UC_6QWK0K',7,'UC_U2UJ60') 
                                         $leadActiveCondition");
    $strtotleadActiveRestot = $strtotleadActiveSql->Fetch();

    $totActive = $strtotleadActiveRestot['tot_lead_active_count'];
    $totAssigned = $strtotleadAssignedRestot['tot_lead_assigned_count'];
    $proactivities = $proactRes['proactivities_count'];

    // Calculate the percentage
    if ($totAssigned > 0 && $proactivities > 0) {
        $percentage = ($proactivities / $totAssigned) * 100;
    }elseif ($totAssigned > 0 && $proactivities == 0) {
        $percentage =  100;
    } else {
        $percentage = 0;
    }
    $percentage = round($percentage);

    // Determine proactiveness score based on percentage range
    if ($percentage == 100) {
        $proscore = 15; // 100% from $totAssigned: 15 points
    } elseif ($percentage >= 90 && $percentage <= 99) {
        $proscore = 11; // 90-99% from $totAssigned: 11 points
    } elseif ($percentage >= 75 && $percentage <= 89) {
        $proscore = 7.5; // 75-89% from $totAssigned: 7.5 points
    } else {
        $proscore = 0; // Default score if none of the conditions match
    }

    // Return an associative array with the required values
    return [
        'tot_lead_active_count' => $totActive,
        'tot_lead_assigned_count' => $totAssigned,
        'proactivities_count' => $proactivities,
        'proscore' => min($proscore, 15),
        'percentage' => $percentage
    ];
}


function getAttendanceScore($DB, $arRes, $fromdate = null, $todate = null, $USER, $current_user, $sub, $cuser) {
    global $DB, $USER;

    $dateCondition = ($fromdate && $todate) ? "AND DATE_START BETWEEN '".$fromdate."' AND '".$todate."' 
                                             AND DATE_FINISH BETWEEN '".$fromdate."' AND '".$todate."'" : "";

    $dateConditionadmin = ($fromdate && $todate) ? "WHERE DATE_START BETWEEN '".$fromdate."' AND '".$todate."' 
                                             AND DATE_FINISH BETWEEN '".$fromdate."' AND '".$todate."'" : "";

    // Query for specific user
    $strtimeSql = $DB->Query("SELECT SUM(TIMESTAMPDIFF(SECOND, DATE_START, DATE_FINISH)) AS score_time 
                              FROM b_timeman_entries 
                              WHERE USER_ID = '".$arRes['ID']."' 
                              $dateCondition");
    $timeRes = $strtimeSql->Fetch();

    // Query for total time based on conditions
    if (!$USER->IsAdmin() && !empty($current_user)) {
        $strtimeSqltot = $DB->Query("SELECT SUM(TIMESTAMPDIFF(SECOND, DATE_START, DATE_FINISH)) AS score_time 
                                      FROM b_timeman_entries 
                                      WHERE USER_ID IN (".$sub.") 
                                      $dateCondition");
    } elseif (!$USER->IsAdmin() && empty($current_user)) {
        $strtimeSqltot = $DB->Query("SELECT SUM(TIMESTAMPDIFF(SECOND, DATE_START, DATE_FINISH)) AS score_time 
                                      FROM b_timeman_entries 
                                      WHERE USER_ID = ".$cuser." 
                                      $dateCondition");
    } else {
        $strtimeSqltot = $DB->Query("SELECT SUM(TIMESTAMPDIFF(SECOND, DATE_START, DATE_FINISH)) AS score_time 
                                      FROM b_timeman_entries 
                                      $dateConditionadmin");
    }
    $timeRestot = $strtimeSqltot->Fetch();



    $timeduration = secondsToHoursMinutes($timeRes['score_time']); 

return [
        'hours' => $timeduration['hours'],
        'minutes' => $timeduration['minutes'],
    ];
}


function getOfficeAttendanceScore($DB, $arRes, $fromdate = null, $todate = null) {
global $DB,$USER;

$dateCondition = ($fromdate && $todate) ? "AND LOG_DATE BETWEEN '".$fromdate."' AND '".$todate."'" : "";

    $stroftimeSql = $DB->Query("SELECT USER_ID, SEC_TO_TIME(SUM(TIMESTAMPDIFF(SECOND, MIN_LOG_DATE, AdjustedPunchOut))) AS WorkingHours
                                FROM (SELECT USER_ID, DATE(LOG_DATE) AS WorkDate, MIN(LOG_DATE) AS MIN_LOG_DATE, 
                                      MAX(LOG_DATE) AS MAX_LOG_DATE, COUNT(LOG_DATE) AS EntryCount, 
                                      CASE WHEN COUNT(LOG_DATE) % 2 = 1 THEN MAX(LOG_DATE) 
                                      ELSE MAX(LOG_DATE) END AS AdjustedPunchOut
                                      FROM c_office_attandance 
                                      WHERE USER_ID = '".$arRes['UF_OFFICE_ATTN']."' 
                                      $dateCondition
                                      GROUP BY USER_ID, WorkDate
                                      HAVING MIN(LOG_DATE) IS NOT NULL AND MAX(LOG_DATE) IS NOT NULL) AS DailyLogs
                                GROUP BY USER_ID");
    $oftimeRes = $stroftimeSql->Fetch();

    $ofsec = hoursToSeconds($oftimeRes['WorkingHours']);
    $officeattandance = secondsToHoursMinutes($ofsec);


return [
        'hours' => $officeattandance['hours'],
        'minutes' => $officeattandance['minutes'],
    ];
}

function getAttendanceDays($DB, $arRes, $fromdate = null, $todate = null) {
global $DB,$USER;

$dateCondition = ($fromdate && $todate) ? "AND LOG_DATE BETWEEN '".$fromdate."' AND '".$todate."'" : "";

    $attendSql = $DB->Query("SELECT DATE(LOG_DATE) AS date,
                                    MIN(CASE WHEN TIME(LOG_DATE) < '10:00:00' THEN LOG_DATE END) AS punch_in,
                                    CASE WHEN COUNT(LOG_DATE) % 2 = 1 THEN MAX(LOG_DATE)
                                         ELSE MAX(LOG_DATE) END AS punch_out
                             FROM c_office_attandance
                             WHERE USER_ID = '".$arRes['UF_OFFICE_ATTN']."' 
                             $dateCondition
                             GROUP BY DATE(LOG_DATE)
                             HAVING punch_in IS NOT NULL
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


    return $attendscore;
}

function getActivitiesScore($DB, $arRes, $fromdate = null, $todate = null, $USER, $current_user, $sub, $cuser) {
    $dateCondition = ($fromdate && $todate) ? "AND CREATED BETWEEN '".$fromdate."' AND '".$todate."'" : "";


    // Count activities for the specific responsible ID
    $stractSql = $DB->Query("SELECT COUNT(*) AS activities_count 
                             FROM b_crm_act 
                             WHERE RESPONSIBLE_ID = '".$arRes['ID']."' 
                             AND OWNER_TYPE_ID ='1' 
                             $dateCondition");
    $actRes = $stractSql->Fetch();

    // Determine the total activities count based on user roles
    if (!$USER->IsAdmin() && !empty($current_user)) {
        $stractSqltot = $DB->Query("SELECT COUNT(*) AS activities_count 
                                     FROM b_crm_act 
                                     WHERE RESPONSIBLE_ID IN (".$sub.") 
                                     AND OWNER_TYPE_ID ='1' 
                                     $dateCondition");
    } elseif (!$USER->IsAdmin() && empty($current_user)) {
        $stractSqltot = $DB->Query("SELECT COUNT(*) AS activities_count 
                                     FROM b_crm_act 
                                     WHERE RESPONSIBLE_ID = ".$cuser." 
                                     AND OWNER_TYPE_ID ='1' 
                                     $dateCondition");
    } else {
        $stractSqltot = $DB->Query("SELECT COUNT(*) AS activities_count 
                                     FROM b_crm_act 
                                     WHERE OWNER_TYPE_ID ='1' 
                                     $dateCondition");
    }
    $actRestot = $stractSqltot->Fetch();

    // Calculate activities percentage
    if ($actRestot['activities_count'] > 0) {
        $activities_percentage = number_format(($actRes['activities_count'] / $actRestot['activities_count'] * 15), 2)."%";
    } else {
        $activities_percentage = "0%";
    }

    /*------------------------Activities Score------------------------------*/
    // Count active leads for the specific responsible ID
 $modifydateCondition = ($fromdate && $todate) ? "AND DATE_CREATE BETWEEN '".$fromdate."' AND '".$todate."'" : "";
    $ActiveleadSql = $DB->Query("SELECT COUNT(*) AS act_lead_count 
                                 FROM b_crm_lead 
                                 WHERE ASSIGNED_BY_ID = '".$arRes['ID']."' 
                                 AND STATUS_ID IN ('UC_9WUJ49','UC_23YNYD','UC_TCX0EY','IN_PROCESS','UC_OD8Y57','UC_TB6VLO','UC_6QWK0K',7,'UC_U2UJ60') 
                                 $modifydateCondition");
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

    return [
        'activities_count' => $actRes['activities_count'],
        'activities_percentage' => $activities_percentage,
        'activity_score' => $Activityscore
    ];
}


function getSum($DB, $arRes, $fromdate = null, $todate = null) {
global $DB,$USER;

$dateCondition = ($fromdate && $todate) ? "AND DATE_START BETWEEN '".$fromdate."' AND '".$todate."'
                                             AND DATE_FINISH BETWEEN '".$fromdate."' AND '".$todate."'" : "";

    $strSumSql = $DB->Query("SELECT SUM(TIMESTAMPDIFF(SECOND, DATE_START, DATE_FINISH)) AS total_seconds
                             FROM b_timeman_entries
                             WHERE USER_ID = '".$arRes['ID']."'
                             $dateCondition");
    $strSumRes = $strSumSql->Fetch();
    return $strSumRes['total_seconds'];
}


while($arRes = $dbRes->Fetch())
{

$leadConversion = getLeadMetrics($DB, $arRes, $fromdate, $todate, $USER, $current_user, $sub, $cuser);
$activelead =getActiveLead($DB, $arRes, $fromdate, $todate, $USER, $current_user, $sub, $cuser);
$junklead =getJunkLead($DB, $arRes, $fromdate, $todate, $USER, $current_user, $sub, $cuser);
$totallead =getTotalLead($DB, $arRes, $fromdate, $todate, $USER, $current_user, $sub, $cuser);
$proactivenessScore = getProactivenessScore($DB, $USER, $arRes, $fromdate, $todate, $current_user, $cuser, $sub);
$attendanceScore = getAttendanceScore($DB, $arRes, $fromdate, $todate, $USER, $current_user, $sub, $cuser);
$officeAttendanceScore = getOfficeAttendanceScore($DB, $arRes, $fromdate, $todate);
$attendanceDays = getAttendanceDays($DB, $arRes, $fromdate, $todate);
$activitiesScore = getActivitiesScore($DB, $arRes, $fromdate, $todate, $USER, $current_user, $sub, $cuser);
$sum = getSum($DB, $arRes, $fromdate, $todate);
 $totalscore = $leadConversion['finalWonScore']+$attendanceDays+$activitiesScore['activity_score']+$proactivenessScore['proscore'];


?>

<tr class="view">
<td align='left' style='border:1px #f5f9f9 solid;width:15%;'><a href="/company/personal/user/<?=$arRes['ID'];?>/" bx-tooltip-user-id="<?=$arRes['ID'];?>"><?=$arRes['NAME']." ".$arRes['LAST_NAME']; ?></a></td>
	<td align='center' style='border:1px #f5f9f9 solid;width:5%;'><?php  echo "{$attendanceScore['hours']}h". " {$attendanceScore['minutes']}m"; ?></td>	
	<td align='center' style='border:1px #f5f9f9 solid;width:5%;'><?php  echo "{$officeAttendanceScore['hours']}h". " {$officeAttendanceScore['minutes']}m";?></td>	
	<td align='center' style='border:1px #f5f9f9 solid;width:5%;'><a href="/local/custom-reports/agent-score/lead-details/?ASSIGNED_BY_ID=<?=$arRes['ID'];?>&LEAD_TYPE=ALL&fromdate=<?=$_REQUEST['fromdate'];?>&todate=<?=$_REQUEST['todate'];?>"><?=$activitiesScore['activities_count'];?></a></td>
	<td align='center' style="border:1px #f5f9f9 solid;width:5%; <? if($reactRes['proactivities_count']>0){ ?>color:red;<?}?>"><a href="/local/custom-reports/agent-score/lead-details/?ASSIGNED_BY_ID=<?=$arRes['ID'];?>&LEAD_TYPE=Reactiveness&fromdate=<?=$_REQUEST['fromdate'];?>&todate=<?=$_REQUEST['todate'];?>"><?=$proactivenessScore['proactivities_count'];?></a></td>
<td align='center' style='border:1px #f5f9f9 solid;width:5%;'><a href="/local/custom-reports/agent-score/lead-details/?ASSIGNED_BY_ID=<?=$arRes['ID'];?>&LEAD_TYPE=WON&fromdate=<?=$_REQUEST['fromdate'];?>&todate=<?=$_REQUEST['todate'];?>"><?=$leadConversion['lead_convertion_count'];?></a></td>
<td align='center' style='border:1px #f5f9f9 solid;width:5%;'><a href="/local/custom-reports/agent-score/lead-details/?ASSIGNED_BY_ID=<?=$arRes['ID'];?>&LEAD_TYPE=ACTIVE&fromdate=<?=$_REQUEST['fromdate'];?>&todate=<?=$_REQUEST['todate'];?>"><?=$activelead['act_lead_count'];?></a></td>
<td align='center' style='border:1px #f5f9f9 solid;width:5%;'><a href="/local/custom-reports/agent-score/lead-details/?ASSIGNED_BY_ID=<?=$arRes['ID'];?>&LEAD_TYPE=JUNK&fromdate=<?=$_REQUEST['fromdate'];?>&todate=<?=$_REQUEST['todate'];?>"><?=$junklead['junk_lead_count'];?></a></td>
<td align='center' style='border:1px #f5f9f9 solid;width:5%;'><a href="/local/custom-reports/agent-score/lead-details/?ASSIGNED_BY_ID=<?=$arRes['ID'];?>&fromdate=<?=$_REQUEST['fromdate'];?>&todate=<?=$_REQUEST['todate'];?>"><?=$totallead['tot_lead_count'];?></a></td>
<td align='center' style='border:1px #f5f9f9 solid;width:5%;'><a href="/local/custom-reports/agent-score/lead-details/?ASSIGNED_BY_ID=<?=$arRes['ID'];?>&LEAD_TYPE=ASSIGNED&fromdate=<?=$_REQUEST['fromdate'];?>&todate=<?=$_REQUEST['todate'];?>"><?=$proactivenessScore['tot_lead_assigned_count'];?></a></td>
<td align='center' style='border:1px #f5f9f9 solid;width:5%;<? if($totalscore<0){ ?> color:red;<?}?>' class="tooltip-trigger" data-tooltip="Lead Target: <?=$leadConversion['finalWonScore'];?><br/>Attendance: <?=$attendanceDays;?><br/>Activities: <?=$activitiesScore['activity_score'];?><br/>Reactiveness: <?=$proactivenessScore['proscore'];?>"><?=$totalscore;?></td>
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
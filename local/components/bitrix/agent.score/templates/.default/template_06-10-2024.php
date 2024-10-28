<?php
include($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");
   \Bitrix\Main\UI\Extension::load("ui.tooltip");
global $DB,$USER;

$current_user=getBitrixUserSubEmployees($USER->GetID());

if(!$USER->IsAdmin() && empty($current_user)) {echo "Access Denied"; die();}

if(!empty($current_user)) {
 $sub = implode(', ', $current_user);
}


$currentdate=date('d-m-Y');
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
if(!$USER->IsAdmin()){
$strSql = "Select ID,NAME,SECOND_NAME,LAST_NAME,EMAIL,c.UF_DEPARTMENT AS DEP,c.UF_OFFICE_ATTN from b_user a,b_user_group b,b_uts_user c where a.active ='Y' and a.LID ='s1' and a.ID = b.USER_ID and b.GROUP_ID='11' and c.VALUE_ID=a.ID and a.ID IN (".$sub.")  ORDER BY a.ID ASC";
}
else
{
$strSql = "Select ID,NAME,SECOND_NAME,LAST_NAME,EMAIL,c.UF_DEPARTMENT AS DEP,c.UF_OFFICE_ATTN from b_user a,b_user_group b,b_uts_user c where a.active ='Y' and a.LID ='s1' and a.ID = b.USER_ID and b.GROUP_ID='11' and c.VALUE_ID=a.ID and a.ID ORDER BY a.ID ASC";
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
<th align='center' style='border:1px #f5f9f9 solid; color:#fff;'>Individual Performance (%)</th>
</tr>
</thead>
<?
while($arRes = $dbRes->Fetch())
{

if($fromdate!=''){
/*------------------------Convertion Ratio------------------------------*/
$leadconvSql = $DB->Query("select count(*) as lead_convertion_count from b_crm_lead where ASSIGNED_BY_ID = '".$arRes['ID']."' and STATUS_ID = 'CONVERTED' and DATE_MODIFY between '".$fromdate."' and '".$todate."' ");
$leadconvRes = $leadconvSql->Fetch();
if(!$USER->IsAdmin()){
$leadconvSqltot = $DB->Query("select count(*) as lead_convertion_count from b_crm_lead where ASSIGNED_BY_ID IN (".$sub.") and STATUS_ID = 'CONVERTED' and DATE_MODIFY between '".$fromdate."' and '".$todate."'");
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
/*------------------------Attendance------------------------------*/
$strtimeSql = $DB->Query("Select sum(TIMESTAMPDIFF(SECOND,DATE_START,DATE_FINISH)) AS score_time from b_timeman_entries where USER_ID = '".$arRes['ID']."' and DATE_START between '".$fromdate."' and '".$todate."' and DATE_FINISH between '".$fromdate."' and '".$todate."'");
$timeRes = $strtimeSql->Fetch();

if(!$USER->IsAdmin()){
$strtimeSqltot = $DB->Query("Select sum(TIMESTAMPDIFF(SECOND,DATE_START,DATE_FINISH)) AS score_time from b_timeman_entries where USER_ID IN (".$sub.") and DATE_START between '".$fromdate."' and '".$todate."' and DATE_FINISH between '".$fromdate."' and '".$todate."'");
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
    TIMEDIFF(MAX(CASE WHEN PUNCH_DIRECTION = 'out' THEN LOG_DATE END),
             MIN(CASE WHEN PUNCH_DIRECTION = 'in' THEN LOG_DATE END)) AS WorkingHours
FROM 
    c_office_attandance
WHERE 
    USER_ID = '".$arRes['UF_OFFICE_ATTN']."' and LOG_DATE between '".$fromdate."' and '".$todate."'");
$oftimeRes = $stroftimeSql->Fetch();

$ofsec=hoursToSeconds($oftimeRes['WorkingHours']);
$oftimeduration = secondsToHoursMinutes($ofsec); 

/*------------------------Activities------------------------------*/
$stractSql = $DB->Query("Select count(*) as activities_count from b_crm_act where RESPONSIBLE_ID = '".$arRes['ID']."' and OWNER_TYPE_ID ='1' and CREATED between '".$fromdate."' and '".$todate."'");
$actRes = $stractSql->Fetch();
if(!$USER->IsAdmin()){
$stractSqltot = $DB->Query("Select count(*) as activities_count from b_crm_act where RESPONSIBLE_ID IN (".$sub.") and OWNER_TYPE_ID ='1' and CREATED between '".$fromdate."' and '".$todate."'");
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
/*------------------------Proactiveness------------------------------*/
$strproactSql = $DB->Query("Select count(*) as proactivities_count from c_distribution_lead_missing where USER_ID = '".$arRes['ID']."'  and MISSED_AT between '".$fromdate."' and '".$todate."' ");
$proactRes = $strproactSql->Fetch();
if(!$USER->IsAdmin()){
$strproactSqltot = $DB->Query("Select count(*) as proactivities_count from  c_distribution_lead_missing where USER_ID IN (".$sub.") and MISSED_AT between '".$fromdate."' and '".$todate."'");
}else{
$strproactSqltot = $DB->Query("Select count(*) as proactivities_count from  c_distribution_lead_missing where MISSED_AT between '".$fromdate."' and '".$todate."'");
}

$proactRestot = $strproactSqltot->Fetch();

if($proactRestot['proactivities_count'] > 0)
{
$proactper = number_format(($proactRes['proactivities_count'] / $proactRestot['proactivities_count'] * 15), 2)."%\n";
}
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
$finalscore= $leadconv + $timeSumper + $stractper - $proactper."%\n";
  if ($finalscore < 0) {
        $totalscore = '0%';
  }else{
$totalscore = $finalscore;
  }
	}else
	{
		/*------------------------Convertion Ratio------------------------------*/
$leadconvSql = $DB->Query("select count(*) as lead_convertion_count from b_crm_lead where ASSIGNED_BY_ID = '".$arRes['ID']."' and STATUS_ID = 'CONVERTED'");
$leadconvRes = $leadconvSql->Fetch();
if(!$USER->IsAdmin()){
$leadconvSqltot = $DB->Query("select count(*) as lead_convertion_count from b_crm_lead where ID IN (".$sub.") and STATUS_ID = 'CONVERTED'");
}else{
$leadconvSqltot = $DB->Query("select count(*) as lead_convertion_count from b_crm_lead where STATUS_ID = 'CONVERTED'");
}


$leadconvRestot = $leadconvSqltot->Fetch();

if($leadconvRestot['lead_convertion_count'] > 0)
{
$conversionPercentage = ($leadconvRes['lead_convertion_count'] / $leadconvRestot['lead_convertion_count']) * 60;
$leadconv = number_format($conversionPercentage, 2);
}
/*------------------------Attendance------------------------------*/
$strtimeSql = $DB->Query("Select sum(TIMESTAMPDIFF(SECOND,DATE_START,DATE_FINISH)) AS score_time from b_timeman_entries where USER_ID = '".$arRes['ID']."'");
$timeRes = $strtimeSql->Fetch();
if(!$USER->IsAdmin()){
$strtimeSqltot = $DB->Query("Select sum(TIMESTAMPDIFF(SECOND,DATE_START,DATE_FINISH)) AS score_time from b_timeman_entries where USER_ID IN (".$sub.") ");
}else{
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
    TIMEDIFF(MAX(CASE WHEN PUNCH_DIRECTION = 'out' THEN LOG_DATE END),
             MIN(CASE WHEN PUNCH_DIRECTION = 'in' THEN LOG_DATE END)) AS WorkingHours
FROM 
    c_office_attandance
WHERE 
    USER_ID = '".$arRes['UF_OFFICE_ATTN']."'");
$oftimeRes = $stroftimeSql->Fetch();
$ofsec=hoursToSeconds($oftimeRes['WorkingHours']);
$oftimeduration = secondsToHoursMinutes($ofsec); 

/*------------------------Activities------------------------------*/
$stractSql = $DB->Query("Select count(*) as activities_count from b_crm_act where RESPONSIBLE_ID = '".$arRes['ID']."' and OWNER_TYPE_ID ='1'");
$actRes = $stractSql->Fetch();
if(!$USER->IsAdmin()){
$stractSqltot = $DB->Query("Select count(*) as activities_count from b_crm_act where RESPONSIBLE_ID IN (".$sub.") and OWNER_TYPE_ID ='1' ");
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
/*------------------------Proactiveness------------------------------*/
$strproactSql = $DB->Query("Select count(*) as proactivities_count from c_distribution_lead_missing where USER_ID = '".$arRes['ID']."'");
$proactRes = $strproactSql->Fetch();
if(!$USER->IsAdmin()){
$strproactSqltot = $DB->Query("Select count(*) as proactivities_count from c_distribution_lead_missing where USER_ID IN (".$sub.")");
}else{
$strproactSqltot = $DB->Query("Select count(*) as proactivities_count from c_distribution_lead_missing");
}
$proactRestot = $strproactSqltot->Fetch();

if($proactRestot['proactivities_count'] > 0)
{
$proactper = number_format(($proactRes['proactivities_count'] / $proactRestot['proactivities_count'] * 15), 2)."%\n";
}

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
$finalscore= $leadconv + $timeSumper + $stractper - $proactper."%\n";
  if ($finalscore < 0) {
        $totalscore ='0%';
  }else{
$totalscore = $finalscore;
  }
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
<td align='center' style='border:1px #f5f9f9 solid;width:5%;<? if($totalscore<0){ ?> color:red;<?}?>'><?=$totalscore;?></td>
</tr>
<?
} 
?>

</table>
<script>
	new DataTable('#score', {
    lengthMenu: [
        [20, 40, 60, -1],
        [20, 40, 60, 'All']
    ]
});
	</script>



<p style="color:red;">
	<span style="color:red;"><b>Notes:</b></span><br/>
    1. Each Employee Individual Percentage (100%) =  Lead Conversion (60%) + Attendance (10%) + Activities (15%) - Reactiveness (15%)<br/>
	2. Reactivness lead ID data is showing from <b>9th April 2024</b> <br/>
	3. Attendance data is showing from <b>15th April 2024</b><br/>
</p>
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





$strSql = "Select ID,NAME,SECOND_NAME,LAST_NAME,EMAIL,c.UF_DEPARTMENT AS DEP,c.UF_OFFICE_ATTN from b_user a,b_user_group b,b_uts_user c where a.active ='Y' and a.LID ='s1' and a.ID = b.USER_ID and b.GROUP_ID='11' and c.VALUE_ID=a.ID  AND a.ID NOT IN (1,27,1709,1013,4817,4818) ORDER BY a.ID ASC";

$dbRes = $DB->Query($strSql);



 global $APPLICATION;

	$GLOBALS['APPLICATION']->SetAdditionalCSS('/local/components/bitrix/agent.score/templates/.default/datatables.min.css');

	$GLOBALS['APPLICATION']->AddHeadScript('/local/components/bitrix/agent.score/templates/.default/datatables.min.js');
?>

<h2>Boost Score ( Based on last 90 days activity)</h2>
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

<table id="score" border='0'  cellpadding='8' cellspacing='0' width='100%'>
<thead>
<tr bgcolor='#8d9089'>
<th align='center' style='border:1px #f5f9f9 solid; color:#fff;'>Employee</th>
<th align='center' style='border:1px #f5f9f9 solid; color:#fff;'>Won Deals</th>
<th align='center' style='border:1px #f5f9f9 solid; color:#fff;'>Score (%)</th>
</tr>
</thead>
<?
while($arRes = $dbRes->Fetch())
{
$agentId = $arRes['ID'];
$metrics = calculateAgentMetrics($agentId);
	/*    global $DB;

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
$totalscore = $leadconvPercentage + $timePercentage + $stractper - $reactivenessPercentage;*/

?>

<tr class="view">
<td align='left' style='border:1px #f5f9f9 solid;width:15%;'><a href="/company/personal/user/<?=$arRes['ID'];?>/" bx-tooltip-user-id="<?=$arRes['ID'];?>"><?=$arRes['NAME']." ".$arRes['LAST_NAME']; ?></a></td>
<td align='center' style='border:1px #f5f9f9 solid;width:5%; <? if($metrics["lead_conversion"] < 1){ ?> color:red;<?}?>'><?=$metrics['lead_conversion'];?></td> 
<td align='center' style='border:1px #f5f9f9 solid;width:5%; <? if($metrics["total_score"] < 0){ ?> color:red;<?}?>'><?=$metrics['total_score'];?>%</td>
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
</p>
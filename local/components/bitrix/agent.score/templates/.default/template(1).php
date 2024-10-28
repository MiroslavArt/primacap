<?php
include($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");
   \Bitrix\Main\UI\Extension::load("ui.tooltip");
global $DB;
$currentdate=date('d-m-Y');

if($_REQUEST['fromdate']!='')
{
$fromdate=$_REQUEST['fromdate']." 00:00:00";
$todate=$_REQUEST['todate']." 23:59:59";
}



$strSql = "Select ID,NAME,SECOND_NAME,LAST_NAME,EMAIL,c.UF_DEPARTMENT AS DEP from b_user a,b_user_group b,b_uts_user c where a.active ='Y' and a.LID ='s1' and a.ID = b.USER_ID and b.GROUP_ID='11' and c.VALUE_ID=a.ID ORDER BY a.ID ASC";

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
<th align='center' style='border:1px #f5f9f9 solid; color:#fff;'>Attandance</th>
<th align='center' style='border:1px #f5f9f9 solid; color:#fff;'>Activities</th>
<th align='center' style='border:1px #f5f9f9 solid; color:#fff;'>Reactiveness</th>
<th align='center' style='border:1px #f5f9f9 solid; color:#fff;'>Won Leads</th>
<th align='center' style='border:1px #f5f9f9 solid; color:#fff;'>Active Leads</th>
<th align='center' style='border:1px #f5f9f9 solid; color:#fff;'>Junk Leads</th>
<th align='center' style='border:1px #f5f9f9 solid; color:#fff;'>Total Leads</th>
<th align='center' style='border:1px #f5f9f9 solid; color:#fff;'>Overall Score</th>
</tr>
</thead>
<?
while($arRes = $dbRes->Fetch())
{
	if($fromdate!=''){
/*------------------------Convertion Ratio------------------------------*/
$leadconvSql = $DB->Query("select count(*) as lead_convertion_count from b_crm_lead where ASSIGNED_BY_ID = '".$arRes['ID']."' and STATUS_ID = 'CONVERTED' and DATE_MODIFY between '".$fromdate."' and '".$todate."' ");
$leadconvRes = $leadconvSql->Fetch();

$leadconvSqltot = $DB->Query("select count(*) as lead_convertion_count from b_crm_lead where STATUS_ID = 'CONVERTED' and DATE_MODIFY between '".$fromdate."' and '".$todate."'  ");
$leadconvRestot = $leadconvSqltot->Fetch();

if($leadconvRes['lead_convertion_count'] > 0)
{
$leadconv = round( $leadconvRes['lead_convertion_count'] / $leadconvRestot['lead_convertion_count'] * 100, 2)."%\n";
}
/*------------------------Attendance------------------------------*/
$strtimeSql = $DB->Query("Select sum(DURATION) AS score_time from b_timeman_entries where USER_ID = '".$arRes['ID']."' and DATE_START between '".$fromdate."' and '".$todate."'");
$timeRes = $strtimeSql->Fetch();

$strtimeSqltot = $DB->Query("Select sum(DURATION) AS score_time from b_timeman_entries where DATE_START between '".$fromdate."' and '".$todate."'");
$timeRestot = $strtimeSqltot->Fetch();

if($timeRes['score_time'] > 0)
{
$timeSumper = round($timeRes['score_time'] / $timeRestot['score_time'] * 100, 2)."%\n";
}

$seconds = round($timeRes['score_time']);
  $outputtime = sprintf('%02d:%02d:%02d', ($seconds/ 3600),($seconds/ 60 % 60), $seconds% 60);

/*------------------------Activities------------------------------*/
$stractSql = $DB->Query("Select count(*) as activities_count from b_crm_act where RESPONSIBLE_ID = '".$arRes['ID']."' and OWNER_TYPE_ID ='1' and CREATED between '".$fromdate."' and '".$todate."'");
$actRes = $stractSql->Fetch();

$stractSqltot = $DB->Query("Select count(*) as activities_count from b_crm_act where  OWNER_TYPE_ID ='1' and CREATED between '".$fromdate."' and '".$todate."'");
$actRestot = $stractSqltot->Fetch();

if($actRes['activities_coun'] > 0)
{
$stractper = round($actRes['activities_count'] / $actRestot['activities_count'] * 100, 2)."%\n";
}
/*------------------------Proactiveness------------------------------*/
$strproactSql = $DB->Query("Select count(*) as proactivities_count from c_distribution_lead_missing where USER_ID = '".$arRes['ID']."'  and MISSED_AT between '".$fromdate."' and '".$todate."' ");
$proactRes = $strproactSql->Fetch();

$strproactSqltot = $DB->Query("Select count(*) as proactivities_count from c_distribution_lead_missing  where MISSED_AT between '".$fromdate."' and '".$todate."' ");
$proactRestot = $strproactSqltot->Fetch();

if($proactRes['proactivities_count'] > 0)
{
$proactper = round($proactRes['proactivities_count'] / $proactRestot['proactivities_count'] * 100, 2)."%\n";
}
/*------------------------Active Leads------------------------------*/
$stractleadSql = $DB->Query("Select count(*) as act_lead_count from b_crm_lead where ASSIGNED_BY_ID = '".$arRes['ID']."' and STATUS_ID IN ('UC_9WUJ49','UC_23YNYD','UC_TCX0EY','IN_PROCESS','UC_OD8Y57','UC_TB6VLO','UC_6QWK0K',7,'UC_U2UJ60') and DATE_MODIFY between '".$fromdate."' and '".$todate."' ");
$stractleadRes = $stractleadSql->Fetch();

$stractleadSqltot = $DB->Query("Select count(*) as act_lead_count from b_crm_lead where STATUS_ID IN ('UC_9WUJ49','UC_23YNYD','UC_TCX0EY','IN_PROCESS','UC_OD8Y57','UC_TB6VLO','UC_6QWK0K',7,'UC_U2UJ60') and DATE_MODIFY between '".$fromdate."' and '".$todate."'");
$stractleadRestot = $stractleadSqltot->Fetch();

if($stractleadRes['act_lead_count'] > 0)
{
$stractleadper = round($stractleadRes['act_lead_count'] / $stractleadRestot['act_lead_count'] * 100, 2)."%\n";
}
/*------------------------Junk Leads------------------------------*/
$strjunkleadSql = $DB->Query("Select count(*) as junk_lead_count from b_crm_lead where ASSIGNED_BY_ID = '".$arRes['ID']."' and STATUS_ID IN ('JUNK',1,2,3,4,5,6) and DATE_MODIFY between '".$fromdate."' and '".$todate."'");
$strjunkleadRes = $strjunkleadSql->Fetch();

$strjunkleadSqltot = $DB->Query("Select count(*) as junk_lead_count from b_crm_lead where STATUS_ID IN ('JUNK',1,2,3,4,5,6) and DATE_MODIFY between '".$fromdate."' and '".$todate."'");
$strjunkleadRestot = $strjunkleadSqltot->Fetch();


if($strjunkleadRes['junk_lead_count'] > 0)
{
$strjunkleadper = round($strjunkleadRes['junk_lead_count'] / $strjunkleadRestot['junk_lead_count'] * 100, 2)."%\n";
}
/*------------------------Total Leads------------------------------*/
$strtotleadSql = $DB->Query("Select count(*) as tot_lead_count from b_crm_lead where ASSIGNED_BY_ID = '".$arRes['ID']."' and STATUS_ID IN ('UC_9WUJ49','UC_23YNYD','UC_TCX0EY','IN_PROCESS','UC_OD8Y57','UC_TB6VLO','UC_6QWK0K',7,'UC_U2UJ60','JUNK',1,2,3,4,5,6,'CONVERTED') and DATE_MODIFY between '".$fromdate."' and '".$todate."' ");
$strtotleadRes = $strtotleadSql->Fetch();

$strtotleadSqltot = $DB->Query("Select count(*) as tot_lead_count from b_crm_lead where STATUS_ID IN ('UC_9WUJ49','UC_23YNYD','UC_TCX0EY','IN_PROCESS','UC_OD8Y57','UC_TB6VLO','UC_6QWK0K',7,'UC_U2UJ60','JUNK',1,2,3,4,5,6,'CONVERTED') and DATE_MODIFY between '".$fromdate."' and '".$todate."'");
$strtotleadRestot = $strtotleadSqltot->Fetch();

if($strtotleadRes['tot_lead_count'] > 0)
{
$stractleadper = round($stractleadRes['act_lead_count'] / $stractleadRestot['act_lead_count'] * 100, 2)."%\n";
}
/*------------------------Total Score------------------------------*/
$totalscore= round($leadconv + $timeSumper + $stractper + $proactper + $stractleadper + $strjunkleadper, 2)."%\n";
	}else
	{
		/*------------------------Convertion Ratio------------------------------*/
$leadconvSql = $DB->Query("select count(*) as lead_convertion_count from b_crm_lead where ASSIGNED_BY_ID = '".$arRes['ID']."' and STATUS_ID = 'CONVERTED'");
$leadconvRes = $leadconvSql->Fetch();

$leadconvSqltot = $DB->Query("select count(*) as lead_convertion_count from b_crm_lead where STATUS_ID = 'CONVERTED'");
$leadconvRestot = $leadconvSqltot->Fetch();

if($leadconvRes['lead_convertion_count'] > 0)
{
$leadconv = round( $leadconvRes['lead_convertion_count'] / $leadconvRestot['lead_convertion_count'] * 100, 2)."%\n";
}
/*------------------------Attendance------------------------------*/
$strtimeSql = $DB->Query("Select sum(DURATION) AS score_time from b_timeman_entries where USER_ID = '".$arRes['ID']."'");
$timeRes = $strtimeSql->Fetch();

$strtimeSqltot = $DB->Query("Select sum(DURATION) AS score_time from b_timeman_entries");
$timeRestot = $strtimeSqltot->Fetch();

if($timeRes['score_time'] > 0)
{
$timeSumper = round($timeRes['score_time'] / $timeRestot['score_time'] * 100, 2)."%\n";
}
$seconds = round($timeRes['score_time']);
  $outputtime = sprintf('%02d:%02d:%02d', ($seconds/ 3600),($seconds/ 60 % 60), $seconds% 60);
/*------------------------Activities------------------------------*/
$stractSql = $DB->Query("Select count(*) as activities_count from b_crm_act where RESPONSIBLE_ID = '".$arRes['ID']."' and OWNER_TYPE_ID ='1'");
$actRes = $stractSql->Fetch();

$stractSqltot = $DB->Query("Select count(*) as activities_count from b_crm_act where  OWNER_TYPE_ID ='1' ");
$actRestot = $stractSqltot->Fetch();

if($actRes['activities_coun'] > 0)
{
$stractper = round($actRes['activities_count'] / $actRestot['activities_count'] * 100, 2)."%\n";
}
/*------------------------Proactiveness------------------------------*/
$strproactSql = $DB->Query("Select count(*) as proactivities_count from c_distribution_lead_missing where USER_ID = '".$arRes['ID']."'");
$proactRes = $strproactSql->Fetch();

$strproactSqltot = $DB->Query("Select count(*) as proactivities_count from c_distribution_lead_missing");
$proactRestot = $strproactSqltot->Fetch();

if($proactRes['proactivities_count'] > 0)
{
$proactper = round($proactRes['proactivities_count'] / $proactRestot['proactivities_count'] * 100, 2)."%\n";
}

/*------------------------Active Leads------------------------------*/
$stractleadSql = $DB->Query("Select count(*) as act_lead_count from b_crm_lead where ASSIGNED_BY_ID = '".$arRes['ID']."' and STATUS_ID IN ('UC_9WUJ49','UC_23YNYD','UC_TCX0EY','IN_PROCESS','UC_OD8Y57','UC_TB6VLO','UC_6QWK0K',7,'UC_U2UJ60') ");
$stractleadRes = $stractleadSql->Fetch();

$stractleadSqltot = $DB->Query("Select count(*) as act_lead_count from b_crm_lead where STATUS_ID IN ('UC_9WUJ49','UC_23YNYD','UC_TCX0EY','IN_PROCESS','UC_OD8Y57','UC_TB6VLO','UC_6QWK0K',7,'UC_U2UJ60')");
$stractleadRestot = $stractleadSqltot->Fetch();

if($stractleadRes['act_lead_count'] > 0)
{
$stractleadper = round($stractleadRes['act_lead_count'] / $stractleadRestot['act_lead_count'] * 100, 2)."%\n";
}
/*------------------------Junk Leads------------------------------*/
$strjunkleadSql = $DB->Query("Select count(*) as junk_lead_count from b_crm_lead where ASSIGNED_BY_ID = '".$arRes['ID']."' and STATUS_ID IN ('JUNK',1,2,3,4,5,6)");
$strjunkleadRes = $strjunkleadSql->Fetch();

$strjunkleadSqltot = $DB->Query("Select count(*) as junk_lead_count from b_crm_lead where STATUS_ID IN ('JUNK',1,2,3,4,5,6) ");
$strjunkleadRestot = $strjunkleadSqltot->Fetch();

if($strjunkleadRes['junk_lead_count'] > 0)
{
$strjunkleadper = round($strjunkleadRes['junk_lead_count'] / $strjunkleadRestot['junk_lead_count'] * 100, 2)."%\n";
}
/*------------------------Total Leads------------------------------*/
$strtotleadSql = $DB->Query("Select count(*) as tot_lead_count from b_crm_lead where ASSIGNED_BY_ID = '".$arRes['ID']."' and STATUS_ID IN ('UC_9WUJ49','UC_23YNYD','UC_TCX0EY','IN_PROCESS','UC_OD8Y57','UC_TB6VLO','UC_6QWK0K',7,'UC_U2UJ60','JUNK',1,2,3,4,5,6,'CONVERTED') ");
$strtotleadRes = $strtotleadSql->Fetch();

$strtotleadSqltot = $DB->Query("Select count(*) as tot_lead_count from b_crm_lead where STATUS_ID IN ('UC_9WUJ49','UC_23YNYD','UC_TCX0EY','IN_PROCESS','UC_OD8Y57','UC_TB6VLO','UC_6QWK0K',7,'UC_U2UJ60','JUNK',1,2,3,4,5,6,'CONVERTED')");
$strtotleadRestot = $strtotleadSqltot->Fetch();

if($strtotleadRes['tot_lead_count'] > 0)
{
$stractleadper = round($stractleadRes['act_lead_count'] / $stractleadRestot['act_lead_count'] * 100, 2)."%\n";
}
/*------------------------Total Score------------------------------*/
$totalscore= round($leadconv + $timeSumper + $stractper + $stractleadper + $strjunkleadper - $reactper , 2)."%\n";
	}
?>

<tr class="view">
<td align='left' style='border:1px #f5f9f9 solid;width:15%;'><a href="/company/personal/user/<?=$arRes['ID'];?>/" bx-tooltip-user-id="<?=$arRes['ID'];?>"><?=$arRes['NAME']." ".$arRes['LAST_NAME']; ?></a></td>
<td align='center' style='border:1px #f5f9f9 solid;width:5%;'><?=$timeSumper;?></td>	
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
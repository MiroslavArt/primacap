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

if(!$USER->IsAdmin()){
$strSql = "Select ID,NAME,SECOND_NAME,LAST_NAME,EMAIL,c.UF_DEPARTMENT AS DEP from b_user a,b_user_group b,b_uts_user c where a.active ='Y' and a.LID ='s1' and a.ID = b.USER_ID and b.GROUP_ID='11' and c.VALUE_ID=a.ID and a.ID IN (".$sub.") ORDER BY a.ID ASC";
}else{
$strSql = "Select ID,NAME,SECOND_NAME,LAST_NAME,EMAIL,c.UF_DEPARTMENT AS DEP from b_user a,b_user_group b,b_uts_user c where a.active ='Y' and a.LID ='s1' and a.ID = b.USER_ID and b.GROUP_ID='11' and c.VALUE_ID=a.ID ORDER BY a.ID ASC";
}
$dbRes = $DB->Query($strSql);


 global $APPLICATION;

	$GLOBALS['APPLICATION']->SetAdditionalCSS('/local/components/bitrix/agent.active.leads/templates/.default/datatables.min.css');

	$GLOBALS['APPLICATION']->AddHeadScript('/local/components/bitrix/agent.active.leads/templates/.default/datatables.min.js');

?>
<h2>Active Lead (with Stages)</h2>
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
        <input type="reset" name="reset" value="Reset">
	</p>
</form>

<table id="score" border='0'  cellpadding='8' cellspacing='0' width='100%'>
<thead>
<tr bgcolor='#8d9089'>
<th align='center' style='border:1px #f5f9f9 solid; color:#fff;'>Employee</th>
<th align='center' style='border:1px #f5f9f9 solid; color:#fff;'>New Lead</th>
<th align='center' style='border:1px #f5f9f9 solid; color:#fff;'>Option sent</th>
<th align='center' style='border:1px #f5f9f9 solid; color:#fff;'>No Answer</th>
<th align='center' style='border:1px #f5f9f9 solid; color:#fff;'>Contacted</th>
<th align='center' style='border:1px #f5f9f9 solid; color:#fff;'>Warm</th>
<th align='center' style='border:1px #f5f9f9 solid; color:#fff;'>Hot</th>
<th align='center' style='border:1px #f5f9f9 solid; color:#fff;'>Calls</th>
<th align='center' style='border:1px #f5f9f9 solid; color:#fff;'>Assigned</th>
<th align='center' style='border:1px #f5f9f9 solid; color:#fff;'>Cold</th>
<th align='center' style='border:1px #f5f9f9 solid; color:#fff;'>Not Matched</th>
<th align='center' style='border:1px #f5f9f9 solid; color:#fff;'>Reshuffle Leads</th>
<th align='center' style='border:1px #f5f9f9 solid; color:#fff;'>Zoom Meeting</th>
</tr>
</thead>
<?
while($arRes = $dbRes->Fetch())
{
	if($fromdate!=''){
/*------------------------Active Leads------------------------------*/


$str01leadSql = $DB->Query("Select count(*) as act_lead_count from b_crm_lead where ASSIGNED_BY_ID = '".$arRes['ID']."' and STATUS_ID IN ('NEW') and DATE_MODIFY between '".$fromdate."' and '".$todate."'");
$str01leadRes = $str01leadSql->Fetch();

$str02leadSql = $DB->Query("Select count(*) as act_lead_count from b_crm_lead where ASSIGNED_BY_ID = '".$arRes['ID']."' and STATUS_ID IN ('IN_PROCESS') and DATE_MODIFY between '".$fromdate."' and '".$todate."'");
$str02leadRes = $str02leadSql->Fetch();

$str03leadSql = $DB->Query("Select count(*) as act_lead_count from b_crm_lead where ASSIGNED_BY_ID = '".$arRes['ID']."' and STATUS_ID IN ('UC_23YNYD') and DATE_MODIFY between '".$fromdate."' and '".$todate."'");
$str03leadRes = $str03leadSql->Fetch();

$str04leadSql = $DB->Query("Select count(*) as act_lead_count from b_crm_lead where ASSIGNED_BY_ID = '".$arRes['ID']."' and STATUS_ID IN ('UC_TCX0EY') and DATE_MODIFY between '".$fromdate."' and '".$todate."' ");
$str04leadRes = $str04leadSql->Fetch();

$str05leadSql = $DB->Query("Select count(*) as act_lead_count from b_crm_lead where ASSIGNED_BY_ID = '".$arRes['ID']."' and STATUS_ID IN ('UC_TB6VLO') and DATE_MODIFY between '".$fromdate."' and '".$todate."'");
$str05leadRes = $str05leadSql->Fetch();

$str06leadSql = $DB->Query("Select count(*) as act_lead_count from b_crm_lead where ASSIGNED_BY_ID = '".$arRes['ID']."' and STATUS_ID IN ('UC_6QWK0K') and DATE_MODIFY between '".$fromdate."' and '".$todate."'");
$str06leadRes = $str06leadSql->Fetch();

$str07leadSql = $DB->Query("Select count(*) as act_lead_count from b_crm_lead where ASSIGNED_BY_ID = '".$arRes['ID']."' and STATUS_ID IN ('UC_ZCVQ0B') and DATE_MODIFY between '".$fromdate."' and '".$todate."'");
$str07leadRes = $str07leadSql->Fetch();

$str08leadSql = $DB->Query("Select count(*) as act_lead_count from b_crm_lead where ASSIGNED_BY_ID = '".$arRes['ID']."' and STATUS_ID IN ('UC_9WUJ49') and DATE_MODIFY between '".$fromdate."' and '".$todate."'");
$str08leadRes = $str08leadSql->Fetch();

$str09leadSql = $DB->Query("Select count(*) as act_lead_count from b_crm_lead where ASSIGNED_BY_ID = '".$arRes['ID']."' and STATUS_ID IN ('UC_OD8Y57') and DATE_MODIFY between '".$fromdate."' and '".$todate."'");
$str09leadRes = $str09leadSql->Fetch();

$str10leadSql = $DB->Query("Select count(*) as act_lead_count from b_crm_lead where ASSIGNED_BY_ID = '".$arRes['ID']."' and STATUS_ID IN ('UC_U2UJ60') and DATE_MODIFY between '".$fromdate."' and '".$todate."'");
$str10leadRes = $str10leadSql->Fetch();

$str11leadSql = $DB->Query("Select count(*) as act_lead_count from b_crm_lead where ASSIGNED_BY_ID = '".$arRes['ID']."' and STATUS_ID IN ('UC_0L8X7G') and DATE_MODIFY between '".$fromdate."' and '".$todate."'");
$str11leadRes = $str11leadSql->Fetch();

$str12leadSql = $DB->Query("Select count(*) as act_lead_count from b_crm_lead where ASSIGNED_BY_ID = '".$arRes['ID']."' and STATUS_ID IN ('7') and DATE_MODIFY between '".$fromdate."' and '".$todate."'");
$str12leadRes = $str12leadSql->Fetch();

	}else
	{
/*------------------------Active Leads------------------------------*/

$str01leadSql = $DB->Query("Select count(*) as act_lead_count from b_crm_lead where ASSIGNED_BY_ID = '".$arRes['ID']."' and STATUS_ID IN ('NEW') ");
$str01leadRes = $str01leadSql->Fetch();

$str02leadSql = $DB->Query("Select count(*) as act_lead_count from b_crm_lead where ASSIGNED_BY_ID = '".$arRes['ID']."' and STATUS_ID IN ('IN_PROCESS') ");
$str02leadRes = $str02leadSql->Fetch();

$str03leadSql = $DB->Query("Select count(*) as act_lead_count from b_crm_lead where ASSIGNED_BY_ID = '".$arRes['ID']."' and STATUS_ID IN ('UC_23YNYD') ");
$str03leadRes = $str03leadSql->Fetch();

$str04leadSql = $DB->Query("Select count(*) as act_lead_count from b_crm_lead where ASSIGNED_BY_ID = '".$arRes['ID']."' and STATUS_ID IN ('UC_TCX0EY') ");
$str04leadRes = $str04leadSql->Fetch();

$str05leadSql = $DB->Query("Select count(*) as act_lead_count from b_crm_lead where ASSIGNED_BY_ID = '".$arRes['ID']."' and STATUS_ID IN ('UC_TB6VLO') ");
$str05leadRes = $str05leadSql->Fetch();

$str06leadSql = $DB->Query("Select count(*) as act_lead_count from b_crm_lead where ASSIGNED_BY_ID = '".$arRes['ID']."' and STATUS_ID IN ('UC_6QWK0K') ");
$str06leadRes = $str06leadSql->Fetch();

$str07leadSql = $DB->Query("Select count(*) as act_lead_count from b_crm_lead where ASSIGNED_BY_ID = '".$arRes['ID']."' and STATUS_ID IN ('UC_ZCVQ0B') ");
$str07leadRes = $str07leadSql->Fetch();

$str08leadSql = $DB->Query("Select count(*) as act_lead_count from b_crm_lead where ASSIGNED_BY_ID = '".$arRes['ID']."' and STATUS_ID IN ('UC_9WUJ49') ");
$str08leadRes = $str08leadSql->Fetch();

$str09leadSql = $DB->Query("Select count(*) as act_lead_count from b_crm_lead where ASSIGNED_BY_ID = '".$arRes['ID']."' and STATUS_ID IN ('UC_OD8Y57') ");
$str09leadRes = $str09leadSql->Fetch();

$str10leadSql = $DB->Query("Select count(*) as act_lead_count from b_crm_lead where ASSIGNED_BY_ID = '".$arRes['ID']."' and STATUS_ID IN ('UC_U2UJ60') ");
$str10leadRes = $str10leadSql->Fetch();

$str11leadSql = $DB->Query("Select count(*) as act_lead_count from b_crm_lead where ASSIGNED_BY_ID = '".$arRes['ID']."' and STATUS_ID IN ('UC_0L8X7G') ");
$str11leadRes = $str11leadSql->Fetch();

$str12leadSql = $DB->Query("Select count(*) as act_lead_count from b_crm_lead where ASSIGNED_BY_ID = '".$arRes['ID']."' and STATUS_ID IN ('7') ");
$str12leadRes = $str12leadSql->Fetch();
	}
?>
<tr>
<td align='left' style='border:1px #f5f9f9 solid;width:15%;'><a href="/company/personal/user/<?=$arRes['ID'];?>/" bx-tooltip-user-id="<?=$arRes['ID'];?>"><?=$arRes['NAME']." ".$arRes['LAST_NAME']; ?></a></td>
<td align='center' style='border:1px #f5f9f9 solid;width:5%;'><a href="/local/custom-reports/agent-active-leads/lead-details/?ASSIGNED_BY_ID=<?=$arRes['ID'];?>&LEAD_TYPE=ACTIVE&LEAD_STATUS=NEW&fromdate=<?=$_REQUEST['fromdate'];?>&todate=<?=$_REQUEST['todate'];?>"><?=$str01leadRes['act_lead_count'];?></a></td>
<td align='center' style='border:1px #f5f9f9 solid;width:5%;'><a href="/local/custom-reports/agent-active-leads/lead-details/?ASSIGNED_BY_ID=<?=$arRes['ID'];?>&LEAD_TYPE=ACTIVE&LEAD_STATUS=IN_PROCESS&fromdate=<?=$_REQUEST['fromdate'];?>&todate=<?=$_REQUEST['todate'];?>"><?=$str02leadRes['act_lead_count'];?></a></td>
<td align='center' style='border:1px #f5f9f9 solid;width:5%;'><a href="/local/custom-reports/agent-active-leads/lead-details/?ASSIGNED_BY_ID=<?=$arRes['ID'];?>&LEAD_TYPE=ACTIVE&LEAD_STATUS=UC_23YNYD&fromdate=<?=$_REQUEST['fromdate'];?>&todate=<?=$_REQUEST['todate'];?>"><?=$str03leadRes['act_lead_count'];?></a></td>	
<td align='center' style='border:1px #f5f9f9 solid;width:5%;'><a href="/local/custom-reports/agent-active-leads/lead-details/?ASSIGNED_BY_ID=<?=$arRes['ID'];?>&LEAD_TYPE=ACTIVE&LEAD_STATUS=UC_TCX0EY&fromdate=<?=$_REQUEST['fromdate'];?>&todate=<?=$_REQUEST['todate'];?>"><?=$str04leadRes['act_lead_count'];?></a></td>
<td align='center' style='border:1px #f5f9f9 solid;width:5%;'><a href="/local/custom-reports/agent-active-leads/lead-details/?ASSIGNED_BY_ID=<?=$arRes['ID'];?>&LEAD_TYPE=ACTIVE&LEAD_STATUS=UC_TB6VLO&fromdate=<?=$_REQUEST['fromdate'];?>&todate=<?=$_REQUEST['todate'];?>"><?=$str05leadRes['act_lead_count']?></a></td>
<td align='center' style='border:1px #f5f9f9 solid;width:5%;'><a href="/local/custom-reports/agent-active-leads/lead-details/?ASSIGNED_BY_ID=<?=$arRes['ID'];?>&LEAD_TYPE=ACTIVE&LEAD_STATUS=UC_6QWK0K&fromdate=<?=$_REQUEST['fromdate'];?>&todate=<?=$_REQUEST['todate'];?>"><?=$str06leadRes['act_lead_count'];?></a></td>
<td align='center' style='border:1px #f5f9f9 solid;width:5%;'><a href="/local/custom-reports/agent-active-leads/lead-details/?ASSIGNED_BY_ID=<?=$arRes['ID'];?>&LEAD_TYPE=ACTIVE&LEAD_STATUS=UC_ZCVQ0B&fromdate=<?=$_REQUEST['fromdate'];?>&todate=<?=$_REQUEST['todate'];?>"><?=$str07leadRes['act_lead_count'];?></a></td>
<td align='center' style='border:1px #f5f9f9 solid;width:5%;'><a href="/local/custom-reports/agent-active-leads/lead-details/?ASSIGNED_BY_ID=<?=$arRes['ID'];?>&LEAD_TYPE=ACTIVE&LEAD_STATUS=UC_9WUJ49&fromdate=<?=$_REQUEST['fromdate'];?>&todate=<?=$_REQUEST['todate'];?>"><?=$str08leadRes['act_lead_count'];?></a></td>
<td align='center' style='border:1px #f5f9f9 solid;width:5%;'><a href="/local/custom-reports/agent-active-leads/lead-details/?ASSIGNED_BY_ID=<?=$arRes['ID'];?>&LEAD_TYPE=ACTIVE&LEAD_STATUS=UC_OD8Y57&fromdate=<?=$_REQUEST['fromdate'];?>&todate=<?=$_REQUEST['todate'];?>"><?=$str09leadRes['act_lead_count'];?></a></td>
<td align='center' style='border:1px #f5f9f9 solid;width:5%;'><a href="/local/custom-reports/agent-active-leads/lead-details/?ASSIGNED_BY_ID=<?=$arRes['ID'];?>&LEAD_TYPE=ACTIVE&LEAD_STATUS=UC_U2UJ60&fromdate=<?=$_REQUEST['fromdate'];?>&todate=<?=$_REQUEST['todate'];?>"><?=$str10leadRes['act_lead_count'];?></a></td>
<td align='center' style='border:1px #f5f9f9 solid;width:5%;'><a href="/local/custom-reports/agent-active-leads/lead-details/?ASSIGNED_BY_ID=<?=$arRes['ID'];?>&LEAD_TYPE=ACTIVE&LEAD_STATUS=UC_0L8X7G&fromdate=<?=$_REQUEST['fromdate'];?>&todate=<?=$_REQUEST['todate'];?>"><?=$str11leadRes['act_lead_count'];?></a></td>
<td align='center' style='border:1px #f5f9f9 solid;width:5%;'><a href="/local/custom-reports/agent-active-leads/lead-details/?ASSIGNED_BY_ID=<?=$arRes['ID'];?>&LEAD_TYPE=ACTIVE&LEAD_STATUS=7&fromdate=<?=$_REQUEST['fromdate'];?>&todate=<?=$_REQUEST['todate'];?>"><?=$str12leadRes['act_lead_count'];?></a></td>
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
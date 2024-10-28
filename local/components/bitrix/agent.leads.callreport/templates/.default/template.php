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

}
else
{
$strSql = "Select ID,NAME,SECOND_NAME,LAST_NAME,EMAIL,c.UF_DEPARTMENT AS DEP from b_user a,b_user_group b,b_uts_user c where a.active ='Y' and a.LID ='s1' and a.ID = b.USER_ID and b.GROUP_ID='11' and c.VALUE_ID=a.ID ORDER BY a.ID ASC";

}


$dbRes = $DB->Query($strSql);
 global $APPLICATION;

	$GLOBALS['APPLICATION']->SetAdditionalCSS('/local/components/bitrix/agent.score/templates/.default/datatables.min.css');

	$GLOBALS['APPLICATION']->AddHeadScript('/local/components/bitrix/agent.score/templates/.default/datatables.min.js');
?>
<h2>Call, Conversation & Activity Report</h2>
<style>

table.fold-table > tbody > tr.view td, table.fold-table > tbody > tr.view th {
  cursor: pointer;
}
table#score > tbody > tr.view td:first-child,
table#score > tbody > tr.view th:first-child {
  position: relative;
  padding-left: 5px;
}

table#score > tbody > tr.view:nth-child(4n-1) {
  background: #eee;
}
table#score > tbody > tr.view:hover {
  background: #ff6347;
}
table#score > tbody > tr.view.open {
  background: #ff6347;
  color: white;
}
table#score > tbody > tr.view.open td:first-child:before, table#score > tbody > tr.view.open th:first-child:before {
  transform: rotate(-180deg);
  color: #333;
}
table#score > tbody > tr.fold {
  display: none;
}
table#score > tbody > tr.fold.open {
  display: table-row;
}
table#score tr:hover {background-color: #dadcda;cursor: pointer;}
.fold-content {
  padding: 0.5em;
}
.fold-content h3 {
  margin-top: 0;
}
.fold-content > table {
  border: 2px solid #ccc;
}
.fold-content > table > tbody tr:nth-child(even) {
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
<th align='center' style='border:1px #f5f9f9 solid; color:#fff;'>Won Leads</th>
<th align='center' style='border:1px #f5f9f9 solid; color:#fff;'>Active Leads</th>
<th align='center' style='border:1px #f5f9f9 solid; color:#fff;'>Junk Leads</th>
</tr>
	</thead>
<?
while($arRes = $dbRes->Fetch())
{
	if($fromdate!=''){

/*------------------------Convertion Ratio------------------------------*/
$leadconvSql = $DB->Query("select count(*) as lead_convertion_count from b_crm_lead where ASSIGNED_BY_ID = '".$arRes['ID']."' and STATUS_ID = 'CONVERTED' and DATE_MODIFY between '".$fromdate."' and '".$todate."' ");
$leadconvRes = $leadconvSql->Fetch();

/*------------------------Active Leads------------------------------*/
$stractleadSql = $DB->Query("Select count(*) as act_lead_count from b_crm_lead where ASSIGNED_BY_ID = '".$arRes['ID']."' and STATUS_ID IN ('UC_9WUJ49','UC_23YNYD','UC_TCX0EY','IN_PROCESS','UC_OD8Y57','UC_TB6VLO','UC_6QWK0K',7,'UC_U2UJ60') and DATE_MODIFY between '".$fromdate."' and '".$todate."' ");
$stractleadRes = $stractleadSql->Fetch();


/*------------------------Junk Leads------------------------------*/
$strjunkleadSql = $DB->Query("Select count(*) as junk_lead_count from b_crm_lead where ASSIGNED_BY_ID = '".$arRes['ID']."' and STATUS_ID IN ('JUNK',1,2,3,4,5,6) and DATE_MODIFY between '".$fromdate."' and '".$todate."'");
$strjunkleadRes = $strjunkleadSql->Fetch();


	}else
	{
		/*------------------------Convertion Ratio------------------------------*/
$leadconvSql = $DB->Query("select count(*) as lead_convertion_count from b_crm_lead where ASSIGNED_BY_ID = '".$arRes['ID']."' and STATUS_ID = 'CONVERTED'");
$leadconvRes = $leadconvSql->Fetch();

/*------------------------Active Leads------------------------------*/
$stractleadSql = $DB->Query("Select count(*) as act_lead_count from b_crm_lead where ASSIGNED_BY_ID = '".$arRes['ID']."' and STATUS_ID IN ('UC_9WUJ49','UC_23YNYD','UC_TCX0EY','IN_PROCESS','UC_OD8Y57','UC_TB6VLO','UC_6QWK0K',7,'UC_U2UJ60') ");
$stractleadRes = $stractleadSql->Fetch();

/*------------------------Junk Leads------------------------------*/
$strjunkleadSql = $DB->Query("Select count(*) as junk_lead_count from b_crm_lead where ASSIGNED_BY_ID = '".$arRes['ID']."' and STATUS_ID IN ('JUNK',1,2,3,4,5,6)");
$strjunkleadRes = $strjunkleadSql->Fetch();


	}
?>
<tr class="view">
<td align='left' style='border:1px #f5f9f9 solid;width:15%;padding-left:20px'><a href="/company/personal/user/<?=$arRes['ID'];?>/" bx-tooltip-user-id="<?=$arRes['ID'];?>"><?=$arRes['NAME']." ".$arRes['LAST_NAME']; ?></a></td>
<td align='center' style='border:1px #f5f9f9 solid;width:5%;'><a href="/local/custom-reports/agent-leads-call-report/lead-details/?ASSIGNED_BY_ID=<?=$arRes['ID'];?>&LEAD_TYPE=WON&fromdate=<?=$_REQUEST['fromdate'];?>&todate=<?=$_REQUEST['todate'];?>"><?=$leadconvRes['lead_convertion_count'];?></a></td>
<td align='center' style='border:1px #f5f9f9 solid;width:5%;'><a href="/local/custom-reports/agent-leads-call-report/lead-details/?ASSIGNED_BY_ID=<?=$arRes['ID'];?>&LEAD_TYPE=ACTIVE&fromdate=<?=$_REQUEST['fromdate'];?>&todate=<?=$_REQUEST['todate'];?>"><?=$stractleadRes['act_lead_count'];?></a></td>
<td align='center' style='border:1px #f5f9f9 solid;width:5%;'><a href="/local/custom-reports/agent-leads-call-report/lead-details/?ASSIGNED_BY_ID=<?=$arRes['ID'];?>&LEAD_TYPE=JUNK&fromdate=<?=$_REQUEST['fromdate'];?>&todate=<?=$_REQUEST['todate'];?>"><?=$strjunkleadRes['junk_lead_count'];?></a></td>

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

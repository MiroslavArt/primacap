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
<h2>Junk Lead (with Stages)</h2>
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
<th align='center' style='border:1px #f5f9f9 solid; color:#fff;'>Junk</th>
<th align='center' style='border:1px #f5f9f9 solid; color:#fff;'>Additional Services</th>
<th align='center' style='border:1px #f5f9f9 solid; color:#fff;'>Not Qualified</th>
<th align='center' style='border:1px #f5f9f9 solid; color:#fff;'>Secondary</th>
<th align='center' style='border:1px #f5f9f9 solid; color:#fff;'>Junk 1</th>
<th align='center' style='border:1px #f5f9f9 solid; color:#fff;'>Junk 2</th>
<th align='center' style='border:1px #f5f9f9 solid; color:#fff;'>Duplicate</th>
</tr>
</thead>
<?
while($arRes = $dbRes->Fetch())
{
	if($fromdate!=''){
/*------------------------Junk Leads------------------------------*/

$str01leadSql = $DB->Query("Select count(*) as junk_lead_count from b_crm_lead where ASSIGNED_BY_ID = '".$arRes['ID']."' and STATUS_ID IN ('JUNK') and DATE_MODIFY between '".$fromdate."' and '".$todate."'");
$str01leadRes = $str01leadSql->Fetch();

$str02leadSql = $DB->Query("Select count(*) as junk_lead_count from b_crm_lead where ASSIGNED_BY_ID = '".$arRes['ID']."' and STATUS_ID IN (1)  and DATE_MODIFY between '".$fromdate."' and '".$todate."'");
$str02leadRes = $str02leadSql->Fetch();

$str03leadSql = $DB->Query("Select count(*) as junk_lead_count from b_crm_lead where ASSIGNED_BY_ID = '".$arRes['ID']."' and STATUS_ID IN (2)  and DATE_MODIFY between '".$fromdate."' and '".$todate."'");
$str03leadRes = $str03leadSql->Fetch();

$str04leadSql = $DB->Query("Select count(*) as junk_lead_count from b_crm_lead where ASSIGNED_BY_ID = '".$arRes['ID']."' and STATUS_ID IN (3)  and DATE_MODIFY between '".$fromdate."' and '".$todate."' ");
$str04leadRes = $str04leadSql->Fetch();

$str05leadSql = $DB->Query("Select count(*) as junk_lead_count from b_crm_lead where ASSIGNED_BY_ID = '".$arRes['ID']."' and STATUS_ID IN (4)  and DATE_MODIFY between '".$fromdate."' and '".$todate."'");
$str05leadRes = $str05leadSql->Fetch();

$str06leadSql = $DB->Query("Select count(*) as junk_lead_count from b_crm_lead where ASSIGNED_BY_ID = '".$arRes['ID']."' and STATUS_ID IN (5)  and DATE_MODIFY between '".$fromdate."' and '".$todate."'");
$str06leadRes = $str06leadSql->Fetch();

$str07leadSql = $DB->Query("Select count(*) as junk_lead_count from b_crm_lead where ASSIGNED_BY_ID = '".$arRes['ID']."' and STATUS_ID IN (6)  and DATE_MODIFY between '".$fromdate."' and '".$todate."'");
$str07leadRes = $str07leadSql->Fetch();


	}else
	{
/*------------------------Active Leads------------------------------*/

$str01leadSql = $DB->Query("Select count(*) as junk_lead_count from b_crm_lead where ASSIGNED_BY_ID = '".$arRes['ID']."' and STATUS_ID IN ('JUNK')");
$str01leadRes = $str01leadSql->Fetch();

$str02leadSql = $DB->Query("Select count(*) as junk_lead_count from b_crm_lead where ASSIGNED_BY_ID = '".$arRes['ID']."' and STATUS_ID IN (1) ");
$str02leadRes = $str02leadSql->Fetch();

$str03leadSql = $DB->Query("Select count(*) as junk_lead_count from b_crm_lead where ASSIGNED_BY_ID = '".$arRes['ID']."' and STATUS_ID IN (2)");
$str03leadRes = $str03leadSql->Fetch();

$str04leadSql = $DB->Query("Select count(*) as junk_lead_count from b_crm_lead where ASSIGNED_BY_ID = '".$arRes['ID']."' and STATUS_ID IN (3)");
$str04leadRes = $str04leadSql->Fetch();

$str05leadSql = $DB->Query("Select count(*) as junk_lead_count from b_crm_lead where ASSIGNED_BY_ID = '".$arRes['ID']."' and STATUS_ID IN (4)");
$str05leadRes = $str05leadSql->Fetch();

$str06leadSql = $DB->Query("Select count(*) as junk_lead_count from b_crm_lead where ASSIGNED_BY_ID = '".$arRes['ID']."' and STATUS_ID IN (5)");
$str06leadRes = $str06leadSql->Fetch();

$str07leadSql = $DB->Query("Select count(*) as junk_lead_count from b_crm_lead where ASSIGNED_BY_ID = '".$arRes['ID']."' and STATUS_ID IN (6)");
$str07leadRes = $str07leadSql->Fetch();

	}
?>
<tr class="view">
<td align='left' style='border:1px #f5f9f9 solid;width:15%;'><a href="/company/personal/user/<?=$arRes['ID'];?>/" bx-tooltip-user-id="<?=$arRes['ID'];?>"><?=$arRes['NAME']." ".$arRes['LAST_NAME']; ?></a></td>
<td align='center' style='border:1px #f5f9f9 solid;width:5%;'><a href="/local/custom-reports/agent-junk-leads/lead-details/?ASSIGNED_BY_ID=<?=$arRes['ID'];?>&LEAD_TYPE=JUNK&LEAD_STATUS=JUNK&fromdate=<?=$_REQUEST['fromdate'];?>&todate=<?=$_REQUEST['todate'];?>"><?=$str01leadRes['junk_lead_count'];?></a></td>
<td align='center' style='border:1px #f5f9f9 solid;width:5%;'><a href="/local/custom-reports/agent-junk-leads/lead-details/?ASSIGNED_BY_ID=<?=$arRes['ID'];?>&LEAD_TYPE=JUNK&LEAD_STATUS=1&fromdate=<?=$_REQUEST['fromdate'];?>&todate=<?=$_REQUEST['todate'];?>"><?=$str02leadRes['junk_lead_count'];?></a></td>
<td align='center' style='border:1px #f5f9f9 solid;width:5%;'><a href="/local/custom-reports/agent-junk-leads/lead-details/?ASSIGNED_BY_ID=<?=$arRes['ID'];?>&LEAD_TYPE=JUNK&LEAD_STATUS=2&fromdate=<?=$_REQUEST['fromdate'];?>&todate=<?=$_REQUEST['todate'];?>"><?=$str03leadRes['junk_lead_count'];?></a></td>	
<td align='center' style='border:1px #f5f9f9 solid;width:5%;'><a href="/local/custom-reports/agent-junk-leads/lead-details/?ASSIGNED_BY_ID=<?=$arRes['ID'];?>&LEAD_TYPE=JUNK&LEAD_STATUS=3&fromdate=<?=$_REQUEST['fromdate'];?>&todate=<?=$_REQUEST['todate'];?>"><?=$str04leadRes['junk_lead_count'];?></a></td>
<td align='center' style='border:1px #f5f9f9 solid;width:5%;'><a href="/local/custom-reports/agent-junk-leads/lead-details/?ASSIGNED_BY_ID=<?=$arRes['ID'];?>&LEAD_TYPE=JUNK&LEAD_STATUS=4&fromdate=<?=$_REQUEST['fromdate'];?>&todate=<?=$_REQUEST['todate'];?>"><?=$str05leadRes['junk_lead_count']?></a></td>
<td align='center' style='border:1px #f5f9f9 solid;width:5%;'><a href="/local/custom-reports/agent-junk-leads/lead-details/?ASSIGNED_BY_ID=<?=$arRes['ID'];?>&LEAD_TYPE=JUNK&LEAD_STATUS=5&fromdate=<?=$_REQUEST['fromdate'];?>&todate=<?=$_REQUEST['todate'];?>"><?=$str06leadRes['junk_lead_count'];?></a></td>
<td align='center' style='border:1px #f5f9f9 solid;width:5%;'><a href="/local/custom-reports/agent-junk-leads/lead-details/?ASSIGNED_BY_ID=<?=$arRes['ID'];?>&LEAD_TYPE=JUNK&LEAD_STATUS=6&fromdate=<?=$_REQUEST['fromdate'];?>&todate=<?=$_REQUEST['todate'];?>"><?=$str07leadRes['junk_lead_count'];?></a></td>
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
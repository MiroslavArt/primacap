<?php
include($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");
   \Bitrix\Main\UI\Extension::load("ui.tooltip");
global $DB;
$currentdate=date('d-m-Y');

global $APPLICATION;

	$GLOBALS['APPLICATION']->SetAdditionalCSS('/local/components/bitrix/agent.active.leads/templates/.default/datatables.min.css');

	$GLOBALS['APPLICATION']->AddHeadScript('/local/components/bitrix/agent.active.leads/templates/.default/datatables.min.js');

$fromdate=$_REQUEST['fromdate'];
$todate=$_REQUEST['todate'];

if($fromdate!='')
{
if($_REQUEST['LEAD_TYPE']=='WON')
{
/*------------------------WON Leads------------------------------*/
$leadsSql = $DB->Query("select ID,TITLE,STATUS_ID from b_crm_lead where ASSIGNED_BY_ID = '".$_REQUEST['ASSIGNED_BY_ID']."' and STATUS_ID = 'CONVERTED' and DATE_MODIFY between '".$fromdate."' and '".$todate."'");
}
elseif($_REQUEST['LEAD_TYPE']=='ACTIVE')
{
/*------------------------Active Leads------------------------------*/
$leadsSql = $DB->Query("Select ID,TITLE,STATUS_ID from b_crm_lead where ASSIGNED_BY_ID = '".$_REQUEST['ASSIGNED_BY_ID']."' and STATUS_ID IN ('UC_9WUJ49','UC_23YNYD','UC_TCX0EY','IN_PROCESS','UC_OD8Y57','UC_TB6VLO','UC_6QWK0K',7,'UC_U2UJ60') and DATE_MODIFY between '".$fromdate."' and '".$todate."'");
}
elseif($_REQUEST['LEAD_TYPE']=='JUNK')
{
/*------------------------Junk Leads------------------------------*/
$leadsSql = $DB->Query("Select ID,TITLE,STATUS_ID from b_crm_lead where ASSIGNED_BY_ID = '".$_REQUEST['ASSIGNED_BY_ID']."' and STATUS_ID IN ('JUNK',1,2,3,4,5,6) and DATE_MODIFY between '".$fromdate."' and '".$todate."'");
}
else
{
$leadsSql = $DB->Query("Select ID,TITLE,STATUS_ID from b_crm_lead where ASSIGNED_BY_ID = '".$_REQUEST['ASSIGNED_BY_ID']."' and STATUS_ID IN ('UC_9WUJ49','UC_23YNYD','UC_TCX0EY','IN_PROCESS','UC_OD8Y57','UC_TB6VLO','UC_6QWK0K',7,'UC_U2UJ60','JUNK',1,2,3,4,5,6,'CONVERTED') and DATE_MODIFY between '".$fromdate."' and '".$todate."'");
}
}else{
if($_REQUEST['LEAD_TYPE']=='WON')
{
/*------------------------WON Leads------------------------------*/
$leadsSql = $DB->Query("select ID,TITLE,STATUS_ID from b_crm_lead where ASSIGNED_BY_ID = '".$_REQUEST['ASSIGNED_BY_ID']."' and STATUS_ID = 'CONVERTED' ");
}
elseif($_REQUEST['LEAD_TYPE']=='ACTIVE')
{
/*------------------------Active Leads------------------------------*/
$leadsSql = $DB->Query("Select ID,TITLE,STATUS_ID from b_crm_lead where ASSIGNED_BY_ID = '".$_REQUEST['ASSIGNED_BY_ID']."' and STATUS_ID IN ('UC_9WUJ49','UC_23YNYD','UC_TCX0EY','IN_PROCESS','UC_OD8Y57','UC_TB6VLO','UC_6QWK0K',7,'UC_U2UJ60') ");
}
elseif($_REQUEST['LEAD_TYPE']=='JUNK')
{
/*------------------------Junk Leads------------------------------*/
$leadsSql = $DB->Query("Select ID,TITLE,STATUS_ID from b_crm_lead where ASSIGNED_BY_ID = '".$_REQUEST['ASSIGNED_BY_ID']."' and STATUS_ID IN ('JUNK',1,2,3,4,5,6) ");
}
else
{
$leadsSql = $DB->Query("Select ID,TITLE,STATUS_ID from b_crm_lead where ASSIGNED_BY_ID = '".$_REQUEST['ASSIGNED_BY_ID']."' and STATUS_ID IN ('UC_9WUJ49','UC_23YNYD','UC_TCX0EY','IN_PROCESS','UC_OD8Y57','UC_TB6VLO','UC_6QWK0K',7,'UC_U2UJ60','JUNK',1,2,3,4,5,6,'CONVERTED') ");
}
}

?>

<style>

table#score > tbody > tr.view:nth-child(4n-1) {
  background: #eee;
}

table#score tr:hover {background-color: #dadcda;cursor: pointer;}

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
<th align='center' style='border:1px #f5f9f9 solid; color:#fff;'>ID</th>
<th align='center' style='border:1px #f5f9f9 solid; color:#fff;'>Lead Name</th>
<th align='center' style='border:1px #f5f9f9 solid; color:#fff;'>Lead Status</th>
<th align='center' style='border:1px #f5f9f9 solid; color:#fff;'>No., of Successful Calls</th>
<th align='center' style='border:1px #f5f9f9 solid; color:#fff;'>Successful Calls Duration</th>
<th align='center' style='border:1px #f5f9f9 solid; color:#fff;'>No., of Unsuccessful Calls</th>
<th align='center' style='border:1px #f5f9f9 solid; color:#fff;'>No., of Conversations</th>
<th align='center' style='border:1px #f5f9f9 solid; color:#fff;'>No., of Activities</th>
</tr>
</thead>
<?
while($ileadconvRes = $leadsSql->Fetch()) {

$istrleadSql = $DB->Query("Select a.ID,a.TITLE,b.Name AS STATUS_NAME,b.COLOR from b_crm_lead a,b_crm_status b where a.ID = '".$ileadconvRes['ID']."' and b.ENTITY_ID ='STATUS' and b.STATUS_ID = a.STATUS_ID ");
$istrleadSqlRes = $istrleadSql->Fetch();
$istrsucccallSql = $DB->Query("Select count(*) as successful_calls_count from b_voximplant_statistic  where CRM_ENTITY_ID = '".$istrleadSqlRes['ID']."' and PORTAL_USER_ID = '".$_REQUEST['ASSIGNED_BY_ID']."' and CALL_STATUS = '1' and CRM_ENTITY_TYPE = 'LEAD '");
$istrsuccRescall= $istrsucccallSql->Fetch();
$strconvSql = $DB->Query("Select count(*) as conv_count from b_crm_act where RESPONSIBLE_ID = '".$_REQUEST['ASSIGNED_BY_ID']."' and OWNER_TYPE_ID ='1' and OWNER_ID = '".$istrleadSqlRes['ID']."' and PROVIDER_ID = 'IMOPENLINES_SESSION'");
$convRes = $strconvSql->Fetch();
$stractSql = $DB->Query("Select count(*) as activities_count from b_crm_act where RESPONSIBLE_ID = '".$_REQUEST['ASSIGNED_BY_ID']."' and OWNER_TYPE_ID ='1' and OWNER_ID = '".$istrleadSqlRes['ID']."' and PROVIDER_ID != 'VOXIMPLANT_CALL' and PROVIDER_ID != 'IMOPENLINES_SESSION'");
$actRes = $stractSql->Fetch();
$istrsucccallDSql = $DB->Query("Select SUM(CALL_DURATION) from b_voximplant_statistic  where CRM_ENTITY_ID = '".$istrleadSqlRes['ID']."' and PORTAL_USER_ID = '".$_REQUEST['ASSIGNED_BY_ID']."' and CALL_STATUS = '1' and CRM_ENTITY_TYPE = 'LEAD '");
$istrsuccDRescall= $istrsucccallDSql->Fetch();
$sucDseconds = round($istrsuccDRescall['SUM(CALL_DURATION)']);
$sucDsecondsOut = sprintf('%02d:%02d:%02d', ($sucDseconds/ 3600),($sucDseconds/ 60 % 60), $sucDseconds% 60);
$istrunsucccallSql = $DB->Query("Select count(*) as unsuccessful_calls_count from b_voximplant_statistic  where CRM_ENTITY_ID = '".$istrleadSqlRes['ID']."' and PORTAL_USER_ID = '".$_REQUEST['ASSIGNED_BY_ID']."' and CALL_STATUS = '0' and CRM_ENTITY_TYPE = 'LEAD '");
$istrunsuccRescall= $istrunsucccallSql->Fetch();


?>
              <tr>
<td align='center' style='border:1px #f5f9f9 solid;width:5%;'><a href="/crm/lead/details/<?=$istrleadSqlRes['ID'];?>/"><?=$istrleadSqlRes['ID'];?></a></td>
<td align='left' style='border:1px #f5f9f9 solid;width:25%;'><a href="/crm/lead/details/<?=$istrleadSqlRes['ID'];?>/"><?=$istrleadSqlRes['TITLE'];?></a></td>
<td align='center' style="border:1px #f5f9f9 solid;width:10%;background:<?=$istrleadSqlRes['COLOR'];?>"><?=$istrleadSqlRes['STATUS_NAME'];?></td>
<td align='center' style='border:1px #f5f9f9 solid;width:2%;'><?=$istrsuccRescall['successful_calls_count'];?></td>	
<td align='center' style='border:1px #f5f9f9 solid;width:10%;'><?=$sucDsecondsOut;?></td>
<td align='center' style='border:1px #f5f9f9 solid;width:3%;'><?=$istrunsuccRescall['unsuccessful_calls_count'];?></td>
<td align='center' style='border:1px #f5f9f9 solid;width:3%;'><?=$convRes['conv_count'];?></td>
<td align='center' style='border:1px #f5f9f9 solid;width:3%;'><?=$actRes['activities_count'];?></td>

              </tr>
				<? } ?>

          </table>    
<script>
	new DataTable('#score', {
    lengthMenu: [
        [20, 40, 60, -1],
        [20, 40, 60, 'All']
    ]
});
	</script>

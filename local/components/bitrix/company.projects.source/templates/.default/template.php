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

$strSql = "Select ID,NAME from b_iblock_element where ACTIVE ='Y' and IBLOCK_ID='29' ORDER BY ID DESC";
$dbRes = $DB->Query($strSql);

 global $APPLICATION;

	$GLOBALS['APPLICATION']->SetAdditionalCSS('/local/components/bitrix/agent.active.leads/templates/.default/datatables.min.css');

	$GLOBALS['APPLICATION']->AddHeadScript('/local/components/bitrix/agent.active.leads/templates/.default/datatables.min.js');

?>
<h2>Project Leads from Different Sources</h2>
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
<th align='center' style='border:1px #f5f9f9 solid; color:#fff;'>Project</th>
<th align='center' style='border:1px #f5f9f9 solid; color:#fff;'>Call</th>
<th align='center' style='border:1px #f5f9f9 solid; color:#fff;'>Facebook Ads</th>
<th align='center' style='border:1px #f5f9f9 solid; color:#fff;'>Whatsapp</th>
<th align='center' style='border:1px #f5f9f9 solid; color:#fff;'>Hubspot</th>
<th align='center' style='border:1px #f5f9f9 solid; color:#fff;'>Russian Agency Leads</th>
</tr>
</thead>
<?
while($arRes = $dbRes->Fetch())
{
	if($fromdate!=''){
$str01leadSql = $DB->Query("Select count(a.VALUE_ID) as act_lead_count from b_uts_crm_lead a,b_crm_lead b where a.UF_CRM_1646721978 = '".$arRes['NAME']."' and a.VALUE_ID =b.ID and b.SOURCE_ID IN ('CALL') and b.DATE_CREATE between '".$fromdate."' and '".$todate."'");
$str01leadRes = $str01leadSql->Fetch();

$str02leadSql = $DB->Query("Select count(a.VALUE_ID) as act_lead_count from b_uts_crm_lead a,b_crm_lead b where a.UF_CRM_1646721978 = '".$arRes['NAME']."' and a.VALUE_ID =b.ID and b.SOURCE_ID IN ('WEBFORM') and b.DATE_CREATE between '".$fromdate."' and '".$todate."'");
$str02leadRes = $str02leadSql->Fetch();

$str03leadSql = $DB->Query("Select count(a.VALUE_ID) as act_lead_count from b_uts_crm_lead a,b_crm_lead b where a.UF_CRM_1646721978 = '".$arRes['NAME']."' and a.VALUE_ID =b.ID and b.SOURCE_ID IN ('1') and b.DATE_CREATE between '".$fromdate."' and '".$todate."'");
$str03leadRes = $str03leadSql->Fetch();

$str04leadSql = $DB->Query("Select count(a.VALUE_ID) as act_lead_count from b_uts_crm_lead a,b_crm_lead b where a.UF_CRM_1646721978 = '".$arRes['NAME']."' and a.VALUE_ID =b.ID and b.SOURCE_ID IN ('2') and b.DATE_CREATE between '".$fromdate."' and '".$todate."'");
$str04leadRes = $str04leadSql->Fetch();

$str05leadSql = $DB->Query("Select count(a.VALUE_ID) as act_lead_count from b_uts_crm_lead a,b_crm_lead b where a.UF_CRM_1646721978 = '".$arRes['NAME']."' and a.VALUE_ID =b.ID and b.SOURCE_ID IN ('3') and b.DATE_CREATE between '".$fromdate."' and '".$todate."'");
$str05leadRes = $str05leadSql->Fetch();
	}else
	{
$str01leadSql = $DB->Query("Select count(a.VALUE_ID) as act_lead_count from b_uts_crm_lead a,b_crm_lead b where a.UF_CRM_1646721978 = '".$arRes['NAME']."' and a.VALUE_ID =b.ID and b.SOURCE_ID IN ('CALL') ");
$str01leadRes = $str01leadSql->Fetch();

$str02leadSql = $DB->Query("Select count(a.VALUE_ID) as act_lead_count from b_uts_crm_lead a,b_crm_lead b where a.UF_CRM_1646721978 = '".$arRes['NAME']."' and a.VALUE_ID =b.ID and b.SOURCE_ID IN ('WEBFORM') ");
$str02leadRes = $str02leadSql->Fetch();

$str03leadSql = $DB->Query("Select count(a.VALUE_ID) as act_lead_count from b_uts_crm_lead a,b_crm_lead b where a.UF_CRM_1646721978 = '".$arRes['NAME']."' and a.VALUE_ID =b.ID and b.SOURCE_ID IN ('1') ");
$str03leadRes = $str03leadSql->Fetch();

$str04leadSql = $DB->Query("Select count(a.VALUE_ID) as act_lead_count from b_uts_crm_lead a,b_crm_lead b where a.UF_CRM_1646721978 = '".$arRes['NAME']."' and a.VALUE_ID =b.ID and b.SOURCE_ID IN ('2') ");
$str04leadRes = $str04leadSql->Fetch();

$str05leadSql = $DB->Query("Select count(a.VALUE_ID) as act_lead_count from b_uts_crm_lead a,b_crm_lead b where a.UF_CRM_1646721978 = '".$arRes['NAME']."' and a.VALUE_ID =b.ID and b.SOURCE_ID  IN ('3') ");
$str05leadRes = $str05leadSql->Fetch();
	}
?>
<tr>
<td align='left' style='border:1px #f5f9f9 solid;width:15%;'><?=$arRes['NAME']; ?></td>
<td align='center' style='border:1px #f5f9f9 solid;width:5%;'><a href="/local/custom-reports/project-leads-from-sources/lead-details/?PROJECT_ID=<?=$arRes['ID'];?>&LEAD_SOURCE=CALL&fromdate=<?=$_REQUEST['fromdate'];?>&todate=<?=$_REQUEST['todate'];?>"><?=$str01leadRes['act_lead_count'];?></a></td>
<td align='center' style='border:1px #f5f9f9 solid;width:5%;'><a href="/local/custom-reports/project-leads-from-sources/lead-details/?PROJECT_ID=<?=$arRes['ID'];?>&LEAD_SOURCE=WEBFORM&fromdate=<?=$_REQUEST['fromdate'];?>&todate=<?=$_REQUEST['todate'];?>"><?=$str02leadRes['act_lead_count'];?></a></td>
<td align='center' style='border:1px #f5f9f9 solid;width:5%;'><a href="/local/custom-reports/project-leads-from-sources/lead-details/?PROJECT_ID=<?=$arRes['ID'];?>&LEAD_SOURCE=1&fromdate=<?=$_REQUEST['fromdate'];?>&todate=<?=$_REQUEST['todate'];?>"><?=$str03leadRes['act_lead_count'];?></a></td>	
<td align='center' style='border:1px #f5f9f9 solid;width:5%;'><a href="/local/custom-reports/project-leads-from-sources/lead-details/?PROJECT_ID=<?=$arRes['ID'];?>&LEAD_SOURCE=2&fromdate=<?=$_REQUEST['fromdate'];?>&todate=<?=$_REQUEST['todate'];?>"><?=$str04leadRes['act_lead_count'];?></a></td>
<td align='center' style='border:1px #f5f9f9 solid;width:5%;'><a href="/local/custom-reports/project-leads-from-sources/lead-details/?PROJECT_ID=<?=$arRes['ID'];?>&LEAD_SOURCE=3&fromdate=<?=$_REQUEST['fromdate'];?>&todate=<?=$_REQUEST['todate'];?>"><?=$str05leadRes['act_lead_count']?></a></td>
</tr>
<?
} 
?>
</table>
<script>

new DataTable('#score', {
    initComplete: function () {
        this.api()
            .columns([0])
            .every(function () {
                let column = this;
 
                // Create select element
                let select = document.createElement('select');
                select.add(new Option('Project Name'));
                column.header().replaceChildren(select);
 
                // Apply listener for user change in value
                select.addEventListener('change', function () {
                    column
                        .search(select.value, {exact: true})
                        .draw();
                });
 
                // Add list of options
                column
                    .data()
                    .unique()
                    .sort()
                    .each(function (d, j) {
                        select.add(new Option(d));
                    });
            });
    },lengthMenu: [
        [20, 40, 60, -1],
        [20, 40, 60, 'All']
    ]
});
	</script>
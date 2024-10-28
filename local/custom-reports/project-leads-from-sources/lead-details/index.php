<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");

if(!$USER->IsAdmin())die();

$APPLICATION->SetTitle("Company Project Leads from Different Sources Details");


$APPLICATION->IncludeComponent(
	'bitrix:ui.sidepanel.wrapper',
	'',
	[
		'POPUP_COMPONENT_NAME' => 'bitrix:agent.leads.callreportdetail',
		'POPUP_COMPONENT_TEMPLATE_NAME' => '',
		'POPUP_COMPONENT_PARAMS' => [
				'PAGE_TITLE' => "Company Project Leads from Different Sources Details",
  				'ASSIGNED_BY_ID' => $_REQUEST['ASSIGNED_BY_ID'],
				'LEAD_TYPE' =>  $_REQUEST['LEAD_TYPE'],
				'PROJECT_ID' => $_REQUEST['PROJECT_ID'],
				'LEAD_SOURCE' =>  $_REQUEST['LEAD_SOURCE'],
				'fromdate' =>  $_REQUEST['fromdate'],
				'todate' =>  $_REQUEST['todate']
			],
		'USE_UI_TOOLBAR' => 'N',
		'USE_PADDING' => true,
		'PLAIN_VIEW' => true,
		'PAGE_MODE' => false,
		'PAGE_MODE_OFF_BACK_URL' => "/local/custom-reports/project-leads-from-sources/",
	]
);

?>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>
<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");

if(!$USER->IsAdmin())die();

$APPLICATION->SetTitle("Agent Leads Call, Conversation & Activity Report");


$APPLICATION->IncludeComponent(
	'bitrix:ui.sidepanel.wrapper',
	'',
	[
		'POPUP_COMPONENT_NAME' => 'bitrix:agent.leads.callreport',
		'POPUP_COMPONENT_TEMPLATE_NAME' => '',
		'POPUP_COMPONENT_PARAMS' => [
				'PAGE_TITLE' => "Agent Leads Call, Conversation & Activity Report",
			],
		'USE_UI_TOOLBAR' => 'N',
		'USE_PADDING' => true,
		'PLAIN_VIEW' => true,
		'PAGE_MODE' => false,
		'PAGE_MODE_OFF_BACK_URL' => "/local/custom-reports/",
	]
);


?>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>
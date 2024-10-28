<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");

global $USER;
 function getBitrixUserSubEmployees($user_id){
   if(CModule::IncludeModule("intranet")){

      $arUsers = CIntranetUtils::GetSubordinateEmployees($user_id, true);
      while($User = $arUsers->GetNext()){
         $arr[] = $User['ID'];
      }
      return $arr;
   }
 }

$current_user=getBitrixUserSubEmployees($USER->GetID());

if(!$USER->IsAdmin() && empty($current_user)) {echo "Access Denied"; die();}

$APPLICATION->SetTitle("Agent Junk Leads");


$APPLICATION->IncludeComponent(
	'bitrix:ui.sidepanel.wrapper',
	'',
	[
		'POPUP_COMPONENT_NAME' => 'bitrix:agent.junk.leads',
		'POPUP_COMPONENT_TEMPLATE_NAME' => '',
		'POPUP_COMPONENT_PARAMS' => [
				'PAGE_TITLE' => "Agent Junk Leads",
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
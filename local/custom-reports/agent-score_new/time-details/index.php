<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/public/timeman/timeman.php");
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

$APPLICATION->SetTitle("Agent Time Detail");

if (\Bitrix\Main\Loader::includeModule('timeman'))
{
	$APPLICATION->IncludeComponent(
		"bitrix:ui.sidepanel.wrapper",
		"",
		array(
			"POPUP_COMPONENT_NAME" => "bitrix:timeman.worktime.stats",
			"POPUP_COMPONENT_TEMPLATE_NAME" => "",
			"POPUP_COMPONENT_PARAMS" => array(
				'SCHEDULE_ID' => $_REQUEST['SCHEDULE_ID'],
				'USER_ID' => $_REQUEST['USER_ID'],
			),
		'USE_UI_TOOLBAR' => 'Y',
		'USE_PADDING' => true,
		'PLAIN_VIEW' => true,
		'PAGE_MODE' => false,
		'PAGE_MODE_OFF_BACK_URL' => "/local/custom-reports/agent-score/",
		)
	);
}

?>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>
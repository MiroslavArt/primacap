<?
define('PUBLIC_AJAX_MODE', true);
define('STOP_STATISTICS', true);
define('NO_AGENT_CHECK', true);
require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');

check_bitrix_sessid() || die;

GLOBAL $USER;
$APPLICATION->ShowAjaxHead();

//Get current user
$USER_ID = $USER->GetID();

//Array departments participating in the integration
$arDepartments = [18,19,11,12,13];
//Array of hidden stages for managers
$arStageHideManager = [
	'UC_ZCVQ0B', 	//Calls
	'CONVERTED', 	//Deal won
	'UC_U2UJ60', 	//not matched
];
//Array of hidden stages for agent
$arStageHideAgent = [
	'UC_ZCVQ0B', 	//Calls
	'1', 			//Additional Services
	'UC_U2UJ60', 	//not matched
	'2', 			//Not Qualified
	'3', 			//Secondary
	'4', 			//Junk 1
	'5', 			//Junk 2
	'JUNK',			//Junk
];

$userIsManager = false;
$userIsAgent = false;
$arResultStage = [];

$employees_tree = CIntranetUtils::GetStructure();
if(is_array($employees_tree['DATA'])){
	foreach($employees_tree['DATA'] as $key=>$value){
		if(in_array($value['ID'], $arDepartments)){
			//Check manager
			if($value['UF_HEAD'] == $USER_ID)$userIsManager = true;
			//Check agent
			if(in_array($USER_ID, $value['EMPLOYEES']))$userIsAgent = true;
		}
	}
}
//Exception Users
if(in_array($USER_ID, [3451])){
	$userIsManager = false;
	$userIsAgent = false;
}
//Hiding stages in the leads
if($userIsManager){
	$arResultStage = $arStageHideManager;
}elseif($userIsAgent){
	$arResultStage = $arStageHideAgent;
}
die(json_encode($arResultStage));
?>
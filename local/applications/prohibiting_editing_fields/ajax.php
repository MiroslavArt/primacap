<?
ini_set('display_startup_errors', 1);
ini_set('error_log', 'php-errors.log');
define("NO_KEEP_STATISTIC", true);
define("NOT_CHECK_PERMISSIONS", true);
define('STOP_STATISTICS', true);
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

foreach($_POST as $key_post=>$value_post){	
	if($key_post=='ADD_FIELD'){
		//Connecting files with user access
		if(file_exists('fieldsLead.php')) require_once 'fieldsLead.php';
		
		$arResult = [];
		//Configuring the data
		if(!is_array($value_post['USERS'])) $value_post['USERS'] = [];
		$arFieldLeadAccess[$value_post['FIELD']] = $value_post['USERS'];
		
		//Save data to file
		file_put_contents('fieldsLead.php', '<? $arFieldLeadAccess='. var_export($arFieldLeadAccess,true). '; ?>');

		die(json_encode($arResult));
	}
	if($key_post=='DEL_FIELD'){
		//Connecting files with user access
		if(file_exists('fieldsLead.php')) require_once 'fieldsLead.php';
		
		$arResult = [];
		unset($arFieldLeadAccess[$value_post['FIELD']]);
		
		//Save data to file
		file_put_contents('fieldsLead.php', '<? $arFieldLeadAccess='. var_export($arFieldLeadAccess,true). '; ?>');

		die(json_encode($arResult));
	}
	if($key_post=='GET_FIELD'){
		CModule::IncludeModule('crm');
		//Get ID employees
		$idsEmployees = [];
		$arUsers = [];
		$arResult = [];
		//Get data employees
		$userResult = \Bitrix\Main\UserTable::getList([
			'filter' => [],
			'select'=>['ID','LAST_NAME','NAME','SECOND_NAME'],
			'order' => ['LAST_NAME'=>'ASC'],
		]);
		while($user = $userResult->fetch()){
			$user['FULL_NAME'] = trim($user['LAST_NAME'].' '.$user['NAME'].' '.$user['SECOND_NAME']);
			$arUsers[$user['ID']] = $user;
		}

		//Standart fild
		$fieldsLead = [
			'NAME'=>[
				'FIELD_NAME' => 'NAME',
				'NAME_EN' => 'Name',
			],
			'SECOND_NAME'=>[
				'FIELD_NAME' => 'SECOND_NAME',
				'NAME_EN' => 'Second name',
			],
			'LAST_NAME'=>[
				'FIELD_NAME' => 'LAST_NAME',
				'NAME_EN' => 'Last name',
			],
			'ASSIGNED_BY_ID'=>[
				'FIELD_NAME' => 'ASSIGNED_BY_ID',
				'NAME_EN' => 'Responsible person',
			],
			'PHONE'=>[
				'FIELD_NAME' => 'PHONE',
				'NAME_EN' => 'Phone',
			],
			'EMAIL'=>[
				'FIELD_NAME' => 'EMAIL',
				'NAME_EN' => 'Email',
			],
		];

		//User field
		$userFieldsLead = CCrmLead::GetUserFields();
		foreach($userFieldsLead as $key=>$value){
			//Find out the name of the field
			$value['NAME_EN'] = \Bitrix\Main\UserFieldLangTable::getList(array(
				'filter' => array('USER_FIELD_ID' => $value['ID'], 'LANGUAGE_ID'=>'en'),
			))->fetch()['EDIT_FORM_LABEL'];
			$fieldsLead[$key] = [
				'FIELD_NAME'=>$value['FIELD_NAME'],
				'NAME_EN'=>$value['NAME_EN'],
			];
		}
		if(file_exists('fieldsLead.php')) require_once 'fieldsLead.php';
		foreach($arFieldLeadAccess as $key=>$value){
			$userNames = '';
			foreach($value as $key2=>$value2){
				if($userNames) $userNames .=' , ';
				$userNames .= $arUsers[$value2]['FULL_NAME'];
			}
			$arResult[] = [
				'ID'=>$key,
				'NAME_FIELD'=>$fieldsLead[$key]['NAME_EN'],
				'NAME_USER'=>$userNames,
			];
		}
		die(json_encode($arResult));
	}
}
?>
<?
//Control of access to editing fields in leads
function control_field_access(&$arFields) {
	//Connecting a file with field accesses
	if(file_exists($_SERVER['DOCUMENT_ROOT'] . '/local/applications/prohibiting_editing_fields/fieldsLead.php')) require_once $_SERVER['DOCUMENT_ROOT'] . '/local/applications/prohibiting_editing_fields/fieldsLead.php';
	$lead = CCrmLead::GetListEx([], ['ID'=>$arFields['ID']], false, false, ['*','UF_*'])->fetch();

	//We check whether the user is a manager
	$arDepartments = [18,19,11,12,13];
	$employees_tree = CIntranetUtils::GetStructure();
	$userIsManager = false;
	if(is_array($employees_tree['DATA'])){
		foreach($employees_tree['DATA'] as $key=>$value){
			if(in_array($value['ID'], $arDepartments)){
				//Check manager
				if($value['UF_HEAD'] == $arFields['MODIFY_BY_ID']){
					$userIsManager = true;
					break;
				}
			}
		}
	}
	
	//Standart fild
	$fieldsLead = [
		'TITLE'=>[
			'FIELD_NAME' => 'TITLE',
			'NAME_EN' => 'Lead Name',
		],
	'COMMENTS'=>[
		'FIELD_NAME' => 'COMMENTS',
        'NAME_EN' => 'Comments',
	],
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
	$arError = [];
	//Sorting through the changed fields 
	foreach($arFields as $key=>$value){
		if($key=='FM'){
			//We take out the phone / email
			$FM = [];
			$dbCont = CCrmFieldMulti::GetList(     
				['ID' => 'asc'], 
				[
					'ELEMENT_ID' => $arFields['ID'],
					'ENTITY_ID' => "LEAD",
				],
			);
			while($arCont = $dbCont->fetch()){
				$FM[$arCont['TYPE_ID']][$arCont['ID']] = [
					'VALUE'=>$arCont['VALUE'],
					'VALUE_TYPE'=>$arCont['VALUE_TYPE'],
				];
			}

			foreach($value as $key2=>$value2){
				$trigger_error = false;
				//Let's find out if there were any changes
				foreach($value2 as $key3=>$value3){
					if($FM[$key2][$key3]['VALUE'] != $value3['VALUE'] or $FM[$key2][$key3]['VALUE_TYPE'] != $value3['VALUE_TYPE']){
						$trigger_error = true;
					}
				}
				if(isset($arFieldLeadAccess[$key2]) and !in_array($arFields['MODIFY_BY_ID'], $arFieldLeadAccess[$key2]) and $trigger_error){
					$arError[] = 'There is no access to the field "'.$fieldsLead[$key2]['NAME_EN'].'"';
				}
			}
		}elseif($key=='ASSIGNED_BY_ID'){
			if(is_array($arFieldLeadAccess['ASSIGNED_BY_ID']) and !in_array($arFields['MODIFY_BY_ID'], $arFieldLeadAccess['ASSIGNED_BY_ID'])){
				if($userIsManager and $lead[$key]!=$value and $lead['STATUS_SEMANTIC_ID']!='F'){
					//Managers can only change the responsible person at the failed stages
					$arError[] = 'There is no access to the field "Responsible person"';
				}
				if(!$userIsManager and isset($arFieldLeadAccess[$key]) and !in_array($arFields['MODIFY_BY_ID'], $arFieldLeadAccess[$key]) and $lead[$key]!=$value){
					$arError[] = 'There is no access to the field "'.$fieldsLead[$key]['NAME_EN'].'"';
				}
			}
		}else{
			if(isset($arFieldLeadAccess[$key]) and !in_array($arFields['MODIFY_BY_ID'], $arFieldLeadAccess[$key]) and $lead[$key]!=$value){
				$arError[] = 'There is no access to the field "'.$fieldsLead[$key]['NAME_EN'].'"';
			}
		}
	}
	//If there were errors
	if(count($arError)){
		foreach($arError as $key=>$value){
			if(isset($arFields['RESULT_MESSAGE']) and !empty($arFields['RESULT_MESSAGE'])) $arFields['RESULT_MESSAGE'] .= PHP_EOL;
			$arFields['RESULT_MESSAGE'] .= $value;
		}
		return false;
	}
}
?>
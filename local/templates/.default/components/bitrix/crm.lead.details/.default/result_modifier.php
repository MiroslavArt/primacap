<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
//Substitution for a standard template
$originalComponentPath = '/bitrix/components/bitrix/crm.lead.details/templates/';
$originalTemplatePath = '.default';

$this->__file = $originalComponentPath.$originalTemplatePath.'/template.php';
$this->__folder = $originalComponentPath.$originalTemplatePath;
$this->__hasCSS = true;
$this->__hasJS = true;

if(file_exists($_SERVER['DOCUMENT_ROOT'] . $originalComponentPath . $originalTemplatePath . '/result_modifier.php'))require $_SERVER['DOCUMENT_ROOT'] . $originalComponentPath . $originalTemplatePath . '/result_modifier.php';

//Array ID user and Name hide field
$field_hide = [
	'1414'=>['NAME', 'PHONE', 'EMAIL', 'UF_CRM_LEAD_1645535443807'],
	//'31'=>['NAME', 'PHONE', 'EMAIL', 'UF_CRM_LEAD_1645535443807'],
	'4481'=>['NAME', 'PHONE', 'EMAIL', 'UF_CRM_LEAD_1645535443807'], //Mark
];
//Get current user
$USER_ID = $USER->GetID();



$rsUser = CUser::GetByID($USER_ID);
$arUser = $rsUser->Fetch();

//if(!$USER->IsAdmin()&&($arResult['ENTITY_DATA']['STATUS_ID']=='UC_9WUJ49')){

//if(!$USER->IsAdmin() && !in_array('18',$arUser['UF_DEPARTMENT']) && $USER_ID!='4481'){

if(!$USER->IsAdmin() && $USER_ID!='4481'){

	//if(!in_array($USER_ID,$arResult['ENTITY_DATA']['OBSERVER_IDS']))
	//{
?>
<style>
div#popup-window-content-timeline-more-button-menu {
    display: none;
}
	/**#timeline-filter{display:none;}
	.crm-entity-stream-section-today-label { display: none;}
.crm-entity-stream-section-history { display: none;}
.crm-entity-stream-section.crm-entity-stream-section-history-label {
		display: none;
}
	.crm-entity-stream-section.crm-entity-stream-section-planned{
display: none;
	}
	.crm-entity-stream-section-planned-label{
display: none;
}
</style>
<?
	foreach($arResult['TABS'] as $key=>$value){
	if($value['name']=='History' || $value['name']=='Workflows' || $value['name']=='Documents') {
		unset($arResult['TABS'][$key]);
		}


		//}
}
}

//If current user in array $field_hide
if(isset($field_hide[$USER_ID]) and is_array($field_hide[$USER_ID]) and count($field_hide[$USER_ID])){
	//Find hiding field in ENTITY_FIELDS
	foreach($arResult['ENTITY_FIELDS'] as $key=>$value){
		if(in_array($value['name'], $field_hide[$USER_ID])){
			//Delete this field
			unset($arResult['ENTITY_FIELDS'][$key]);
		}
	}
}
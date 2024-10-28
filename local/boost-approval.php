<?
require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');


$_REQUEST['id'] = intval($_REQUEST['id']);

if($_REQUEST['id']!='')
{
global $USER;
	
	CModule::IncludeModule("iblock");
	CModule::IncludeModule("bizproc");
	CModule::IncludeModule("crm");

           $PROP = array();
			$PROP['BOOST_REQUESTED_BY'] = $_REQUEST['id'];


$IblockFields = array(
			  "IBLOCK_SECTION_ID" => false,         
			  "IBLOCK_ID"      => 45,
			"PROPERTY_VALUES"=> $PROP,
			  "NAME"           => "Boost Approval #".$_REQUEST['id'],
			  "ACTIVE"         => "Y",           
			  "PREVIEW_TEXT"   => "",
			  "DETAIL_TEXT"    => "",

			  );
			$IBLOCKELEMENT = new CIBlockElement();
			$element_id = $IBLOCKELEMENT->Add($IblockFields);
			if($element_id)
			{
				$arErrors = array();
				$arParameters = array();

CBPDocument::StartWorkflow(
			143,
            array("lists", "BizprocDocument", $element_id),
					$arParameters,
					$arErrors
        );
}

header("Location: https://".$_SERVER['HTTP_HOST']."/crm/lead/kanban/"); 
exit;
}

?>
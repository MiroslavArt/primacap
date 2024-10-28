<?
define("NO_KEEP_STATISTIC", true);
define("NOT_CHECK_PERMISSIONS", true);
define('STOP_STATISTICS', true);
function dump($mess) {echo '<pre>'.print_r($mess,true).'</pre>';}
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
CModule::IncludeModule('crm');
CModule::IncludeModule('voximplant');
GLOBAL $USER;
/*
$lead_id = 15623;
$arCalls = Bitrix\Voximplant\StatisticTable::getList([            
	"select" => ['ID', 'PHONE_NUMBER', 'CALL_DURATION'],            
	"filter" => [
	   "CRM_ENTITY_TYPE"=>"LEAD",
	   "CRM_ENTITY_ID" => $lead_id, 
	   "INCOMING"=>1,//1-исходящий, 2-входящий 
	],
	"order" => ['ID' => 'DESC'],
	"limit" => 1000,
])->fetchAll();


$arStatusLead = Bitrix\Crm\History\Entity\LeadStatusHistoryTable::getList([
   'order'=>['ID' => 'DESC'],
   'filter'=>['OWNER_ID'=>54784],
   'select'=>['ID', 'OWNER_ID', 'STATUS_ID'],
])->fetchAll();
$lastStatus = $arStatusLead[1] ?? 0; //Previous status STATUS_ID
*/
/*
$resId = \Bitrix\Crm\Timeline\CommentEntry::create([
	'TEXT' => 'Test comment timeline 3',
	'SETTINGS' => [], // тут можно указать, что есть прикрепленные файлы
	'AUTHOR_ID' => 1,
	'BINDINGS' => [['ENTITY_TYPE_ID' => \CCrmOwnerType::Lead, 'ENTITY_ID' => 54784]],
]);
$push = new \Bitrix\Crm\Timeline\Pusher(false);
$push->sendPullEvent(\CCrmOwnerType::Lead, 54784, 'Pusher');
*/
dump($arCalls);

?>
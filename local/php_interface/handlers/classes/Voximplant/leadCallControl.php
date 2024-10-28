<?php
namespace Bit\Custom\Voximplant;

use Bitrix\Main\EventManager;
use Bitrix\Main\Loader;

class leadCallControl {
    private static $count_seconds = 5;

	public function __construct(){
        if (!Loader::includeModule('voximplant')) {
            throw new \Exception("Voximplant module not found");
        }

        if (!Loader::includeModule('crm')) {
            throw new \Exception("Crm module not found");
        }
    }
    public static function registerEventHandlers(\Bitrix\Main\EventManager $eventManager){
        //$eventManager->addEventHandlerCompatible("crm", "OnAfterCrmLeadUpdate", [static::class, 'run']);
    }

    public static function run(&$arFields){
		if(isset($arFields['STATUS_ID'])){
			$current_status_id = $arFields['STATUS_ID'] ?? 0;
			$lead_id = $arFields['ID'] ?? 0;
			
			//STATUS_ID = No Answer
			if($current_status_id == 'UC_23YNYD' and $lead_id){
				$arCallsLead = self::getCallsLead($lead_id) ?? [];
				//There were no calls on the lead
				if(count($arCallsLead) == 0){
					$previus_status = 'UC_9WUJ49';
					if($previus_status and $previus_status != $current_status_id){
						//The previous status should not be "Contacted"
						//Returning the lead on previous status UC_9WUJ49
						self::updateLead($lead_id, ['STATUS_ID'=>$previus_status]);
						self::sendTimelineMessageLead($lead_id, 'No outgoing calls. The lead has been returned to the previous stage.');
					}
				}
			}
			
			//STATUS_ID = Contacted
			if($current_status_id == 'UC_TCX0EY' and $lead_id){
				$arCallsLead = self::getCallsLead($lead_id) ?? [];
				foreach($arCallsLead as $key=>$value){
					if($value['CALL_DURATION'] >= self::$count_seconds){
						return true;
					}
				}
				$previus_status = 'UC_9WUJ49';
				if($previus_status and $previus_status != $current_status_id){
					//Returning the lead on previous status
					self::updateLead($lead_id, ['STATUS_ID'=>$previus_status]);
					self::sendTimelineMessageLead($lead_id, 'There are no outgoing calls longer than '.self::$count_seconds.' seconds. The lead has been returned to the previous stage.');
				}
			}
		}
    }
    private static function getCallsLead($lead_id){
        $arCalls = \Bitrix\Voximplant\StatisticTable::getList([            
			"select" => ['ID', 'PHONE_NUMBER', 'CALL_DURATION'],            
			"filter" => [
			   "CRM_ENTITY_TYPE"=>"LEAD",
			   "CRM_ENTITY_ID" => $lead_id, 
			   "INCOMING"=>1,//1-outgoing, 2-incoming
			],
			"order" => ['ID' => 'DESC'],
			"limit" => 1000,
		])->fetchAll();
		
		return $arCalls;
    }
	private static function getPreviousStatusLead($lead_id){
		$arStatusLead = \Bitrix\Crm\History\Entity\LeadStatusHistoryTable::getList([
		   'order'=>['ID' => 'DESC'],
		   'filter'=>['OWNER_ID'=>$lead_id],
		   'select'=>['ID', 'OWNER_ID', 'STATUS_ID'],
		])->fetchAll();		
		$lastStatus = $arStatusLead[1]['STATUS_ID'] ?? 0; //Previous status
		
		return $lastStatus;
	}
	private static function updateLead($id, $fields){
		$lead  =  new  \CCrmLead(false); 
		$arOptions = ['CURRENT_USER'=>1];       
		$upRes = $lead->Update($id, $fields, true, true, $arOptions);
	}
	private static function sendTimelineMessageLead($id, $text){
		\Bitrix\Crm\Timeline\CommentEntry::create([
			'TEXT' => $text,
			'SETTINGS' => [],
			'AUTHOR_ID' => 1,
			'BINDINGS' => [['ENTITY_TYPE_ID' => \CCrmOwnerType::Lead, 'ENTITY_ID' => $id]],
		]);
	}
}

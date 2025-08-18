<?php
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

header("Content-Type: application/json");
$data = json_decode(file_get_contents("php://input"), true);

global $DB;
$observersArray=[];
if ($data) {    
   $observers = $data['observers'] ?? '';
    $name = $data['name'] ?? 'No name'; 
		if (!empty($observers) && is_string($observers)) {
			$observersArray = array_map('intval', explode(',', $observers)); 
		} else {
			$observersArray = [];
		}	
}

global $DB;
$sql = "SELECT * FROM b_calendar_sharing_link WHERE OBJECT_TYPE = 'event' ORDER BY ID DESC LIMIT 1";
$result = $DB->Query($sql);

// Check if the query ran successfully
if (!$result) {
    die(json_encode(["error" => "Query Error: " . $DB->db_Error]));
}

if ($event = $result->Fetch()) {
     $Id = $event["OBJECT_ID"];
     $ownerId = $event["OWNER_ID"];
      $hostId = intval($event["HOST_ID"]);
		$observersArray[] = $hostId; // ✅ Append value correctly
		$attendeesList = json_encode($observersArray);

	if($attendeesList){
			$curl = curl_init();
			
			curl_setopt_array($curl, array(
			  CURLOPT_URL => 'https://primocapitalcrm.ae/rest/4780/uk1s3te1b1qvhn7n/calendar.event.update',
			  CURLOPT_RETURNTRANSFER => true,
			  CURLOPT_ENCODING => '',
			  CURLOPT_MAXREDIRS => 10,
			  CURLOPT_TIMEOUT => 0,
			  CURLOPT_FOLLOWLOCATION => true,
			  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			  CURLOPT_CUSTOMREQUEST => 'POST',
			  CURLOPT_POSTFIELDS =>'{
			  "id":'.$Id.',
               "name": "Meeting:-'.$name.'",
				"type":"user",
				"ownerId":'.$ownerId.',
				"is_meeting": "Y",
				"host":'.$hostId.',
				"attendees":'. $attendeesList.',
				"meeting": {
				  "text": "Please join the meeting to discuss project updates.",
				  "open": "Y",
				  "notify": "Y",
				  "reinvite": "Y"
				}
			  }
			
			',
			  CURLOPT_HTTPHEADER => array(
				'Content-Type: application/json'
			  ),
			));
			
			$response = curl_exec($curl);
			
			curl_close($curl);
			echo $response;
}

} else {
    echo json_encode(["error" => "No event found"]);
}
?>

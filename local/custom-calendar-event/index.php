<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");
global $DB;

function process_attendance() {
    global $DB;

    $processed_count = 0;
    $api_call_count = 0;
    $section_not_found = 0;

    $sql = "SELECT ID, USER_ID, PUNCH_DIRECTION, LOG_DATE 
            FROM c_office_attandance 
            WHERE Userlog_status = 0 
            ORDER BY ID ASC
LIMIT 1000 OFFSET 8000";
    $result = $DB->Query($sql);
    
    $total_records = $result->SelectedRowsCount();
    echo "Total Pending Attendance Records: $total_records<br>\n";

    if ($total_records == 0) {
        echo "No records to process.<br>\n";
        return;
    }

    while ($row = $result->Fetch()) {
        $uniqId = (int) $row['ID'];
        $userId = (int) $row['USER_ID'];
        $punchDr = trim($row['PUNCH_DIRECTION']);
        $punchDate = trim($row['LOG_DATE']);

        $color = ($punchDr == 'in') ? '#28a745' : (($punchDr == 'out') ? '#dc3545' : '');
        $name = ($punchDr == 'in') ? 'PUNCH IN' : (($punchDr == 'out') ? 'PUNCH OUT' : '');

        if (empty($name)) {
            echo "Invalid PUNCH_DIRECTION '$punchDr' for ID: $uniqId. Skipping record.<br>\n";
            continue;
        }

        // Get VALUE_ID for User
        $sql4 = "SELECT VALUE_ID FROM b_uts_user WHERE UF_OFFICE_ATTN = $userId";
        $result4 = $DB->Query($sql4);
        $row4 = $result4->Fetch();
        $valueId = isset($row4['VALUE_ID']) ? (int) $row4['VALUE_ID'] : null;

        if (!$valueId) {
            echo "No VALUE_ID found for User ID: $userId<br>\n";
            continue;
        }

        // Get calendar section ID
        $sql1 = "SELECT ID FROM b_calendar_section WHERE CAL_TYPE='user' AND OWNER_ID = $valueId LIMIT 1";
        $calendarResult = $DB->Query($sql1);
        $calendarRow = $calendarResult->Fetch();
        $sectionId = isset($calendarRow['ID']) ? (int) $calendarRow['ID'] : null;

        if (!$sectionId) {
            $section_not_found++;
            echo "No calendar section found for VALUE_ID: $valueId<br>\n";
            continue;
        }

        // API Call
        $response = api_call($valueId, $sectionId, $punchDate, $color, $name);
        $processed_count++;
        echo "Processing Unique ID: $uniqId - User ID: $valueId - Date: $punchDate<br>\n";

        if (isset($response['result']) && $response['result'] == true) {
            $api_call_count++;

            $sql3 = "UPDATE c_office_attandance SET Userlog_status = 1 WHERE ID = $uniqId";
            $DB->Query($sql3);

            echo "Updated Userlog_status for ID: $uniqId<br>\n";
        } else {
            echo "API call failed for ID: $uniqId<br>\n";
        }
    }

    echo "Total Records Processed: $processed_count<br>\n";
    echo "Total Successful API Calls: $api_call_count<br>\n";
    echo "Total 'Section Not Found' Count: $section_not_found<br>\n";
    echo "Process completed.<br>\n";
}

function api_call($valueId, $sectionId, $from, $color, $name) {
    $postData = json_encode([
        "type" => "user",
        "ownerId" => $valueId,
        "section" => $sectionId,
        "name" => $name,
        "description" => "User Punched In for work.",
        "from" => $from,
        "to" => $from,
        "is_meeting" => false,
        "accessibility" => "free",
        "importance" => "normal",
        "color" => $color
    ]);

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => 'https://primocapitalcrm.ae/rest/4780/uk1s3te1b1qvhn7n/calendar.event.add',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $postData,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json']
    ]);

    $response = curl_exec($curl);
    if (curl_errno($curl)) {
        echo "cURL Error: " . curl_error($curl) . "<br>\n";
    }
    curl_close($curl);

    return json_decode($response, true);
}

process_attendance();
?>

<?php
$DBType = "mysql";
$DBHost = "localhost";
$DBLogin = "bitrix0";
$DBPassword = "5llDr(7O!x9tUV%N2tYZ";
$DBName = "sitemanager";

// Create connection with mysqli
$connection = new mysqli($DBHost, $DBLogin, $DBPassword, $DBName);

// Check connection
if ($connection->connect_error) {
    die("Connection failed: " . $connection->connect_error);
}

$webhookUrl = "https://primocapitalcrm.ae/rest/4780/ua3qos1ab1cp7lc9/crm.item.get";

$entityTypeId = 1032;
 $itemId = $_GET['item'];



$queryData = [
    "entityTypeId" => $entityTypeId,
    "id" => $itemId,
];

$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => $webhookUrl,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query($queryData), // Corrected encoding
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_SSL_VERIFYPEER => false
]);

$response = curl_exec($curl);
curl_close($curl);

$data = json_decode($response, true);

$observers = $data['result']['item']['observers'];
$asignId = $data['result']['item']['assignedById'];
$nameOfCandidate = $data['result']['item']['ufCrm4_1736765994'];
$emailOfCandidate = $data['result']['item']['ufCrm4_1738559442224'];
$observers = $data['result']['item']['observers']; 
$query_string = implode(',', $observers);
$asignId = intval($asignId);
$nameOfintrwivar='';
if ($asignId) {
    // Corrected Webhook URL (Remove POST fields)
    $webhookUrl = "https://primocapitalcrm.ae/rest/4780/ua3qos1ab1cp7lc9/user.get.json?ID=" . $asignId;

    // Initialize cURL
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $webhookUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false
    ]);

    // Execute cURL Request
    $response = curl_exec($curl);
    curl_close($curl);

    // Decode JSON Response
    $userData = json_decode($response, true);
    $IntrFName= $userData['result'][0]['NAME'];
     $IntrLName= $userData['result'][0]['LAST_NAME'];
    $nameOfintrwivar= $IntrFName." ".$IntrLName;
}

// Prepare and execute the query using mysqli
$query = "SELECT * FROM b_calendar_sharing_link 
WHERE OBJECT_TYPE = 'user' AND OBJECT_ID = " . $asignId . "
ORDER BY ID DESC 
LIMIT 1";
$result = $connection->query($query);

// Check if a result was returned
if ($result->num_rows > 0) {
    // Fetch the first row
    $row = $result->fetch_assoc();


if ($row['HASH']) {
    $calender_id = $row['HASH'];
		header("Location: https://primocapitalcrm.ae/local/apoinment-form/form.php?calender_id=" . urlencode($calender_id) . 
			"&itemId=" . urlencode($itemId) . 
			"&nameOfCandidate=" . urlencode($nameOfCandidate)."&emailOfCandidate=" . urlencode($emailOfCandidate). 
			"&nameOfintrwivar=" . urlencode($nameOfintrwivar)."&observers=" .urlencode($query_string));
		exit(); // Always use exit() after header redirect


}
}
// Close the connection
$connection->close();
?>

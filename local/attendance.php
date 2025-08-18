<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$csvFile = '20250318110949_EmployeeDetails_Export.csv'; 
$requiredColumns = ['EmployeeCode', 'EmployeeName', 'DeviceCode']; 
$data = [];

// Database connection details
$DBHost = "localhost";
$DBLogin = "bitrix0";
$DBPassword = "5llDr(7O!x9tUV%N2tYZ";
$DBName = "sitemanager";

// Create connection
$conn = new mysqli($DBHost, $DBLogin, $DBPassword, $DBName);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch user data from Bitrix24 database
$sql = "SELECT u.ID, u.NAME, u.LAST_NAME, u.EMAIL, uts.UF_OFFICE_ATTN
        FROM b_user u
        JOIN b_uts_user uts ON u.ID = uts.VALUE_ID";
$result = $conn->query($sql);

$userData = [];
$codeToNameMap = []; 

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $id = $row['ID'];
        $name = trim($row['NAME'] ?? '');
        $lastName = trim($row['LAST_NAME'] ?? '');
        $empCode = trim($row['UF_OFFICE_ATTN'] ?? '');


    }
}

// Check if CSV file exists and is readable
if (!file_exists($csvFile)) {
    die("❌ Error: File not found!\n");
}
if (!is_readable($csvFile)) {
    die("❌ Error: File is not readable!\n");
}

// Read CSV and compare names + employee codes
$matchedRecords = [];
$partialMatchedRecords = [];
$unmatchedRecords = [];

if (($handle = fopen($csvFile, "r")) !== FALSE) {
    $headers = fgetcsv($handle, 1000, ","); 
    if (!$headers) {
        die("❌ Error: Unable to read CSV headers!\n");
    }

    $columnIndexes = [];
    foreach ($requiredColumns as $col) {
        $index = array_search($col, $headers);
        if ($index !== false) {
            $columnIndexes[$col] = $index;
        }
    }

    if (count($columnIndexes) !== count($requiredColumns)) {
        die("❌ Error: Required columns not found in CSV!\n");
    }

    while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
        $csvName = trim($row[$columnIndexes['EmployeeName']] ?? '');
        $csvEmpCode = trim($row[$columnIndexes['EmployeeCode']] ?? '');

        // Ignore empty rows
        if ($csvName === '' || $csvEmpCode === '') {
            continue;
        }

        // **Condition 1: नाम और कोड दोनों मैच करते हैं (Exact Match)**
        if (isset($userData[$csvName]) && $userData[$csvName]['EmployeeCode'] == $csvEmpCode) {
            $matchedRecords[] = [
                'ID' => $userData[$csvName]['ID'],
                'EmployeeName' => $csvName,
                'EmployeeCode' => $csvEmpCode
            ];
        }
        // **Condition 2: नाम मैच नहीं करता लेकिन EmployeeCode मैच करता है (Partial Match)**
        elseif (isset($codeToNameMap[$csvEmpCode]) && $codeToNameMap[$csvEmpCode]['EmployeeName'] !== $csvName) {
            $partialMatchedRecords[] = [
                'ID' => $codeToNameMap[$csvEmpCode]['ID'],
                'EmployeeName_DB' => $codeToNameMap[$csvEmpCode]['EmployeeName'],
                'EmployeeName_CSV' => $csvName,
                'EmployeeCode' => $csvEmpCode
            ];
        }
        // **Condition 3: EmployeeCode और Name दोनों नहीं मिले (Unmatched)**
        else {
            $unmatchedRecords[] = [
                'EmployeeName' => $csvName,
                'EmployeeCode' => $csvEmpCode
            ];
        }
    }
    fclose($handle);
}

// Close database connection
$conn->close();

// Print matched and unmatched records
echo "<pre>";
echo "✅ Matched Records (Exact Match):\n";
print_r($matchedRecords);

echo "\n⚠️ Partial Matched Records (Code Match but Name Different):\n";
print_r($partialMatchedRecords);

echo "\n❌ Unmatched Records:\n";
print_r($unmatchedRecords);
echo "</pre>";
?>

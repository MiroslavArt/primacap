<?php

namespace Webmatrik\Integrations;

use Bitrix\Crm\Service\Container;
use Bitrix\Main\Loader;
use Bitrix\Main\Application;

class FeedBayutLocations extends Feed
{
    protected static $bayutLocationsEntityTypeId = 1074;

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Import locations from CSV file to Bayut Locations SPA
     * @param string|null $filename CSV filename (relative to current directory)
     * @return array Statistics array with created/failed counts
     * @throws \Exception
     */
    public function importLocations($filename = null)
    {
        if (!$filename || !file_exists(__DIR__ . '/' . $filename)) {
            throw new \Exception('CSV file not found: ' . $filename);
        }

        // Ensure CRM module is loaded
        \Bitrix\Main\Loader::includeModule('crm');

        $fullPath = __DIR__ . '/' . $filename;

        // Detect delimiter and open file
        $delimiter = $this->detectDelimiter($fullPath);
        $handle = fopen($fullPath, 'r');

        if ($handle === false) {
            throw new \Exception('Unable to open CSV file: ' . $filename);
        }

        // Handle BOM if present
        $firstLine = fgets($handle);
        if ($firstLine === false) {
            fclose($handle);
            throw new \Exception('Empty CSV file');
        }

        $hasBom = (strncmp($firstLine, "\xEF\xBB\xBF", 3) === 0);
        rewind($handle);

        if ($hasBom) {
            fread($handle, 3);
        }

        // Read headers
        $headers = fgetcsv($handle, 0, $delimiter);

        if (!$headers) {
            fclose($handle);
            throw new \Exception('CSV has no headers');
        }

        $headers = array_map('trim', $headers);

        // Log headers for debugging
        \Bitrix\Main\Diag\Debug::writeToFile(
            $headers,
            'bayut_locations_csv_headers ' . date('Y-m-d H:i:s'),
            'bayut_locations_import.log'
        );

        // Normalize header names for flexible matching
        $normalize = function ($s) {
            $s = mb_strtolower(trim($s));
            return preg_replace('/[^a-z0-9]+/', '', $s);
        };

        $normalizedHeaderMap = [];
        foreach ($headers as $h) {
            $normalizedHeaderMap[$normalize($h)] = $h;
        }

        // Expected CSV columns
        $requiredFields = [
            'city' => 'city',
            'locality' => 'locality',
            'sub_locality' => 'sub_locality',
            'tower_name' => 'tower_name',
            'location_id' => 'location_id'
        ];

        // Map normalized names to actual header names
        $fieldMap = [];
        foreach ($requiredFields as $key => $csvName) {
            $normalized = $normalize($csvName);
            if (isset($normalizedHeaderMap[$normalized])) {
                $fieldMap[$key] = $normalizedHeaderMap[$normalized];
            } else {
                fclose($handle);
                throw new \Exception("Required column '$csvName' not found in CSV");
            }
        }

        // Get factory for Bayut Locations SPA
        $container = Container::getInstance();
        $factory = $container->getFactory(static::$bayutLocationsEntityTypeId);

        if (!$factory) {
            fclose($handle);
            throw new \Exception('Factory for entity type ' . static::$bayutLocationsEntityTypeId . ' not found');
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $failed = 0;
        $errors = [];
        $rowNum = 0;

        // Process each row
        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            // Skip empty rows
            if (count($row) == 1 && trim($row[0]) === '') {
                continue;
            }

            $rowNum++;

            // Create associative array
            $assoc = array_combine($headers, $row);
            if ($assoc === false) {
                continue;
            }

            $assoc = array_map(function ($v) {
                return is_string($v) ? trim($v) : $v;
            }, $assoc);

            try {
                // Extract data using field map
                $city = trim($assoc[$fieldMap['city']] ?? '');
                $locality = trim($assoc[$fieldMap['locality']] ?? '');
                $subLocality = trim($assoc[$fieldMap['sub_locality']] ?? '');
                $towerName = trim($assoc[$fieldMap['tower_name']] ?? '');
                $locationId = trim($assoc[$fieldMap['location_id']] ?? '');

                // Validate location_id
                if (empty($locationId)) {
                    $skipped++;
                    $errors[] = "Row $rowNum: Missing location_id";
                    continue;
                }

                // Build location parts (remove "Unknown" entries)
                $locationParts = [];

                if (!empty($city) && mb_strtolower($city) !== 'unknown') {
                    $locationParts[] = $city;
                }

                if (!empty($locality) && mb_strtolower($locality) !== 'unknown') {
                    $locationParts[] = $locality;
                }

                if (!empty($subLocality) && mb_strtolower($subLocality) !== 'unknown') {
                    $locationParts[] = $subLocality;
                }

                if (!empty($towerName) && mb_strtolower($towerName) !== 'unknown') {
                    $locationParts[] = $towerName;
                }

                // Skip if no valid location parts
                if (empty($locationParts)) {
                    $skipped++;
                    $errors[] = "Row $rowNum: No valid location data (all fields are empty or 'Unknown')";
                    continue;
                }

                // Create TITLE: "City, Locality, SubLocality, TowerName"
                $title = implode(', ', $locationParts);

                // Check if location already exists by location_id
                $existingItems = $factory->getItems([
                    'select' => ['ID', 'TITLE'],
                    'filter' => ['=UF_CRM_13_1762325631' => $locationId],
                    'limit' => 1
                ]);

                $existingItem = $existingItems ? reset($existingItems) : null;

                if ($existingItem) {
                    // Update existing item
                    $existingItem->setTitle($title);

                    $updateOperation = $factory->getUpdateOperation($existingItem);
                    $updateOperation->disableCheckFields()
                        ->disableBizProc()
                        ->disableCheckAccess();

                    $result = $updateOperation->launch();

                    if ($result->isSuccess()) {
                        $updated++;
                    } else {
                        $failed++;
                        $errors[] = "Row $rowNum: Update failed - " . implode(', ', $result->getErrorMessages());
                    }
                } else {
                    // Create new item
                    $fields = [
                        'TITLE' => $title,
                        'UF_CRM_13_1762325631' => $locationId
                    ];

                    $item = $factory->createItem($fields);
                    $addOperation = $factory->getAddOperation($item);
                    $addOperation->disableCheckFields()
                        ->disableBizProc()
                        ->disableCheckAccess();

                    $result = $addOperation->launch();

                    if ($result->isSuccess()) {
                        $created++;
                    } else {
                        $failed++;
                        $errors[] = "Row $rowNum: Creation failed - " . implode(', ', $result->getErrorMessages());
                    }
                }

                // Log first few rows for debugging
                if ($rowNum <= 3) {
                    \Bitrix\Main\Diag\Debug::writeToFile([
                        'row_number' => $rowNum,
                        'raw_data' => $assoc,
                        'title' => $title,
                        'location_id' => $locationId,
                    ], "bayut_location_row_$rowNum " . date('Y-m-d H:i:s'), 'bayut_locations_import.log');
                }
            } catch (\Throwable $e) {
                $failed++;
                $errors[] = "Row $rowNum: Exception - " . $e->getMessage();
            }
        }

        fclose($handle);

        // Prepare result summary
        $summary = [
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
            'failed' => $failed,
            'total_rows' => $rowNum
        ];

        // Output summary
        echo "Import completed:\n";
        echo "Created: $created\n";
        echo "Updated: $updated\n";
        echo "Skipped: $skipped\n";
        echo "Failed: $failed\n";
        echo "Total rows processed: $rowNum\n";

        // Log errors if any
        if (!empty($errors)) {
            \Bitrix\Main\Diag\Debug::writeToFile(
                $errors,
                'bayut_locations_import_errors ' . date('Y-m-d H:i:s'),
                'bayut_locations_import.log'
            );
            echo "\nErrors logged to bayut_locations_import.log\n";
        }

        return $summary;
    }

    /**
     * Detect CSV delimiter
     * @param string $filename Full path to CSV file
     * @return string Detected delimiter
     */
    protected function detectDelimiter($filename)
    {
        $file = fopen($filename, 'r');
        $firstLine = fgets($file);
        fclose($file);

        $delimiters = [',', ';', "\t", '|'];
        $counts = [];

        foreach ($delimiters as $delimiter) {
            $counts[$delimiter] = count(str_getcsv($firstLine, $delimiter));
        }

        return array_search(max($counts), $counts);
    }
}

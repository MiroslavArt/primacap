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

        \Bitrix\Main\Loader::includeModule('crm');

        $fullPath = __DIR__ . '/' . $filename;

        $delimiter = $this->detectDelimiter($fullPath);
        $handle = fopen($fullPath, 'r');

        if ($handle === false) {
            throw new \Exception('Unable to open CSV file: ' . $filename);
        }

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

        $headers = fgetcsv($handle, 0, $delimiter);
        if (!$headers) {
            fclose($handle);
            throw new \Exception('CSV has no headers');
        }

        $headers = array_map('trim', $headers);

        \Bitrix\Main\Diag\Debug::writeToFile(
            $headers,
            'bayut_locations_csv_headers ' . date('Y-m-d H:i:s'),
            'bayut_locations_import.log'
        );

        $normalize = function ($s) {
            $s = mb_strtolower(trim($s));
            return preg_replace('/[^a-z0-9]+/', '', $s);
        };

        $normalizedHeaderMap = [];
        foreach ($headers as $h) {
            $normalizedHeaderMap[$normalize($h)] = $h;
        }

        $requiredFields = [
            'tower_name' => 'tower_name',
            'sub_locality' => 'sub_locality',
            'locality' => 'locality',
            'city' => 'city',
            'location_id' => 'location_id'
        ];

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

        $container = Container::getInstance();
        $factory = $container->getFactory(static::$bayutLocationsEntityTypeId);

        if (!$factory) {
            fclose($handle);
            throw new \Exception('Factory for entity type ' . static::$bayutLocationsEntityTypeId . ' not found');
        }

        // --- Fetch all existing locations at once ---
        $existingById = [];
        $existingByTitle = [];

        $allItems = $factory->getItems([
            'select' => ['ID', 'TITLE', 'UF_CRM_13_1762325631'],
        ]);

        foreach ($allItems as $item) {
            $locId = trim($item->get('UF_CRM_13_1762325631') ?? '');
            $title = trim($item->getTitle());

            if (!empty($locId)) {
                $existingById[$locId] = $item;
            }
            if (!empty($title)) {
                $existingByTitle[mb_strtolower($title)] = $item;
            }
        }

        \Bitrix\Main\Diag\Debug::writeToFile(
            ['count' => count($existingById), 'titles' => count($existingByTitle)],
            'Preloaded existing Bayut locations ' . date('Y-m-d H:i:s'),
            'bayut_locations_import.log'
        );

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $failed = 0;
        $errors = [];
        $rowNum = 0;

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            if (count($row) == 1 && trim($row[0]) === '') {
                continue;
            }

            $rowNum++;
            $assoc = array_combine($headers, $row);
            if ($assoc === false) {
                continue;
            }

            $assoc = array_map(fn($v) => is_string($v) ? trim($v) : $v, $assoc);

            try {
                $towerName = trim($assoc[$fieldMap['tower_name']] ?? '');
                $subLocality = trim($assoc[$fieldMap['sub_locality']] ?? '');
                $locality = trim($assoc[$fieldMap['locality']] ?? '');
                $city = trim($assoc[$fieldMap['city']] ?? '');
                $locationId = trim($assoc[$fieldMap['location_id']] ?? '');

                if (empty($locationId)) {
                    $skipped++;
                    $errors[] = "Row $rowNum: Missing location_id";
                    continue;
                }

                $locationParts = [];

                if (!empty($towerName) && mb_strtolower($towerName) !== 'unknown') {
                    $locationParts[] = $towerName;
                }
                if (!empty($subLocality) && mb_strtolower($subLocality) !== 'unknown') {
                    $locationParts[] = $subLocality;
                }
                if (!empty($locality) && mb_strtolower($locality) !== 'unknown') {
                    $locationParts[] = $locality;
                }
                if (!empty($city) && mb_strtolower($city) !== 'unknown') {
                    $locationParts[] = $city;
                }

                if (empty($locationParts)) {
                    $skipped++;
                    $errors[] = "Row $rowNum: No valid location data (all fields are empty or 'Unknown')";
                    continue;
                }

                $title = implode(', ', $locationParts);
                $normalizedTitle = mb_strtolower($title);

                // --- Optimized Lookup ---
                $existingItem = $existingById[$locationId] ?? $existingByTitle[$normalizedTitle] ?? null;

                if ($existingItem) {
                    $needsUpdate = false;

                    // Update title if changed
                    if ($existingItem->getTitle() !== $title) {
                        $existingItem->setTitle($title);
                        $needsUpdate = true;
                    }

                    // Add missing location_id if empty
                    $existingLocId = $existingItem->get('UF_CRM_13_1762325631');
                    if (empty($existingLocId) && !empty($locationId)) {
                        $existingItem->set('UF_CRM_13_1762325631', $locationId);
                        $needsUpdate = true;

                        \Bitrix\Main\Diag\Debug::writeToFile(
                            ['row' => $rowNum, 'title' => $title, 'added_location_id' => $locationId],
                            'Added missing location_id ' . date('Y-m-d H:i:s'),
                            'bayut_locations_import.log'
                        );
                    }

                    if ($needsUpdate) {
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
                        $skipped++;
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
                        // Add to memory for later lookups
                        $existingById[$locationId] = $item;
                        $existingByTitle[$normalizedTitle] = $item;
                    } else {
                        $failed++;
                        $errors[] = "Row $rowNum: Creation failed - " . implode(', ', $result->getErrorMessages());
                    }
                }

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

        $summary = [
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
            'failed' => $failed,
            'total_rows' => $rowNum
        ];

        echo "Import completed:\n";
        echo "Created: $created\n";
        echo "Updated: $updated\n";
        echo "Skipped: $skipped\n";
        echo "Failed: $failed\n";
        echo "Total rows processed: $rowNum\n";

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


    public function importLocationsHierarchy($filename = null)
    {
        if (!$filename || !file_exists(__DIR__ . '/' . $filename)) {
            throw new \Exception('CSV file not found: ' . $filename);
        }

        Loader::includeModule('crm');

        $fullPath = __DIR__ . '/' . $filename;
        $delimiter = $this->detectDelimiter($fullPath);
        $handle = fopen($fullPath, 'r');

        if ($handle === false) {
            throw new \Exception('Unable to open CSV file: ' . $filename);
        }

        // Handle BOM
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
        $normalize = fn($s) => preg_replace('/[^a-z0-9]+/', '', mb_strtolower(trim($s)));

        $normalizedHeaderMap = [];
        foreach ($headers as $h) {
            $normalizedHeaderMap[$normalize($h)] = $h;
        }

        // Check for required field
        if (!isset($normalizedHeaderMap['locationhierarchy'])) {
            fclose($handle);
            throw new \Exception("Required column 'location_hierarchy' not found in CSV");
        }

        $locationHierarchyCol = $normalizedHeaderMap['locationhierarchy'];

        $container = Container::getInstance();
        $factory = $container->getFactory(static::$bayutLocationsEntityTypeId);

        if (!$factory) {
            fclose($handle);
            throw new \Exception('Factory for entity type ' . static::$bayutLocationsEntityTypeId . ' not found');
        }

        $created = 0;
        $skipped = 0;
        $failed = 0;
        $errors = [];
        $rowNum = 0;

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            if (count($row) == 1 && trim($row[0]) === '') {
                continue;
            }

            $rowNum++;
            $assoc = array_combine($headers, $row);
            if ($assoc === false) {
                continue;
            }

            $assoc = array_map(fn($v) => is_string($v) ? trim($v) : $v, $assoc);
            $hierarchy = trim($assoc[$locationHierarchyCol] ?? '');

            if (empty($hierarchy)) {
                $skipped++;
                $errors[] = "Row $rowNum: Missing location_hierarchy";
                continue;
            }

            // Split hierarchy by ">"
            $parts = array_map('trim', explode('>', $hierarchy));
            $parts = array_filter($parts, fn($p) => $p !== '' && mb_strtolower($p) !== 'unknown');

            if (empty($parts)) {
                $skipped++;
                $errors[] = "Row $rowNum: No valid parts in hierarchy";
                continue;
            }

            // If 5 parts → remove the 4th one (sub sub community)
            if (count($parts) === 5) {
                unset($parts[3]);
                $parts = array_values($parts); // reindex
            }

            // Reverse to have tower first, city last
            $parts = array_reverse($parts);

            // Join with commas
            $title = implode(', ', $parts);

            try {
                // Check if item already exists by title
                $existingItems = $factory->getItems([
                    'select' => ['ID'],
                    'filter' => ['=TITLE' => $title],
                    'limit' => 1
                ]);

                if (!empty($existingItems)) {
                    $skipped++;
                    continue;
                }

                // Create new item
                $fields = ['TITLE' => $title];
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

                // Log first few for sanity check
                if ($rowNum <= 3) {
                    \Bitrix\Main\Diag\Debug::writeToFile([
                        'row_number' => $rowNum,
                        'raw_data' => $assoc,
                        'title' => $title,
                    ], "bayut_location_hierarchy_row_$rowNum " . date('Y-m-d H:i:s'), 'bayut_locations_import.log');
                }
            } catch (\Throwable $e) {
                $failed++;
                $errors[] = "Row $rowNum: Exception - " . $e->getMessage();
            }
        }

        fclose($handle);

        $summary = [
            'created' => $created,
            'skipped' => $skipped,
            'failed' => $failed,
            'total_rows' => $rowNum
        ];

        echo "Import completed:\n";
        echo "Created: $created\n";
        echo "Skipped: $skipped\n";
        echo "Failed: $failed\n";
        echo "Total rows processed: $rowNum\n";

        if (!empty($errors)) {
            \Bitrix\Main\Diag\Debug::writeToFile(
                $errors,
                'bayut_locations_hierarchy_import_errors ' . date('Y-m-d H:i:s'),
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

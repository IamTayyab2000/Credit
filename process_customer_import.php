<?php
// Customer Import Logic
// This file handles importing customer data with 5 columns: Customer ID, Name, Area, Salesman, Day

// Always return JSON and don't leak PHP warnings/notices into the response
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(E_ALL);

include_once(__DIR__ . '/functionality/components/crud.php');

// Custom logging function
function logCustomerImport($message) {
    $logFile = __DIR__ . '/logs/customer_import.log';
    $logDir = dirname($logFile);
    
    // Create logs directory if it doesn't exist
    if (!file_exists($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[{$timestamp}] {$message}" . PHP_EOL;
    
    // Append to log file
    file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
}

// SQL logging function for debugging
function logSqlCommand($query, $result = null, $error = null) {
    $sqlLogFile = __DIR__ . '/logs/customer_import_sql.log';
    $logDir = dirname($sqlLogFile);
    
    // Create logs directory if it doesn't exist
    if (!file_exists($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $status = $error ? 'FAILED' : ($result ? 'SUCCESS' : 'NO RESULT');
    $errorMessage = $error ? " | ERROR: {$error}" : '';
    $logMessage = "[{$timestamp}] [{$status}] QUERY: {$query}{$errorMessage}" . PHP_EOL;
    
    // Append to SQL log file
    file_put_contents($sqlLogFile, $logMessage, FILE_APPEND | LOCK_EX);
}

// Check if file was uploaded
if (!isset($_FILES['customer_file']) || $_FILES['customer_file']['error'] !== UPLOAD_ERR_OK) {
    logCustomerImport("No file uploaded or file upload error. Code: " . $_FILES['customer_file']['error']);
    echo json_encode([
        'status' => 0,
        'message' => 'No file uploaded or file upload error'
    ]);
    exit;
}

$file = $_FILES['customer_file']['tmp_name'];
$filename = $_FILES['customer_file']['name'];

// Validate file extension
if (!preg_match('/\.(csv|xlsx|xls)$/i', $filename)) {
    logCustomerImport("Invalid file format. File: " . $filename);
    echo json_encode([
        'status' => 0,
        'message' => 'Invalid file format. Please upload a CSV or Excel file.'
    ]);
    exit;
}

// Parse the file based on its type
$data = [];
if (preg_match('/\.csv$/i', $filename)) {
    // Handle CSV file
    $csv_data = array_map('str_getcsv', file($file));
    if (empty($csv_data)) {
        logCustomerImport("CSV file is empty. File: " . $filename);
        echo json_encode([
            'status' => 0,
            'message' => 'CSV file is empty'
        ]);
        exit;
    }
    
    // Extract header (first row)
    $headers = array_shift($csv_data);
    $data = $csv_data;
} else {
    // Handle Excel file - for now, we'll redirect to CSV processing
    // In a real implementation, you might use a library like PhpSpreadsheet
    logCustomerImport("Excel file not yet supported. File: " . $filename);
    echo json_encode([
        'status' => 0,
        'message' => 'Excel file support coming soon. Please convert to CSV format.'
    ]);
    exit;
}

// Validate required columns
$required_columns = ['customer id', 'name', 'area', 'salesman', 'day'];
$headers_lower = array_map('strtolower', array_map('trim', $headers));

$column_indices = [];
foreach ($required_columns as $column) {
    $index = array_search(strtolower($column), $headers_lower);
    if ($index === false) {
        logCustomerImport("Required column '$column' not found. Found columns: " . implode(', ', $headers));
        echo json_encode([
            'status' => 0,
            'message' => "Required column '$column' not found in file. Found columns: " . implode(', ', $headers)
        ]);
        exit;
    }
    $column_indices[$column] = $index;
}

// Track unique values for migration
$salesman_data = [];
$sector_data = [];
$customer_data = [];
$errors = [];
$routes_to_create = [];

// Process rows
foreach ($data as $row_index => $row) {
    if (empty(implode('', $row))) {
        continue; // Skip empty rows
    }

    // Extract values based on column indices
    $customer_id = isset($row[$column_indices['customer id']]) ? trim($row[$column_indices['customer id']]) : '';
    $customer_name = isset($row[$column_indices['name']]) ? trim($row[$column_indices['name']]) : '';
    $area_name = isset($row[$column_indices['area']]) ? trim($row[$column_indices['area']]) : '';
    $salesman_name = isset($row[$column_indices['salesman']]) ? trim($row[$column_indices['salesman']]) : '';
    $day = isset($row[$column_indices['day']]) ? trim($row[$column_indices['day']]) : '';

    // Validate data
    if (empty($customer_id) || empty($customer_name) || empty($area_name) || empty($salesman_name) || empty($day)) {
        logCustomerImport("Missing required fields in row " . ($row_index + 2) . ". Data: " . implode(', ', $row));
        $errors[] = "Row " . ($row_index + 2) . ": Missing required fields";
        continue;
    }

    // Validate day
    $valid_days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
    $day_normalized = ucfirst(strtolower(trim($day)));
    
    // Handle common misspellings
    $day_mappings = [
        'monday' => 'Monday',
        'tuesday' => 'Tuesday', 
        'wednesday' => 'Wednesday',
        'thursday' => 'Thursday',
        'thurday' => 'Thursday',
        'friday' => 'Friday',
        'saturday' => 'Saturday',
        'sunday' => 'Sunday',
        'thrusday' => 'Thursday',  // Handle the 'Thrusday' typo
        'thurday' => 'Thursday',   // Handle the 'Thurday' typo
        'tuesay' => 'Tuesday',   // Handle the 'Tuesay' typo
        'wednesay' => 'Wednesday', // Handle the 'Wednesay' typo
        'friday' => 'Friday',     // Already handled, but included for completeness
        'saturday' => 'Saturday',   // Already handled, but included for completeness
        'sunday' => 'Sunday'       // Already handled, but included for completeness
    ];
    
    $day_lower = strtolower(trim($day));
    if (isset($day_mappings[$day_lower])) {
        $day_normalized = $day_mappings[$day_lower];
    }
    
    if (!in_array($day_normalized, $valid_days)) {
        logCustomerImport("Invalid day '$day' in row " . ($row_index + 2) . ". Normalized to: '$day_normalized'. Valid days: " . implode(', ', $valid_days));
        $errors[] = "Row " . ($row_index + 2) . ": Invalid day '$day'. Must be one of: " . implode(', ', $valid_days);
        continue;
    }

    // Collect salesman data
    if (!array_key_exists($salesman_name, $salesman_data)) {
        $salesman_data[$salesman_name] = true;
    }

    // Collect sector/area data
    if (!array_key_exists($area_name, $sector_data)) {
        $sector_data[$area_name] = true;
    }

    // Collect customer data
    $customer_data[] = [
        'customer_id' => $customer_id,
        'customer_name' => $customer_name,
        'area_name' => $area_name,
        'salesman_name' => $salesman_name,
        'day' => $day_normalized // Use normalized day for consistency
    ];

    // Track routes to create
    $route_key = $area_name . '_' . $salesman_name . '_' . $day_normalized; // Use normalized day
    if (!array_key_exists($route_key, $routes_to_create)) {
        $routes_to_create[$route_key] = [
            'area_name' => $area_name,
            'salesman_name' => $salesman_name,
            'day' => $day_normalized // Use normalized day
        ];
    }
}

// Step 1: Insert Salesmen
$salesman_mapping = [];
foreach ($salesman_data as $salesman_name => $val) {
    $salesman_name_escaped = mysqli_real_escape_string($GLOBALS['conn'], $salesman_name);
    
    // Check if salesman exists
    $check_query = "SELECT saleman_id FROM `salesman` WHERE `saleman_name` = '{$salesman_name_escaped}'";
    logSqlCommand($check_query);
    $result = json_decode(read($check_query));
    
    if (empty($result) || !isset($result[0]->saleman_id)) {
        // Insert new salesman
        $insert_query = "INSERT INTO `salesman` (`saleman_name`) VALUES ('{$salesman_name_escaped}')";
        logSqlCommand($insert_query);
        $res = create_update_delete($insert_query);
        if ($res) {
            // Get the actual MySQL error if any
            $mysql_error = mysqli_error($GLOBALS['conn']);
            if ($mysql_error) {
                logSqlCommand($insert_query, false, $mysql_error);
                logCustomerImport("Failed to create salesman: " . $salesman_name . " - MySQL Error: " . $mysql_error);
            } else {
                logSqlCommand($insert_query, true);
                // Get inserted ID
                $id_query = "SELECT saleman_id FROM `salesman` WHERE `saleman_name` = '{$salesman_name_escaped}' LIMIT 1";
                logSqlCommand($id_query);
                $id_result = json_decode(read($id_query));
                if (!empty($id_result) && isset($id_result[0]->saleman_id)) {
                    $salesman_mapping[$salesman_name] = $id_result[0]->saleman_id;
                    logCustomerImport("Created new salesman: " . $salesman_name . " with ID: " . $id_result[0]->saleman_id);
                } else {
                    logCustomerImport("Failed to retrieve ID for new salesman: " . $salesman_name);
                }
            }
        } else {
            $mysql_error = mysqli_error($GLOBALS['conn']);
            logSqlCommand($insert_query, false, $mysql_error);
            logCustomerImport("Failed to create salesman: " . $salesman_name . " - MySQL Error: " . $mysql_error);
        }
    } else {
        $salesman_mapping[$salesman_name] = $result[0]->saleman_id;
        logCustomerImport("Found existing salesman: " . $salesman_name . " with ID: " . $result[0]->saleman_id);
    }
}

// Step 2: Insert Sectors/Areas
$sector_mapping = [];
foreach ($sector_data as $area_name => $val) {
    $area_escaped = mysqli_real_escape_string($GLOBALS['conn'], $area_name);
    
    // Check if sector exists
    $check_query = "SELECT sector_id FROM `sector` WHERE `sector_name` = '{$area_escaped}'";
    logSqlCommand($check_query);
    $result = json_decode(read($check_query));
    
    if (empty($result) || !isset($result[0]->sector_id)) {
        // Insert new sector
        $insert_query = "INSERT INTO `sector` (`sector_name`) VALUES ('{$area_escaped}')";
        logSqlCommand($insert_query);
        $res = create_update_delete($insert_query);
        if ($res) {
            // Get the actual MySQL error if any
            $mysql_error = mysqli_error($GLOBALS['conn']);
            if ($mysql_error) {
                logSqlCommand($insert_query, false, $mysql_error);
                logCustomerImport("Failed to create sector: " . $area_name . " - MySQL Error: " . $mysql_error);
            } else {
                logSqlCommand($insert_query, true);
                // Get inserted ID
                $id_query = "SELECT sector_id FROM `sector` WHERE `sector_name` = '{$area_escaped}' LIMIT 1";
                logSqlCommand($id_query);
                $id_result = json_decode(read($id_query));
                if (!empty($id_result) && isset($id_result[0]->sector_id)) {
                    $sector_mapping[$area_name] = $id_result[0]->sector_id;
                    logCustomerImport("Created new sector: " . $area_name . " with ID: " . $id_result[0]->sector_id);
                } else {
                    logCustomerImport("Failed to retrieve ID for new sector: " . $area_name);
                }
            }
        } else {
            $mysql_error = mysqli_error($GLOBALS['conn']);
            logSqlCommand($insert_query, false, $mysql_error);
            logCustomerImport("Failed to create sector: " . $area_name . " - MySQL Error: " . $mysql_error);
        }
    } else {
        $sector_mapping[$area_name] = $result[0]->sector_id;
        logCustomerImport("Found existing sector: " . $area_name . " with ID: " . $result[0]->sector_id);
    }
}

// Step 3: Insert Routes
$route_mapping = [];
foreach ($routes_to_create as $route_key => $route_info) {
    $area_name = $route_info['area_name'];
    $salesman_name = $route_info['salesman_name'];
    $day = $route_info['day'];
    
    $sector_id = isset($sector_mapping[$area_name]) ? $sector_mapping[$area_name] : 0;
    $salesman_id = isset($salesman_mapping[$salesman_name]) ? $salesman_mapping[$salesman_name] : 0;
    
    if ($sector_id == 0 || $salesman_id == 0) {
        logCustomerImport("Skipping route creation - missing mapping for area: $area_name or salesman: $salesman_name");
        continue; // Skip if mapping failed
    }
    
    // Check if route exists
    $check_query = "SELECT route_id FROM `routes` WHERE `sector_id` = {$sector_id} AND `saleman_id` = {$salesman_id}";
    logSqlCommand($check_query);
    $result = json_decode(read($check_query));
    
    if (empty($result) || !isset($result[0]->route_id)) {
        // Insert new route
        $insert_query = "INSERT INTO `routes` (`sector_id`, `saleman_id`) VALUES ({$sector_id}, {$salesman_id})";
        logSqlCommand($insert_query);
        $res = create_update_delete($insert_query);
        if ($res) {
            // Get the actual MySQL error if any
            $mysql_error = mysqli_error($GLOBALS['conn']);
            if ($mysql_error) {
                logSqlCommand($insert_query, false, $mysql_error);
                logCustomerImport("Failed to create route for area: $area_name, salesman: $salesman_name - MySQL Error: " . $mysql_error);
            } else {
                logSqlCommand($insert_query, true);
                $id_query = "SELECT route_id FROM `routes` WHERE `sector_id` = {$sector_id} AND `saleman_id` = {$salesman_id} LIMIT 1";
                logSqlCommand($id_query);
                $id_result = json_decode(read($id_query));
                if (!empty($id_result) && isset($id_result[0]->route_id)) {
                    $route_mapping[$route_key] = $id_result[0]->route_id;
                    logCustomerImport("Created new route for area: $area_name, salesman: $salesman_name with ID: " . $id_result[0]->route_id);
                } else {
                    logCustomerImport("Failed to retrieve ID for new route: area: $area_name, salesman: $salesman_name");
                }
            }
        } else {
            $mysql_error = mysqli_error($GLOBALS['conn']);
            logSqlCommand($insert_query, false, $mysql_error);
            logCustomerImport("Failed to create route for area: $area_name, salesman: $salesman_name - MySQL Error: " . $mysql_error);
        }
    } else {
        $route_mapping[$route_key] = $result[0]->route_id;
        logCustomerImport("Found existing route for area: $area_name, salesman: $salesman_name with ID: " . $result[0]->route_id);
    }
}

// Step 4: Insert Customers
$inserted_count = 0;
$failed_count = 0;

foreach ($customer_data as $idx => $customer) {
    $cust_id = mysqli_real_escape_string($GLOBALS['conn'], $customer['customer_id']);
    $cust_name = mysqli_real_escape_string($GLOBALS['conn'], $customer['customer_name']);
    $area_name = $customer['area_name'];
    $salesman_name = $customer['salesman_name'];
    $day_normalized = $customer['day']; // Already normalized from earlier processing
    
    $sector_id = isset($sector_mapping[$area_name]) ? $sector_mapping[$area_name] : 0;
    $salesman_id = isset($salesman_mapping[$salesman_name]) ? $salesman_mapping[$salesman_name] : 0;
    $route_key = $area_name . '_' . $salesman_name . '_' . $day_normalized; // Use normalized day
    $route_id = isset($route_mapping[$route_key]) ? $route_mapping[$route_key] : 0;
    
    if ($route_id == 0) {
       logCustomerImport("Route not found for customer in row " . ($idx + 2) . " - area: $area_name, salesman: $salesman_name, day: $day_normalized");
       $errors[] = "Row " . ($idx + 2) . ": Route not found for customer";
       $failed_count++;
       continue;
    }
    
    // Check if customer already exists
    $check_query = "SELECT COUNT(*) as count FROM `customer` WHERE `customer_id` = '{$cust_id}'";
    logSqlCommand($check_query);
    $check_result = json_decode(read($check_query));
    
    $customer_exists = ($check_result && isset($check_result[0]) && $check_result[0]->count > 0);

    // Insert customer if not exists
    if (!$customer_exists) {
        $insert_cust = "INSERT INTO `customer` (`customer_id`, `customer_name`, `customer_route`) VALUES ('{$cust_id}', '{$cust_name}', {$route_id})";
        logSqlCommand($insert_cust);
        $cust_res = create_update_delete($insert_cust);
        if ($cust_res) {
            // Get the actual MySQL error if any
            $mysql_error = mysqli_error($GLOBALS['conn']);
            if ($mysql_error) {
                logSqlCommand($insert_cust, false, $mysql_error);
                logCustomerImport("Failed to insert customer: $cust_id - $cust_name - MySQL Error: " . $mysql_error);
                $failed_count++;
                $errors[] = "Row " . ($idx + 2) . ": Failed to insert customer - MySQL Error: " . $mysql_error;
            } else {
                logSqlCommand($insert_cust, true);
                $inserted_count++;
                logCustomerImport("Successfully inserted customer: $cust_id - $cust_name (Route ID: $route_id)");
            }
        } else {
            $mysql_error = mysqli_error($GLOBALS['conn']);
            logSqlCommand($insert_cust, false, $mysql_error);
            $failed_count++;
            logCustomerImport("Failed to insert customer: $cust_id - $cust_name - MySQL Error: " . $mysql_error);
            $errors[] = "Row " . ($idx + 2) . ": Failed to insert customer - MySQL Error: " . $mysql_error;
        }
    } else {
        // Customer already exists, count as skipped
        logCustomerImport("Customer ID already exists: $cust_id - $cust_name");
        $errors[] = "Row " . ($idx + 2) . ": Customer ID '{$cust_id}' already exists";
    }
}

// Return response
$response = [
    'status' => 1,
    'inserted' => $inserted_count,
    'failed' => $failed_count,
    'total_processed' => $inserted_count + $failed_count,
    'salesmen_created' => count($salesman_mapping),
    'sectors_created' => count($sector_mapping),
    'routes_created' => count($route_mapping)
];

if (!empty($errors)) {
    $response['errors'] = array_slice($errors, 0, 10); // Limit to first 10 errors
    if (count($errors) > 10) {
        $response['errors'][] = "... and " . (count($errors) - 10) . " more errors";
    }
}

// Log final summary
logCustomerImport("Import Summary: Inserted: $inserted_count, Failed: $failed_count, Salesmen: " . count($salesman_mapping) . ", Sectors: " . count($sector_mapping) . ", Routes: " . count($route_mapping));
logCustomerImport("SQL Log file created at: logs/customer_import_sql.log for detailed debugging");

echo json_encode($response);
?>

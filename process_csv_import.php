<?php
// Recovery Sheet Migration Logic
// This file handles importing legacy recovery sheet data and populating all related tables

// Always return JSON and don't leak PHP warnings/notices into the response
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(E_ALL);

include_once(__DIR__ . '/functionality/components/crud.php');

function formatDateForMySQL($dateString) {
    if (empty($dateString)) return date('Y-m-d');
    
    // Try to parse various formats
    $timestamp = strtotime(str_replace('/', '-', $dateString)); // Handle DD/MM/YYYY
    if (!$timestamp) {
        $timestamp = strtotime($dateString);
    }
    
    if ($timestamp) {
        return date('Y-m-d', $timestamp);
    }
    return date('Y-m-d'); // Fallback
}

// Check if file was uploaded
if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode([
        'status' => 0,
        'message' => 'No file uploaded or file upload error'
    ]);
    exit;
}

$file = $_FILES['csv_file']['tmp_name'];
$filename = $_FILES['csv_file']['name'];

// Validate file extension
if (!preg_match('/\.csv$/i', $filename)) {
    echo json_encode([
        'status' => 0,
        'message' => 'Invalid file format. Please upload a CSV file.'
    ]);
    exit;
}

// Read and parse CSV file
$csv_data = array_map('str_getcsv', file($file));

if (empty($csv_data)) {
    echo json_encode([
        'status' => 0,
        'message' => 'CSV file is empty'
    ]);
    exit;
}

// Extract header (first row)
$headers = array_shift($csv_data);

// Validate required columns
$required_columns = ['customer_id', 'customer_name', 'area_address', 'salesman_name', 'bill_amount', 'remaining_credit', 'bill_date'];
$headers_lower = array_map('strtolower', array_map('trim', $headers));

$column_indices = [];
foreach ($required_columns as $column) {
    $index = array_search(strtolower($column), $headers_lower);
    if ($index === false) {
        echo json_encode([
            'status' => 0,
            'message' => "Required column '$column' not found in CSV"
        ]);
        exit;
    }
    $column_indices[$column] = $index;
}

// Track unique values for migration
$salesman_data = [];
$sector_data = [];
$customer_data = [];
$bill_data = [];
$errors = [];

// Process CSV rows
foreach ($csv_data as $row_index => $row) {
    if (empty(implode('', $row))) {
        continue; // Skip empty rows
    }

    // Extract values based on column indices
    $customer_id = isset($row[$column_indices['customer_id']]) ? trim($row[$column_indices['customer_id']]) : '';
    $customer_name = isset($row[$column_indices['customer_name']]) ? trim($row[$column_indices['customer_name']]) : '';
    $area_address = isset($row[$column_indices['area_address']]) ? trim($row[$column_indices['area_address']]) : '';
    $salesman_name = isset($row[$column_indices['salesman_name']]) ? trim($row[$column_indices['salesman_name']]) : '';
    $bill_amount = isset($row[$column_indices['bill_amount']]) ? (int)trim($row[$column_indices['bill_amount']]) : 0;
    $remaining_credit = isset($row[$column_indices['remaining_credit']]) ? (int)trim($row[$column_indices['remaining_credit']]) : 0;
    $bill_date = isset($row[$column_indices['bill_date']]) ? trim($row[$column_indices['bill_date']]) : '';

    // Validate data
    if (empty($customer_id) || empty($customer_name) || empty($area_address) || empty($salesman_name)) {
        $errors[] = "Row " . ($row_index + 2) . ": Missing required fields";
        continue;
    }

    // Collect salesman data
    if (!array_key_exists($salesman_name, $salesman_data)) {
        $salesman_data[$salesman_name] = true;
    }

    // Collect sector/area data
    if (!array_key_exists($area_address, $sector_data)) {
        $sector_data[$area_address] = true;
    }

    // Collect customer data
    $customer_data[] = [
        'customer_id' => $customer_id,
        'customer_name' => $customer_name,
        'area_address' => $area_address,
        'salesman_name' => $salesman_name,
        'bill_amount' => $bill_amount,
        'remaining_credit' => $remaining_credit,
        'bill_date' => $bill_date
    ];
}

// Step 1: Insert Salesman
$salesman_mapping = [];
foreach ($salesman_data as $salesman_name => $val) {
    $salesman_name_escaped = mysqli_real_escape_string($GLOBALS['conn'], $salesman_name);
    
    // Check if salesman exists
    $check_query = "SELECT saleman_id FROM `salesman` WHERE `saleman_name` = '{$salesman_name_escaped}'";
    $result = json_decode(read($check_query));
    
    if (empty($result) || !isset($result[0]->saleman_id)) {
        // Insert new salesman
        $insert_query = "INSERT INTO `salesman` (`saleman_name`) VALUES ('{$salesman_name_escaped}')";
        $res = create_update_delete($insert_query);
        if ($res) {
            // Get the inserted ID
            $id_query = "SELECT saleman_id FROM `salesman` WHERE `saleman_name` = '{$salesman_name_escaped}' LIMIT 1";
            $id_result = json_decode(read($id_query));
            $salesman_mapping[$salesman_name] = $id_result[0]->saleman_id;
        }
    } else {
        $salesman_mapping[$salesman_name] = $result[0]->saleman_id;
    }
}

// Step 2: Insert Sectors/Areas
$sector_mapping = [];
foreach ($sector_data as $area_address => $val) {
    $area_escaped = mysqli_real_escape_string($GLOBALS['conn'], $area_address);
    
    // Check if sector exists
    $check_query = "SELECT sector_id FROM `sector` WHERE `sector_name` = '{$area_escaped}'";
    $result = json_decode(read($check_query));
    
    if (empty($result) || !isset($result[0]->sector_id)) {
        // Insert new sector
        $insert_query = "INSERT INTO `sector` (`sector_name`) VALUES ('{$area_escaped}')";
        $res = create_update_delete($insert_query);
        if ($res) {
            // Get the inserted ID
            $id_query = "SELECT sector_id FROM `sector` WHERE `sector_name` = '{$area_escaped}' LIMIT 1";
            $id_result = json_decode(read($id_query));
            $sector_mapping[$area_address] = $id_result[0]->sector_id;
        }
    } else {
        $sector_mapping[$area_address] = $result[0]->sector_id;
    }
}

// Step 3: Insert Routes
$route_mapping = [];
foreach ($customer_data as $customer) {
    $area = $customer['area_address'];
    $salesman = $customer['salesman_name'];
    
    $sector_id = isset($sector_mapping[$area]) ? $sector_mapping[$area] : 0;
    $salesman_id = isset($salesman_mapping[$salesman]) ? $salesman_mapping[$salesman] : 0;
    
    if ($sector_id == 0 || $salesman_id == 0) continue; // Skip if mapping failed
    
    $route_key = $sector_id . '_' . $salesman_id;
    
    if (!array_key_exists($route_key, $route_mapping)) {
        // Check if route exists
        $check_query = "SELECT route_id FROM `routes` WHERE `sector_id` = {$sector_id} AND `saleman_id` = {$salesman_id}";
        $result = json_decode(read($check_query));
        
        if (empty($result) || !isset($result[0]->route_id)) {
            // Insert new route
            $insert_query = "INSERT INTO `routes` (`sector_id`, `saleman_id`) VALUES ({$sector_id}, {$salesman_id})";
            $res = create_update_delete($insert_query);
            if ($res) {
                $id_query = "SELECT route_id FROM `routes` WHERE `sector_id` = {$sector_id} AND `saleman_id` = {$salesman_id} LIMIT 1";
                $id_result = json_decode(read($id_query));
                $route_mapping[$route_key] = $id_result[0]->route_id;
            }
        } else {
            $route_mapping[$route_key] = $result[0]->route_id;
        }
    }
}

// Step 3.5: Create initial picklists per salesman from imported data
$picklist_mapping = [];
$picklist_amounts = [];
$picklist_credits = [];
foreach ($customer_data as $customer) {
    $salesman = $customer['salesman_name'];
    $salesman_id = isset($salesman_mapping[$salesman]) ? $salesman_mapping[$salesman] : 0;
    if ($salesman_id == 0) continue;
    if (!isset($picklist_amounts[$salesman_id])) $picklist_amounts[$salesman_id] = 0;
    if (!isset($picklist_credits[$salesman_id])) $picklist_credits[$salesman_id] = 0;
    // Sum of actual bill amounts
    $picklist_amounts[$salesman_id] += (int)$customer['bill_amount'];
    // Sum of remaining credit amounts
    $picklist_credits[$salesman_id] += (int)$customer['remaining_credit'];
}

foreach ($picklist_amounts as $saleman_id => $total_amount) {
    $today = date('Y-m-d');
    $picklist_id = 'MF_' . $saleman_id . '_' . date('Ymd') . '_' . uniqid();
    $picklist_id_esc = mysqli_real_escape_string($GLOBALS['conn'], $picklist_id);
    $saleman_id_esc = (int)$saleman_id;
    $total_amount_esc = (int)$total_amount;
    $total_credit_esc = (int)$picklist_credits[$saleman_id];
    $insert_picklist = "INSERT INTO `picklist`(`picklist_id`,`picklist_saleman`, `picklist_date`, `picklist_amount`, `picklist_recovery`, `picklist_credit`, `picklist_sceheme_amount`, `picklist_return`,`picklist_date_processed`) VALUES ('{$picklist_id_esc}',{$saleman_id_esc},'{$today}',{$total_amount_esc},0,{$total_credit_esc},0,0,CURRENT_DATE)";
    $res = create_update_delete($insert_picklist);
    if ($res) {
        $picklist_mapping[$saleman_id] = $picklist_id;
    }
}

// Step 4: Insert Customers and Bills
$inserted_count = 0;
$failed_count = 0;

foreach ($customer_data as $idx => $customer) {
    $cust_id = mysqli_real_escape_string($GLOBALS['conn'], $customer['customer_id']);
    $cust_name = mysqli_real_escape_string($GLOBALS['conn'], $customer['customer_name']);
    $area = $customer['area_address'];
    $salesman = $customer['salesman_name'];
    $bill_amount = $customer['bill_amount'];
    $remaining_credit = $customer['remaining_credit'];
    
    $sector_id = isset($sector_mapping[$area]) ? $sector_mapping[$area] : 0;
    $salesman_id = isset($salesman_mapping[$salesman]) ? $salesman_mapping[$salesman] : 0;
    $route_key = $sector_id . '_' . $salesman_id;
    $route_id = isset($route_mapping[$route_key]) ? $route_mapping[$route_key] : 0;
    
    if ($route_id == 0) {
       $errors[] = "Row " . ($idx + 2) . ": Route not found for customer";
       $failed_count++;
       continue;
    }
    
    // Check if customer already exists
    $check_query = "SELECT COUNT(*) as count FROM `customer` WHERE `customer_id` = '{$cust_id}'";
    $check_result = json_decode(read($check_query));
    
    $customer_exists = ($check_result[0]->count > 0);

    // Insert customer if not exists
    if (!$customer_exists) {
        $insert_cust = "INSERT INTO `customer` (`customer_id`, `customer_name`, `customer_route`) VALUES ('{$cust_id}', '{$cust_name}', {$route_id})";
        $cust_res = create_update_delete($insert_cust);
        if (!$cust_res) {
            $failed_count++;
            $errors[] = "Row " . ($idx + 2) . ": Failed to insert customer";
            // continue to try inserting bill/ledger even if customer insert failed
        }
    }

    // Insert bill (always attempt, whether customer was new or existed)
    // Fix: Use uniqid and a static counter to ensure uniqueness in fast loops
    static $bill_counter = 0;
    $bill_counter++;
    $bill_id = 'BILL_' . $cust_id . '_' . uniqid() . '_' . $bill_counter;
    
    $picklist_for_customer = isset($picklist_mapping[$salesman_id]) ? mysqli_real_escape_string($GLOBALS['conn'], $picklist_mapping[$salesman_id]) : '';
    $mysql_bill_date = formatDateForMySQL($customer['bill_date']);
    $bill_date_esc = mysqli_real_escape_string($GLOBALS['conn'], $mysql_bill_date);
    
    // FIX: The 'bill' table should track the CURRENT outstanding balance, not the original invoice amount.
    // If we put the full original amount here, the system thinks the customer owes the full amount.
    // However, we want to preserve the original history in the ledger.
    $current_outstanding_balance = (int)$remaining_credit;
    
    $insert_bill = "INSERT INTO `bill` (`bill_id`, `cutomer_id`, `picklist_id`, `bill_amount`, `bill_date`, `Bill_status`) VALUES ('{$bill_id}', '{$cust_id}', '{$picklist_for_customer}', {$current_outstanding_balance}, '{$bill_date_esc}', 'INFILE')";

    if (create_update_delete($insert_bill)) {
        // Insert bill ledger entry
        // For migrated data: `bill_amount` = original invoice, `recived_amount` = already paid (bill_amount - remaining_credit)
        // `remaining_amount` = still outstanding (remaining_credit)
        // We use the ORIGINAL bill amount here for the ledger record to keep history correct.
        $original_bill_amount = (int)$bill_amount;
        $recived_amount = $original_bill_amount - (int)$remaining_credit;
        $remaining_amount = (int)$remaining_credit;
        
        // Ledger query uses original amounts for record keeping
        $ledger_query = "INSERT INTO `bill_ledger` (`ledger_date`, `customer_id`, `bill_id`, `bill_amount`, `recived_amount`, `remaining_amount`) VALUES (CURDATE(), '{$cust_id}', '{$bill_id}', {$original_bill_amount}, {$recived_amount}, {$remaining_amount})";

        if (create_update_delete($ledger_query)) {
            $inserted_count++;
        } else {
            $failed_count++;
            $errors[] = "Row " . ($idx + 2) . ": Failed to create bill ledger";
        }
    } else {
        $failed_count++;
        $errors[] = "Row " . ($idx + 2) . ": Failed to create bill";
    }
}

// Return response
$response = [
    'status' => 1,
    'inserted' => $inserted_count,
    'failed' => $failed_count,
    'total_processed' => $inserted_count + $failed_count,
    'salesmen_created' => count($salesman_mapping),
    'sectors_created' => count($sector_mapping)
    ,'picklists_created' => count($picklist_mapping)
];

if (!empty($errors)) {
    $response['errors'] = $errors;
}

echo json_encode($response);
?>

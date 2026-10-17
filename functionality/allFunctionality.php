<?php
# I know this is not a good move but i want it to work fastly
# This is the most Important document after database... So Dont ever Delete it
# Yes! You read the file name correctly this file will contain all the functionality to this aplication
require_once(__DIR__ . '/components/session.php');
startApplicationSession();
include_once('components/crud.php');
if (($function_to_call ?? '') !== 'login' && empty($_SESSION['admin'])) {
    http_response_code(401);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => 'Admin login is required.']);
    exit;
}

function logDsrProcessError($stage, $message, array $context = [])
{
    $entry = json_encode([
        'timestamp' => date(DATE_ATOM),
        'stage' => $stage,
        'message' => $message,
        'context' => $context,
    ], JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
    $log_path = __DIR__ . '/../logs/dsr_process.log';

    if ($entry === false || @file_put_contents($log_path, $entry . PHP_EOL, FILE_APPEND | LOCK_EX) === false) {
        error_log('[DSR] ' . ($entry ?: $stage . ': ' . $message));
    }
}

function recoverySheetResponse($stage, $response, array $context = [])
{
    $entry = json_encode([
        'timestamp' => date(DATE_ATOM),
        'stage' => $stage,
        'response' => $response,
        'context' => $context,
    ], JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
    $log_path = __DIR__ . '/../logs/recovery_sheet.log';

    if ($entry === false || @file_put_contents($log_path, $entry . PHP_EOL, FILE_APPEND | LOCK_EX) === false) {
        error_log('[Recovery Sheet] ' . ($entry ?: $stage . ': unable to encode response'));
    }

    return json_encode($response, JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
}

function authorizeRecoveryCorrection($conn, $requirePassword, &$error, $csrfField = 'csrf_token')
{
    if (empty($_SESSION['admin'])) {
        $error = 'Admin login is required.';
        return false;
    }
    $sessionToken = $_SESSION['recovery_correction_csrf'] ?? '';
    $requestToken = $_POST[$csrfField] ?? '';
    if ($sessionToken === '' || !hash_equals($sessionToken, $requestToken)) {
        $error = 'The request expired. Reload the correction panel and try again.';
        return false;
    }
    if (!$requirePassword) {
        return true;
    }

    $password = $_POST['correction_password'] ?? '';
    if ($password === '') {
        $error = 'Enter the correction password to confirm this action.';
        return false;
    }
    $stmt = $conn->prepare('SELECT password_hash FROM recovery_correction_auth WHERE auth_id=1');
    if (!$stmt) {
        $error = 'Password verification is currently unavailable.';
        return false;
    }
    $storedPassword = '';
    $stmt->execute();
    $stmt->bind_result($storedPassword);
    $found = $stmt->fetch();
    $stmt->close();
    if (!$found) {
        $error = 'The current admin account could not be verified.';
        return false;
    }

    if (!password_verify($password, (string) $storedPassword)) {
        $error = 'The correction password is incorrect.';
        return false;
    }
    return true;
}

function insertRecoveryCorrectionAudit($conn, $sheetId, $billId, $action, $admin, $reason, $before, $after)
{
    $stmt = $conn->prepare('INSERT INTO recovery_sheet_correction (recovery_sheet_id, bill_id, action_type, admin_user, reason, before_state, after_state) VALUES (?, ?, ?, ?, ?, ?, ?)');
    if (!$stmt) {
        throw new RuntimeException('The correction audit record could not be prepared.');
    }
    $beforeJson = json_encode($before, JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
    $afterJson = json_encode($after, JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
    $stmt->bind_param('issssss', $sheetId, $billId, $action, $admin, $reason, $beforeJson, $afterJson);
    if (!$stmt->execute()) {
        throw new RuntimeException('The correction audit record could not be saved.');
    }
    $stmt->close();
}

$transactionalFunctions = [
    'update_customer_info',
    'bulk_update_customer_names',
    'save_saleman_data',
    'save_sector_data',
    'save_route_data',
    'update_sector',
    'save_customer_data',
    'process_dsr',
    'update_backup_settings',
    'execute_manual_backup',
    'check_auto_backup',
];
ini_set('display_errors', '0');
set_exception_handler(static function (Throwable $error) use ($conn) {
    $operation = $GLOBALS['function_to_call'] ?? 'api_request';
    logDatabaseFailure($operation, $error->getMessage());

    if (isset($GLOBALS['database_request_transaction'])
        && $GLOBALS['database_request_transaction'] === $conn) {
        try {
            $conn->rollback();
        } catch (Throwable $rollbackError) {
            logDatabaseFailure($operation, 'Rollback failed after an unhandled exception: ' . $rollbackError->getMessage());
        }
        unset($GLOBALS['database_request_transaction']);
    }

    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'message' => 'The task could not be completed. Please try again.',
    ], JSON_UNESCAPED_SLASHES);
});
$legacyDsrFunctions = ['store_data_picklist', 'store_bill_data', 'store_bill_ledger'];
if (in_array($function_to_call ?? '', $legacyDsrFunctions, true)) {
    http_response_code(410);
    echo json_encode([
        'success' => false,
        'message' => 'Reload the DSR page and submit again. DSR records must be saved together.',
    ], JSON_UNESCAPED_SLASHES);
    exit;
}
if (($function_to_call ?? '') === 'bulk_update_customer_names' && empty($_SESSION['admin'])) {
    http_response_code(403);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => 'Admin login is required.']);
    exit;
}
if (($function_to_call ?? '') === 'bulk_update_customer_names'
    && (empty($_SESSION['bulk_customer_rename_csrf'])
        || !hash_equals($_SESSION['bulk_customer_rename_csrf'], (string) ($_POST['csrf_token'] ?? '')))) {
    http_response_code(403);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => 'The request expired. Reload the customer page and try again.']);
    exit;
}
if (in_array($function_to_call ?? '', $transactionalFunctions, true)) {
    beginDatabaseRequestTransaction($conn, $function_to_call);
}

switch ($function_to_call) {
    case 'login':
        $username = postData('username');
        $password = postData('password');
        $responce = (int) auth($username, $password, 'admin');
        if ($responce == 1) {
            session_regenerate_id(true);
            $_SESSION['admin'] = $username;
            echo 1;
        } else {
            echo $responce;
        }
        break;
    case 'update_customer_info':
        $auth_error = '';
        if (!authorizeRecoveryCorrection($conn, true, $auth_error)) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => $auth_error]);
            break;
        }
        $id = trim((string) postData('id'));
        $name = trim((string) postData('name'));
        $phone = trim((string) postData('phone'));
        $routeId = filter_var(postData('route_id'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        if ($id === '' || $name === '' || $routeId === false) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Customer name and a valid assigned route are required.']);
            break;
        }

        $checkRoute = $conn->prepare('SELECT route_id FROM routes WHERE route_id = ? LIMIT 1');
        if (!$checkRoute) {
            throw new RuntimeException('The assigned route could not be verified.');
        }
        $checkRoute->bind_param('i', $routeId);
        if (!$checkRoute->execute()) {
            throw new RuntimeException('The assigned route could not be verified.');
        }
        $checkRoute->store_result();
        $routeExists = $checkRoute->num_rows > 0;
        $checkRoute->close();
        if (!$routeExists) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Select a valid assigned route.']);
            break;
        }

        $updateCustomer = $conn->prepare('UPDATE customer c JOIN routes r ON r.route_id = ? SET c.customer_name = ?, c.phone = ?, c.customer_route = r.route_id WHERE c.customer_id = ?');
        if (!$updateCustomer) {
            throw new RuntimeException('Customer details could not be prepared for saving.');
        }
        $updateCustomer->bind_param('isss', $routeId, $name, $phone, $id);
        if (!$updateCustomer->execute()) {
            throw new RuntimeException('Customer details could not be saved.');
        }
        $customerUpdated = $updateCustomer->affected_rows > 0;
        $updateCustomer->close();

        if (!$customerUpdated) {
            $checkCustomer = $conn->prepare('SELECT customer_id FROM customer WHERE customer_id = ? LIMIT 1');
            if (!$checkCustomer) {
                throw new RuntimeException('The customer could not be verified after saving.');
            }
            $checkCustomer->bind_param('s', $id);
            if (!$checkCustomer->execute()) {
                throw new RuntimeException('The customer could not be verified after saving.');
            }
            $checkCustomer->store_result();
            $customerExists = $checkCustomer->num_rows > 0;
            $checkCustomer->close();
            if (!$customerExists) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Customer not found.']);
                break;
            }
        }
        echo '1';
        break;
    case 'bulk_update_customer_names':
        header('Content-Type: application/json; charset=utf-8');
        $auth_error = '';
        if (!authorizeRecoveryCorrection($conn, true, $auth_error, 'correction_csrf_token')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => $auth_error]);
            break;
        }
        $updates = json_decode($_POST['updates'] ?? '', true);
        if (!is_array($updates) || count($updates) < 1) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Provide at least one customer name update.']);
            break;
        }

        $validatedUpdates = [];
        $seenCustomerIds = [];
        $validationErrors = [];
        foreach ($updates as $index => $update) {
            $customerId = is_array($update) ? trim((string) ($update['customer_id'] ?? '')) : '';
            $customerName = is_array($update) ? trim((string) ($update['customer_name'] ?? '')) : '';
            $rowNumber = $index + 1;
            if ($customerId === '' || strlen($customerId) > 12 || strpos($customerId, "\0") !== false || $customerName === '') {
                $validationErrors[] = "Update {$rowNumber} needs a valid store code (maximum 12 characters) and a non-empty new name.";
                continue;
            }

            $normalizedCustomerId = strtolower($customerId);
            if (isset($seenCustomerIds[$normalizedCustomerId])) {
                $validationErrors[] = "Store code {$customerId} appears more than once.";
                continue;
            }
            $seenCustomerIds[$normalizedCustomerId] = true;
            $validatedUpdates[] = ['customer_id' => $customerId, 'customer_name' => $customerName];
        }
        if ($validationErrors) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => implode(' ', $validationErrors)]);
            break;
        }

        $lookup = $conn->prepare('SELECT customer_id FROM customer WHERE customer_id = ? LIMIT 1');
        if (!$lookup) {
            throw new RuntimeException('Customer codes could not be validated.');
        }
        $unknownCustomerIds = [];
        $matchedCustomerId = '';
        $lookup->bind_param('s', $customerId);
        $lookup->bind_result($matchedCustomerId);
        foreach ($validatedUpdates as $update) {
            $customerId = $update['customer_id'];
            if (!$lookup->execute()) {
                throw new RuntimeException('Customer codes could not be validated.');
            }
            $lookup->store_result();
            if ($lookup->num_rows === 0) {
                $unknownCustomerIds[] = $customerId;
            }
            $lookup->free_result();
        }
        $lookup->close();
        if ($unknownCustomerIds) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'No names were changed. These store codes were not found: ' . implode(', ', $unknownCustomerIds),
            ], JSON_UNESCAPED_UNICODE);
            break;
        }

        $rename = $conn->prepare('UPDATE customer SET customer_name = ? WHERE customer_id = ?');
        if (!$rename) {
            throw new RuntimeException('Customer names could not be updated.');
        }
        $customerName = '';
        $customerId = '';
        $rename->bind_param('ss', $customerName, $customerId);
        foreach ($validatedUpdates as $update) {
            $customerName = $update['customer_name'];
            $customerId = $update['customer_id'];
            if (!$rename->execute()) {
                throw new RuntimeException('Customer names could not be updated.');
            }
        }
        $rename->close();
        echo json_encode(['success' => true, 'updated' => count($validatedUpdates)]);
        break;
    case 'Load_customer_table':
        $query = "select a.customer_id,a.customer_name,a.phone,a.customer_route AS customer_route_id,b.sector_name,b.saleman_name from customer as a left join route_saleman_relation b on a.customer_route=b.route_id";
        $responce = read($query);
        //echo $query;
        echo $responce;
        break;
    case 'load_customer_routes':
        $query = "SELECT r.route_id AS ID, CONCAT_WS(' - ', s.sector_name, m.saleman_name, r.day) AS NAME FROM routes r LEFT JOIN sector s ON r.sector_id = s.sector_id LEFT JOIN salesman m ON r.saleman_id = m.saleman_id ORDER BY s.sector_name, m.saleman_name, r.day";
        $responce = read($query);
        echo $responce;
        break;
    case 'save_saleman_data':
        $saleman_name = postData('saleman_name');
        $query = "INSERT INTO `salesman`(`saleman_name`) VALUES ('{$saleman_name}')";
        $responce = create_update_delete($query);
        if ($responce) {
            echo 1;
        } else {
            echo $responce;
        }
        break;
    case 'load_saleman_data':
        $query = "SELECT `saleman_id` As 'ID', `saleman_name` AS 'NAME' FROM `salesman`";
        $responce = read($query);
        echo $responce;
        break;
    case 'load_sectors_options':
        $query = "Select sector_id As 'id',sector_name As 'text' from sector";
        $responce = read($query);
        echo $responce;
        break;
    case "load_sector_data":
        $query = "SELECT `sector_id` AS 'ID', `sector_name` AS 'NAME' FROM sector";
        $responce = read($query);
        echo $responce;
        break;
    case "load_route_data":
        $query = "SELECT s.sector_name As `sector_name`, m.saleman_name As 'saleman_name',t.day As 'day'
        FROM routes AS t
        LEFT JOIN sector AS s ON t.sector_id = s.sector_id
        LEFT JOIN salesman AS m ON t.saleman_id = m.saleman_id";
        $responce = read($query);
        echo $responce;
        break;
    case 'save_sector_data':
        $sector_name = postData('sector_name');
        $query = "INSERT into sector (sector_name) values ('{$sector_name}')";
        //echo $query;
        $responce = create_update_delete($query);
        if ($responce) {
            echo 1;
        } else {
            echo $responce;
        }
        break;
    case 'save_route_data':
        $sector_id = postData('sector_id');
        $saleman_id = postData('saleman_id');
        $day = postData('day');
        $query = "Select Count(*) As 'Existence' from routes where sector_id={$sector_id}";
        $responce = json_decode(read($query));
        if ((int) ($responce[0]->Existence)) {
            $query = "Update routes set saleman_id={$saleman_id},day='{$day}' where sector_id={$sector_id}";
            $responce = create_update_delete($query);
            if ($responce) {
                // START: Bill Transfer Logic
                // 1. Get all INFILE bills for this sector
                $find_bills_query = "SELECT b.bill_id, b.bill_amount, b.picklist_id 
                                     FROM bill b 
                                     JOIN customer c ON b.cutomer_id = c.customer_id 
                                     JOIN routes r ON c.customer_route = r.route_id 
                                     WHERE r.sector_id = {$sector_id} AND b.Bill_status = 'INFILE'";
                $bills_to_move = json_decode(read($find_bills_query), true);

                if (!empty($bills_to_move)) {
                    // Group totals by OLD picklist to subtract later
                    $old_picklists_totals = [];
                    $total_transfer_amount = 0;
                    $bill_ids_to_update = [];

                    foreach ($bills_to_move as $bill) {
                        $pid = $bill['picklist_id'];
                        $amt = (int)$bill['bill_amount'];

                        if (!isset($old_picklists_totals[$pid])) {
                            $old_picklists_totals[$pid] = 0;
                        }
                        $old_picklists_totals[$pid] += $amt;
                        $total_transfer_amount += $amt;
                        $bill_ids_to_update[] = $bill['bill_id'];
                    }

                    // 2. Find or Create NEW Picklist for the NEW Salesman
                    $today_str = date('Ymd');
                    $new_picklist_id = "MF_{$saleman_id}_{$today_str}_TRANSFER"; // Try specific transfer ID first or generic
                    
                    // Check if a picklist exists for this salesman for today (simplified check)
                    // We'll just create/use a specific 'TRANSFER' one to avoid complex merging logic with existing partial ones if possible, 
                    // or just check if one exists. Let's try to append to today's main one if exists.
                    $check_new_pl = "SELECT picklist_id FROM picklist WHERE picklist_saleman = {$saleman_id} AND picklist_date = CURRENT_DATE LIMIT 1";
                    $new_pl_res = json_decode(read($check_new_pl), true);
                    
                    if (!empty($new_pl_res) && isset($new_pl_res[0]['picklist_id'])) {
                        $target_picklist_id = $new_pl_res[0]['picklist_id'];
                        // Update existing
                        $update_new_pl = "UPDATE picklist SET picklist_amount = picklist_amount + {$total_transfer_amount}, picklist_credit = picklist_credit + {$total_transfer_amount} WHERE picklist_id = '{$target_picklist_id}'";
                        create_update_delete($update_new_pl);
                    } else {
                        // Create new
                        $target_picklist_id = "MF_{$saleman_id}_{$today_str}_" . uniqid();
                         $insert_new_pl = "INSERT INTO `picklist`(`picklist_id`,`picklist_saleman`, `picklist_date`, `picklist_amount`, `picklist_recovery`, `picklist_credit`, `picklist_sceheme_amount`, `picklist_return`,`picklist_date_processed`) 
                                           VALUES ('{$target_picklist_id}',{$saleman_id},CURRENT_DATE,{$total_transfer_amount},0,{$total_transfer_amount},0,0,CURRENT_DATE)";
                        create_update_delete($insert_new_pl);
                    }

                    // 3. Subtract from OLD Picklists
                    foreach ($old_picklists_totals as $old_pid => $amt_to_sub) {
                        if (empty($old_pid)) continue;
                        $update_old = "UPDATE picklist SET picklist_amount = picklist_amount - {$amt_to_sub}, picklist_credit = picklist_credit - {$amt_to_sub} WHERE picklist_id = '{$old_pid}'";
                        create_update_delete($update_old);
                    }

                    // 4. Update Bills to point to NEW Picklist
                    if (!empty($bill_ids_to_update)) {
                        $ids_str = implode("','", $bill_ids_to_update);
                        $update_bills = "UPDATE bill SET picklist_id = '{$target_picklist_id}' WHERE bill_id IN ('{$ids_str}')";
                        create_update_delete($update_bills);
                    }
                }
                // END: Bill Transfer Logic

                echo 2;
            } else {
                echo $responce;
            }
        } else {
            $query = "Insert into routes (sector_id,saleman_id,day) values('{$sector_id}','{$saleman_id}','{$day}')";
            // echo $query;
            $responce = create_update_delete($query);
            if ($responce) {
                echo 1;
            } else {
                echo $responce;
            }
        }
        break;
    case 'update_sector':
        $selected_sector = postData('selected_sector');
        $updated_sector_name = postData('updated_sector_name');
        $query = "Update sector set sector_name='{$updated_sector_name}' where sector_id={$selected_sector}";
        //echo $query;
        $responce = create_update_delete($query);
        if ($responce) {
            echo 1;
        } else {
            echo $responce;
        }
        break;
    case 'save_customer_data':
        $customer_id = postData('customer_id');
        $customer_name = postData('customer_name');
        $customer_phone = postData('customer_phone');
        $customer_route_id = postData('customer_route_id');
        $query = "INSERT INTO `customer` (`customer_id`, `customer_name`, `phone`, `customer_route`) VALUES ('{$customer_id}', '{$customer_name}', '{$customer_phone}', (SELECT `route_id` FROM `routes` WHERE `sector_id` = '{$customer_route_id}'))";
        
        $responce = create_update_delete($query);
        if ($responce) {
            echo 1;
        } else {
            echo $query;
        }
        break;
    case 'Load_customer_table_by_saleman':
        $saleman_id = postData('saleman_id');
        // Only select customers whose route is assigned to the selected salesman
        $query = "SELECT c.customer_id, c.customer_name FROM customer c JOIN routes r ON c.customer_route = r.route_id WHERE r.saleman_id = '{$saleman_id}'";
        $responce = read($query);
        echo $responce;
        break;
    case 'check_dsr_bill_ids':
        header('Content-Type: application/json; charset=utf-8');
        $billIds = json_decode($_POST['bill_ids'] ?? '', true);
        if (!is_array($billIds) || count($billIds) > 1000) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Provide a valid list of up to 1000 bill IDs.']);
            break;
        }

        $billStmt = $conn->prepare('SELECT bill_id FROM bill WHERE bill_id=? LIMIT 1');
        if (!$billStmt) {
            throw new RuntimeException('Bill IDs could not be checked.');
        }
        $billId = '';
        $existingBillId = '';
        $billStmt->bind_param('s', $billId);
        $billStmt->bind_result($existingBillId);
        $existingBillIds = [];
        foreach ($billIds as $requestedBillId) {
            $billId = is_scalar($requestedBillId) ? trim((string) $requestedBillId) : '';
            if ($billId === '') {
                continue;
            }
            if (!$billStmt->execute()) {
                throw new RuntimeException('Bill IDs could not be checked.');
            }
            $billStmt->store_result();
            if ($billStmt->num_rows > 0 && $billStmt->fetch()) {
                $existingBillIds[] = $existingBillId;
            }
            $billStmt->free_result();
        }
        $billStmt->close();
        echo json_encode(['existing_bill_ids' => array_values(array_unique($existingBillIds))], JSON_UNESCAPED_UNICODE);
        break;
    case 'store_bill_data':
        $values = "";
        $query = "INSERT INTO `bill`(`bill_id`, `cutomer_id`, `picklist_id`, `bill_amount`, `bill_date`, `Bill_status`) VALUES";
        $customer_bills = postData('customer_bills');

        if (!empty($customer_bills)) { // Check if the JSON data is not empty
            $customer_bills = json_decode(urldecode($customer_bills), true); // Decode as an associative array

            if (is_array($customer_bills)) { // Check if JSON decoding was successful
                foreach ($customer_bills as $bill) {
                    $customer_id = $bill['customer_id'];
                    $credit_amount = $bill['credit_amount']; // This is the net credit/debt
                    $invoice_date = $bill['invoice_date'];
                    $invoice_id = $bill['invoice_id'];
                    $picklist_id = $bill['picklist_id'];

                    // Record the net credit amount as the bill amount (debt)
                    $values .= "('$invoice_id', '$customer_id', '$picklist_id', '$credit_amount', '$invoice_date', 'INFILE'),";
                }

                // Remove the trailing comma from $values
                $values = rtrim($values, ',');

                // Combine the query and values
                $query .= $values;
                $responce = create_update_delete($query);
                if ($responce == 1) {
                    echo 1;
                } else {
                    $database_error = $conn->error;
                    logDsrProcessError('store_bill_data', $database_error, [
                        'picklist_id' => $customer_bills[0]['picklist_id'] ?? null,
                        'bill_count' => count($customer_bills),
                    ]);
                    if ($conn->errno === 1062 && preg_match("/Duplicate entry '([^']+)'/", $database_error, $duplicate_match)) {
                        $message = 'Invoice ID already exists: ' . $duplicate_match[1];
                    } else {
                        $message = 'Unable to save bills: ' . $database_error;
                    }
                    echo json_encode(['status' => 'error', 'message' => $message]);
                }
                // echo 'store_bill_data:/n'.$query.'/n';
            } else {
                logDsrProcessError('store_bill_data', 'Invalid bill JSON: ' . json_last_error_msg());
                echo "Error: Failed to decode JSON data.";
            }
        } else {
            logDsrProcessError('store_bill_data', 'Bill payload was empty.');
            echo "Error: JSON data is empty or null.";
        }

        break;
    case 'store_data_picklist':
        $picklist_id = postData('picklist_id');
        $picklist_saleman=postData('picklist_saleman');
        $picklist_date = postData('picklist_date');
        $picklist_amount = postData('picklist_amount');
        $picklist_recovery = postData('picklist_recovery');
        $picklist_credit = postData('picklist_credit');
        $picklist_scheme = postData('picklist_scheme');
        $picklist_return = postData('picklist_return');
        $query = "INSERT INTO `picklist`(`picklist_id`,`picklist_saleman`, `picklist_date`, `picklist_amount`, `picklist_recovery`, `picklist_credit`, `picklist_sceheme_amount`, `picklist_return`,`picklist_date_processed`) VALUES ('{$picklist_id}','$picklist_saleman','{$picklist_date}','{$picklist_amount}','{$picklist_recovery}','{$picklist_credit}','{$picklist_scheme}','{$picklist_return}',CURRENT_DATE)";
        $responce = create_update_delete($query);
        if ($responce) {
            echo 1;
        } else {
            logDsrProcessError('store_data_picklist', $conn->error, [
                'picklist_id' => $picklist_id,
            ]);
            echo $responce;
        }
        break;
    case 'store_bill_ledger':
        $values = "";
        $query = "INSERT INTO `bill_ledger`(`ledger_date`, `customer_id`, `bill_id`, `bill_amount`, `recived_amount`, `remaining_amount`) VALUES";
        $customer_bills = postData('customer_bills');

        if (!empty($customer_bills)) { // Check if the JSON data is not empty
            $customer_bills = json_decode(urldecode($customer_bills), true); // Decode as an associative array

            if (is_array($customer_bills)) {
                foreach ($customer_bills as $bill) {
                    $customer_id = $bill['customer_id'];
                    $bill_total = $bill['bill_total'];
                    $bill_recovered = $bill['bill_recovered'];
                    $bill_returned = $bill['bill_returned']; // New: Extract return amount
                    $credit_amount = $bill['credit_amount']; // Net balance
                    $invoice_date = $bill['invoice_date'];
                    $invoice_id = $bill['invoice_id'];

                    // Concatenate the values for the INSERT statement
                    // bill_total is the original gross, bill_recovered is the cash payment
                    // bill_returned is the item return amount
                    // remaining_amount is the net credit (debt)
                    $values .= "('{$invoice_date}','{$customer_id}','{$invoice_id}',{$bill_total},{$bill_recovered},{$bill_returned},{$credit_amount}),";
                }
                // Remove the trailing comma from $values
                $values = rtrim($values, ',');

                // Combine the query and values
                // Updated query to include 'return_amount'
                $query = "INSERT INTO `bill_ledger`(`ledger_date`, `customer_id`, `bill_id`, `bill_amount`, `recived_amount`, `return_amount`, `remaining_amount`) VALUES" . $values;

                if (create_update_delete($query)) {
                    echo true;
                } else {
                    logDsrProcessError('store_bill_ledger', $conn->error, [
                        'picklist_id' => $customer_bills[0]['picklist_id'] ?? null,
                        'bill_count' => count($customer_bills),
                    ]);
                    echo 0;
                }
            } else {
                logDsrProcessError('store_bill_ledger', 'Invalid bill JSON: ' . json_last_error_msg());
                echo "Error: Failed to decode JSON data.";
            }
        } else {
            logDsrProcessError('store_bill_ledger', 'Bill payload was empty.');
            echo "Error: JSON data is empty or null.";
        }
        break;
    case 'getBillsData':
        $saleman_id = postData('saleman_id');
        if (empty($saleman_id)) {
            echo json_encode([]);
            break;
        }
        // Optimized query: Join bill -> customer -> relation view directly via route_id
        $query = "SELECT a.bill_id, b.customer_name, e.saleman_name, e.sector_name, a.bill_date, a.bill_amount 
                  FROM bill as a 
                  JOIN customer as b ON a.cutomer_id = b.customer_id 
                  JOIN route_saleman_relation as e ON b.customer_route = e.route_id 
                  WHERE a.Bill_status = 'INFILE' 
                  AND EXISTS (SELECT 1 FROM picklist p WHERE p.picklist_id = a.picklist_id AND p.picklist_saleman = '{$saleman_id}');";
        $responce = read($query);
        echo $responce;
        break;
    case 'store_recovery_sheet':
        $salesmanValue = $_POST['saleman'] ?? null;
        $salesmanId = is_string($salesmanValue) || is_int($salesmanValue)
            ? filter_var($salesmanValue, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])
            : false;
        $requestKeyValue = $_POST['request_key'] ?? '';
        $requestKey = is_string($requestKeyValue) ? strtolower(trim($requestKeyValue)) : '';
        $billsJson = $_POST['bills'] ?? '';
        $billPayload = is_string($billsJson) ? json_decode($billsJson, true) : null;
        if (!$salesmanId || !preg_match('/^[a-f0-9]{32}$/', $requestKey)
            || !is_array($billPayload) || !$billPayload) {
            http_response_code(400);
            echo recoverySheetResponse('store_recovery_sheet', [
                'success' => false,
                'message' => 'Select a salesman and at least one bill, then try again.',
            ]);
            break;
        }

        $billIds = [];
        foreach ($billPayload as $bill) {
            if (!is_string($bill) && !is_int($bill)
                && !(is_array($bill) && isset($bill['bill_id']) && (is_string($bill['bill_id']) || is_int($bill['bill_id'])))) {
                http_response_code(400);
                echo recoverySheetResponse('store_recovery_sheet', [
                    'success' => false,
                    'message' => 'The selected bill list is invalid. Reload the bills and try again.',
                ]);
                break 2;
            }
            $billId = trim((string) (is_array($bill) ? $bill['bill_id'] : $bill));
            if ($billId === '' || strlen($billId) > 100 || isset($billIds[$billId])) {
                http_response_code(400);
                echo recoverySheetResponse('store_recovery_sheet', [
                    'success' => false,
                    'message' => 'The selected bill list is invalid or contains duplicates. Reload the page and try again.',
                ]);
                break 2;
            }
            $billIds[$billId] = true;
        }
        $billIds = array_keys($billIds);
        sort($billIds, SORT_STRING);
        $payloadHash = hash('sha256', json_encode([
            'salesman_id' => $salesmanId,
            'bill_ids' => $billIds,
        ], JSON_UNESCAPED_SLASHES));

        try {
            $conn->begin_transaction();
            $createStmt = $conn->prepare("INSERT INTO recovery_sheet (recovery_date, recovery_sheet_saleman_id, recovery_sheet_amount, recovery_sheet_recovery, sheet_status, creation_request_key, creation_payload_hash) VALUES (CURRENT_DATE, ?, 0, 0, '0', ?, ?) ON DUPLICATE KEY UPDATE recovery_id=LAST_INSERT_ID(recovery_id)");
            if (!$createStmt) {
                throw new RuntimeException('The recovery creation migration must be applied before creating sheets.');
            }
            $createStmt->bind_param('iss', $salesmanId, $requestKey, $payloadHash);
            if (!$createStmt->execute()) {
                throw new RuntimeException('The recovery sheet could not be created.');
            }
            $recoveryId = (int) $conn->insert_id;
            $createStmt->close();
            if ($recoveryId < 1) {
                throw new RuntimeException('The recovery sheet ID could not be confirmed.');
            }

            $sheetStmt = $conn->prepare('SELECT creation_payload_hash FROM recovery_sheet WHERE recovery_id=? FOR UPDATE');
            if (!$sheetStmt) {
                throw new RuntimeException('The created recovery sheet could not be verified.');
            }
            $sheetStmt->bind_param('i', $recoveryId);
            $sheetStmt->execute();
            $sheetStmt->bind_result($storedPayloadHash);
            if (!$sheetStmt->fetch() || !hash_equals((string) $storedPayloadHash, $payloadHash)) {
                $sheetStmt->close();
                throw new RuntimeException('This submission key was already used for different bills. Reload the page and try again.');
            }
            $sheetStmt->close();

            $existingDetailsStmt = $conn->prepare('SELECT COUNT(*) FROM recovery_sheet_detail WHERE recovery_sheet_id=?');
            if (!$existingDetailsStmt) {
                throw new RuntimeException('The recovery sheet details could not be verified.');
            }
            $existingDetailsStmt->bind_param('i', $recoveryId);
            $existingDetailsStmt->execute();
            $existingDetailsStmt->bind_result($existingDetailCount);
            $existingDetailsStmt->fetch();
            $existingDetailsStmt->close();

            if ((int) $existingDetailCount > 0) {
                if ((int) $existingDetailCount !== count($billIds)) {
                    throw new RuntimeException('The existing recovery sheet does not match this submission. Contact an administrator.');
                }
                $conn->commit();
                echo recoverySheetResponse('store_recovery_sheet', [
                    'success' => true,
                    'recovery_sheet_id' => $recoveryId,
                    'duplicate_request' => true,
                ]);
                break;
            }

            $placeholders = implode(',', array_fill(0, count($billIds), '?'));
            $billQuery = "SELECT b.bill_id, b.bill_amount FROM bill b WHERE b.Bill_status='INFILE' AND b.bill_id IN ({$placeholders}) AND EXISTS (SELECT 1 FROM picklist p WHERE p.picklist_id=b.picklist_id AND p.picklist_saleman=?) ORDER BY b.bill_id FOR UPDATE";
            $billStmt = $conn->prepare($billQuery);
            if (!$billStmt) {
                throw new RuntimeException('The selected bills could not be checked.');
            }
            $bindValues = array_merge($billIds, [$salesmanId]);
            $bindTypes = str_repeat('s', count($billIds)) . 'i';
            $bindArguments = [$bindTypes];
            foreach ($bindValues as $index => &$bindValue) {
                $bindArguments[] = &$bindValue;
            }
            call_user_func_array([$billStmt, 'bind_param'], $bindArguments);
            unset($bindValue);
            if (!$billStmt->execute()) {
                throw new RuntimeException('The selected bills could not be checked.');
            }
            $selectedBillsFromDatabase = $billStmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $billStmt->close();
            if (count($selectedBillsFromDatabase) !== count($billIds)) {
                throw new RuntimeException('One or more selected bills are no longer available for this salesman. Reload the bills and try again.');
            }

            $detailStmt = $conn->prepare('INSERT INTO recovery_sheet_detail (recovery_sheet_id, recovery_sheet_bill_id, recovery_sheet_bill_amount, recovery_sheet_bill_recovered, recovery_sheet_bill_returned) VALUES (?, ?, ?, 0, 0)');
            $statusStmt = $conn->prepare("UPDATE bill SET Bill_status='ISSUED' WHERE bill_id=? AND Bill_status='INFILE'");
            if (!$detailStmt || !$statusStmt) {
                throw new RuntimeException('The recovery sheet bills could not be saved.');
            }
            foreach ($selectedBillsFromDatabase as $bill) {
                $billId = (string) $bill['bill_id'];
                $billAmount = (float) $bill['bill_amount'];
                $detailStmt->bind_param('isd', $recoveryId, $billId, $billAmount);
                if (!$detailStmt->execute()) {
                    throw new RuntimeException('A selected bill could not be added to the recovery sheet.');
                }
                $statusStmt->bind_param('s', $billId);
                if (!$statusStmt->execute() || $statusStmt->affected_rows !== 1) {
                    throw new RuntimeException('A selected bill is no longer available. No recovery sheet changes were saved.');
                }
            }
            $detailStmt->close();
            $statusStmt->close();

            $totalStmt = $conn->prepare('UPDATE recovery_sheet SET recovery_sheet_amount=(SELECT COALESCE(SUM(recovery_sheet_bill_amount), 0) FROM recovery_sheet_detail WHERE recovery_sheet_id=?) WHERE recovery_id=?');
            if (!$totalStmt) {
                throw new RuntimeException('The recovery sheet total could not be saved.');
            }
            $totalStmt->bind_param('ii', $recoveryId, $recoveryId);
            if (!$totalStmt->execute()) {
                throw new RuntimeException('The recovery sheet total could not be saved.');
            }
            $totalStmt->close();

            $conn->commit();
            echo recoverySheetResponse('store_recovery_sheet', [
                'success' => true,
                'recovery_sheet_id' => $recoveryId,
                'duplicate_request' => false,
            ], ['salesman_id' => $salesmanId, 'bill_count' => count($billIds)]);
        } catch (Throwable $error) {
            try {
                $conn->rollback();
            } catch (Throwable $rollbackError) {
                logDatabaseFailure('store_recovery_sheet', 'Rollback failed: ' . $rollbackError->getMessage());
            }
            logDatabaseFailure('store_recovery_sheet', $error->getMessage());
            http_response_code($error instanceof RuntimeException ? 409 : 500);
            echo recoverySheetResponse('store_recovery_sheet', [
                'success' => false,
                'message' => $error instanceof RuntimeException
                    ? $error->getMessage()
                    : 'The recovery sheet could not be created. No changes were saved.',
            ], ['salesman_id' => $salesmanId, 'bill_count' => count($billIds)]);
        }
        break;
    case 'process_dsr':
        $picklist_id = trim($_POST['picklist_id'] ?? '');
        $salesman_id = filter_var($_POST['picklist_saleman'] ?? null, FILTER_VALIDATE_INT);
        $picklist_date = $_POST['picklist_date'] ?? '';
        $bills = json_decode($_POST['customer_bills'] ?? '', true);
        $date = DateTime::createFromFormat('!Y-m-d', $picklist_date);
        $dateErrors = DateTime::getLastErrors();

        if ($picklist_id === '' || strlen($picklist_id) > 36 || !$salesman_id
            || !$date || ($dateErrors && ($dateErrors['warning_count'] || $dateErrors['error_count']))
            || $date->format('Y-m-d') !== $picklist_date || !is_array($bills) || !$bills) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Enter a valid picklist ID, date, salesman, and at least one bill.']);
            break;
        }

        try {
            $picklistAmount = 0;
            $picklistRecovery = 0;
            $picklistCredit = 0;
            $picklistScheme = 0;
            $picklistReturn = 0;
            $seenBillIds = [];
            $validatedBills = [];

            foreach ($bills as $bill) {
                if (!is_array($bill)) {
                    throw new InvalidArgumentException('A bill entry is invalid.');
                }
                $billId = trim((string) ($bill['invoice_id'] ?? ''));
                $customerId = trim((string) ($bill['customer_id'] ?? ''));
                $billDate = (string) ($bill['invoice_date'] ?? '');
                $billDateValue = DateTime::createFromFormat('!Y-m-d', $billDate);
                $billDateErrors = DateTime::getLastErrors();
                $amounts = [];
                foreach (['bill_total', 'bill_recovered', 'bill_returned', 'bill_scheme', 'credit_amount'] as $key) {
                    $value = $bill[$key] ?? null;
                    if (!is_numeric($value) || !is_finite((float) $value)
                        || floor((float) $value) !== (float) $value || (float) $value < 0) {
                        throw new InvalidArgumentException('Bill amounts must be non-negative whole numbers.');
                    }
                    $amounts[$key] = (int) $value;
                }

                if ($billId === '' || strlen($billId) > 100 || $customerId === ''
                    || strlen($customerId) > 33 || isset($seenBillIds[$billId])
                    || !$billDateValue || ($billDateErrors && ($billDateErrors['warning_count'] || $billDateErrors['error_count']))
                    || $billDateValue->format('Y-m-d') !== $billDate
                    || $amounts['bill_total'] <= 0
                    || $amounts['bill_recovered'] + $amounts['bill_returned'] + $amounts['bill_scheme'] > $amounts['bill_total']
                    || $amounts['credit_amount'] !== $amounts['bill_total'] - $amounts['bill_recovered'] - $amounts['bill_returned'] - $amounts['bill_scheme']) {
                    throw new InvalidArgumentException('A bill has an invalid ID, date, customer, or amount.');
                }

                $seenBillIds[$billId] = true;
                $picklistAmount += $amounts['bill_total'];
                $picklistRecovery += $amounts['bill_recovered'];
                $picklistCredit += $amounts['credit_amount'];
                $picklistScheme += $amounts['bill_scheme'];
                $picklistReturn += $amounts['bill_returned'];
                $validatedBills[] = [
                    'invoice_id' => $billId,
                    'customer_id' => $customerId,
                    'invoice_date' => $billDate,
                    'amounts' => $amounts,
                ];
            }
            $bills = $validatedBills;

            $existingBillStmt = $conn->prepare('SELECT bill_id FROM bill WHERE bill_id=? LIMIT 1');
            if (!$existingBillStmt) {
                throw new RuntimeException('Bill IDs could not be checked.');
            }
            $billIdToCheck = '';
            $existingBillId = '';
            $existingBillStmt->bind_param('s', $billIdToCheck);
            $existingBillStmt->bind_result($existingBillId);
            foreach ($bills as $bill) {
                $billIdToCheck = $bill['invoice_id'];
                if (!$existingBillStmt->execute()) {
                    throw new RuntimeException('Bill IDs could not be checked.');
                }
                $existingBillStmt->store_result();
                $billAlreadyExists = $existingBillStmt->num_rows > 0 && $existingBillStmt->fetch();
                $existingBillStmt->free_result();
                if ($billAlreadyExists) {
                    throw new InvalidArgumentException('Bill ID ' . $existingBillId . ' already exists in the database.');
                }
            }
            $existingBillStmt->close();

            $picklistStmt = $conn->prepare('INSERT INTO picklist (picklist_id, picklist_saleman, picklist_date, picklist_amount, picklist_recovery, picklist_credit, picklist_sceheme_amount, picklist_return, picklist_date_processed) VALUES (?, ?, ?, ?, ?, ?, ?, ?, CURRENT_DATE)');
            if (!$picklistStmt) {
                throw new RuntimeException('The picklist could not be prepared.');
            }
            $picklistStmt->bind_param('sisiiiii', $picklist_id, $salesman_id, $picklist_date, $picklistAmount, $picklistRecovery, $picklistCredit, $picklistScheme, $picklistReturn);
            $picklistStmt->execute();
            $picklistStmt->close();

            $customerStmt = $conn->prepare('SELECT c.customer_id FROM customer c JOIN routes r ON r.route_id=c.customer_route WHERE c.customer_id=? AND r.saleman_id=? LIMIT 1');
            $billStmt = $conn->prepare('INSERT INTO bill (bill_id, cutomer_id, picklist_id, bill_amount, bill_date, Bill_status) VALUES (?, ?, ?, ?, ?, ?)');
            $ledgerStmt = $conn->prepare('INSERT INTO bill_ledger (ref_id, ledger_date, customer_id, bill_id, bill_amount, recived_amount, return_amount, remaining_amount, bill_status) VALUES (0, ?, ?, ?, ?, ?, ?, ?, ?)');
            if (!$customerStmt || !$billStmt || !$ledgerStmt) {
                throw new RuntimeException('The DSR bill records could not be prepared.');
            }

            foreach ($bills as $bill) {
                $amounts = $bill['amounts'];
                $billId = $bill['invoice_id'];
                $customerId = $bill['customer_id'];
                $billDate = (string) $bill['invoice_date'];
                $customerStmt->bind_param('si', $customerId, $salesman_id);
                $customerStmt->execute();
                $customerStmt->store_result();
                $customerExists = $customerStmt->num_rows === 1;
                $customerStmt->free_result();
                if (!$customerExists) {
                    throw new InvalidArgumentException('A bill customer does not belong to the selected salesman.');
                }

                $creditAmount = $amounts['credit_amount'];
                $billStatus = $creditAmount > 0 ? 'INFILE' : 'NILL';
                $billStmt->bind_param('sssiss', $billId, $customerId, $picklist_id, $creditAmount, $billDate, $billStatus);
                $billStmt->execute();
                $billTotal = $amounts['bill_total'];
                $billRecovered = $amounts['bill_recovered'];
                $billReturned = $amounts['bill_returned'];
                $ledgerStmt->bind_param('sssiiiis', $billDate, $customerId, $billId, $billTotal, $billRecovered, $billReturned, $creditAmount, $billStatus);
                $ledgerStmt->execute();
            }

            $customerStmt->close();
            $billStmt->close();
            $ledgerStmt->close();
            echo json_encode(['success' => true, 'message' => 'The DSR and all bill ledger entries were saved.']);
        } catch (Throwable $error) {
            logDatabaseFailure('process_dsr', $error->getMessage());
            echo json_encode([
                'success' => false,
                'message' => $error instanceof InvalidArgumentException
                    ? $error->getMessage()
                    : 'The DSR could not be saved. No changes were applied.',
            ], JSON_UNESCAPED_SLASHES);
        }
        break;
    case 'get_editable_dsr_picklists':
        if (empty($_SESSION['admin'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Admin login is required.']);
            break;
        }
        $query = "SELECT p.picklist_id AS ID, p.picklist_date AS Date, s.saleman_name AS Saleman, p.picklist_amount AS Amount,
            (SELECT COUNT(*) FROM bill b JOIN bill_ledger l ON l.bill_id=b.bill_id AND l.ref_id=0 WHERE b.picklist_id=p.picklist_id) AS BillCount,
            (SELECT COUNT(*) FROM bill b JOIN bill_ledger l ON l.bill_id=b.bill_id AND l.ref_id=0 WHERE b.picklist_id=p.picklist_id AND (b.Bill_status NOT IN ('INFILE','NILL') OR EXISTS (SELECT 1 FROM bill_ledger later WHERE later.bill_id=b.bill_id AND later.ref_id>0))) AS LockedCount
            FROM picklist p JOIN salesman s ON s.saleman_id=p.picklist_saleman ORDER BY p.picklist_date DESC, p.picklist_id DESC";
        echo read($query);
        break;
    case 'get_dsr_edit_details':
        if (empty($_SESSION['admin'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Admin login is required.']);
            break;
        }
        $picklist_id = trim($_POST['picklist_id'] ?? '');
        if ($picklist_id === '') {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Choose a DSR to edit.']);
            break;
        }
        $stmt = $conn->prepare("SELECT b.bill_id AS ID, b.cutomer_id AS CustomerID, c.customer_name AS CustomerName, l.bill_amount AS Total, l.recived_amount AS Recovered, l.return_amount AS Returned, l.bill_amount-l.recived_amount-l.return_amount-l.remaining_amount AS Scheme, l.remaining_amount AS Remaining, b.Bill_status AS BillStatus, EXISTS(SELECT 1 FROM bill_ledger later WHERE later.bill_id=b.bill_id AND later.ref_id>0) AS HasRecovery FROM bill b JOIN bill_ledger l ON l.bill_id=b.bill_id AND l.ref_id=0 LEFT JOIN customer c ON c.customer_id=b.cutomer_id WHERE b.picklist_id=? ORDER BY b.bill_date, b.bill_id");
        if (!$stmt) {
            markDatabaseRequestFailed('get_dsr_edit_details', $conn->error);
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'DSR details are unavailable.']);
            break;
        }
        $stmt->bind_param('s', $picklist_id);
        if (!$stmt->execute()) {
            markDatabaseRequestFailed('get_dsr_edit_details', $stmt->error);
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'DSR details are unavailable.']);
            $stmt->close();
            break;
        }
        $details = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        echo json_encode($details, JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
        break;
    case 'update_dsr_amounts':
        if (empty($_SESSION['admin'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Admin login is required.']);
            break;
        }
        $csrfToken = $_SESSION['dsr_edit_csrf'] ?? '';
        $requestToken = $_POST['csrf_token'] ?? '';
        if ($csrfToken === '' || !hash_equals($csrfToken, $requestToken)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'The request expired. Reload the DSR page and try again.']);
            break;
        }
        $picklist_id = trim($_POST['picklist_id'] ?? '');
        $bills = json_decode($_POST['bills'] ?? '', true);
        if ($picklist_id === '' || strlen($picklist_id) > 36 || !is_array($bills) || !$bills) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'A valid DSR and bill updates are required.']);
            break;
        }
        try {
            $conn->begin_transaction();
            $picklistStmt = $conn->prepare('SELECT picklist_id FROM picklist WHERE picklist_id=? FOR UPDATE');
            if (!$picklistStmt) {
                throw new RuntimeException('The DSR could not be checked.');
            }
            $picklistStmt->bind_param('s', $picklist_id);
            $picklistStmt->execute();
            $picklistStmt->store_result();
            if ($picklistStmt->num_rows !== 1) {
                throw new RuntimeException('The selected DSR was not found.');
            }
            $picklistStmt->close();

            $expectedStmt = $conn->prepare('SELECT COUNT(*) FROM bill b JOIN bill_ledger l ON l.bill_id=b.bill_id AND l.ref_id=0 WHERE b.picklist_id=?');
            $expectedStmt->bind_param('s', $picklist_id);
            $expectedStmt->execute();
            $expectedStmt->bind_result($expectedCount);
            $expectedStmt->fetch();
            $expectedStmt->close();
            $seen = [];
            $selectStmt = $conn->prepare("SELECT l.ledger_id, l.bill_amount, l.recived_amount, l.return_amount, l.remaining_amount, b.bill_amount, b.Bill_status, EXISTS(SELECT 1 FROM bill_ledger later WHERE later.bill_id=b.bill_id AND later.ref_id>0) FROM bill b JOIN bill_ledger l ON l.bill_id=b.bill_id AND l.ref_id=0 WHERE b.picklist_id=? AND b.bill_id=? FOR UPDATE");
            $updateLedger = $conn->prepare('UPDATE bill_ledger SET recived_amount=?, return_amount=?, remaining_amount=?, bill_status=? WHERE ledger_id=?');
            $updateBill = $conn->prepare('UPDATE bill SET bill_amount=?, Bill_status=? WHERE bill_id=?');
            if (!$selectStmt || !$updateLedger || !$updateBill) {
                throw new RuntimeException('The DSR bills could not be prepared for update.');
            }

            foreach ($bills as $item) {
                $billId = isset($item['bill_id']) ? trim((string) $item['bill_id']) : '';
                if ($billId === '' || isset($seen[$billId])) {
                    throw new InvalidArgumentException('The DSR contains a missing or duplicate bill ID.');
                }
                $seen[$billId] = true;
                $amounts = [];
                foreach (['recovered', 'returned', 'scheme'] as $key) {
                    $value = $item[$key] ?? null;
                    if (!is_numeric($value) || !is_finite((float) $value)
                        || floor((float) $value) !== (float) $value || (float) $value < 0) {
                        throw new InvalidArgumentException('Recovered, returned, and scheme values must be non-negative whole numbers.');
                    }
                    $amounts[$key] = (int) $value;
                }
                $selectStmt->bind_param('ss', $picklist_id, $billId);
                $selectStmt->execute();
                $selectStmt->bind_result($ledgerId, $total, $oldRecovered, $oldReturned, $oldRemaining, $billBalance, $billStatus, $hasRecovery);
                if (!$selectStmt->fetch()) {
                    $selectStmt->free_result();
                    throw new RuntimeException('A bill does not belong to the selected DSR.');
                }
                $selectStmt->free_result();
                if ((int) $hasRecovery !== 0 || !in_array($billStatus, ['INFILE', 'NILL'], true)
                    || (float) $billBalance !== (float) $oldRemaining) {
                    throw new RuntimeException('A bill already has recovery activity and cannot be changed in its original DSR.');
                }
                $remaining = (float) $total - $amounts['recovered'] - $amounts['returned'] - $amounts['scheme'];
                if ($remaining < 0) {
                    throw new InvalidArgumentException('The amounts for a bill cannot exceed its original total.');
                }
                $newStatus = $remaining > 0 ? 'INFILE' : 'NILL';
                $updateLedger->bind_param('iiisi', $amounts['recovered'], $amounts['returned'], $remaining, $newStatus, $ledgerId);
                $updateLedger->execute();
                $updateBill->bind_param('dss', $remaining, $newStatus, $billId);
                $updateBill->execute();
            }
            if (count($seen) > (int) $expectedCount) {
                throw new RuntimeException('The bill list changed. Reload the DSR and try again.');
            }
            $totalsStmt = $conn->prepare("UPDATE picklist p SET
                picklist_amount=(SELECT COALESCE(SUM(l.bill_amount),0) FROM bill b JOIN bill_ledger l ON l.bill_id=b.bill_id AND l.ref_id=0 WHERE b.picklist_id=p.picklist_id),
                picklist_recovery=(SELECT COALESCE(SUM(l.recived_amount),0) FROM bill b JOIN bill_ledger l ON l.bill_id=b.bill_id AND l.ref_id=0 WHERE b.picklist_id=p.picklist_id),
                picklist_return=(SELECT COALESCE(SUM(l.return_amount),0) FROM bill b JOIN bill_ledger l ON l.bill_id=b.bill_id AND l.ref_id=0 WHERE b.picklist_id=p.picklist_id),
                picklist_credit=(SELECT COALESCE(SUM(l.remaining_amount),0) FROM bill b JOIN bill_ledger l ON l.bill_id=b.bill_id AND l.ref_id=0 WHERE b.picklist_id=p.picklist_id),
                picklist_sceheme_amount=(SELECT COALESCE(SUM(l.bill_amount-l.recived_amount-l.return_amount-l.remaining_amount),0) FROM bill b JOIN bill_ledger l ON l.bill_id=b.bill_id AND l.ref_id=0 WHERE b.picklist_id=p.picklist_id)
                WHERE p.picklist_id=?");
            $totalsStmt->bind_param('s', $picklist_id);
            $totalsStmt->execute();
            $totalsStmt->close();
            $conn->commit();
            echo json_encode(['success' => true, 'message' => 'DSR amounts and totals were updated.']);
        } catch (Throwable $error) {
            try {
                $conn->rollback();
            } catch (Throwable $rollbackError) {
                logDatabaseFailure('update_dsr_amounts', 'Rollback failed: ' . $rollbackError->getMessage());
            }
            logDatabaseFailure('update_dsr_amounts', $error->getMessage());
            http_response_code($error instanceof InvalidArgumentException ? 400 : 409);
            echo json_encode([
                'success' => false,
                'message' => $error instanceof InvalidArgumentException || $error instanceof RuntimeException
                    ? $error->getMessage()
                    : 'The DSR update failed. No changes were applied.',
            ], JSON_UNESCAPED_SLASHES);
        }
        break;
    case 'save_rec_details':
        $details_json = $_POST['recovery_sheet_detail'] ?? '';
        $customer_issued_bills = json_decode($details_json, true);
        if (!is_array($customer_issued_bills) || empty($customer_issued_bills)) {
            if ($details_json === '') {
                $message = 'No recovery sheet bill details were received.';
            } elseif (json_last_error() !== JSON_ERROR_NONE) {
                $message = 'Invalid recovery sheet bill details JSON: ' . json_last_error_msg();
            } else {
                $message = 'The recovery sheet bill details contained no bills.';
            }
            echo recoverySheetResponse('save_rec_details', [
                'success' => false,
                'message' => $message,
            ], ['payload_length' => strlen($details_json)]);
            break;
        }

        $value_for_recovery_sheet = '';
        $value_for_bill_status = '';
        $my_recovery_sheet_id = filter_var($customer_issued_bills[0]['recovery_sheet_id'] ?? null, FILTER_VALIDATE_INT);
        if (!$my_recovery_sheet_id) {
            echo recoverySheetResponse('save_rec_details', [
                'success' => false,
                'message' => 'A valid recovery sheet ID is required.',
            ]);
            break;
        }
        foreach ($customer_issued_bills as $bill) {
            $bill_sheet_id = filter_var($bill['recovery_sheet_id'] ?? null, FILTER_VALIDATE_INT);
            $bill_id = isset($bill['bill_id']) ? (string) $bill['bill_id'] : '';
            $bill_amount = $bill['bill_amount'] ?? null;
            if ($bill_sheet_id !== $my_recovery_sheet_id || $bill_id === '' || !is_numeric($bill_amount) || !is_finite((float) $bill_amount) || (float) $bill_amount < 0) {
                echo recoverySheetResponse('save_rec_details', [
                    'success' => false,
                    'message' => 'A bill has a mismatched sheet ID or invalid bill details.',
                ]);
                break 2;
            }
            $bill_id = $conn->real_escape_string($bill_id);
            $value_for_recovery_sheet .= "('{$my_recovery_sheet_id}','{$bill_id}','{$bill_amount}',0),";
            $value_for_bill_status .= "'{$bill_id}',";
        }

        $value_for_recovery_sheet = rtrim($value_for_recovery_sheet, ',');
        $value_for_bill_status = rtrim($value_for_bill_status, ',') . ')';
        $query_for_recovery_sheet = "INSERT INTO `recovery_sheet_detail`(`recovery_sheet_id`, `recovery_sheet_bill_id`, `recovery_sheet_bill_amount`, `recovery_sheet_bill_recovered`) VALUES {$value_for_recovery_sheet}";
        $query_for_bill_status = "UPDATE `bill` SET `Bill_status`='ISSUED' WHERE `Bill_status`='INFILE' AND `bill_id` IN ({$value_for_bill_status}";
        $query_for_recovery_sheet_total = "UPDATE `recovery_sheet` SET `recovery_sheet_amount`=(SELECT SUM(recovery_sheet_bill_amount) FROM recovery_sheet_detail WHERE recovery_sheet_id='{$my_recovery_sheet_id}') WHERE recovery_id='{$my_recovery_sheet_id}' AND sheet_status='0' AND correction_status='ACTIVE'";

        try {
            $conn->begin_transaction();
            $sheet_stmt = $conn->prepare("SELECT recovery_id FROM recovery_sheet WHERE recovery_id=? AND sheet_status='0' AND correction_status='ACTIVE' FOR UPDATE");
            if (!$sheet_stmt) {
                throw new RuntimeException($conn->error ?: 'The recovery sheet could not be checked.');
            }
            $sheet_stmt->bind_param('i', $my_recovery_sheet_id);
            $sheet_stmt->execute();
            $sheet_stmt->store_result();
            if ($sheet_stmt->num_rows !== 1) {
                throw new RuntimeException('Only an open recovery sheet can receive bills.');
            }
            if (!create_update_delete($query_for_recovery_sheet)) {
                throw new RuntimeException($conn->error ?: 'The recovery sheet bill details could not be inserted.');
            }
            if (!create_update_delete($query_for_bill_status) || $conn->affected_rows !== count($customer_issued_bills)) {
                throw new RuntimeException($conn->error ?: 'One or more bills are no longer available to issue.');
            }
            if (!create_update_delete($query_for_recovery_sheet_total)) {
                throw new RuntimeException($conn->error ?: 'The recovery sheet total could not be updated.');
            }
            $conn->commit();
            $sheet_stmt->close();
            echo recoverySheetResponse('save_rec_details', true, [
                'recovery_sheet_id' => $my_recovery_sheet_id,
                'bill_count' => count($customer_issued_bills),
            ]);
        } catch (Throwable $error) {
            try {
                $conn->rollback();
            } catch (Throwable $rollbackError) {
                logDatabaseFailure('save_rec_details', 'Rollback failed: ' . $rollbackError->getMessage());
            }
            logDatabaseFailure('save_rec_details', $error->getMessage());
            http_response_code(500);
            echo recoverySheetResponse('save_rec_details', [
                'success' => false,
                'message' => 'The recovery sheet details could not be saved. No changes were applied.',
            ], ['recovery_sheet_id' => $my_recovery_sheet_id]);
        }
        break;
    case "get_rec_sheet":
        $query = "SELECT a.recovery_id AS ID, b.saleman_name AS Saleman, a.recovery_date AS Date, a.recovery_sheet_amount AS Amount, a.recovery_sheet_recovery AS Recoverd, a.sheet_status AS Status FROM recovery_sheet AS a LEFT JOIN salesman AS b ON a.recovery_sheet_saleman_id=b.saleman_id WHERE a.correction_status='ACTIVE'";
        $responce = read($query);
        echo $responce;
        break;
    case 'finalize_recovery_sheet':
        $rec_id = filter_var($_POST['recID'] ?? null, FILTER_VALIDATE_INT);
        $bill_payload = $_POST['customer_bills'] ?? '';
        $bills = json_decode($bill_payload, true);
        if (!is_array($bills)) {
            $bills = json_decode(urldecode($bill_payload), true);
        }
        if (!$rec_id || !is_array($bills) || empty($bills)) {
            echo recoverySheetResponse('finalize_recovery_sheet', [
                'success' => false,
                'message' => 'A valid recovery sheet and at least one bill are required.',
            ]);
            break;
        }

        try {
            $conn->begin_transaction();
            $sheet_stmt = $conn->prepare("SELECT recovery_sheet_saleman_id, sheet_status FROM recovery_sheet WHERE recovery_id=? AND correction_status='ACTIVE' FOR UPDATE");
            if (!$sheet_stmt) {
                throw new RuntimeException($conn->error);
            }
            $sheet_stmt->bind_param('i', $rec_id);
            $sheet_stmt->execute();
            $sheet_stmt->bind_result($saleman_id, $sheet_status);
            if (!$sheet_stmt->fetch() || $sheet_status !== '0') {
                throw new RuntimeException('This recovery sheet is missing or already processed.');
            }
            $sheet_stmt->close();

            $bill_stmt = $conn->prepare("SELECT d.recovery_sheet_bill_amount, b.bill_amount, b.cutomer_id FROM recovery_sheet_detail d JOIN bill b ON b.bill_id=d.recovery_sheet_bill_id WHERE d.recovery_sheet_id=? AND d.recovery_sheet_bill_id=? AND b.Bill_status='ISSUED' FOR UPDATE");
            $bill_update_stmt = $conn->prepare("UPDATE bill SET bill_amount=?, Bill_status=? WHERE bill_id=? AND Bill_status='ISSUED'");
            $detail_update_stmt = $conn->prepare("UPDATE recovery_sheet_detail SET recovery_sheet_bill_recovered=?, recovery_sheet_bill_returned=? WHERE recovery_sheet_id=? AND recovery_sheet_bill_id=?");
            $return_stmt = $conn->prepare("INSERT INTO bill_return (bill_id, ref_id, return_amount, return_date) VALUES (?, ?, ?, CURRENT_DATE)");
            $ledger_stmt = $conn->prepare("INSERT INTO bill_ledger (ledger_date, customer_id, ref_id, bill_id, bill_amount, recived_amount, return_amount, remaining_amount, bill_status) VALUES (CURRENT_DATE, ?, ?, ?, ?, ?, ?, ?, ?)");
            if (!$bill_stmt || !$bill_update_stmt || !$detail_update_stmt || !$return_stmt || !$ledger_stmt) {
                throw new RuntimeException($conn->error);
            }

            $seen_bill_ids = [];
            $bf_bills = [];
            $bf_total = 0;
            $total_recovered = 0;
            $total_returned = 0;
            $detail_count_stmt = $conn->prepare("SELECT COUNT(*) FROM recovery_sheet_detail WHERE recovery_sheet_id=?");
            if (!$detail_count_stmt) {
                throw new RuntimeException($conn->error);
            }
            $detail_count_stmt->bind_param('i', $rec_id);
            $detail_count_stmt->execute();
            $detail_count_stmt->bind_result($expected_bill_count);
            $detail_count_stmt->fetch();
            $detail_count_stmt->close();
            foreach ($bills as $bill) {
                if (!is_array($bill)) {
                    throw new RuntimeException('A bill entry is malformed.');
                }
                $bill_id = isset($bill['ID']) ? (string) $bill['ID'] : '';
                $recovered = $bill['Recovered'] ?? null;
                $returned = $bill['Returned'] ?? 0;
                $status = $bill['Status'] ?? '';
                if ($bill_id === '' || isset($seen_bill_ids[$bill_id]) || !is_numeric($recovered) || !is_numeric($returned) || !is_finite((float) $recovered) || !is_finite((float) $returned) || (float) $recovered < 0 || (float) $returned < 0 || floor((float) $recovered) !== (float) $recovered || floor((float) $returned) !== (float) $returned || !in_array($status, ['Nill', 'Return', 'BF'], true)) {
                    throw new RuntimeException('A bill has an invalid ID, amount, or status.');
                }
                $seen_bill_ids[$bill_id] = true;
                $recovered = (float) $recovered;
                $returned = (float) $returned;

                $bill_stmt->bind_param('is', $rec_id, $bill_id);
                $bill_stmt->execute();
                $bill_stmt->bind_result($sheet_amount, $current_amount, $customer_id);
                if (!$bill_stmt->fetch()) {
                    $bill_stmt->free_result();
                    throw new RuntimeException('A bill does not belong to this recovery sheet.');
                }
                $bill_stmt->free_result();
                $sheet_amount = (float) $sheet_amount;
                $current_amount = (float) $current_amount;
                if ($sheet_amount !== $current_amount || $recovered + $returned > $current_amount) {
                    throw new RuntimeException('A bill amount changed or exceeds its remaining balance.');
                }
                $remaining = $current_amount - $recovered - $returned;
                if ($status === 'Nill' && $remaining > 0) {
                    throw new RuntimeException('A bill with a remaining balance cannot be marked Nill.');
                }
                if (isset($bill['Remaining']) && (!is_numeric($bill['Remaining']) || abs((float) $bill['Remaining'] - $remaining) > 0.001)) {
                    throw new RuntimeException('A bill balance changed. Reload the recovery sheet and try again.');
                }

                $bill_status = $status === 'Nill' ? 'NILL' : ($status === 'BF' ? 'ISSUED' : 'INFILE');
                $bill_update_stmt->bind_param('dss', $remaining, $bill_status, $bill_id);
                if (!$bill_update_stmt->execute()) {
                    throw new RuntimeException('A bill could not be updated.');
                }
                $detail_update_stmt->bind_param('ddis', $recovered, $returned, $rec_id, $bill_id);
                if (!$detail_update_stmt->execute()) {
                    throw new RuntimeException('Recovery details could not be updated.');
                }

                if ($returned > 0) {
                    $return_stmt->bind_param('sid', $bill_id, $rec_id, $returned);
                    if (!$return_stmt->execute()) {
                        throw new RuntimeException('A bill return could not be logged.');
                    }
                }

                $ledger_status = $status === 'Nill' ? 'NILL' : ($status === 'BF' ? 'BF' : 'INFILE');
                $ledger_stmt->bind_param('sisdddds', $customer_id, $rec_id, $bill_id, $current_amount, $recovered, $returned, $remaining, $ledger_status);
                if (!$ledger_stmt->execute()) {
                    throw new RuntimeException('The bill ledger could not be saved.');
                }

                $total_recovered += $recovered;
                $total_returned += $returned;
                if ($status === 'BF') {
                    $bf_bills[] = [$bill_id, $remaining];
                    $bf_total += $remaining;
                }
            }
            if (count($seen_bill_ids) !== (int) $expected_bill_count) {
                throw new RuntimeException('The submitted bills do not match the recovery sheet. Reload and try again.');
            }

            $new_rec_id = null;
            if ($bf_bills) {
                $new_sheet_stmt = $conn->prepare("INSERT INTO recovery_sheet (recovery_date, recovery_sheet_saleman_id, recovery_sheet_amount, recovery_sheet_recovery, recovery_sheet_returned, sheet_status, recovery_sheet_source_id) VALUES (CURRENT_DATE, ?, ?, 0, 0, '0', ?)");
                $new_detail_stmt = $conn->prepare("INSERT INTO recovery_sheet_detail (recovery_sheet_id, recovery_sheet_bill_id, recovery_sheet_bill_amount, recovery_sheet_bill_recovered, recovery_sheet_bill_returned) VALUES (?, ?, ?, 0, 0)");
                if (!$new_sheet_stmt || !$new_detail_stmt) {
                    throw new RuntimeException($conn->error);
                }
                $new_sheet_stmt->bind_param('idi', $saleman_id, $bf_total, $rec_id);
                if (!$new_sheet_stmt->execute()) {
                    throw new RuntimeException('The forwarded recovery sheet could not be created.');
                }
                $new_rec_id = $conn->insert_id;
                foreach ($bf_bills as [$bill_id, $remaining]) {
                    $new_detail_stmt->bind_param('isd', $new_rec_id, $bill_id, $remaining);
                    if (!$new_detail_stmt->execute()) {
                        throw new RuntimeException('Forwarded bill details could not be saved.');
                    }
                }
            }

            $finalize_stmt = $conn->prepare("UPDATE recovery_sheet SET recovery_sheet_recovery=?, recovery_sheet_returned=?, sheet_status='1' WHERE recovery_id=? AND sheet_status='0'");
            if (!$finalize_stmt) {
                throw new RuntimeException($conn->error);
            }
            $finalize_stmt->bind_param('ddi', $total_recovered, $total_returned, $rec_id);
            if (!$finalize_stmt->execute() || $finalize_stmt->affected_rows !== 1) {
                throw new RuntimeException('The recovery sheet could not be finalized.');
            }

            $conn->commit();
            echo recoverySheetResponse('finalize_recovery_sheet', [
                'success' => true,
                'new_recovery_id' => $new_rec_id,
            ], ['recovery_sheet_id' => $rec_id, 'bill_count' => count($bills)]);
        } catch (Throwable $error) {
            $conn->rollback();
            echo recoverySheetResponse('finalize_recovery_sheet', [
                'success' => false,
                'message' => $error->getMessage() ?: 'Recovery sheet processing failed.',
            ], ['recovery_sheet_id' => $rec_id]);
        }
        break;
    case 'get_recovery_correction_sheets':
        $auth_error = '';
        if (!authorizeRecoveryCorrection($conn, false, $auth_error)) {
            echo json_encode(['success' => false, 'message' => $auth_error]);
            break;
        }
        $query = "SELECT r.recovery_id AS ID, r.recovery_date AS Date, s.saleman_name AS Saleman, r.recovery_sheet_amount AS Amount, r.sheet_status AS Status, r.correction_status AS CorrectionStatus, r.recovery_sheet_source_id AS SourceID FROM recovery_sheet r LEFT JOIN salesman s ON s.saleman_id=r.recovery_sheet_saleman_id ORDER BY r.recovery_id DESC";
        echo read($query);
        break;
    case 'get_recovery_correction_details':
        $auth_error = '';
        $rec_id = filter_var($_POST['recID'] ?? null, FILTER_VALIDATE_INT);
        if (!authorizeRecoveryCorrection($conn, false, $auth_error) || !$rec_id) {
            echo json_encode(['success' => false, 'message' => $auth_error ?: 'A valid recovery sheet is required.']);
            break;
        }
        $stmt = $conn->prepare("SELECT d.recovery_sheet_bill_id AS ID, c.customer_name AS Shop, d.recovery_sheet_bill_amount AS Amount, d.recovery_sheet_bill_recovered AS Recovered, COALESCE(d.recovery_sheet_bill_returned, 0) AS Returned, b.bill_amount AS Remaining, b.Bill_status AS BillStatus, COALESCE((SELECT l.bill_status FROM bill_ledger l WHERE l.ref_id=d.recovery_sheet_id AND l.bill_id=d.recovery_sheet_bill_id ORDER BY l.ledger_id DESC LIMIT 1), b.Bill_status) AS RecoveryStatus FROM recovery_sheet_detail d JOIN bill b ON b.bill_id=d.recovery_sheet_bill_id LEFT JOIN customer c ON c.customer_id=b.cutomer_id WHERE d.recovery_sheet_id=? ORDER BY d.recovery_detail_id");
        if (!$stmt) {
            echo json_encode(['success' => false, 'message' => 'Recovery details are unavailable.']);
            break;
        }
        $stmt->bind_param('i', $rec_id);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        echo json_encode($rows, JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
        break;
    case 'get_recovery_correction_audit':
        $auth_error = '';
        if (!authorizeRecoveryCorrection($conn, false, $auth_error)) {
            echo json_encode(['success' => false, 'message' => $auth_error]);
            break;
        }
        $result = $conn->query("SELECT correction_id AS ID, recovery_sheet_id AS SheetID, bill_id AS BillID, action_type AS Action, admin_user AS Admin, reason AS Reason, before_state AS BeforeState, after_state AS AfterState, created_at AS Created FROM recovery_sheet_correction ORDER BY correction_id DESC LIMIT 100");
        if (!$result) {
            echo json_encode(['success' => false, 'message' => 'Correction audit history is unavailable.']);
            break;
        }
        echo json_encode($result->fetch_all(MYSQLI_ASSOC), JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
        break;
    case 'correct_recovery_bill':
        $auth_error = '';
        if (!authorizeRecoveryCorrection($conn, true, $auth_error)) {
            echo json_encode(['success' => false, 'message' => $auth_error]);
            break;
        }
        $rec_id = filter_var($_POST['recID'] ?? null, FILTER_VALIDATE_INT);
        $bill_id = trim($_POST['billID'] ?? '');
        $reason = trim($_POST['reason'] ?? '');
        $recovered = $_POST['recovered'] ?? null;
        $returned = $_POST['returned'] ?? null;
        $status = $_POST['status'] ?? '';
        if (!$rec_id || $bill_id === '' || strlen($reason) < 8 || strlen($reason) > 500 || !is_numeric($recovered) || !is_numeric($returned) || !is_finite((float) $recovered) || !is_finite((float) $returned) || (float) $recovered < 0 || (float) $returned < 0 || floor((float) $recovered) !== (float) $recovered || floor((float) $returned) !== (float) $returned || !in_array($status, ['Nill', 'Return'], true)) {
            echo json_encode(['success' => false, 'message' => 'Enter valid whole-number amounts, a supported status, and a reason of at least 8 characters.']);
            break;
        }
        $recovered = (float) $recovered;
        $returned = (float) $returned;

        try {
            $conn->begin_transaction();
            $sheet_stmt = $conn->prepare("SELECT sheet_status FROM recovery_sheet WHERE recovery_id=? FOR UPDATE");
            $sheet_stmt->bind_param('i', $rec_id);
            $sheet_stmt->execute();
            $sheet_stmt->bind_result($sheet_status);
            if (!$sheet_stmt->fetch() || $sheet_status !== '1') {
                throw new RuntimeException('Only a processed recovery sheet can be corrected.');
            }
            $sheet_stmt->close();

            $detail_stmt = $conn->prepare("SELECT d.recovery_sheet_bill_amount, d.recovery_sheet_bill_recovered, COALESCE(d.recovery_sheet_bill_returned,0), b.bill_amount, b.cutomer_id, COALESCE((SELECT l.bill_status FROM bill_ledger l WHERE l.ref_id=d.recovery_sheet_id AND l.bill_id=d.recovery_sheet_bill_id ORDER BY l.ledger_id DESC LIMIT 1), b.Bill_status) FROM recovery_sheet_detail d JOIN bill b ON b.bill_id=d.recovery_sheet_bill_id WHERE d.recovery_sheet_id=? AND d.recovery_sheet_bill_id=? FOR UPDATE");
            $detail_stmt->bind_param('is', $rec_id, $bill_id);
            $detail_stmt->execute();
            $detail_stmt->bind_result($original_amount, $old_recovered, $old_returned, $current_amount, $customer_id, $recovery_status);
            if (!$detail_stmt->fetch()) {
                throw new RuntimeException('The bill does not belong to this recovery sheet.');
            }
            $detail_stmt->close();
            if ($recovery_status === 'BF') {
                throw new RuntimeException('A forwarded BF bill cannot be edited alone. Roll back the sheet while its forwarded sheet is still untouched.');
            }
            $later_activity = $conn->prepare("SELECT (SELECT COUNT(*) FROM bill_ledger WHERE bill_id=? AND ref_id>?) + (SELECT COUNT(*) FROM recovery_sheet_detail d JOIN recovery_sheet r ON r.recovery_id=d.recovery_sheet_id WHERE d.recovery_sheet_bill_id=? AND r.recovery_id>? AND (r.recovery_sheet_source_id IS NULL OR r.recovery_sheet_source_id<>?))");
            $later_activity->bind_param('sisii', $bill_id, $rec_id, $bill_id, $rec_id, $rec_id);
            $later_activity->execute();
            $later_activity->bind_result($later_count);
            $later_activity->fetch();
            $later_activity->close();
            if ((int) $later_count > 0) {
                throw new RuntimeException('This bill has later recovery activity. Correct the latest affected sheet first.');
            }

            $remaining = (float) $original_amount - $recovered - $returned;
            if ($remaining < 0 || ($status === 'Nill' && $remaining > 0)) {
                throw new RuntimeException('The amounts exceed the original bill or do not satisfy the selected Nill status.');
            }
            $before = ['amount' => (float) $original_amount, 'recovered' => (float) $old_recovered, 'returned' => (float) $old_returned, 'remaining' => (float) $current_amount, 'status' => $recovery_status];
            $after = ['amount' => (float) $original_amount, 'recovered' => $recovered, 'returned' => $returned, 'remaining' => $remaining, 'status' => $status];

            $update_detail = $conn->prepare("UPDATE recovery_sheet_detail SET recovery_sheet_bill_recovered=?, recovery_sheet_bill_returned=? WHERE recovery_sheet_id=? AND recovery_sheet_bill_id=?");
            $update_detail->bind_param('ddis', $recovered, $returned, $rec_id, $bill_id);
            if (!$update_detail->execute()) {
                throw new RuntimeException('The recovery detail could not be updated.');
            }
            $update_bill = $conn->prepare("UPDATE bill SET bill_amount=?, Bill_status=? WHERE bill_id=?");
            $bill_status = $status === 'Nill' ? 'NILL' : 'INFILE';
            $update_bill->bind_param('dss', $remaining, $bill_status, $bill_id);
            if (!$update_bill->execute()) {
                throw new RuntimeException('The bill balance could not be updated.');
            }

            $delete_return = $conn->prepare('DELETE FROM bill_return WHERE ref_id=? AND bill_id=?');
            $delete_return->bind_param('is', $rec_id, $bill_id);
            if (!$delete_return->execute()) {
                throw new RuntimeException('The old return record could not be replaced.');
            }
            if ($returned > 0) {
                $insert_return = $conn->prepare('INSERT INTO bill_return (bill_id, ref_id, return_amount, return_date) VALUES (?, ?, ?, CURRENT_DATE)');
                $insert_return->bind_param('sid', $bill_id, $rec_id, $returned);
                if (!$insert_return->execute()) {
                    throw new RuntimeException('The corrected return could not be recorded.');
                }
            }

            $ledger_status = $status === 'Nill' ? 'NILL' : 'INFILE';
            $find_ledger = $conn->prepare("SELECT ledger_id FROM bill_ledger WHERE ref_id=? AND bill_id=? ORDER BY ledger_id DESC LIMIT 1 FOR UPDATE");
            $find_ledger->bind_param('is', $rec_id, $bill_id);
            $find_ledger->execute();
            $find_ledger->bind_result($ledger_id);
            $has_ledger = $find_ledger->fetch();
            $find_ledger->close();
            if ($has_ledger) {
                $update_ledger = $conn->prepare("UPDATE bill_ledger SET bill_amount=?, recived_amount=?, return_amount=?, remaining_amount=?, bill_status=? WHERE ledger_id=?");
                $update_ledger->bind_param('ddddsi', $original_amount, $recovered, $returned, $remaining, $ledger_status, $ledger_id);
                if (!$update_ledger->execute()) {
                    throw new RuntimeException('The ledger entry could not be corrected.');
                }
            } else {
                $insert_ledger = $conn->prepare("INSERT INTO bill_ledger (ledger_date, customer_id, ref_id, bill_id, bill_amount, recived_amount, return_amount, remaining_amount, bill_status) VALUES (CURRENT_DATE, ?, ?, ?, ?, ?, ?, ?, ?)");
                $insert_ledger->bind_param('sisdddds', $customer_id, $rec_id, $bill_id, $original_amount, $recovered, $returned, $remaining, $ledger_status);
                if (!$insert_ledger->execute()) {
                    throw new RuntimeException('The replacement ledger entry could not be saved.');
                }
            }

            $totals = $conn->prepare("UPDATE recovery_sheet SET recovery_sheet_recovery=(SELECT COALESCE(SUM(recovery_sheet_bill_recovered),0) FROM recovery_sheet_detail WHERE recovery_sheet_id=?), recovery_sheet_returned=(SELECT COALESCE(SUM(recovery_sheet_bill_returned),0) FROM recovery_sheet_detail WHERE recovery_sheet_id=?) WHERE recovery_id=?");
            $totals->bind_param('iii', $rec_id, $rec_id, $rec_id);
            if (!$totals->execute()) {
                throw new RuntimeException('Recovery sheet totals could not be updated.');
            }
            insertRecoveryCorrectionAudit($conn, $rec_id, $bill_id, 'EDIT_BILL', (string) $_SESSION['admin'], $reason, $before, $after);
            $conn->commit();
            echo json_encode(['success' => true, 'message' => 'Bill recovery corrected.']);
        } catch (Throwable $error) {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => $error->getMessage()]);
        }
        break;
    case 'rollback_recovery_sheet':
        $auth_error = '';
        if (!authorizeRecoveryCorrection($conn, true, $auth_error)) {
            echo json_encode(['success' => false, 'message' => $auth_error]);
            break;
        }
        $rec_id = filter_var($_POST['recID'] ?? null, FILTER_VALIDATE_INT);
        $reason = trim($_POST['reason'] ?? '');
        if (!$rec_id || strlen($reason) < 8 || strlen($reason) > 500) {
            echo json_encode(['success' => false, 'message' => 'Choose a sheet and enter a reason of at least 8 characters.']);
            break;
        }

        try {
            $conn->begin_transaction();
            $sheet_stmt = $conn->prepare("SELECT sheet_status, correction_status FROM recovery_sheet WHERE recovery_id=? FOR UPDATE");
            $sheet_stmt->bind_param('i', $rec_id);
            $sheet_stmt->execute();
            $sheet_stmt->bind_result($sheet_status, $correction_status);
            if (!$sheet_stmt->fetch() || $sheet_status !== '1' || $correction_status !== 'ACTIVE') {
                throw new RuntimeException('Only an active, processed recovery sheet can be rolled back.');
            }
            $sheet_stmt->close();

            $child_stmt = $conn->prepare("SELECT recovery_id, sheet_status, correction_status FROM recovery_sheet WHERE recovery_sheet_source_id=? FOR UPDATE");
            $child_stmt->bind_param('i', $rec_id);
            $child_stmt->execute();
            $children = $child_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $child_stmt->close();
            if (count($children) > 1) {
                throw new RuntimeException('More than one forwarded sheet is linked to this sheet; manual review is required.');
            }
            $child_id = null;
            if ($children) {
                $child = $children[0];
                $child_id = (int) $child['recovery_id'];
                if ($child['correction_status'] !== 'ROLLED_BACK' && $child['sheet_status'] !== '0') {
                    throw new RuntimeException('Roll back the processed forwarded sheet first, then retry this sheet.');
                }
                if ($child['correction_status'] === 'ACTIVE') {
                    $child_count_stmt = $conn->prepare('SELECT COUNT(*) FROM recovery_sheet_detail WHERE recovery_sheet_id=?');
                    $child_count_stmt->bind_param('i', $child_id);
                    $child_count_stmt->execute();
                    $child_count_stmt->bind_result($child_count);
                    $child_count_stmt->fetch();
                    $child_count_stmt->close();
                    $bf_count_stmt = $conn->prepare("SELECT COUNT(*) FROM bill_ledger WHERE ref_id=? AND bill_status='BF'");
                    $bf_count_stmt->bind_param('i', $rec_id);
                    $bf_count_stmt->execute();
                    $bf_count_stmt->bind_result($bf_count);
                    $bf_count_stmt->fetch();
                    $bf_count_stmt->close();
                    if ((int) $child_count !== (int) $bf_count) {
                        throw new RuntimeException('The forwarded sheet contains other bills; separate it before rollback.');
                    }
                }
            }

            $details_stmt = $conn->prepare("SELECT d.recovery_sheet_bill_id, d.recovery_sheet_bill_amount, d.recovery_sheet_bill_recovered, COALESCE(d.recovery_sheet_bill_returned,0), b.bill_amount FROM recovery_sheet_detail d JOIN bill b ON b.bill_id=d.recovery_sheet_bill_id WHERE d.recovery_sheet_id=? FOR UPDATE");
            $details_stmt->bind_param('i', $rec_id);
            $details_stmt->execute();
            $details = $details_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $details_stmt->close();
            if (!$details) {
                throw new RuntimeException('The recovery sheet has no bill details to roll back.');
            }

            $update_bill = $conn->prepare("UPDATE bill SET bill_amount=?, Bill_status='INFILE' WHERE bill_id=?");
            $clear_detail = $conn->prepare('UPDATE recovery_sheet_detail SET recovery_sheet_bill_recovered=0, recovery_sheet_bill_returned=0 WHERE recovery_sheet_id=? AND recovery_sheet_bill_id=?');
            $beforeState = [];
            foreach ($details as $detail) {
                $bill_id = (string) $detail['recovery_sheet_bill_id'];
                $later_stmt = $conn->prepare("SELECT (SELECT COUNT(*) FROM bill_ledger WHERE bill_id=? AND ref_id>?) + (SELECT COUNT(*) FROM recovery_sheet_detail d JOIN recovery_sheet r ON r.recovery_id=d.recovery_sheet_id WHERE d.recovery_sheet_bill_id=? AND r.recovery_id>? AND COALESCE(r.recovery_sheet_source_id,0)<>? AND r.correction_status='ACTIVE')");
                $later_stmt->bind_param('sisii', $bill_id, $rec_id, $bill_id, $rec_id, $rec_id);
                $later_stmt->execute();
                $later_stmt->bind_result($later_count);
                $later_stmt->fetch();
                $later_stmt->close();
                if ((int) $later_count > 0) {
                    throw new RuntimeException('Bill ' . $bill_id . ' has later recovery activity; roll back newer sheets first.');
                }

                $original_amount = (float) $detail['recovery_sheet_bill_amount'];
                $beforeState[] = [
                    'bill_id' => $bill_id,
                    'amount' => $original_amount,
                    'recovered' => (float) $detail['recovery_sheet_bill_recovered'],
                    'returned' => (float) $detail['recovery_sheet_bill_returned'],
                    'current_amount' => (float) $detail['bill_amount'],
                ];
                $update_bill->bind_param('ds', $original_amount, $bill_id);
                if (!$update_bill->execute()) {
                    throw new RuntimeException('Bill ' . $bill_id . ' could not be restored.');
                }
                $clear_detail->bind_param('is', $rec_id, $bill_id);
                if (!$clear_detail->execute()) {
                    throw new RuntimeException('Bill ' . $bill_id . ' recovery details could not be cleared.');
                }
            }

            $delete_returns = $conn->prepare('DELETE FROM bill_return WHERE ref_id=?');
            $delete_returns->bind_param('i', $rec_id);
            if (!$delete_returns->execute()) {
                throw new RuntimeException('Return records could not be rolled back.');
            }
            $delete_ledger = $conn->prepare('DELETE FROM bill_ledger WHERE ref_id=?');
            $delete_ledger->bind_param('i', $rec_id);
            if (!$delete_ledger->execute()) {
                throw new RuntimeException('Ledger entries could not be rolled back.');
            }
            $mark_sheet = $conn->prepare("UPDATE recovery_sheet SET recovery_sheet_recovery=0, recovery_sheet_returned=0, correction_status='ROLLED_BACK' WHERE recovery_id=? AND correction_status='ACTIVE'");
            $mark_sheet->bind_param('i', $rec_id);
            if (!$mark_sheet->execute() || $mark_sheet->affected_rows !== 1) {
                throw new RuntimeException('The recovery sheet could not be marked as rolled back.');
            }

            if ($child_id !== null && $children[0]['correction_status'] === 'ACTIVE') {
                $clear_child_details = $conn->prepare('UPDATE recovery_sheet_detail SET recovery_sheet_bill_recovered=0, recovery_sheet_bill_returned=0 WHERE recovery_sheet_id=?');
                $clear_child_details->bind_param('i', $child_id);
                if (!$clear_child_details->execute()) {
                    throw new RuntimeException('The forwarded sheet details could not be rolled back.');
                }
                $mark_child = $conn->prepare("UPDATE recovery_sheet SET recovery_sheet_recovery=0, recovery_sheet_returned=0, sheet_status='1', correction_status='ROLLED_BACK' WHERE recovery_id=? AND correction_status='ACTIVE'");
                $mark_child->bind_param('i', $child_id);
                if (!$mark_child->execute()) {
                    throw new RuntimeException('The forwarded sheet could not be marked as rolled back.');
                }
                insertRecoveryCorrectionAudit($conn, $child_id, null, 'ROLLBACK_SHEET', (string) $_SESSION['admin'], $reason, ['source_sheet_id' => $rec_id, 'status' => '0'], ['status' => 'ROLLED_BACK']);
            }

            insertRecoveryCorrectionAudit($conn, $rec_id, null, 'ROLLBACK_SHEET', (string) $_SESSION['admin'], $reason, $beforeState, ['status' => 'ROLLED_BACK', 'forwarded_sheet_id' => $child_id]);
            $conn->commit();
            echo json_encode(['success' => true, 'message' => 'Recovery sheet rolled back. Bills were restored and the audit record was saved.']);
        } catch (Throwable $error) {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => $error->getMessage()]);
        }
        break;
    case 'process_nill_bills':
    case 'process_return_bills':
    case 'process_BF_bills':
    case 'update_recovery_sheet_header':
    case 'save_bill_ledger':
        echo json_encode([
            'success' => false,
            'message' => 'Use the atomic recovery-sheet finalization request.',
        ]);
        break;
    case 'get_saleman_from_recovery_sheet':
        $recId = postData('recID');
        $query = "Select recovery_sheet_saleman_id from recovery_sheet where recovery_id=" . $recId;
        echo read($query);
        break;
    case 'get_credit_by_saleman':
        // Return salesman id, name and outstanding credit (sum of remaining amounts)
        $query = "SELECT sl.saleman_id, sl.saleman_name, IFNULL(SUM(b.bill_amount),0) AS Credit
                  FROM salesman sl
                  LEFT JOIN routes r ON r.saleman_id = sl.saleman_id
                  LEFT JOIN customer c ON c.customer_route = r.route_id
                  LEFT JOIN bill b ON b.cutomer_id = c.customer_id AND b.Bill_status IN ('INFILE', 'ISSUED')
                  GROUP BY sl.saleman_id, sl.saleman_name;";
        $responce = read($query);
        echo $responce;
        break;
    case 'get_full_credit_report':
        $saleman_id = postData('saleman_id');
        if(empty($saleman_id)){
            echo json_encode(['error'=>'saleman_id missing']);
            break;
        }
        $query = "SELECT COALESCE(s.sector_name,'Unknown') AS area, c.customer_name AS shop_name, b.bill_id, b.bill_date, b.bill_amount, b.bill_amount AS remaining_amount, IF(DATEDIFF(CURRENT_DATE, b.bill_date) < 0, DATEDIFF(CURRENT_DATE, STR_TO_DATE(CONCAT(YEAR(b.bill_date), '-', DAY(b.bill_date), '-', MONTH(b.bill_date)), '%Y-%m-%d')), DATEDIFF(CURRENT_DATE, b.bill_date)) AS bill_age
                  FROM bill b
                  LEFT JOIN customer c ON b.cutomer_id = c.customer_id
                  LEFT JOIN routes r ON c.customer_route = r.route_id
                  LEFT JOIN sector s ON r.sector_id = s.sector_id
                  LEFT JOIN picklist p ON b.picklist_id = p.picklist_id
                  WHERE p.picklist_saleman = '{$saleman_id}' AND b.bill_amount > 0 AND b.Bill_status IN ('INFILE', 'ISSUED')
                  ORDER BY s.sector_name, c.customer_name, b.bill_date DESC;";
        $responce = read($query);
        echo $responce;
        break;
    case 'today_sales':
            $query="SELECT a.picklist_id,b.saleman_name,a.picklist_amount, a.picklist_credit,a.picklist_sceheme_amount,a.picklist_return,a.picklist_recovery from picklist as a join salesman as b on a.picklist_saleman=b.saleman_id where a.picklist_date_processed=CURRENT_DATE;";
            $responce=read($query);
            echo $responce;
    break;
    case 'picklist_report':
        $query="SELECT 
        pl.picklist_id,
        sl.saleman_name,
        pl.picklist_date,
        pl.picklist_date_processed AS 'Processed Date',
        pl.picklist_amount AS 'Gross Amount',
        pl.picklist_recovery AS 'Recovered at DSR',
        pl.picklist_return AS 'Returned at DSR',
        pl.picklist_sceheme_amount AS 'Scheme / Discount',
        pl.picklist_credit AS 'Credit Amount',
        IFNULL(ledger_totals.recovered_amount, 0) AS 'Recovery Since DSR',
        IFNULL(ledger_totals.returned_amount, 0) AS 'Return Since DSR',
        IFNULL(SUM(bill.bill_amount), 0) AS 'Remaining Amount',
        CASE
            WHEN pl.picklist_credit > 0
            THEN IFNULL(ledger_totals.recovered_amount, 0) * 100 / pl.picklist_credit
            ELSE 0
        END AS 'Recovery %'
    FROM `picklist` AS pl
    JOIN `salesman` AS sl ON pl.picklist_saleman = sl.saleman_id
    LEFT JOIN `bill` AS bill ON pl.picklist_id = bill.picklist_id
    LEFT JOIN (
        SELECT b.picklist_id,
             SUM(CASE WHEN l.ref_id > 0 THEN l.recived_amount ELSE 0 END) AS recovered_amount,
             SUM(CASE WHEN l.ref_id > 0 THEN l.return_amount ELSE 0 END) AS returned_amount
        FROM `bill` AS b
        JOIN `bill_ledger` AS l ON l.bill_id = b.bill_id
        GROUP BY b.picklist_id
    ) AS ledger_totals ON ledger_totals.picklist_id = pl.picklist_id
    GROUP BY pl.picklist_id, sl.saleman_name, pl.picklist_date, pl.picklist_date_processed,
             pl.picklist_amount, pl.picklist_recovery, pl.picklist_return, pl.picklist_credit,
             pl.picklist_sceheme_amount, ledger_totals.recovered_amount, ledger_totals.returned_amount
    ORDER BY pl.picklist_date DESC;
    ";
    $responce=read($query);
    echo $responce;
        break;
    case 'get_bills_for_picklist':
        $picklist_id=postData('picklist_id');
        $query="SELECT a.bill_id,a.cutomer_id,b.customer_name,a.bill_date,a.bill_amount,a.Bill_status FROM `bill` as a left join `customer` as b on a.cutomer_id=b.customer_id where a.picklist_id='{$picklist_id}';";
        //echo $query;
        $responce=read($query);
        echo $responce;
        break;
    case 'get_bills_by_route':
        $saleman_id = postData('saleman_id');
        $day = postData('day');
        $filter_saleman_id = postData('filter_saleman_id');
        
        $day_filter = !empty($day) ? " AND r.day='{$day}'" : "";
        $route_salesman_target = !empty($filter_saleman_id) ? $filter_saleman_id : $saleman_id;

        // Optimized query: Fetch bills based on the route relation rather than redundant sector joins
        $query = "SELECT DISTINCT a.bill_id, b.customer_name, e.saleman_name, e.sector_name, a.bill_date, a.bill_amount 
                  FROM bill as a 
                  JOIN customer as b ON a.cutomer_id = b.customer_id 
                  JOIN route_saleman_relation as e ON b.customer_route = e.route_id 
                  JOIN routes as r ON r.route_id = e.route_id
                  WHERE r.saleman_id = '{$route_salesman_target}' 
                  {$day_filter} 
                  AND a.Bill_status = 'INFILE'
                  AND a.bill_amount > 0";
        
        // Optional filter: Only show bills that belong to a specific salesman's picklist
        if (!empty($filter_saleman_id)) {
            $query .= " AND EXISTS (SELECT 1 FROM picklist p WHERE p.picklist_id = a.picklist_id AND p.picklist_saleman = '{$filter_saleman_id}')";
        }
        
        $responce = read($query);
        echo $responce;
        break;
    case 'get_dashboard_analytics':
        $response = [];

        // 1. Key Metrics
        // Total Outstanding
        $q1 = "SELECT IFNULL(SUM(b.bill_amount),0) as total FROM bill b WHERE b.Bill_status IN ('INFILE', 'ISSUED')";
        $r1 = json_decode(read($q1), true);
        $response['outstanding'] = $r1[0]['total'] ?? 0;

        // Sales This Month
        $q2 = "SELECT IFNULL(SUM(picklist_credit),0) as total FROM picklist WHERE picklist_date >= DATE_FORMAT(CURRENT_DATE, '%Y-%m-01') AND picklist_date < DATE_ADD(CURRENT_DATE, INTERVAL 1 DAY)";
        $r2 = json_decode(read($q2), true);
        $response['sales_month'] = $r2[0]['total'] ?? 0;

        // Recovery This Month (Using recovery_sheet for actual collections)
        $q3 = "SELECT IFNULL(SUM(recovery_sheet_recovery),0) as total FROM recovery_sheet WHERE recovery_date >= DATE_FORMAT(CURRENT_DATE, '%Y-%m-01') AND recovery_date < DATE_ADD(CURRENT_DATE, INTERVAL 1 DAY)";
        $r3 = json_decode(read($q3), true);
        $response['recovery_month'] = $r3[0]['total'] ?? 0;

        // 2. Trends (Last 30 Days)
        // Service should merge these in PHP or JS, but sending separate arrays is easier
        $q4 = "SELECT picklist_date as date, SUM(picklist_credit) as amount FROM picklist WHERE picklist_date >= DATE_SUB(CURRENT_DATE, INTERVAL 29 DAY) AND picklist_date < DATE_ADD(CURRENT_DATE, INTERVAL 1 DAY) GROUP BY picklist_date ORDER BY picklist_date ASC";
        $response['sales_trend'] = json_decode(read($q4), true);

        $q5 = "SELECT recovery_date as date, SUM(recovery_sheet_recovery) as amount FROM recovery_sheet WHERE recovery_date >= DATE_SUB(CURRENT_DATE, INTERVAL 29 DAY) AND recovery_date < DATE_ADD(CURRENT_DATE, INTERVAL 1 DAY) GROUP BY recovery_date ORDER BY recovery_date ASC";
        $response['recovery_trend'] = json_decode(read($q5), true);

        $q6 = "SELECT return_events.date, SUM(return_events.amount) AS amount
               FROM (
                   SELECT picklist_date AS date, SUM(picklist_return) AS amount
                   FROM picklist
                   WHERE picklist_date >= DATE_SUB(CURRENT_DATE, INTERVAL 29 DAY)
                     AND picklist_date < DATE_ADD(CURRENT_DATE, INTERVAL 1 DAY)
                   GROUP BY picklist_date
                   UNION ALL
                   SELECT recovery_date AS date, SUM(recovery_sheet_returned) AS amount
                   FROM recovery_sheet
                   WHERE recovery_date >= DATE_SUB(CURRENT_DATE, INTERVAL 29 DAY)
                     AND recovery_date < DATE_ADD(CURRENT_DATE, INTERVAL 1 DAY)
                   GROUP BY recovery_date
               ) AS return_events
               GROUP BY return_events.date
               ORDER BY return_events.date ASC";
        $response['return_trend'] = json_decode(read($q6), true);

        // 3. Top 5 Salesmen by recovery this month
        $q6 = "SELECT s.saleman_name as name, SUM(rs.recovery_sheet_recovery) as total 
               FROM recovery_sheet rs 
               JOIN salesman s ON rs.recovery_sheet_saleman_id = s.saleman_id 
               WHERE rs.recovery_date >= DATE_FORMAT(CURRENT_DATE, '%Y-%m-01')
                 AND rs.recovery_date < DATE_ADD(CURRENT_DATE, INTERVAL 1 DAY)
               GROUP BY rs.recovery_sheet_saleman_id 
               ORDER BY total DESC LIMIT 5";
        $response['top_salesmen'] = json_decode(read($q6), true);

        // 4. Sector Performance (Dead Zones - By Outstanding)
        $q7 = "SELECT s.sector_name, SUM(b.bill_amount) as outstanding
               FROM bill b 
               JOIN customer c ON b.cutomer_id = c.customer_id
               JOIN routes r ON c.customer_route = r.route_id
               JOIN sector s ON r.sector_id = s.sector_id
               WHERE b.Bill_status IN ('INFILE', 'ISSUED')
               GROUP BY s.sector_id, s.sector_name
               ORDER BY outstanding DESC LIMIT 5";
        $response['dead_zones'] = json_decode(read($q7), true);

        // 5. Top Defaulters: oldest outstanding bill first, then highest balance
        $q8 = "SELECT c.customer_name, SUM(b.bill_amount) as outstanding, s.sector_name, MIN(b.bill_date) as oldest_bill
               FROM bill b
               JOIN customer c ON b.cutomer_id = c.customer_id
               JOIN routes r ON c.customer_route = r.route_id
               JOIN sector s ON r.sector_id = s.sector_id
               WHERE b.Bill_status IN ('INFILE', 'ISSUED')
               GROUP BY c.customer_id, c.customer_name, s.sector_name
               ORDER BY oldest_bill IS NULL ASC, oldest_bill ASC, outstanding DESC LIMIT 5";
        $response['top_defaulters'] = json_decode(read($q8), true);

        // 6. Separate monthly credit sales and recovery totals by salesman
        $q9 = "SELECT s.saleman_name, 
                      IFNULL(SUM(p.picklist_credit),0) as sales,
                      (SELECT IFNULL(SUM(rs.recovery_sheet_recovery),0) 
                       FROM recovery_sheet rs 
                       WHERE rs.recovery_sheet_saleman_id = s.saleman_id 
                       AND rs.recovery_date >= DATE_FORMAT(CURRENT_DATE, '%Y-%m-01')
                       AND rs.recovery_date < DATE_ADD(CURRENT_DATE, INTERVAL 1 DAY)) as recovery
               FROM salesman s
               LEFT JOIN picklist p ON p.picklist_saleman = s.saleman_id 
                               AND p.picklist_date >= DATE_FORMAT(CURRENT_DATE, '%Y-%m-01')
                               AND p.picklist_date < DATE_ADD(CURRENT_DATE, INTERVAL 1 DAY)
               GROUP BY s.saleman_id";
        $salesman_stats = json_decode(read($q9), true);
        $salesman_monthly_totals = [];
        foreach($salesman_stats as $stat) {
            $salesman_monthly_totals[] = [
                'name' => $stat['saleman_name'],
                'sales' => (float) $stat['sales'],
                'recovery' => (float) $stat['recovery'],
            ];
        }
        $response['salesman_monthly_totals'] = $salesman_monthly_totals;

        // 7. Debt Aging Analysis
        $q10 = "SELECT 
                    CASE 
                        WHEN DATEDIFF(NOW(), bill_date) <= 30 THEN '0-30 Days'
                        WHEN DATEDIFF(NOW(), bill_date) <= 60 THEN '31-60 Days'
                        WHEN DATEDIFF(NOW(), bill_date) <= 90 THEN '61-90 Days'
                        ELSE '90+ Days'
                    END as age_bucket,
                    SUM(b.bill_amount) as total_outstanding
                FROM bill b
                WHERE b.Bill_status IN ('INFILE', 'ISSUED')
                GROUP BY age_bucket";
        $response['aging_analysis'] = json_decode(read($q10), true);

        // 8. At-Risk Customers: last outstanding bill is older than 60 days
        $q11 = "SELECT c.customer_name,
                   COALESCE(s.saleman_name, 'Unassigned') as salesman_name,
                       SUM(b.bill_amount) as outstanding,
                       MAX(b.bill_date) as last_bill
                FROM bill b
                JOIN customer c ON b.cutomer_id = c.customer_id
            LEFT JOIN routes r ON c.customer_route = r.route_id
            LEFT JOIN salesman s ON r.saleman_id = s.saleman_id
                WHERE b.Bill_status IN ('INFILE', 'ISSUED')
            GROUP BY c.customer_id, c.customer_name, s.saleman_name
                HAVING last_bill < DATE_SUB(NOW(), INTERVAL 60 DAY)
                ORDER BY outstanding DESC LIMIT 10";
        $response['at_risk_customers'] = json_decode(read($q11), true);



        echo json_encode($response);
        break;
        
    case 'get_backup_settings':
        include_once('backup_logic.php');
        $settings = get_backup_settings();
        echo json_encode($settings);
        break;

    case 'update_backup_settings':
        include_once('backup_logic.php');
        $path = postData('backup_path');
        $auto = postData('auto_backup');
        $frequency = postData('backup_frequency_hours');
        
        $result = update_backup_settings($path, $auto, $frequency);
        echo json_encode(['success' => $result]);
        break;

    case 'execute_manual_backup':
        include_once('backup_logic.php');
        $result = perform_backup(true);
        echo json_encode($result);
        break;
        
    case 'check_auto_backup':
        include_once('backup_logic.php');
        $result = check_and_run_auto_backup();
        echo json_encode($result);
        break;

    default:
        echo 'No function Found';
        break;
}

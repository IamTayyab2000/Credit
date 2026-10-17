<?php
include_once(__DIR__ . '/task_activity_logger.php');
include_once(__DIR__ . '/condb.php');
$conn=$conn;

function logDatabaseFailure($operation, $message)
{
    $entry = json_encode([
        'timestamp' => date(DATE_ATOM),
        'operation' => $operation,
        'message' => $message,
    ], JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
    $logPath = __DIR__ . '/../../logs/database_errors.log';

    if ($entry === false || @file_put_contents($logPath, $entry . PHP_EOL, FILE_APPEND | LOCK_EX) === false) {
        error_log('[Database] ' . ($entry ?: $operation . ': unable to encode failure'));
    }
}

function markDatabaseRequestFailed($operation, $message)
{
    if (isset($GLOBALS['database_request_transaction'])) {
        $GLOBALS['database_request_failed'] = true;
        if (empty($GLOBALS['database_request_error'])) {
            $GLOBALS['database_request_error'] = $message;
        }
    }
    logDatabaseFailure($operation, $message);
}

function beginDatabaseRequestTransaction(mysqli $connection, $operation)
{
    $connection->begin_transaction();
    $GLOBALS['database_request_transaction'] = $connection;
    $GLOBALS['database_request_failed'] = false;
    $GLOBALS['database_request_error'] = '';
    $GLOBALS['database_request_operation'] = $operation;

    register_shutdown_function(static function () use ($connection, $operation) {
        $error = error_get_last();
        $fatalTypes = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR];
        if ($error && in_array($error['type'], $fatalTypes, true)
            && isset($GLOBALS['database_request_transaction'])
            && $GLOBALS['database_request_transaction'] === $connection) {
            try {
                $connection->rollback();
            } catch (Throwable $rollbackError) {
                logDatabaseFailure($operation, 'Rollback failed after a fatal error: ' . $rollbackError->getMessage());
            }
            unset($GLOBALS['database_request_transaction']);
            $GLOBALS['database_request_failed'] = true;
            logDatabaseFailure($operation, $error['message']);
        }
    });

    ob_start(static function ($responseBody) use ($connection, $operation) {
        $decoded = json_decode($responseBody, true);
        $httpStatus = http_response_code();
        $failed = !empty($GLOBALS['database_request_failed'])
            || (is_int($httpStatus) && $httpStatus >= 400)
            || trim($responseBody) === '';

        if (is_array($decoded)) {
            $failed = $failed
                || (array_key_exists('success', $decoded) && in_array($decoded['success'], [false, 0, '0'], true))
                || (array_key_exists('status', $decoded) && in_array($decoded['status'], [false, 0, '0', 'error'], true))
                || !empty($decoded['error'])
                || (isset($decoded['failed']) && (int) $decoded['failed'] > 0)
                || !empty($decoded['errors']);
        } elseif (trim($responseBody) === '0'
            || (!preg_match('/^\d+$/', trim($responseBody))
            && !in_array(trim($responseBody), ['true'], true))) {
            $failed = true;
        }

        if (!isset($GLOBALS['database_request_transaction'])
            || $GLOBALS['database_request_transaction'] !== $connection) {
            return $responseBody;
        }

        if ($failed) {
            try {
                $connection->rollback();
            } catch (Throwable $rollbackError) {
                logDatabaseFailure($operation, 'Rollback failed: ' . $rollbackError->getMessage());
            }
            unset($GLOBALS['database_request_transaction']);

            if (!empty($GLOBALS['database_request_error'])) {
                http_response_code(500);
                return json_encode([
                    'success' => false,
                    'message' => 'The task failed. No database changes were saved.',
                ], JSON_UNESCAPED_SLASHES);
            }
            return $responseBody;
        }

        try {
            $connection->commit();
            unset($GLOBALS['database_request_transaction']);
            return $responseBody;
        } catch (Throwable $commitError) {
            try {
                $connection->rollback();
            } catch (Throwable $rollbackError) {
                logDatabaseFailure($operation, 'Rollback failed after commit error: ' . $rollbackError->getMessage());
            }
            unset($GLOBALS['database_request_transaction']);
            logDatabaseFailure($operation, 'Commit failed: ' . $commitError->getMessage());
            http_response_code(500);
            return json_encode([
                'success' => false,
                'message' => 'The task could not be saved. No database changes were committed.',
            ], JSON_UNESCAPED_SLASHES);
        }
    });
}

function auth($username,$password,$tableName){
    $username=mysqli_escape_string($GLOBALS['conn'],$username);
    $password=mysqli_escape_string($GLOBALS['conn'],$password);
    $tableName=mysqli_escape_string($GLOBALS['conn'],$tableName);
    $query="SELECT COUNT(*) AS 'Auth'
    FROM (SELECT admin_id FROM {$tableName} WHERE admin_name = '{$username}' AND admin_password = '$password') AS SubqueryAlias;";
    $result=json_decode(read($query));
    return $result[0]->Auth ?? 'not fount';
}
function create_update_delete($query){
    try {
        $result = mysqli_query($GLOBALS['conn'], $query);
    } catch (mysqli_sql_exception $error) {
        markDatabaseRequestFailed('write', $error->getMessage());
        return 0;
    }
    if ($result === false) {
        markDatabaseRequestFailed('write', mysqli_error($GLOBALS['conn']));
        return 0;
    }
    return 1;
}
function read($query){
    try {
        $result = mysqli_query($GLOBALS['conn'], $query);
    } catch (mysqli_sql_exception $error) {
        markDatabaseRequestFailed('read', $error->getMessage());
        return json_encode(["status" => "error", "message" => "The database query failed."]);
    }
    if (!$result) {
        markDatabaseRequestFailed('read', mysqli_error($GLOBALS['conn']));
        return json_encode(["status" => "error", "message" => "The database query failed."]);
    }
    $data=array();
    while($row=mysqli_fetch_assoc($result)){
        $data[]=$row;
    }
    return json_encode($data);
}
function ifexist($query) {
     try {
         $result = mysqli_query($GLOBALS['conn'], $query);
     } catch (mysqli_sql_exception $error) {
         markDatabaseRequestFailed('existence_check', $error->getMessage());
         throw new RuntimeException('The database check failed.', 0, $error);
     }
     if (!$result) {
         markDatabaseRequestFailed('existence_check', mysqli_error($GLOBALS['conn']));
         throw new RuntimeException('The database check failed.');
     }
 
     $row_count = mysqli_num_rows($result);
     
     if ($row_count > 0) {
         return true; // Record(s) exist
     } else {
         return false; // No record found
     }
 }
 function return_last_entered_record_id($query) {
     try {
         $result = mysqli_query($GLOBALS['conn'], $query);
     } catch (mysqli_sql_exception $error) {
         markDatabaseRequestFailed('insert', $error->getMessage());
         return false;
     }
 
     if ($result) {
         $last_id = mysqli_insert_id($GLOBALS['conn']);
         return $last_id;
     } else {
         markDatabaseRequestFailed('insert', mysqli_error($GLOBALS['conn']));
         return false; // Return false to indicate an error
     }
 }

function getData($key){
      return isset($_GET[$key]) ? mysqli_escape_string($GLOBALS['conn'],$_GET[$key]) : '';
}
function postData($key){
      return isset($_POST[$key]) ? mysqli_escape_string($GLOBALS['conn'],$_POST[$key]) : '';
}
if(isset($_POST['function_to_call'])){
     $function_to_call=mysqli_escape_string($conn,$_POST['function_to_call']);     
 }
 elseif (isset($_GET['function_to_call'])){
     $function_to_call=mysqli_escape_string($conn,$_GET['function_to_call']);
}

?>

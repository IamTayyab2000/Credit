<?php
function reportDatabaseUnavailable($message)
{
  error_log('[Database] Connection failed: ' . $message);
  http_response_code(503);
  $script = basename($_SERVER['SCRIPT_NAME'] ?? '');
  if (in_array($script, ['allFunctionality.php', 'process_customer_import.php', 'process_csv_import.php'], true)) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
      'success' => false,
      'message' => 'The database is temporarily unavailable. Please try again.',
    ]);
  } else {
    echo 'The database is temporarily unavailable. Please try again.';
  }
  exit;
}

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'cred-lipton');

$conn = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_errno) {
  reportDatabaseUnavailable($conn->connect_error);
}

try {
  $conn->set_charset("utf8mb4");
} catch (mysqli_sql_exception $error) {
  reportDatabaseUnavailable($error->getMessage());
}
?>
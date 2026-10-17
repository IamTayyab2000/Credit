<?php
include_once(__DIR__ . '/functionality/components/session.php');
requireAdminSession(true);
// check_open_credit.php
include_once('functionality/components/condb.php');
header('Content-Type: application/json');

$customer_id = isset($_GET['customer_id']) ? $_GET['customer_id'] : '';
$response = ['has_open_credit' => false, 'open_bills' => []];

if ($customer_id) {
    // Use correct table and logic for open bills
    $sql = "SELECT bill_id as invoice_id, bill_amount as outstanding FROM bill WHERE cutomer_id = ? AND bill_amount > 0";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        $response['error'] = 'SQL prepare failed: ' . $conn->error . ' | Check table/column names.';
        echo json_encode($response);
        exit;
    }
    $stmt->bind_param('s', $customer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $response['has_open_credit'] = true;
        $response['open_bills'][] = $row;
    }
    $stmt->close();
}
echo json_encode($response);

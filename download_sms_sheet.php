<?php
include_once('functionality/components/session_chk_admin.php');
include_once('functionality/components/condb.php');

$type = $_GET['type'] ?? '';
$id = trim($_GET['id'] ?? '');

if (!in_array($type, ['recovery', 'dsr'], true) || $id === '') {
    http_response_code(400);
    exit('Invalid SMS sheet request.');
}

if ($type === 'recovery') {
    $query = "SELECT c.customer_name, c.phone, d.recovery_sheet_bill_id AS invoice_id,
                     d.recovery_sheet_bill_amount AS amount_due,
                     d.recovery_sheet_bill_recovered AS amount_paid_today
              FROM recovery_sheet_detail d
              INNER JOIN bill b ON b.bill_id = d.recovery_sheet_bill_id
              INNER JOIN customer c ON c.customer_id = b.cutomer_id
              WHERE d.recovery_sheet_id = ?
              ORDER BY c.customer_name, d.recovery_sheet_bill_id";
    $filename = 'recovery_sms_sheet_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $id) . '.csv';
} else {
    $query = "SELECT c.customer_name, c.phone, b.bill_id AS invoice_id,
                     b.bill_amount AS amount_due, 0 AS amount_paid_today
              FROM bill b
              INNER JOIN customer c ON c.customer_id = b.cutomer_id
              WHERE b.picklist_id = ?
              ORDER BY c.customer_name, b.bill_id";
    $filename = 'dsr_sms_sheet_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $id) . '.csv';
}

$stmt = $conn->prepare($query);
if (!$stmt) {
    http_response_code(500);
    exit('Unable to prepare SMS sheet query.');
}
$stmt->bind_param('s', $id);
$stmt->execute();
$result = $stmt->get_result();

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');
fputcsv($output, ['Name', 'Phone', 'InvoiceID', 'AmountDue', 'AmountPaidToday']);
while ($row = $result->fetch_assoc()) {
    fputcsv($output, [
        $row['customer_name'],
        $row['phone'] ?? '',
        $row['invoice_id'],
        $row['amount_due'],
        $row['amount_paid_today'],
    ]);
}
fclose($output);
$stmt->close();
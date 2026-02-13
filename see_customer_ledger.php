<?php
// File: C:/xampp/htdocs/Credit/see_customer_ledger.php

// Minimal page to view ledger entries for a customer.
// Usage: see_customer_ledger.php?cust_id=CUSTOMER_ID  (also accepts ?customer_id=... for backwards compatibility)

include('functionality/components/condb.php');

$customer_id = '';
// Prefer `cust_id` param but fall back to legacy `customer_id` param.
if (isset($_GET['cust_id'])) {
    $customer_id = trim($_GET['cust_id']);
} elseif (isset($_GET['customer_id'])) {
    $customer_id = trim($_GET['customer_id']);
}

function h($s){ return htmlspecialchars($s, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); }

// Return aggregated bill/document summaries for a customer (one row per bill)
function getBillSummaries($conn, $customer_id) {
    // Read events from the derived view `vw_customer_bill_ledger`.
    // We now select chronological events instead of grouping by bill_id
    $query = "SELECT 
                    bill_id,
                    event_date AS ledger_date,
                    ref_id,
                    debit AS bill_amount,
                    credit AS recived_amount,
                    return_amount,
                    bill_status,
                    source_type
                FROM vw_customer_bill_ledger
                WHERE customer_id = ?
                ORDER BY event_date ASC, bill_id ASC, source_type ASC";

    if ($stmt = $conn->prepare($query)) {
        $stmt->bind_param('s', $customer_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $rows = $res->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }

    return array();
}

// Return raw transactional ledger entries for a customer (one row per entry)
function getRawTransactions($conn, $customer_id) {
    $query = "SELECT ledger_id, ref_id, ledger_date, bill_id, bill_amount, recived_amount, return_amount, remaining_amount, COALESCE(bill_status,'') AS bill_status
              FROM bill_ledger
              WHERE customer_id = ?
              ORDER BY ledger_date ASC, ledger_id ASC";

    if ($stmt = $conn->prepare($query)) {
        $stmt->bind_param('s', $customer_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $rows = $res->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }

    return array();
}

// Compute opening balance (brought forward) before a given date.
// If $from_date is null, use the earliest ledger_date for the customer as cutoff (so opening may be 0).
function getOpeningBalance($conn, $customer_id, $from_date = null) {
    if ($from_date !== null) {
        $query = "SELECT COALESCE(SUM(bill_amount - recived_amount - return_amount),0) AS opening FROM bill_ledger WHERE customer_id = ? AND ledger_date < ?";
        if ($stmt = $conn->prepare($query)) {
            $stmt->bind_param('ss', $customer_id, $from_date);
            $stmt->execute();
            $stmt->bind_result($opening);
            $stmt->fetch();
            $stmt->close();
            return (float)$opening;
        }
        return 0.0;
    }

    // No from_date: determine earliest ledger_date and sum before it
    $minq = "SELECT MIN(ledger_date) FROM bill_ledger WHERE customer_id = ?";
    $min_date = null;
    if ($stmt = $conn->prepare($minq)) {
        $stmt->bind_param('s', $customer_id);
        $stmt->execute();
        $stmt->bind_result($min_date);
        $stmt->fetch();
        $stmt->close();
    }

    if ($min_date === null) return 0.0;

    $query = "SELECT COALESCE(SUM(bill_amount - recived_amount - return_amount),0) AS opening FROM bill_ledger WHERE customer_id = ? AND ledger_date < ?";
    if ($stmt = $conn->prepare($query)) {
        $stmt->bind_param('ss', $customer_id, $min_date);
        $stmt->execute();
        $stmt->bind_result($opening);
        $stmt->fetch();
        $stmt->close();
        return (float)$opening;
    }

    return 0.0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Customer Ledger</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <style>
        body{font-family:Arial,Helvetica,sans-serif;margin:18px}
        .search{margin-bottom:18px}
        table{width:100%;border-collapse:collapse}
        th,td{padding:8px 10px;border:1px solid #ddd;text-align:left}
        th{background:#f6f6f6}
        tr:nth-child(even){background:#fafafa}
        tr:hover{background:#f0f8ff}
        .muted{color:#666;font-size:90%}
    </style>
</head>
<body>
    <h2>Customer Ledger</h2>
<?php
// Get customer name (if exists)
$customer_name = null;
if ($stmt = $conn->prepare("SELECT customer_name FROM customer WHERE customer_id = ? LIMIT 1")) {
    $stmt->bind_param('s', $customer_id);
    $stmt->execute();
    $stmt->bind_result($customer_name);
    $stmt->fetch();
    $stmt->close();
}

if ($customer_name !== null) {
    echo '<h3>Customer: ' . h($customer_name) . ' (ID: ' . h($customer_id) . ')</h3>';
} else {
    echo '<h3>Customer ID: ' . h($customer_id) . ' <span class="muted">(name not found)</span></h3>';
}

// Fetch and render chronolocigal ledger entries
$show_raw = isset($_GET['show_raw']) && $_GET['show_raw'] === '1';

// optional start date to compute opening balance (format YYYY-MM-DD)
$from = isset($_GET['from']) && $_GET['from'] !== '' ? trim($_GET['from']) : null;

$ledger_rows = getBillSummaries($conn, $customer_id);

$base_q = '?cust_id=' . urlencode($customer_id);
if ($from) $base_q .= '&from=' . urlencode($from);
echo '<p><a href="' . $base_q . '">Ledger</a> | <a href="' . $base_q . '&show_raw=1">Raw Entries</a></p>';

if (empty($ledger_rows)) {
    echo '<p class="muted">No ledger entries found for this customer.</p>';
} else {
    echo '<table>';
    echo '<thead><tr>
                <th>Bill No</th>
                <th>Recovery Sheet (Ref)</th>
                <th>Date</th>
                <th>Debit</th>
                <th>Credit</th>
                <th>Return</th>
                <th>Balance</th>
                <th>Status</th>
              </tr></thead><tbody>';

    // Opening/brought-forward balance
    $opening = getOpeningBalance($conn, $customer_id, $from);
    $running = (float)$opening;
    $total_debit = 0.0;
    $total_credit = 0.0;
    $total_return = 0.0;

    // Show opening balance row if any (or show zero if from filter used)
    if ($opening !== 0.0 || $from) {
        echo '<tr style="font-weight:bold;background:#fdf6e3">';
        echo '<td>Opening Balance (B/F)</td>';
        echo '<td></td>';
        echo '<td>' . ($from ? h($from) : '') . '</td>';
        echo '<td></td><td></td><td></td>';
        echo '<td style="text-align:right">' . number_format($running,2) . '</td>';
        echo '<td></td>';
        echo '</tr>';
    }

    foreach ($ledger_rows as $row) {
        $debit = (float)$row['bill_amount'];
        $credit = (float)$row['recived_amount'];
        $return = (float)($row['return_amount'] ?? 0);

        $total_debit += $debit;
        $total_credit += $credit;
        $total_return += $return;

        $running += ($debit - $credit - $return);

        echo '<tr>';
        echo '<td><a href="ledger.php?bill_id=' . urlencode($row['bill_id']) . '">' . h($row['bill_id']) . '</a></td>';
        echo '<td>' . h($row['ref_id']) . '</td>';
        echo '<td>' . h($row['ledger_date']) . '</td>';
        echo '<td style="text-align:right">' . ($debit > 0 ? number_format($debit,2) : '-') . '</td>';
        echo '<td style="text-align:right">' . ($credit > 0 ? number_format($credit,2) : '-') . '</td>';
        echo '<td style="text-align:right">' . ($return > 0 ? number_format($return,2) : '-') . '</td>';
        echo '<td style="text-align:right">' . number_format($running,2) . '</td>';
        echo '<td>' . h($row['bill_status']) . '</td>';
        echo '</tr>';
    }

    // Totals footer
    echo '</tbody><tfoot>';
    echo '<tr style="font-weight:bold;background:#f6f6f6">';
    echo '<td colspan="3">Totals</td>';
    echo '<td style="text-align:right">' . number_format($total_debit,2) . '</td>';
    echo '<td style="text-align:right">' . number_format($total_credit,2) . '</td>';
    echo '<td style="text-align:right">' . number_format($total_return,2) . '</td>';
    echo '<td style="text-align:right">' . number_format($running,2) . '</td>';
    echo '<td></td>';
    echo '</tr>';
    echo '</tfoot></table>';
}

// Optionally show raw transactional entries
if ($show_raw) {
    $raw_rows = getRawTransactions($conn, $customer_id);

    echo '<h3>Raw Transactions</h3>';

    if (empty($raw_rows)) {
        echo '<p class="muted">No raw transactions found.</p>';
    } else {
        echo '<table style="margin-top:12px">';
        echo '<thead><tr>
                <th>Ledger ID</th>
                <th>Recovery Sheet (Ref)</th>
                <th>Date</th>
                <th>Bill No</th>
                <th>Debit</th>
                <th>Credit</th>
                <th>Return</th>
                <th>Remaining</th>
                <th>Status</th>
              </tr></thead><tbody>';

        foreach ($raw_rows as $r) {
            echo '<tr>';
            echo '<td>' . h($r['ledger_id']) . '</td>';
            echo '<td>' . h($r['ref_id']) . '</td>';
            echo '<td>' . h($r['ledger_date']) . '</td>';
            echo '<td><a href="ledger.php?bill_id=' . urlencode($r['bill_id']) . '">' . h($r['bill_id']) . '</a></td>';
            echo '<td style="text-align:right">' . number_format((float)$r['bill_amount'],2) . '</td>';
            echo '<td style="text-align:right">' . number_format((float)$r['recived_amount'],2) . '</td>';
            echo '<td style="text-align:right">' . number_format((float)$r['return_amount'],2) . '</td>';
            echo '<td style="text-align:right">' . number_format((float)$r['remaining_amount'],2) . '</td>';
            echo '<td>' . h($r['bill_status']) . '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
    }
}

$conn->close();
?>
</body>
</html>
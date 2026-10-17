<?php include_once('functionality/components/session_chk_admin.php') ?>
<?php include_once('functionality/components/condb.php') ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/bootstrap.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/main.css">
    <title>Daily Sales Report | Credit Management</title>
    <style>
        @media print {
            @page { margin: 0.5cm; size: landscape; }
            body { font-size: 10px; background: white !important; font-family: 'Arial', sans-serif; }
            .no-print { display: none !important; }
            .container { max-width: 100% !important; padding: 0 !important; }
            .table td, .table th { padding: 3px 6px !important; border: 1px solid #000 !important; font-size: 10px !important; }
            .table thead th { background-color: #e9ecef !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .total-row { background-color: #fff3cd !important; font-weight: bold !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            h3 { font-size: 14px; margin-bottom: 8px; }
        }
        .table-custom { font-size: 13px; }
        .table-custom th { background-color: #f8f9fa; color: #212529; font-weight: 600; text-align: center; vertical-align: middle; padding: 10px 8px; border-bottom: 2px solid #dee2e6; }
        .table-custom td { vertical-align: middle; padding: 8px; }
        .total-row { background-color: #fff3cd; font-weight: bold; }
        .day-sunday { background-color: #ffe5e5; }
        .report-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 10px; }
    </style>
</head>
<body class="bg-light">
<div class="container py-4">
<?php
/*
 * LEDGER LOGIC (same as the Excel sheet)
 *
 *   Credit          = Sheet Amount (DSR) - Supply Cash - Return
 *   Opening Balance = previous day's New Credit
 *   New Credit      = Opening Balance + Credit - Recovery
 *
 * DB mapping:
 *   picklist_amount   -> Sheet Amount
 *   picklist_credit   -> Credit (already net of the load's return)
 *   picklist_return   -> Return on the load
 *   Supply Cash       -> derived: Sheet - Credit - Return
 *   recovery_sheet_returned -> goods returned against OLD credit, so it
 *                              reduces the balance directly (like Recovery)
 */

$saleman_id   = isset($_GET['saleman_id']) ? $_GET['saleman_id'] : '';
$saleman_name = isset($_GET['saleman_name']) ? $_GET['saleman_name'] : '';

if (empty($saleman_id)) {
    echo "<div class='alert alert-danger'>Salesman ID missing.</div>";
    exit;
}

// Validate month (YYYY-MM), default to current month
$selected_month = isset($_GET['month']) ? $_GET['month'] : date('Y-m');
if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $selected_month)) {
    $selected_month = date('Y-m');
}
list($year, $month_num) = explode('-', $selected_month);
$month_name = date('F', mktime(0, 0, 0, (int)$month_num, 1));

$sid         = $conn->real_escape_string($saleman_id);
$month_start = "{$selected_month}-01";
$month_end   = date('Y-m-t', strtotime($month_start));

// ---------------------------------------------------------------
// 1) Opening balance = prior DSR credit less collections and returns against old credit.
// ---------------------------------------------------------------
$opening_balance = 0.0;

$q = "SELECT IFNULL(SUM(picklist_credit),0) AS c
      FROM picklist
      WHERE picklist_saleman = '{$sid}' AND picklist_date < '{$month_start}'";
if ($res = $conn->query($q)) {
    $r = $res->fetch_assoc();
    $opening_balance += floatval($r['c']);
}

$q = "SELECT IFNULL(SUM(recovery_sheet_recovery),0) AS r, IFNULL(SUM(recovery_sheet_returned),0) AS ret
      FROM recovery_sheet
      WHERE recovery_sheet_saleman_id = '{$sid}' AND recovery_date < '{$month_start}'";
if ($res = $conn->query($q)) {
    $r = $res->fetch_assoc();
    $opening_balance -= (floatval($r['r']) + floatval($r['ret']));
}

// ---------------------------------------------------------------
// 2) This month's data, grouped by date
// ---------------------------------------------------------------
$query = "
SELECT
    d.date,
    SUM(d.dsr)       AS dsr,
    SUM(d.credit)    AS credit,
    SUM(d.pl_return) AS pl_return,
    SUM(d.recovery)  AS recovery,
    SUM(d.rs_return) AS rs_return
FROM (
    SELECT
        picklist_date AS date,
        picklist_amount AS dsr,
        picklist_credit AS credit,
        IFNULL(picklist_return, 0) AS pl_return,
        0 AS recovery,
        0 AS rs_return
    FROM picklist
    WHERE picklist_saleman = '{$sid}'
      AND picklist_date BETWEEN '{$month_start}' AND '{$month_end}'

    UNION ALL

    SELECT
        recovery_date AS date,
        0 AS dsr,
        0 AS credit,
        0 AS pl_return,
        IFNULL(recovery_sheet_recovery, 0) AS recovery,
        IFNULL(recovery_sheet_returned, 0) AS rs_return
    FROM recovery_sheet
    WHERE recovery_sheet_saleman_id = '{$sid}'
      AND recovery_date BETWEEN '{$month_start}' AND '{$month_end}'
) d
GROUP BY d.date
ORDER BY d.date";

$res = $conn->query($query);
if (!$res) {
    echo "<div class='alert alert-danger'>Query error: " . htmlspecialchars($conn->error) . "</div>";
    exit;
}

$by_date = [];
while ($row = $res->fetch_assoc()) {
    $by_date[$row['date']] = $row;
}

// ---------------------------------------------------------------
// 3) Build every day of the month (like the Excel sheet) and carry
//    the balance forward
// ---------------------------------------------------------------
$daily_data = [];
$month_totals = [
    'dsr' => 0, 'credit' => 0, 'supply_cash' => 0,
    'recovery' => 0, 'return' => 0
];

$running = $opening_balance;
$days_in_month = (int)date('t', strtotime($month_start));

for ($i = 1; $i <= $days_in_month; $i++) {
    $date = sprintf('%s-%02d', $selected_month, $i);
    $row  = isset($by_date[$date]) ? $by_date[$date] : null;

    $dsr       = $row ? floatval($row['dsr'])       : 0;
    $credit    = $row ? floatval($row['credit'])    : 0;
    $pl_return = $row ? floatval($row['pl_return']) : 0;
    $recovery  = $row ? floatval($row['recovery'])  : 0;
    $rs_return = $row ? floatval($row['rs_return']) : 0;

    // Supply Cash = Sheet - Credit - Return  (Credit = Sheet - Cash - Return)
    $supply_cash = $dsr - $credit - $pl_return;

    $opening    = $running;
    $new_credit = $opening + $credit - $recovery - $rs_return;
    $running    = $new_credit;

    $total_return = $pl_return + $rs_return;

    $daily_data[] = [
        'date'        => $date,
        'day'         => date('l', strtotime($date)),
        'dsr'         => $dsr,
        'supply_cash' => $supply_cash,
        'return'      => $total_return,
        'credit'      => $credit,
        'opening'     => $opening,
        'recovery'    => $recovery + $rs_return,
        'new_credit'  => $new_credit
    ];

    $month_totals['dsr']         += $dsr;
    $month_totals['credit']      += $credit;
    $month_totals['supply_cash'] += $supply_cash;
    $month_totals['recovery']    += $recovery + $rs_return;
    $month_totals['return']      += $total_return;
}
$closing_balance = $running;

// ---------------------------------------------------------------
// UI Header
// ---------------------------------------------------------------
echo "<div class='report-header mb-4 no-print shadow'>";
echo "<div class='row align-items-center'>";
echo "<div class='col-md-6'>";
echo "<h3 class='mb-1'>Daily Sales Report</h3>";
echo "<h5 class='mb-0'>Salesman: " . htmlspecialchars($saleman_name ?: $saleman_id) . "</h5>";
echo "<p class='mb-0 mt-2'><strong>Month: {$month_name} {$year}</strong> | Total Sales: <strong>" . number_format($month_totals['dsr'], 2) . "</strong></p>";
echo "</div>";
echo "<div class='col-md-6'>";
echo "<form method='GET' class='row g-2 justify-content-end'>";
echo "<input type='hidden' name='saleman_id' value='" . htmlspecialchars($saleman_id) . "'>";
echo "<input type='hidden' name='saleman_name' value='" . htmlspecialchars($saleman_name) . "'>";
echo "<div class='col-auto'><div class='input-group input-group-sm'>";
echo "<span class='input-group-text bg-white text-dark'>Select Month</span>";
echo "<input type='month' name='month' class='form-control' value='" . htmlspecialchars($selected_month) . "' onchange='this.form.submit()'>";
echo "</div></div>";
echo "<div class='col-auto'><button type='button' class='btn btn-light btn-sm' onclick='window.print()'><i class='bi bi-printer'></i> Print</button></div>";
echo "<div class='col-auto'><a class='btn btn-outline-light btn-sm' href='adminpanel.php'>Back</a></div>";
echo "</form>";
echo "</div></div></div>";

// Print header
echo "<div class='d-none d-print-block text-center mb-3'>";
echo "<h3>Daily Sales Report - " . htmlspecialchars($saleman_name ?: $saleman_id) . "</h3>";
echo "<p><strong>Month: {$month_name} {$year}</strong></p>";
echo "</div>";

// ---------------------------------------------------------------
// Main Table  (column order follows the Excel sheet)
// ---------------------------------------------------------------
echo "<div class='card shadow-sm border-0'><div class='card-body p-0'><div class='table-responsive'>";
echo "<table class='table table-bordered table-hover mb-0 table-custom'>";
echo "<thead class='bg-light'><tr>";
echo "<th>Date</th><th>Day</th><th>Sheet Amount</th><th>Supply Cash</th><th>Return</th>";
echo "<th>Credit</th><th>Opening Balance</th><th>Recovery</th><th>New Credit</th>";
echo "</tr></thead><tbody>";

// Opening row = previous month's closing credit
$prev_month_label = date('F Y', strtotime($month_start . ' -1 month'));
echo "<tr class='total-row'>";
echo "<td colspan='8' class='text-end'><strong>Opening Credit ({$prev_month_label} Closing)</strong></td>";
echo "<td class='text-end'><strong>" . number_format($opening_balance, 2) . "</strong></td>";
echo "</tr>";

foreach ($daily_data as $day) {
    $row_class = ($day['day'] == 'Sunday') ? 'day-sunday' : '';
    $date_formatted = date('m/d/Y', strtotime($day['date']));

    echo "<tr class='{$row_class}'>";
    echo "<td>{$date_formatted}</td>";
    echo "<td>{$day['day']}</td>";
    echo "<td class='text-end'>" . number_format($day['dsr'], 2) . "</td>";
    echo "<td class='text-end'>" . number_format($day['supply_cash'], 2) . "</td>";
    echo "<td class='text-end'>" . number_format($day['return'], 2) . "</td>";
    echo "<td class='text-end'>" . number_format($day['credit'], 2) . "</td>";
    echo "<td class='text-end'>" . number_format($day['opening'], 2) . "</td>";
    echo "<td class='text-end'>" . number_format($day['recovery'], 2) . "</td>";
    echo "<td class='text-end'>" . number_format($day['new_credit'], 2) . "</td>";
    echo "</tr>";
}

// Totals row: opening = start of month, new credit = end of month
echo "<tr class='total-row'>";
echo "<td colspan='2' class='text-end'><strong>MONTH TOTAL</strong></td>";
echo "<td class='text-end'><strong>" . number_format($month_totals['dsr'], 2) . "</strong></td>";
echo "<td class='text-end'><strong>" . number_format($month_totals['supply_cash'], 2) . "</strong></td>";
echo "<td class='text-end'><strong>" . number_format($month_totals['return'], 2) . "</strong></td>";
echo "<td class='text-end'><strong>" . number_format($month_totals['credit'], 2) . "</strong></td>";
echo "<td class='text-end'><strong>" . number_format($opening_balance, 2) . "</strong></td>";
echo "<td class='text-end'><strong>" . number_format($month_totals['recovery'], 2) . "</strong></td>";
echo "<td class='text-end'><strong>" . number_format($closing_balance, 2) . "</strong></td>";
echo "</tr>";

echo "</tbody></table></div></div></div>";

echo "<div class='alert alert-info mt-3 no-print'>";
echo "<strong>Note:</strong> Opening credit for " . date('F Y', strtotime($month_start)) . " is the closing credit of {$prev_month_label}. ";
echo "Closing balance = Opening + Credit &minus; Recovery.";
echo "</div>";
?>
</div>
<script src="js/jquery.js"></script>
<script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>
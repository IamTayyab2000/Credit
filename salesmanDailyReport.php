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
            @page {
                margin: 0.5cm;
                size: landscape;
            }
            body { 
                font-size: 10px; 
                background: white !important;
                font-family: 'Arial', sans-serif;
            }
            .no-print { 
                display: none !important; 
            }
            .container {
                max-width: 100% !important;
                padding: 0 !important;
            }
            .table td, .table th { 
                padding: 3px 6px !important; 
                border: 1px solid #000 !important;
                font-size: 10px !important;
            }
            .table thead th {
                background-color: #e9ecef !important;
                -webkit-print-color-adjust: exact; 
                print-color-adjust: exact;
            }
            .total-row {
                background-color: #fff3cd !important;
                font-weight: bold !important;
                -webkit-print-color-adjust: exact; 
                print-color-adjust: exact;
            }
            h3 {
                font-size: 14px;
                margin-bottom: 8px;
            }
        }
        .table-custom {
            font-size: 13px;
        }
        .table-custom th {
            background-color: #f8f9fa;
            color: #212529;
            font-weight: 600;
            text-align: center;
            vertical-align: middle;
            padding: 10px 8px;
            border-bottom: 2px solid #dee2e6;
        }
        .table-custom td {
            vertical-align: middle;
            padding: 8px;
        }
        .total-row {
            background-color: #fff3cd;
            font-weight: bold;
        }
        .day-sunday {
            background-color: #ffe5e5;
        }
        .report-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
        }
    </style>
</head>
<body class="bg-light">
<div class="container py-4">
<?php
$saleman_id = isset($_GET['saleman_id']) ? $_GET['saleman_id'] : '';
$saleman_name = isset($_GET['saleman_name']) ? $_GET['saleman_name'] : '';

// Default to current month
$selected_month = isset($_GET['month']) ? $_GET['month'] : date('Y-m');
list($year, $month_num) = explode('-', $selected_month);
$month_name = date('F', mktime(0, 0, 0, $month_num, 1));

if(empty($saleman_id)){
    echo "<div class='alert alert-danger'>Salesman ID missing.</div>";
    exit;
}

// Query to get daily sales data
$query = "SELECT 
    p.picklist_date as date,
    DAYNAME(p.picklist_date) as day_name,
    p.picklist_amount as dsr,
    p.picklist_credit as credit,
    p.picklist_recovery as recovery,
    p.picklist_return as return_amount
FROM picklist p
WHERE p.picklist_saleman = '{$saleman_id}' 
    AND DATE_FORMAT(p.picklist_date, '%Y-%m') = '{$selected_month}'
ORDER BY p.picklist_date";

$res = $conn->query($query);
if(!$res){
    echo "<div class='alert alert-danger'>Query error: " . $conn->error . "</div>";
    exit;
}

$daily_data = [];
$month_totals = [
    'dsr' => 0,
    'credit' => 0,
    'supply_cash' => 0,
    'recovery' => 0,
    'return' => 0,
    'new_credit' => 0
];

while($row = $res->fetch_assoc()){
    $dsr = floatval($row['dsr']);
    $credit = floatval($row['credit']);
    $recovery = floatval($row['recovery']);
    $return_amt = floatval($row['return_amount']);
    
    $supply_cash = $dsr - $credit;
    $new_credit = $credit - $recovery - $return_amt;
    
    $daily_data[] = [
        'date' => $row['date'],
        'day' => $row['day_name'],
        'dsr' => $dsr,
        'credit' => $credit,
        'supply_cash' => $supply_cash,
        'recovery' => $recovery,
        'return' => $return_amt,
        'new_credit' => $new_credit
    ];
    
    $month_totals['dsr'] += $dsr;
    $month_totals['credit'] += $credit;
    $month_totals['supply_cash'] += $supply_cash;
    $month_totals['recovery'] += $recovery;
    $month_totals['return'] += $return_amt;
    $month_totals['new_credit'] += $new_credit;
}

// UI Header
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
echo "<div class='col-auto'>";
echo "<div class='input-group input-group-sm'>";
echo "<span class='input-group-text bg-white text-dark'>Select Month</span>";
echo "<input type='month' name='month' class='form-control' value='" . htmlspecialchars($selected_month) . "' onchange='this.form.submit()'>";
echo "</div>";
echo "</div>";
echo "<div class='col-auto'>";
echo "<button type='button' class='btn btn-light btn-sm' onclick='window.print()'><i class='bi bi-printer'></i> Print</button>";
echo "</div>";
echo "<div class='col-auto'>";
echo "<a class='btn btn-outline-light btn-sm' href='adminpanel.php'>Back</a>";
echo "</div>";
echo "</form>";
echo "</div>";
echo "</div>";
echo "</div>";

// Print header
echo "<div class='d-none d-print-block text-center mb-3'>";
echo "<h3>Daily Sales Report - " . htmlspecialchars($saleman_name ?: $saleman_id) . "</h3>";
echo "<p><strong>Month: {$month_name} {$year}</strong></p>";
echo "</div>";

// Main Table
echo "<div class='card shadow-sm border-0'>";
echo "<div class='card-body p-0'>";
echo "<div class='table-responsive'>";
echo "<table class='table table-bordered table-hover mb-0 table-custom'>";
echo "<thead class='bg-light'>";
echo "<tr>";
echo "<th>Date</th>";
echo "<th>Day</th>";
echo "<th>DSR</th>";
echo "<th>Credit</th>";
echo "<th>Supply Cash</th>";
echo "<th>Recovery</th>";
echo "<th>Return</th>";
echo "<th>New Credit</th>";
echo "</tr>";
echo "</thead>";
echo "<tbody>";

if(empty($daily_data)){
    echo "<tr><td colspan='8' class='text-center py-4 text-muted'>No sales data found for this month.</td></tr>";
} else {
    foreach($daily_data as $day){
        $row_class = ($day['day'] == 'Sunday') ? 'day-sunday' : '';
        $date_formatted = date('m/d/Y', strtotime($day['date']));
        
        echo "<tr class='{$row_class}'>";
        echo "<td>{$date_formatted}</td>";
        echo "<td>{$day['day']}</td>";
        echo "<td class='text-end'>" . number_format($day['dsr'], 2) . "</td>";
        echo "<td class='text-end'>" . number_format($day['credit'], 2) . "</td>";
        echo "<td class='text-end'>" . number_format($day['supply_cash'], 2) . "</td>";
        echo "<td class='text-end'>" . number_format($day['recovery'], 2) . "</td>";
        echo "<td class='text-end'>" . number_format($day['return'], 2) . "</td>";
        echo "<td class='text-end'>" . number_format($day['new_credit'], 2) . "</td>";
        echo "</tr>";
    }
    
    // Totals row
    echo "<tr class='total-row'>";
    echo "<td colspan='2' class='text-end'><strong>MONTH TOTAL</strong></td>";
    echo "<td class='text-end'><strong>" . number_format($month_totals['dsr'], 2) . "</strong></td>";
    echo "<td class='text-end'><strong>" . number_format($month_totals['credit'], 2) . "</strong></td>";
    echo "<td class='text-end'><strong>" . number_format($month_totals['supply_cash'], 2) . "</strong></td>";
    echo "<td class='text-end'><strong>" . number_format($month_totals['recovery'], 2) . "</strong></td>";
    echo "<td class='text-end'><strong>" . number_format($month_totals['return'], 2) . "</strong></td>";
    echo "<td class='text-end'><strong>" . number_format($month_totals['new_credit'], 2) . "</strong></td>";
    echo "</tr>";
}

echo "</tbody>";
echo "</table>";
echo "</div>";
echo "</div>";
echo "</div>";

// Last Month Credit calculation notice
if(!empty($daily_data)){
    $last_month = date('Y-m', strtotime($selected_month . '-01 -1 month'));
    echo "<div class='alert alert-info mt-3 no-print'>";
    echo "<strong>Note:</strong> 'Last Month Credit' should be calculated from " . date('F Y', strtotime($last_month . '-01')) . " New Credit total.";
    echo "</div>";
}
?>
</div>
<script src="js/jquery.js"></script>
<script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>

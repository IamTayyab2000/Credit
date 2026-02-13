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
    <title>Full Credit Report | Credit Management</title>
    <style>
        @media print {
            @page {
                margin: 0.5cm;
                size: portrait;
            }
            body { 
                font-size: 11px; 
                background: white !important;
                font-family: 'Times New Roman', serif;
            }
            .no-print { 
                display: none !important; 
            }
            .container-fluid {
                padding: 0 !important;
            }
            .table td, .table th { 
                padding: 2px 4px !important; 
                border: 1px solid #000 !important;
            }
            .table-striped tbody tr:nth-of-type(odd) {
                background-color: rgba(0,0,0,.05) !important;
                -webkit-print-color-adjust: exact; 
                print-color-adjust: exact;
            }
            .area-total-row {
                background-color: #e2e3e5 !important;
                font-weight: bold;
                -webkit-print-color-adjust: exact; 
                print-color-adjust: exact;
            }
            .text-danger {
                color: #dc3545 !important;
                -webkit-print-color-adjust: exact; 
                print-color-adjust: exact;
            }
            h3 {
                margin-bottom: 5px;
                font-size: 16px;
                text-align: center;
            }
        }
        .table-custom th, .table-custom td {
            vertical-align: middle;
        }
        .area-total-row {
            background-color: #f8f9fc;
            font-weight: bold;
        }
    </style>
</head>
<body class="bg-light">
<div class="container py-4">
<?php
$saleman_id = isset($_GET['saleman_id']) ? $_GET['saleman_id'] : '';
$saleman_name = isset($_GET['saleman_name']) ? $_GET['saleman_name'] : '';

$default_date = date('Y-m-d', strtotime('-30 days'));
$warning_date = isset($_GET['warning_date']) ? $_GET['warning_date'] : $default_date;

if(empty($saleman_id)){
    echo "<div class='alert alert-danger'>Salesman ID missing.</div>";
    exit;
}

$query = "SELECT COALESCE(s.sector_name,'Unknown') AS area, c.customer_name AS shop_name, b.bill_id, b.bill_date, b.bill_amount, COALESCE(bl.remaining_amount, b.bill_amount) AS remaining_amount, DATEDIFF(CURRENT_DATE, b.bill_date) AS bill_age
          FROM bill b
          LEFT JOIN bill_ledger bl ON bl.bill_id = b.bill_id
          LEFT JOIN customer c ON b.cutomer_id = c.customer_id
          LEFT JOIN routes r ON c.customer_route = r.route_id
          LEFT JOIN sector s ON r.sector_id = s.sector_id
          LEFT JOIN picklist p ON b.picklist_id = p.picklist_id
          WHERE p.picklist_saleman = '{$saleman_id}'
          ORDER BY s.sector_name, c.customer_name, b.bill_date DESC;";

$res = $conn->query($query);
if(!$res){
    echo "<div class='alert alert-danger'>Query error: " . $conn->error . "</div>";
    exit;
}

$report = [];
$total_outstanding = 0;
while($row = $res->fetch_assoc()){
    $area = $row['area'] ?: 'Unknown';
    $shop = $row['shop_name'] ?: 'Unknown Shop';
    if(!isset($report[$area])) $report[$area] = [];
    if(!isset($report[$area][$shop])) $report[$area][$shop] = [];
    $report[$area][$shop][] = $row;
    $total_outstanding += floatval($row['remaining_amount']);
}

// UI Header Section
echo "<div class='row mb-4 no-print align-items-center bg-white p-3 rounded shadow-sm mx-0'>";
echo "<div class='col-md-5'>";
echo "<h4 class='mb-0 font-weight-bold'>Report: " . htmlspecialchars($saleman_name ?: $saleman_id) . "</h4>";
echo "<p class='text-muted small mb-0'>Detailed credit analysis for selected salesman</p>";
echo "</div>";
echo "<div class='col-md-7'>";
echo "<form method='GET' class='row g-2 justify-content-end align-items-center'>";
echo "<input type='hidden' name='saleman_id' value='" . htmlspecialchars($saleman_id) . "'>";
echo "<input type='hidden' name='saleman_name' value='" . htmlspecialchars($saleman_name) . "'>";
echo "<div class='col-auto'>";
echo "<div class='input-group input-group-sm'>";
echo "<span class='input-group-text bg-light'>Aging cut-off</span>";
echo "<input type='date' name='warning_date' class='form-control' value='" . htmlspecialchars($warning_date) . "' onchange='this.form.submit()'>";
echo "</div>";
echo "</div>";
echo "<div class='col-auto'>";
echo "<button type='button' class='btn btn-primary btn-sm' onclick='window.print()'>Print</button>";
echo "</div>";
echo "<div class='col-auto'>";
echo "<a class='btn btn-outline-secondary btn-sm' href='creditReport.php'>Back</a>";
echo "</div>";
echo "</form>";
echo "</div>";
echo "</div>";

echo "<h3 class='d-none d-print-block text-center mt-3 mb-4'>Credit Report: " . htmlspecialchars($saleman_name ?: $saleman_id) . "</h3>";

echo "<div class='card shadow-sm border-0 mb-4 no-print'>";
echo "<div class='card-body p-0'>";
echo "<div class='table-responsive'>";
echo "<table class='table table-hover mb-0 table-custom'>";
echo "<thead>";
echo "<tr>";
echo "<th>Area</th>";
echo "<th>Shop</th>";
echo "<th>Bill ID</th>";
echo "<th>Date</th>";
echo "<th class='text-end'>Amount</th>";
echo "<th class='text-end'>Remaining</th>";
echo "<th class='text-center'>Age (Days)</th>";
echo "</tr>";
echo "</thead>";
echo "<tbody>";

if(empty($report)){
    echo "<tr><td colspan='7' class='text-center py-4 text-muted'>No outstanding bills found for this salesman.</td></tr>";
} else {
    foreach($report as $areaName => $shops){
        $area_total = 0;
        foreach($shops as $shopName => $bills){
            foreach($bills as $bill){
                $rem = floatval($bill['remaining_amount']);
                $area_total += $rem;
                $is_overdue = ($bill['bill_date'] < $warning_date);
                $ageClass = $is_overdue ? 'text-danger font-weight-bold' : '';
                
                echo "<tr>";
                echo "<td><span class='badge bg-light text-dark font-weight-normal'>" . htmlspecialchars($areaName) . "</span></td>";
                echo "<td>" . htmlspecialchars($shopName) . "</td>";
                echo "<td><code>" . htmlspecialchars($bill['bill_id']) . "</code></td>";
                echo "<td>" . htmlspecialchars($bill['bill_date']) . "</td>";
                echo "<td class='text-end'>" . number_format($bill['bill_amount'],2) . "</td>";
                echo "<td class='text-end font-weight-bold'>" . number_format($rem,2) . "</td>";
                echo "<td class='text-center $ageClass'>" . $bill['bill_age'] . "</td>";
                echo "</tr>";
            }
        }
        echo "<tr class='area-total-row no-print'>";
        echo "<td colspan='5' class='text-end text-muted small'>Area Total:</td>";
        echo "<td class='text-end font-weight-bold text-primary'>" . number_format($area_total, 2) . "</td>";
        echo "<td></td>";
        echo "</tr>";
    }
    
    echo "<tr class='bg-primary text-white font-weight-bold no-print'>";
    echo "<td colspan='5' class='text-end uppercase letter-spacing-1'>Grand Total Outstanding</td>";
    echo "<td class='text-end h5 mb-0'>" . number_format($total_outstanding, 2) . "</td>";
    echo "<td></td>";
    echo "</tr>";
}
echo "</tbody>";
echo "</table>";
echo "</div>";
echo "</div>";
echo "</div>";

// Separate print-only table (cleaner, no fancy styles)
echo "<div class='d-none d-print-block'>";
echo "<table class='table table-bordered table-sm'>";
echo "<thead><tr><th>Area</th><th>Shop</th><th>Bill ID</th><th>Date</th><th class='text-end'>Bill</th><th class='text-end'>Remaining</th><th class='text-center'>Age</th></tr></thead>";
echo "<tbody>";
foreach($report as $areaName => $shops){
    $area_total = 0;
    foreach($shops as $shopName => $bills){
        foreach($bills as $bill){
            $rem = floatval($bill['remaining_amount']);
            $area_total += $rem;
            $overdue = ($bill['bill_date'] < $warning_date) ? 'text-danger' : '';
            echo "<tr><td>$areaName</td><td>$shopName</td><td>{$bill['bill_id']}</td><td>{$bill['bill_date']}</td><td class='text-end'>".number_format($bill['bill_amount'],2)."</td><td class='text-end'>".number_format($rem,2)."</td><td class='text-center $overdue'>{$bill['bill_age']}</td></tr>";
        }
    }
    echo "<tr class='area-total-row'><td colspan='5' class='text-end'>Area Total:</td><td class='text-end'>".number_format($area_total, 2)."</td><td></td></tr>";
}
echo "<tr class='font-weight-bold'><td colspan='5' class='text-end'>GRAND TOTAL:</td><td class='text-end'>".number_format($total_outstanding, 2)."</td><td></td></tr>";
echo "</tbody></table></div>";

?>
</div>
<script src="js/jquery.js"></script>
<script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php include_once('functionality/components/session_chk_admin.php') ?>
<?php include_once('functionality/components/condb.php') ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/bootstrap.css">
    <title>Full Credit Report</title>
    <style>
        @media print {
            @page {
                margin: 0.5cm;
                size: portrait;
            }
            body { 
                font-size: 11px; 
                background: white;
            }
            .no-print { 
                display: none !important; 
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
            background-color: #e2e3e5;
            font-weight: bold;
        }
    </style>
</head>
<body>
<div class="container-fluid py-3">
<?php
$saleman_id = isset($_GET['saleman_id']) ? $_GET['saleman_id'] : '';
$saleman_name = isset($_GET['saleman_name']) ? $_GET['saleman_name'] : '';

// Default warning cutoff to 30 days ago if not set
$default_date = date('Y-m-d', strtotime('-30 days'));
$warning_date = isset($_GET['warning_date']) ? $_GET['warning_date'] : $default_date;

if(empty($saleman_id)){
    echo "<div class='alert alert-danger'>Salesman id missing.</div>";
    exit;
}

// Query bills
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

// UI Controls
echo "<div class='row mb-3 no-print align-items-center'>";
echo "<div class='col-md-5'>";
echo "<h3>Full Credit Report: " . htmlspecialchars($saleman_name ?: $saleman_id) . "</h3>";
echo "</div>";
echo "<div class='col-md-7 text-end'>";
echo "<form method='GET' class='d-inline-flex gap-2 align-items-center'>";
echo "<input type='hidden' name='saleman_id' value='" . htmlspecialchars($saleman_id) . "'>";
echo "<input type='hidden' name='saleman_name' value='" . htmlspecialchars($saleman_name) . "'>";
echo "<div class='input-group input-group-sm' style='width:auto;'>";
echo "<span class='input-group-text'>Highlight older than:</span>";
echo "<input type='date' name='warning_date' class='form-control' value='" . htmlspecialchars($warning_date) . "' onchange='this.form.submit()'>";
echo "</div>";
echo "<button type='button' class='btn btn-primary btn-sm' onclick='window.print()'>Print Report</button>";
echo "<a class='btn btn-secondary btn-sm' href='adminpanel.php'>Back</a>";
echo "</form>";
echo "</div>";
echo "</div>";

// Print Header
echo "<h3 class='d-none d-print-block text-center'>Credit Report: " . htmlspecialchars($saleman_name ?: $saleman_id) . "</h3>";

// Render Table
echo "<div class='table-responsive'>";
echo "<table class='table table-bordered table-sm table-striped table-custom'>";
echo "<thead class='table-dark'>";
echo "<tr>";
echo "<th>Area</th>";
echo "<th>Shop</th>";
echo "<th>Bill ID</th>";
echo "<th>Bill Date</th>";
echo "<th class='text-end'>Bill Amount</th>";
echo "<th class='text-end'>Remaining</th>";
echo "<th class='text-center'>Age (Days)</th>";
echo "</tr>";
echo "</thead>";
echo "<tbody>";

if(empty($report)){
    echo "<tr><td colspan='7' class='text-center'>No outstanding bills found.</td></tr>";
} else {
    foreach($report as $areaName => $shops){
        $area_total = 0;
        foreach($shops as $shopName => $bills){
            foreach($bills as $bill){
                $rem = floatval($bill['remaining_amount']);
                $area_total += $rem;
                
                // Color code age based on warning date
                $is_overdue = ($bill['bill_date'] < $warning_date);
                $ageClass = $is_overdue ? 'text-danger fw-bold' : '';
                
                echo "<tr>";
                echo "<td>" . htmlspecialchars($areaName) . "</td>";
                echo "<td>" . htmlspecialchars($shopName) . "</td>";
                echo "<td>" . htmlspecialchars($bill['bill_id']) . "</td>";
                echo "<td>" . htmlspecialchars($bill['bill_date']) . "</td>";
                echo "<td class='text-end'>" . number_format($bill['bill_amount'],2) . "</td>";
                echo "<td class='text-end'>" . number_format($rem,2) . "</td>";
                echo "<td class='text-center $ageClass'>" . $bill['bill_age'] . "</td>";
                echo "</tr>";
            }
        }
        // Area Total Row
        echo "<tr class='area-total-row'>";
        echo "<td colspan='5' class='text-end'>Total for " . htmlspecialchars($areaName) . ":</td>";
        echo "<td class='text-end'>" . number_format($area_total, 2) . "</td>";
        echo "<td></td>";
        echo "</tr>";
    }
    
    // Grand Total Row
    echo "<tr class='table-dark border-white'>";
    echo "<td colspan='5' class='text-end fw-bold'>GRAND TOTAL OUTSTANDING:</td>";
    echo "<td class='text-end fw-bold'>" . number_format($total_outstanding, 2) . "</td>";
    echo "<td></td>";
    echo "</tr>";
}

echo "</tbody>";
echo "</table>";
echo "</div>";

?>
</div>
<script src="js/jquery.js"></script>
<script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>

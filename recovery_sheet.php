<?php
include_once('functionality/components/session_chk_admin.php');
include_once('functionality/components/condb.php');
$rec_id = isset($_GET['rec_id']) ? (int) $_GET['rec_id'] : 0;
$query="SELECT a.recovery_id AS ID, b.saleman_name AS Saleman, a.recovery_date AS Date,
           a.recovery_sheet_amount AS Amount, a.recovery_sheet_recovery AS Recoverd,
           a.sheet_status AS Status,
           (SELECT GROUP_CONCAT(DISTINCT NULLIF(TRIM(r.day), '') ORDER BY r.day SEPARATOR ', ')
        FROM recovery_sheet_detail AS rsd
        JOIN bill AS rb ON rb.bill_id = rsd.recovery_sheet_bill_id
        JOIN customer AS rc ON rc.customer_id = rb.cutomer_id
        LEFT JOIN routes AS r ON r.route_id = rc.customer_route
        WHERE rsd.recovery_sheet_id = a.recovery_id) AS RouteDays
    FROM recovery_sheet AS a
    LEFT JOIN salesman AS b ON a.recovery_sheet_saleman_id = b.saleman_id
    WHERE a.recovery_id = {$rec_id}";
//echo $query;
$result = mysqli_query($conn, $query);
$saleman_name='';
$recovery_date='';
$route_days='';
// Check if the query executed successfully
if (!$result) {
    die('Query failed: ' . mysqli_error($conn));
}
else{
    while($row=mysqli_fetch_assoc($result)){
        $saleman_name=$row['Saleman'];
        $recovery_date=$row['Date'];
        $route_days=$row['RouteDays'] ?: 'Not specified';
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/bootstrap.css">
    <script src="js/bootstrap.bundle.min.js"></script>
    <title><?php echo $rec_id ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', 'Helvetica', sans-serif;
            background: #f5f5f5;
            padding: 20px;
            color: #333;
        }

        .document-container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }

        header {
            border-bottom: 3px solid #2c3e50;
            padding-bottom: 20px;
            margin-bottom: 25px;
        }

        header .row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
        }

        header .col {
            flex: 1;
        }

        header .h3 {
            font-size: 24pt;
            font-weight: bold;
            color: #2c3e50;
            margin: 0;
        }

        header .h6 {
            font-size: 11pt;
            color: #555;
            margin: 0;
        }

        .mytable {
            width: 100%;
            border-collapse: collapse;
            margin: 0 auto;
            font-size: 12pt;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .mytable thead {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: white;
        }

        .mytable th {
            padding: 12px 8px;
            font-weight: 600;
            text-align: center;
            border: 1px solid #2c3e50;
            font-size: 11pt;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .mytable td {
            padding: 10px 8px;
            border: 1px solid #ddd;
            text-align: center;
            font-size: 11pt;
        }

        .mytable tbody tr:nth-child(even) {
            background-color: #f8f9fa;
        }

        .mytable tbody tr:nth-child(odd) {
            background-color: #ffffff;
        }

        .mytable tbody tr:hover {
            background-color: #e8f4f8;
            transition: background-color 0.2s ease;
        }

        .mytable tfoot tr {
            background: linear-gradient(135deg, #34495e 0%, #2c3e50 100%);
            color: white;
            font-weight: bold;
        }

        .mytable tfoot th {
            padding: 12px 8px;
            border: 1px solid #2c3e50;
            font-size: 12pt;
        }

        .print-button {
            position: static;
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            color: white;
            border: none;
            padding: 12px 24px;
            font-size: 11pt;
            font-weight: 600;
            border-radius: 6px;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(52, 152, 219, 0.3);
            transition: all 0.3s ease;
            z-index: 1000;
        }

        .print-button:hover {
            background: linear-gradient(135deg, #2980b9 0%, #21618c 100%);
            box-shadow: 0 6px 16px rgba(52, 152, 219, 0.4);
            transform: translateY(-2px);
        }

        .print-button:active {
            transform: translateY(0);
        }

        @media print {
            @page {
                margin: 0.5cm;
                size: A4;
            }
            
            body {
                background: white;
                padding: 0;
                margin: 0;
                color: #000;
            }
            
            .document-container {
                box-shadow: none;
                padding: 4px;
                max-width: 100%;
                margin: 0;
            }
            
            .print-button {
                display: none !important;
            }
            
            header {
                border-bottom: 1.5px solid #000;
                page-break-after: avoid;
                padding-bottom: 5px;
                margin-bottom: 6px;
            }
            
            header .row {
                gap: 8px;
            }
            
            header .h3 {
                color: #000;
                font-size: 16pt;
            }
            
            header .h6 {
                font-size: 12pt;
                color: #000;
            }
            
            .mytable {
                page-break-inside: auto;
                box-shadow: none;
                font-size: 12pt;
                line-height: 1.05;
            }
            
            .mytable thead {
                background: #fff !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
                color: #000 !important;
            }
            
            .mytable th {
                background: #fff !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
                color: #000 !important;
                border: 0.75px solid #000;
                padding: 2px 3px;
                font-size: 12pt;
                font-weight: 700;
                line-height: 1.05;
            }
            
            .mytable tbody tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }
            
            .mytable tbody tr:nth-child(even) {
                background-color: #f0f0f0 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            
            .mytable tbody tr:nth-child(odd) {
                background-color: #ffffff !important;
            }
            
            .mytable tbody tr:hover {
                background-color: inherit !important;
            }
            
            .mytable td {
                color: #000;
                border: 0.75px solid #000;
                padding: 2px 3px;
                font-size: 12pt;
                font-weight: 600;
                line-height: 1.05;
            }
            
            .mytable tfoot tr {
                background: #fff !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
                color: #000 !important;
            }
            
            .mytable tfoot th {
                background: #fff !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
                color: #000 !important;
                border: 0.75px solid #000;
                padding: 2px 3px;
                font-size: 12pt;
                font-weight: 700;
            }
        }
        .document-container {
            --sheet-font-size: 12pt;
        }

        .document-container header .h3 {
            font-size: calc(var(--sheet-font-size) * 2) !important;
        }

        .document-container header .h6,
        .document-container .mytable,
        .document-container .mytable td {
            font-size: var(--sheet-font-size) !important;
        }

        .document-container .mytable th {
            font-size: calc(var(--sheet-font-size) * 0.9) !important;
        }

        .document-container .area-heading {
            font-size: calc(var(--sheet-font-size) * 1.2) !important;
            margin: 0 0 8px;
            page-break-after: avoid;
            padding: 6px 8px;
            border-left: 4px solid #3498db;
            background: #eef4f6;
            color: #2c3e50;
        }

        .document-container .grand-total {
            font-size: calc(var(--sheet-font-size) * 1.1) !important;
            padding-top: 10px;
            border-top: 2px solid #2c3e50;
            text-align: right;
            font-weight: bold;
        }

        .document-container .area-section {
            margin-bottom: 20px;
        }

        .preview-toolbar .print-button {
            top: auto;
            right: auto;
            z-index: auto;
            box-shadow: none;
        }

        .preview-toolbar .print-button:hover {
            transform: none;
        }

        .preview-toolbar {
            max-width: 1200px;
            margin: 0 auto 12px;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            background: white;
            padding: 12px 16px;
            box-shadow: 0 0 12px rgba(0, 0, 0, 0.08);
        }

        .font-control {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 11pt;
        }

        .font-control input {
            width: min(180px, 35vw);
        }

        .preview-label {
            max-width: 1200px;
            margin: 0 auto 8px;
            color: #555;
            font-size: 10pt;
            font-weight: 600;
            text-transform: uppercase;
        }

        @media print {
            .preview-toolbar,
            .preview-label {
                display: none !important;
            }
        }
    </style>
</head>

<body>
    <div class="preview-toolbar">
        <button class="print-button" onclick="window.print()">Print Recovery Sheet</button>
        <label class="font-control" for="fontSize">
            Font size
            <input id="fontSize" type="range" min="8" max="18" value="12">
            <output id="fontSizeValue" for="fontSize">12 pt</output>
        </label>
    </div>
    <div class="preview-label">Live print preview</div>
    <div class="document-container">
    <header>
        <div class="row text-center">
            <div class="col col-3 h6">
                Date: <?php echo htmlspecialchars($recovery_date, ENT_QUOTES, 'UTF-8');?><br>
                Planned Route Day(s): <?php echo htmlspecialchars($route_days, ENT_QUOTES, 'UTF-8');?>
            </div>
            <div class="col col-6 h3">
            <?php echo htmlspecialchars($saleman_name, ENT_QUOTES, 'UTF-8');?>
            </div>
            <div class="col col-3 h6">
            Recovery Sheet#<?php echo htmlspecialchars((string) $rec_id, ENT_QUOTES, 'UTF-8');?>
            </div>
        </div>
    </header>
    <?php
    $query = "SELECT a.bill_id AS ID, a.bill_date AS Date,
                     IF(DATEDIFF(NOW(), a.bill_date) < 0,
                        DATEDIFF(NOW(), STR_TO_DATE(CONCAT(YEAR(a.bill_date), '-', DAY(a.bill_date), '-', MONTH(a.bill_date)), '%Y-%m-%d')),
                        DATEDIFF(NOW(), a.bill_date)) AS Days,
                     b.customer_id AS Shop_id, b.customer_name AS Shop,
                     COALESCE(NULLIF(TRIM(s.sector_name), ''), 'Unknown Area') AS Area,
                     bl.bill_amount AS `Actual Amount`, a.bill_amount AS Amount
              FROM bill AS a
              JOIN customer AS b ON a.cutomer_id = b.customer_id
              LEFT JOIN routes AS r ON b.customer_route = r.route_id
              LEFT JOIN sector AS s ON r.sector_id = s.sector_id
              LEFT JOIN (SELECT bill_id, MAX(bill_amount) AS bill_amount FROM bill_ledger GROUP BY bill_id) AS bl ON a.bill_id = bl.bill_id
              WHERE a.bill_id IN (SELECT recovery_sheet_bill_id FROM recovery_sheet_detail WHERE recovery_sheet_id = {$rec_id})
              ORDER BY Area ASC, Days DESC";
    $responce = mysqli_query($conn, $query);
    if (!$responce) {
        die('Recovery sheet details query failed: ' . mysqli_error($conn));
    }

    $areas = [];
    $grandTotal = 0;
    while ($row = mysqli_fetch_assoc($responce)) {
        $area = $row['Area'] ?: 'Unknown Area';
        $areas[$area][] = $row;
        $grandTotal += (float) $row['Amount'];
    }

    foreach ($areas as $area => $areaRows) {
        $areaTotal = 0;
        ?>
        <section class="area-section">
            <h2 class="area-heading"><?php echo htmlspecialchars($area, ENT_QUOTES, 'UTF-8'); ?></h2>
            <table class="mytable">
                <thead>
                    <tr>
                        <th>Bill ID</th>
                        <th>DATE</th>
                        <th>Days</th>
                        <th>Shop id</th>
                        <th>Shop</th>
                        <th>Actual Amount</th>
                        <th>Amount</th>
                        <th>Recovered</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($areaRows as $row) {
                        $areaTotal += (float) $row['Amount'];
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars((string) $row['ID'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars((string) $row['Date'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars((string) $row['Days'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars((string) $row['Shop_id'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars((string) $row['Shop'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars((string) $row['Actual Amount'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars((string) $row['Amount'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td></td>
                        </tr>
                    <?php } ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="5">Area Total</th>
                        <th></th>
                        <th><?php echo number_format($areaTotal, 2); ?></th>
                        <th></th>
                    </tr>
                </tfoot>
            </table>
        </section>
    <?php } ?>
    <?php if (!$areas) { ?>
        <p>No shops are listed on this recovery sheet.</p>
    <?php } ?>
    <div class="grand-total">Recovery Sheet Total: <?php echo number_format($grandTotal, 2); ?></div>
    </div>
    <script>
        const fontSizeControl = document.getElementById('fontSize');
        const fontSizeValue = document.getElementById('fontSizeValue');
        const recoverySheet = document.querySelector('.document-container');

        fontSizeControl.addEventListener('input', () => {
            const size = fontSizeControl.value;
            recoverySheet.style.setProperty('--sheet-font-size', `${size}pt`);
            fontSizeValue.value = `${size} pt`;
        });
    </script>
</body>

</html>
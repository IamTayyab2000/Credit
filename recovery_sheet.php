<?php
include_once('functionality/components/session_chk_admin.php');
include_once('functionality/components/condb.php');
$rec_id = $_GET['rec_id'];
$query='SELECT a.recovery_id As "ID",b.saleman_name AS "Saleman",a.recovery_date As "Date",a.recovery_sheet_amount As "Amount",a.recovery_sheet_recovery As "Recoverd",a.sheet_status As "Status" from recovery_sheet as a left join salesman as b on a.recovery_sheet_saleman_id=b.saleman_id where a.recovery_id='.$rec_id;
//echo $query;
$result = mysqli_query($conn, $query);
$saleman_name='';
$recovery_date='';
// Check if the query executed successfully
if (!$result) {
    die('Query failed: ' . mysqli_error($conn));
}
else{
    while($row=mysqli_fetch_assoc($result)){
        $saleman_name=$row['Saleman'];
        $recovery_date=$row['Date'];
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
            position: fixed;
            top: 20px;
            right: 20px;
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            color: white;
            border: none;
            padding: 12px 24px;
            font-size: 14pt;
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
            }
            
            .document-container {
                box-shadow: none;
                padding: 8px;
                max-width: 100%;
                margin: 0;
            }
            
            .print-button {
                display: none !important;
            }
            
            header {
                border-bottom: 1.5px solid #000;
                page-break-after: avoid;
                padding-bottom: 8px;
                margin-bottom: 10px;
            }
            
            header .row {
                gap: 8px;
            }
            
            header .h3 {
                color: #000;
                font-size: 16pt;
            }
            
            header .h6 {
                font-size: 9pt;
            }
            
            .mytable {
                page-break-inside: auto;
                box-shadow: none;
                font-size: 9pt;
            }
            
            .mytable thead {
                background: #2c3e50 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
                color: white !important;
            }
            
            .mytable th {
                background: #2c3e50 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
                color: white !important;
                border: 0.5px solid #000;
                padding: 4px 3px;
                font-size: 8pt;
                line-height: 1.2;
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
                border: 0.5px solid #999;
                padding: 3px 2px;
                font-size: 9pt;
                line-height: 1.2;
            }
            
            .mytable tfoot tr {
                background: #2c3e50 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
                color: white !important;
            }
            
            .mytable tfoot th {
                background: #2c3e50 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
                color: white !important;
                border: 0.5px solid #000;
                padding: 4px 3px;
                font-size: 9pt;
            }
            
            .footer-note {
                page-break-inside: avoid;
                margin-top: 8px;
                padding: 6px 8px;
                font-size: 8pt;
                line-height: 1.3;
            }
            
            .footer-note h4 {
                font-size: 9pt;
                margin-bottom: 3px;
            }
            
            .footer-note p {
                margin: 2px 0;
            }
        }

        .footer-note {
            margin-top: 25px;
            padding: 15px 20px;
            background: #fff3cd;
            border-left: 4px solid #ff9800;
            border-radius: 4px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .footer-note h4 {
            margin: 0 0 10px 0;
            color: #856404;
            font-size: 13pt;
            font-weight: 600;
        }

        .footer-note p {
            margin: 8px 0;
            color: #533f03;
            font-size: 11pt;
            line-height: 1.6;
        }

        .footer-note p.urdu {
            direction: rtl;
            text-align: right;
            font-family: 'Jameel Noori Nastaleeq', 'Noto Nastaliq Urdu', serif;
            font-size: 12pt;
        }
    </style>
</head>

<body>
    <button class="print-button" onclick="window.print()">🖨️ Print Recovery Sheet</button>
    <div class="document-container">
    <header>
        <div class="row text-center">
            <div class="col col-3 h6">
                Date:<?php echo $recovery_date;?>
            </div>
            <div class="col col-6 h3">
            <?php echo $saleman_name;?>
            </div>
            <div class="col col-3 h6">
            Recovery Sheet#<?php echo $rec_id;?>
            </div>
        </div>
    </header>
    <table class="mytable">
        <thead>
            <th>Bill ID</th>
            <th>DATE</th>
            <th>Days</th>
            <th>Shop</th>
            <th>Amount</th>
            <th>Recovered</th>
            <th>Return</th>
            <th>Remaining</th>
            <th>BF/NILL/Return</th>
        </thead>
        <tbody>
            <?php
            $query="SELECT a.bill_id AS 'ID', a.bill_date AS 'Date', DATEDIFF(NOW(), a.bill_date) AS 'Days', b.customer_name AS 'Shop', a.bill_amount AS 'Amount' FROM `bill` AS a JOIN `customer` AS b ON a.cutomer_id = b.customer_id WHERE a.bill_id IN (SELECT recovery_sheet_bill_id FROM recovery_sheet_detail WHERE recovery_sheet_id = {$rec_id}) ORDER BY `Days` DESC;";
            //echo $query;
            //print_r($query);
            $responce=mysqli_query($conn,$query);
            $Total=0;
            if($responce){
                while($row=mysqli_fetch_assoc($responce)){
                    echo "<tr>
                        <td>{$row['ID']}</td>
                        <td>{$row['Date']}</td>
                        <td>{$row['Days']}</td>
                        <td>{$row['Shop']}</td>
                        <td>{$row['Amount']}</td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                    <tr>";
                    $Total+=(int)$row['Amount'];
                }
            }
            ?>
        </tbody>
        <tfoot>
            <tr>
                <th></th>
                <th></th>
                <th></th>
                <th>Total</th>
                <th><?php echo $Total; ?></th>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
            </tr>
        </tfoot>
    </table>
    
    <div class="footer-note">
        <h4>⚠️ Important Billing Instructions / اہم بلنگ ہدایات</h4>
        <p><strong>English:</strong> No double billing allowed. Only bill existing customers in the system. New customers must be billed in cash with their information added to the billing system immediately.</p>
        <p class="urdu"><strong>اردو:</strong> ڈبل بلنگ کی اجازت نہیں۔ صرف سسٹم میں موجود کسٹمرز کو بل کریں۔ نئے کسٹمرز کو نقد بل کریں اور فوری طور پر ان کی معلومات بلنگ سسٹم میں شامل کریں۔</p>
    </div>
    </div>
</body>

</html>
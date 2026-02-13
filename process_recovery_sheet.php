<?php
include_once('functionality/components/session_chk_admin.php');
include_once('functionality/components/crud.php');

// Helper to safely echo HTML
function h($s){ return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

$rec_id = $_GET["rec_id"];
$query="SELECT
CASE
    WHEN sheet_status = '1' THEN TRUE
    WHEN sheet_status = '0' THEN FALSE
END AS Processed
FROM
recovery_sheet
WHERE
recovery_id ={$rec_id} ";
$responce=read($query);
$responce=json_decode($responce,true);
//echo ;
if($responce[0]['Processed']){
    echo '<script>
    alert("Processed Recovery Sheet can not be accessed...");
    window.close();
    
    </script>';
}
$query = 'SELECT a.recovery_id As "ID",b.saleman_name AS "Saleman",a.recovery_date As "Date",a.recovery_sheet_amount As "Amount",a.recovery_sheet_recovery As "Recoverd",a.sheet_status As "Status" from recovery_sheet as a left join salesman as b on a.recovery_sheet_saleman_id=b.saleman_id where a.recovery_id=' . $rec_id;
$result = mysqli_query($conn, $query);
$saleman_name = '';
$recovery_date = '';
// Check if the query executed successfully
if (!$result) {
    die('Query failed: ' . mysqli_error($conn));
} else {
    while ($row = mysqli_fetch_assoc($result)) {
        $saleman_name = $row['Saleman'];
        $recovery_date = $row['Date'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/bootstrap.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/main.css">
    <script src="js/jquery.js"></script>
    <title>Process Recovery Sheet - Admin</title>
    <style>
        body { font-size: 0.875rem; }
        .card-body { padding: 1.25rem !important; }
        .table > :not(caption) > * > * { padding: 0.5rem 0.5rem !important; }
        .form-control-compact {
            padding: 0.25rem 0.4rem;
            font-size: 0.8rem;
            height: auto;
            border-radius: 4px;
        }
        .form-select-compact {
            padding: 0.25rem 1.5rem 0.25rem 0.4rem;
            font-size: 0.8rem;
            height: auto;
            border-radius: 4px;
        }
        .letter-spacing-1 { letter-spacing: 0.5px; }
        .h4-compact { font-size: 1.1rem; }
        .h3-compact { font-size: 1.25rem; }
        .badge-compact { padding: 0.35em 0.65em; font-size: 0.75em; }
    </style>
</head>

<body class="bg-light">
    <header>
        <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm mb-3">
            <div class="container">
                <a class="navbar-brand font-weight-bold py-1" href="adminpanel.php">
                    <span class="text-primary">CREDIT</span> SYSTEM
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item"><a class="nav-link py-1" href="adminpanel.php">Dashboard</a></li>
                        <li class="nav-item"><a class="nav-link py-1" href="creditReport.php">Reports</a></li>
                        <li class="nav-item"><a class="nav-link py-1 active" href="insertDSR.php">DSR Entry</a></li>
                        <li class="nav-item"><a class="nav-link py-1" href="insertCustomers.php">Customers</a></li>
                        <li class="nav-item"><a class="nav-link py-1" href="IssueBills.php">Issue Bills</a></li>
                        <li class="nav-item"><a class="nav-link py-1" href="generate_recovery_sheet.php">Recovery</a></li>
                    </ul>
                </div>
            </div>
        </nav>
    </header>

    <main class="container mb-4">
        <div class="row g-3">
            <!-- Header Info Card -->
            <div class="col-12">
                <div class="card shadow-sm border-0 bg-primary text-white overflow-hidden">
                    <div class="card-body p-3 position-relative">
                        <div class="row align-items-center">
                            <div class="col-md-4">
                                <div class="small opacity-75 text-uppercase letter-spacing-1">Recovery Date</div>
                                <div class="h4-compact font-weight-bold mb-0"><?php echo date('d M, Y', strtotime($recovery_date)); ?></div>
                            </div>
                            <div class="col-md-4 text-center border-start border-end border-white border-opacity-25">
                                <div class="small opacity-75 text-uppercase letter-spacing-1">Salesman</div>
                                <div class="h3-compact font-weight-bold mb-0"><?php echo h($saleman_name); ?></div>
                            </div>
                            <div class="col-md-4 text-end">
                                <div class="small opacity-75 text-uppercase letter-spacing-1">Sheet Reference</div>
                                <div class="h4-compact font-weight-bold mb-0">#<?php echo $rec_id; ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recovery Table Area -->
            <div class="col-12">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 font-weight-bold">Bill Recovery Details</h6>
                        <span class="badge badge-compact bg-soft-info text-info">Pending Finalization</span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover table-sm align-middle mb-0" id='recovery_table' style="font-size: 0.85rem;">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="ps-3 py-2">Bill ID</th>
                                        <th class="py-2">Date</th>
                                        <th class="py-2">Age</th>
                                        <th class="py-2">Customer Shop</th>
                                        <th class="text-end py-2">Amount</th>
                                        <th class="text-center py-2" style="width: 110px;">Recovered</th>
                                        <th class="text-center py-2" style="width: 110px;">Returned</th>
                                        <th class="text-end py-2">Remaining</th>
                                        <th class="text-center pe-3 py-2" style="width: 150px;">Status Adjust</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php
                                $query="SELECT a.bill_id AS 'ID', a.bill_date AS 'Date', DATEDIFF(NOW(), a.bill_date) AS 'Days', b.customer_name AS 'Shop', a.bill_amount AS 'Amount' FROM `bill` AS a JOIN `customer` AS b ON a.cutomer_id = b.customer_id WHERE a.bill_id IN (SELECT recovery_sheet_bill_id FROM recovery_sheet_detail WHERE recovery_sheet_id = {$rec_id}) ORDER BY `Days` DESC;";
                                $responce=mysqli_query($conn,$query);
                                $Total=0;
                                if($responce){
                                    while($row=mysqli_fetch_assoc($responce)){
                                        echo "<tr>
                                            <td class='ps-3 font-weight-bold'>{$row['ID']}</td>
                                            <td class='small text-muted'>".date('d/m/y', strtotime($row['Date']))."</td>
                                            <td class='small'><span class='badge badge-compact bg-light text-dark'>{$row['Days']}d</span></td>
                                            <td class='font-weight-bold'>{$row['Shop']}</td>
                                            <td class='text-end' data-amount='{$row['Amount']}'>".number_format($row['Amount'])."</td>
                                            <td>
                                                <input type='number' class='form-control form-control-compact text-center btn-recovery' placeholder='0'>
                                            </td>
                                            <td>
                                                <input type='number' class='form-control form-control-compact text-center btn-returned' placeholder='0'>
                                            </td>
                                            <td class='text-end font-weight-bold'>
                                                <span class='txt_remaining text-primary'>".(int)$row['Amount']."</span>
                                            </td>
                                            <td class='pe-3'>
                                                <select class='form-select form-select-compact selc-status'>
                                                    <option value='0' selected>Active</option>
                                                    <option value='BF'>B/F (Keep)</option>
                                                    <option value='Nill'>Closed (Nill)</option>
                                                    <option value='Return'>Returned</option>
                                                </select>
                                            </td>
                                        </tr>";
                                        $Total+=(int)$row['Amount'];
                                    }
                                }
                                ?>
                                </tbody>
                                <tfoot class="bg-light font-weight-bold border-top">
                                    <tr>
                                        <td colspan="4" class="ps-3 py-2">Totals</td>
                                        <td class="text-end py-2"><?php echo number_format($Total); ?></td>
                                        <td class="text-center py-2"><span id="total_recovery" class="text-success">0</span></td>
                                        <td class="text-center py-2"><span id="total_returned" class="text-warning">0</span></td>
                                        <td class="text-end pe-3 py-2" colspan="2"><span id="total_remaining" class="text-primary h6 mb-0"><?php echo number_format($Total); ?></span></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer bg-white border-0 p-3">
                        <button class="btn btn-success btn-sm w-100 font-weight-bold shadow-sm py-2" id="btn-process">
                            Finalize Recovery & Update Balances
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Notification Box -->
        <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 1050">
            <div id="messageBox" class="toast hide shadow" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="toast-header bg-danger text-white py-1">
                    <strong class="me-auto small">Validation Error</strong>
                    <button type="button" class="btn-close btn-close-white" id="close_messageBox" style="font-size: 0.65rem;"></button>
                </div>
                <div class="toast-body bg-white small" id="messageBody"></div>
            </div>
        </div>
    </main>

    <script src="js/bootstrap.bundle.min.js"></script>
    <script type="module" src="js/process_rec_sheet.js"></script>
</body>

</html>

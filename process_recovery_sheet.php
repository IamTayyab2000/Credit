<?php
include_once('functionality/components/session_chk_admin.php');
include_once('functionality/components/crud.php');

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
    <script src="js/jquery.js"></script>
    <title>Welcome Admin</title>
</head>

<body>
    <header>
        <nav class="navbar navbar-expand-lg bg-body-tertiary">
            <div class="container-fluid">
                <a class="navbar-brand" href="#">Admin Panel</a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav">
                        <li class="nav-item">
                            <a class="nav-link active" aria-current="page" href="adminpanel.php">Home</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="creditReport.php">Credit Reports</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="insertDSR.php">Enter DSR</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="insertCustomers.php">Enter Customer</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="IssueBills.php">Issue Bills</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="generate_recovery_sheet.php">Recovery Sheets</a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>

    </header>
    <main>
        <div class="container">
            <div class="row bg-secondary text-light text-center m-2 p-2 rounded">
                <div class="col col-3 h4">
                    Date#<?php echo $recovery_date; ?>
                </div>
                <div class="col col-4 h3">
                    <?php echo $saleman_name; ?>
                </div>
                <div class="col col-3 h4">
                    Recovery Sheet# <?php echo $rec_id; ?>
                </div>
            </div>
            <div class="row">
                <table class="table" id='recovery_table'>
                    <thead>
                        <th>Bill ID</th>
                        <th>DATE</th>
                        <th>Days</th>
                        <th>Shop</th>
                        <th>Amount</th>
                        <th>Recovered</th>
                        <th>Remaining</th>
                        <th>BF/NILL/Return</th>
                    </thead>
                    <tbody>
                    <?php
            $query="SELECT a.bill_id AS 'ID', a.bill_date AS 'Date', DATEDIFF(NOW(), a.bill_date) AS 'Days', b.customer_name AS 'Shop', a.bill_amount AS 'Amount' FROM `bill` AS a JOIN `customer` AS b ON a.cutomer_id = b.customer_id WHERE a.bill_id IN (SELECT recovery_sheet_bill_id FROM recovery_sheet_detail WHERE recovery_sheet_id = {$rec_id}) ORDER BY `Days` DESC;";
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
                        <td>
                            <input type='number' class='form-control btn-recovery'>
                        </td>
                        <td>
                            <span class='txt_remaining'>".(int)$row['Amount']."</span>
                        </td>
                        <td>
                            <select name='' id='' class='form-select selc-status'>
                            <option value='0' selected>Select</option>
                            <option value='BF'>BF</option>
                            <option value='Nill'>Nill</option>
                            <option value='Return'>Return</option>
                            </select>
                        </td>
                    <tr>";
                    $Total+=(int)$row['Amount'];
                }
                echo "
                    <tr>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th>Total</th>
                        <th>{$Total}</th>
                        <th>
                            <span id='total_recovery'></span>
                        </th>
                        <th>
                            <span id='total_remaining'></span>
                        </th>
                        <th></th>
                    </tr>
                ";
            }
            ?>
                    </tbody>
                </table>
            </div>
            <div class="row"><button class="btn btn-success" id="btn-process">
                Process
            </button>
      
        </div>
        </div>
        <!-- Debugging -->
        <div class='m-3 row' id="messageBox">
            <div class="col bg-danger bg-gradient">
                <div class=" d-flex justify-content-between ">
                    <div class=" h3">Message:</div>
                    <a class="h3 text-light btn" style="text-decoration: none;" id='close_messageBox'>X</a>
                </div>
                <div id="messageBody" class="col h3 text-light p-2" style="background-color:#f08080;">
                </div>
            </div>
        </div>
        <!-- Debugging End -->
    </main>
    <footer>

    </footer>
    <script src="js/bootstrap.bundle.min.js"></script>
    <script type="module" src="js/process_rec_sheet.js"></script>
</body>

</html>
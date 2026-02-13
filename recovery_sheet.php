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
        /* Center-align the table */
        .mytable {
            margin: 0 auto;
            /* This centers the table horizontally */
            text-align: center;
            /* This centers the text within the cells */
        }

        /* Add padding to columns */
        .mytable th,
        .mytable td {
            padding: 10px;
            /* Adjust the padding value as needed */
        }

        /* Add borders to rows and columns */
        .mytable {
            border-collapse: collapse;
            border: 1px solid #ccc;
            /* Adjust the border style and color as needed */
        }

        .mytable th,
        .mytable td {
            border: 1px solid #ccc;
            /* Adjust the border style and color as needed */
        }
    </style>
</head>

<body>
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
                        <th></th>
                        <th></th>
                        <th></th>
                    </tr>
                ";
            }
            ?>
        </tbody>
    </table>
</body>

</html>
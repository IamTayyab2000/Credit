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
    <title>Document</title>
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
    <table class='mytable'>
    <thead>
        <tr>
            <th>Date</th>
            <th><?php echo $recovery_date?></th>
            <th>Saleman</th>
            <th><?php echo $saleman_name?></th>
            <th>Rec no#</th>
            <th><?php echo $rec_id?></th>
        </tr>
        <tr>
            <th>Bill ID</th>
            <th>Shop</th>
            <th>Amount</th>
            <th>Recived</th>
            <th>Returned</th>
            <th>Remaining</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        <?php
            $query="SELECT
            a.bill_id as 'Bill',
            b.customer_name as 'Shop',
            a.bill_amount as 'Amount',
            a.recived_amount as 'Recived',
            a.return_amount as 'Returned',
            a.remaining_amount as 'Remaining',
            CASE
                WHEN a.bill_status = 'INFILE' THEN 'Return'
                ELSE a.bill_status
            END AS bill_status
        FROM
            bill_ledger AS a
        JOIN
            customer AS b
        ON
            a.customer_id = b.customer_id
        WHERE
            a.ref_id = $rec_id;";
            //print_r($query);
            $responce=mysqli_query($conn,$query);
            $SheetTotal=0;
            $RcivedTotal=0;
            $ReturnedTotal=0;
            $RemainingTotal=0;
            if($responce){
                while($row=mysqli_fetch_assoc($responce)){
                    echo "<tr>
                        <td>{$row['Bill']}</td>
                        <td>{$row['Shop']}</td>
                        <td>{$row['Amount']}</td>
                        <td>{$row['Recived']}</td>
                        <td>{$row['Returned']}</td>
                        <td>{$row['Remaining']}</td>
                        <td>{$row['bill_status']}</td>
                    <tr>";
                    $SheetTotal+=(int)$row['Amount'];
                    $RcivedTotal+=(int)$row['Recived'];
                    $ReturnedTotal+=(int)$row['Returned'];
                    $RemainingTotal+=(int)$row['Remaining'];
                }
                echo "
                    <tr>
                        <th></th>
                        <th>Total</th>
                        <th>{$SheetTotal}</th>
                        <th>{$RcivedTotal}</th>
                        <th>{$ReturnedTotal}</th>
                        <th>{$RemainingTotal}</th>
                        <th></th>
                    </tr>
                ";
            }
        ?>
    </tbody>
    </table>
</body>
</html>
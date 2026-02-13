<?php
include('functionality/components/condb.php');
$bill_id = $_GET['bill_id'];
$sql = "SELECT a.ledger_id, a.ref_id, a.ledger_date, b.customer_name, a.bill_id, a.bill_amount, a.recived_amount, a.return_amount, a.remaining_amount, a.bill_status FROM `bill_ledger` as a LEFT JOIN customer as b ON a.customer_id = b.customer_id where a.bill_id='$bill_id'";

$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <style>
        /* Basic table styles */
        table {
            width: 100%;
            border-collapse: collapse;
        }

        table,
        th,
        td {
            border: 1px solid #ddd;
        }

        th,
        td {
            padding: 10px;
            text-align: left;
        }

        /* Header row styles */
        th {
            background-color: #f2f2f2;
        }

        /* Alternating row colors */
        tr:nth-child(even) {
            background-color: #f2f2f2;
        }

        /* Hover effect on rows */
        tr:hover {
            background-color: #dcdcdc;
        }
    </style>
</head>

<body>
    <div>
        <h1>
            Ledger for Bill : <?php echo $bill_id;?>
        </h1>
    </div>
    <table>
        <thead>
            <th>Leger ID</th>
            <th>Recovery Sheet</th>
            <th>Date</th>
            <th>Customer</th>
            <th>Bill ID</th>
            <th>Bill Amount</th>
            <th>Recoverd</th>
            <th>Return</th>
            <th>Remaining</th>
            <th>Status</th>
        </thead>
        <tbody>
            <?php
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . $row["ledger_id"] . "</td>";
                    echo "<td>" . $row["ref_id"] . "</td>";
                    echo "<td>" . $row["ledger_date"] . "</td>";
                    echo "<td>" . $row["customer_name"] . "</td>";
                    echo "<td>" . $row["bill_id"] . "</td>";
                    echo "<td>" . $row["bill_amount"] . "</td>";
                    echo "<td>" . $row["recived_amount"] . "</td>";
                    echo "<td>" . $row["return_amount"] . "</td>";
                    echo "<td>" . $row["remaining_amount"] . "</td>";
                    echo "<td>" . $row["bill_status"] . "</td>";
                    echo "</tr>";
                }
            } else {
                echo "0 results";
            }
            ?>
        </tbody>
    </table>
</body>

</html>

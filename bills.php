<?php include_once('functionality/components/session_chk_admin.php'); ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/bootstrap.css">
    <link rel="stylesheet" href="css/cdn.datatables.net_1.13.6_css_jquery.dataTables.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/main.css">
    <script src="js/jquery.js"></script>
    <script src="js/cdn.datatables.net_1.13.6_js_jquery.dataTables.min.js"></script>
    <title>Welcome Admin</title>
</head>

<body>
    <header>
    <?php include('functionality/components/admin_nav.php'); ?>
    </header>
    <main>
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
        <div class="container">
                <table class="table" id='bills'>
                    <thead>
                        <th>Bill ID</th>
                        <th>Shop ID</th>
                        <th>Shop Name</th>
                        <th>Bill Date</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>See Ledger</th>
                    </thead>
                    <tbody>

                    </tbody>
                </table>

        </div>
    </main>
    <footer>

    </footer>
    <script src="js/bootstrap.bundle.min.js"></script>
    <script type="module" src="js/bills.js"></script>
</body>

</html>

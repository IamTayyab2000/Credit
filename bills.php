<?php include_once('functionality/components/session_chk_admin.php') ?>
<?php include_once('functionality/components/session_chk_admin.php'); ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/bootstrap.css">
    <link rel="stylesheet" href="css/cdn.datatables.net_1.13.6_css_jquery.dataTables.min.css">
    <script src="js/jquery.js"></script>
    <script src="js/cdn.datatables.net_1.13.6_js_jquery.dataTables.min.js"></script>
    <title>Welcome Admin</title>
</head>

<body>
    <header>
    <nav class="navbar navbar-expand-lg bg-body-tertiary">
            <div class="container-fluid">
                <a class="navbar-brand" href="#">Credit</a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                    aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
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
                    </ul>
                </div>
            </div>
        </nav>
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

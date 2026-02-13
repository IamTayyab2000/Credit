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
    <title>Credit Reports | Credit Management</title>
</head>

<body>
    <header>
        <nav class="navbar navbar-expand-lg">
            <div class="container-fluid">
                <a class="navbar-brand" href="adminpanel.php">Credit Management</a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item"><a class="nav-link" href="adminpanel.php">Dashboard</a></li>
                        <li class="nav-item"><a class="nav-link active" href="creditReport.php">Reports</a></li>
                        <li class="nav-item"><a class="nav-link" href="insertDSR.php">DSR</a></li>
                        <li class="nav-item"><a class="nav-link" href="insertCustomers.php">Customers</a></li>
                        <li class="nav-item"><a class="nav-link" href="IssueBills.php">Issue Bills</a></li>
                        <li class="nav-item"><a class="nav-link" href="generate_recovery_sheet.php">Recovery</a></li>
                    </ul>
                </div>
            </div>
        </nav>
    </header>

    <main class="container">
        <h2 class="page-heading">Picklists & Recovery Reports</h2>

        <!-- Report Table Card -->
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <div class="d-flex justify-content-between align-items-center">
                    <span>Recovery Performance</span>
                    <i class="text-muted small">Updated in real-time</i>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive p-3">
                    <table id="picklistTable" class="table table-hover w-100">
                        <thead>
                            <tr>
                                <th>Picklist ID</th>
                                <th>Salesman</th>
                                <th>Date</th>
                                <th class="text-end">Total Amount</th>
                                <th class="text-end">Recovered</th>
                                <th class="text-end">Remaining</th>
                                <th class="text-center">Recovery %</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Data populated via credit_report.js -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Feedback UI -->
        <div id="messageBox" class="mt-4" style="display:none;">
            <div class="alert alert-danger d-flex justify-content-between align-items-center">
                <div id="messageBody"></div>
                <button type="button" class="btn-close" id="close_messageBox"></button>
            </div>
        </div>
    </main>

    <script src="js/bootstrap.bundle.min.js"></script>
    <script type="module" src="js/credit_report.js"></script>
</body>

</html>

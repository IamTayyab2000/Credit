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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        <div class="container p-2">
            <!-- Dashboard Analytics Section -->
            <div class="row mb-4">
                <!-- Status Cards -->
                <div class="col-md-4">
                    <div class="card text-white bg-danger mb-3 shadow">
                        <div class="card-header">Total Outstanding</div>
                        <div class="card-body">
                            <h2 class="card-title" id="dash_outstanding">...</h2>
                            <p class="card-text">Total pending amount in the market.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-white bg-success mb-3 shadow">
                        <div class="card-header">Monthly Recovery</div>
                        <div class="card-body">
                            <h2 class="card-title" id="dash_recovery">...</h2>
                            <p class="card-text">Total recovered this month.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-white bg-primary mb-3 shadow">
                        <div class="card-header">Monthly Sales</div>
                        <div class="card-body">
                            <h2 class="card-title" id="dash_sales">...</h2>
                            <p class="card-text">Total billed amount this month.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mb-4">
                <!-- Charts -->
                <div class="col-lg-8 mb-3">
                    <div class="card shadow h-100">
                        <div class="card-header font-weight-bold">Sales vs Recovery (Last 30 Days)</div>
                        <div class="card-body">
                            <canvas id="salesRecoveryChart"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 mb-3">
                    <div class="card shadow h-100">
                        <div class="card-header font-weight-bold">Top 5 Simplest Collectors</div>
                        <div class="card-body p-0">
                            <ul class="list-group list-group-flush" id="top_salesmen_list">
                                <li class="list-group-item text-center">Loading...</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Advanced Analytics Section -->
            <div class="row mb-4">
                <div class="col-lg-6 mb-3">
                    <div class="card shadow h-100">
                        <div class="card-header font-weight-bold">Salesman Efficiency (Recovery %)</div>
                        <div class="card-body">
                            <canvas id="salesmanEfficiencyChart"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 mb-3">
                    <div class="card shadow h-100">
                        <div class="card-header font-weight-bold">Debt Aging</div>
                        <div class="card-body">
                            <canvas id="agingChart"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 mb-3">
                    <div class="card shadow h-100 border-danger">
                        <div class="card-header bg-danger text-white font-weight-bold">At-Risk Customers</div>
                        <div class="card-body p-0" style="max-height: 300px; overflow-y: auto;">
                            <ul class="list-group list-group-flush" id="at_risk_list">
                                <li class="list-group-item text-center">Loading...</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-lg-6 mb-3">
                    <div class="card shadow h-100">
                        <div class="card-header font-weight-bold">Dead Zones (High Debt Sectors)</div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-sm table-striped mb-0">
                                    <thead>
                                        <tr>
                                            <th>Sector</th>
                                            <th class="text-end">Outstanding</th>
                                        </tr>
                                    </thead>
                                    <tbody id="dead_zones_table">
                                        <tr><td colspan="2" class="text-center">Loading...</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 mb-3">
                    <div class="card shadow h-100">
                        <div class="card-header font-weight-bold">Top Defaulters</div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-sm table-striped mb-0">
                                    <thead>
                                        <tr>
                                            <th>Customer</th>
                                            <th>Sector</th>
                                            <th class="text-end">Outstanding</th>
                                        </tr>
                                    </thead>
                                    <tbody id="top_defaulters_table">
                                        <tr><td colspan="3" class="text-center">Loading...</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- End Dashboard Section -->

            <div class="row border rounded">
                <div class="row h3">
                    Today Sales
                </div>
                <div class="row">
                    <div class="table-responsive">
                        <table class="table" id='today_sales'>
                            <thead>
                                <th>DSR ID</th>
                                <th>Saleman</th>
                                <th>Amount</th>
                                <th>Credit</th>
                                <th>Scheme</th>
                                <th>Return</th>
                                <th>Recived</th>
                            </thead>
                            <tbody>
    
                            </tbody>
                            <tfoot>
                                <th>Total</th>
                                <th></th>
                                <th></th>
                                <th></th>
                                <th></th>
                                <th></th>
                                <th></th>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
            <div class="row ">
                <div class="col col-6 border rounded">
                    <div class="row">
                        <h3>Standing Credit</h3>
                    </div>
                    <div class="row">
                    <div class="table-responsive">
                    <table class="table" id="standing_credit">
                    <thead>
                        <th>Saleman</th>
                        <th>Credit</th>
                        <th>Actions</th>
                    </thead>
                    <tbody>

                    </tbody>
                    <tfoot>
                        
                    </tfoot>
                </table>
                </div>
                    </div>
                </div>
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
    <?php $v = file_exists('js/admin_panel.js') ? filemtime('js/admin_panel.js') : time(); ?>
    <script type="module" src="js/admin_panel.js?v=<?php echo $v; ?>"></script>
</body>

</html>
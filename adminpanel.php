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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <title>Dashboard | Credit Management</title>
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
                        <li class="nav-item"><a class="nav-link active" href="adminpanel.php">Dashboard</a></li>
                        <li class="nav-item"><a class="nav-link" href="creditReport.php">Reports</a></li>
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
        <h2 class="page-heading">Dashboard Overview</h2>
        
        <!-- Status Cards -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card border-0 border-start border-danger border-5">
                    <div class="card-body">
                        <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Total Outstanding</div>
                        <div class="h2 mb-0 font-weight-bold" id="dash_outstanding">...</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 border-start border-success border-5">
                    <div class="card-body">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Monthly Recovery</div>
                        <div class="h2 mb-0 font-weight-bold" id="dash_recovery">...</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 border-start border-primary border-5">
                    <div class="card-body">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Monthly Sales</div>
                        <div class="h2 mb-0 font-weight-bold" id="dash_sales">...</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-lg-8">
                <div class="card h-100">
                    <div class="card-header">Sales vs Recovery (Last 30 Days)</div>
                    <div class="card-body">
                        <canvas id="salesRecoveryChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card h-100">
                    <div class="card-header">Top 5 Collectors</div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush" id="top_salesmen_list">
                            <li class="list-group-item text-center">Loading...</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header">Salesman Efficiency (Recovery %)</div>
                    <div class="card-body">
                        <canvas id="salesmanEfficiencyChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-lg-3">
                <div class="card h-100">
                    <div class="card-header">Debt Aging</div>
                    <div class="card-body">
                        <canvas id="agingChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-lg-3">
                <div class="card h-100">
                    <div class="card-header text-danger">At-Risk Customers</div>
                    <div class="card-body p-0" style="max-height: 300px; overflow-y: auto;">
                        <ul class="list-group list-group-flush" id="at_risk_list">
                            <li class="list-group-item text-center">Loading...</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-lg-6">
                <div class="card shadow h-100">
                    <div class="card-header font-weight-bold">Dead Zones (High Debt Sectors)</div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table mb-0">
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
            <div class="col-lg-6">
                <div class="card shadow h-100">
                    <div class="card-header font-weight-bold">Top Defaulters</div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table mb-0">
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

        <div class="card mt-4">
            <div class="card-header">Today's Sales Summary</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0" id='today_sales'>
                        <thead class="bg-light">
                            <tr>
                                <th>DSR ID</th>
                                <th>Salesman</th>
                                <th>Amount</th>
                                <th>Credit</th>
                                <th>Scheme</th>
                                <th>Return</th>
                                <th>Received</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                        <tfoot>
                            <tr class="font-weight-bold">
                                <td>Total</td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">Standing Credit by Salesman</div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0" id="standing_credit">
                                <thead class="bg-light">
                                    <tr>
                                        <th>Salesman</th>
                                        <th>Credit</th>
                                        <th>Actions</th>
                                        <th>Daily Report</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-6">
                <div class="card h-100 border-primary">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <span class="font-weight-bold">Backup Settings (Google Drive Sync)</span>
                        <button class="btn btn-sm btn-light font-weight-bold" id="btn_backup_now">Backup Now</button>
                    </div>
                    <div class="card-body">
                        <form id="backupSettingsForm">
                            <div class="mb-3">
                                <label for="backup_path" class="form-label font-weight-bold small">Local Backup Path</label>
                                <input type="text" class="form-control" id="backup_path" required placeholder="e.g. G:\My Drive\Backups">
                                <small class="text-muted">Ensure this path exists. If using Google Drive for Desktop, point this to your My Drive folder to auto-sync.</small>
                            </div>
                            <div class="row mb-3 align-items-center">
                                <div class="col-md-6">
                                    <div class="form-check form-switch mt-2">
                                        <input class="form-check-input" type="checkbox" id="auto_backup" style="cursor:pointer">
                                        <label class="form-check-label font-weight-bold small" for="auto_backup" style="cursor:pointer">Enable Auto-Backup</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label for="backup_frequency_hours" class="form-label font-weight-bold small mb-1">Frequency (Hours)</label>
                                    <select class="form-select" id="backup_frequency_hours">
                                        <option value="1">Every 1 Hour</option>
                                        <option value="6">Every 6 Hours</option>
                                        <option value="12">Every 12 Hours</option>
                                        <option value="24" selected>Daily (24 Hours)</option>
                                        <option value="48">Every 2 Days (48 Hours)</option>
                                        <option value="168">Weekly (168 Hours)</option>
                                    </select>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mt-4">
                                <div>
                                    <span class="small font-weight-bold text-muted d-block">Last Backup:</span>
                                    <span class="small text-primary" id="last_backup_time">Never</span>
                                </div>
                                <button type="submit" class="btn btn-success px-4" id="btn_save_backup_settings">Save Settings</button>
                            </div>
                        </form>
                    </div>
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
    <?php $v = file_exists('js/admin_panel.js') ? filemtime('js/admin_panel.js') : time(); ?>
    <script type="module" src="js/admin_panel.js?v=<?php echo $v; ?>"></script>
</body>

</html>
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
    <script src="js/bootstrap.bundle.min.js"></script>
    <title>Issue Bills | Credit Management</title>
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
                        <li class="nav-item"><a class="nav-link" href="creditReport.php">Reports</a></li>
                        <li class="nav-item"><a class="nav-link" href="insertDSR.php">DSR</a></li>
                        <li class="nav-item"><a class="nav-link" href="insertCustomers.php">Customers</a></li>
                        <li class="nav-item"><a class="nav-link active" href="IssueBills.php">Issue Bills</a></li>
                        <li class="nav-item"><a class="nav-link" href="generate_recovery_sheet.php">Recovery</a></li>
                    </ul>
                </div>
            </div>
        </nav>
    </header>

    <main class="container">
        <h2 class="page-heading">Issue Bills</h2>

        <div class="card mb-4 border-start border-success border-5">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-5">
                        <label class="form-label font-weight-bold">Recovery Salesman</label>
                        <select class="form-select" id="select_recovery_saleman"></select>
                    </div>
                    <div class="col-md-1 text-center h4 mt-4">OR</div>
                    <div class="col-md-6">
                        <label class="form-label font-weight-bold">Merge with Existing Recovery Sheet</label>
                        <div class="input-group">
                            <div class="input-group-text">
                                <input class="form-check-input mt-0" id="check_if_merge" type="checkbox">
                            </div>
                            <input type="number" class="form-control" id="mergeto_recovery_sheet_id" placeholder="Recovery Sheet ID" readonly>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4 bg-light">
            <div class="card-header">Quick Bulk Selection</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label small">Filter by Recovery Day</label>
                        <select class="form-select form-select-sm" id="filter_by_day">
                            <option value="">-- No Filter --</option>
                            <option value="Monday">Monday</option>
                            <option value="Tuesday">Tuesday</option>
                            <option value="Wednesday">Wednesday</option>
                            <option value="Thursday">Thursday</option>
                            <option value="Friday">Friday</option>
                            <option value="Saturday">Saturday</option>
                            <option value="Sunday">Sunday</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small">Filter by Source Salesman</label>
                        <select class="form-select form-select-sm" id="filter_by_salesman">
                            <option value="">-- No Filter --</option>
                        </select>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button class="btn btn-primary btn-sm me-2 w-100" id="btn_bulk_add_bills">Add Filtered Bills</button>
                        <button class="btn btn-outline-secondary btn-sm w-100" id="btn_clear_filters">Clear</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-9">
                <div class="card shadow-sm">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>Select Bills to Issue</span>
                        <span class="badge bg-primary rounded-pill" id="total_bills_count">0 selected</span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0" id="table_bills_info">
                                <thead>
                                    <tr>
                                        <th>Bill ID</th>
                                        <th>Customer</th>
                                        <th>Salesman</th>
                                        <th>Sector</th>
                                        <th>Date</th>
                                        <th>Amount</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3">
                <div class="card shadow-sm border-primary">
                    <div class="card-header bg-primary text-white">Issued List</div>
                    <div class="card-body p-0">
                        <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                            <table class="table table-sm mb-0" id="selected_bill_table">
                                <thead>
                                    <tr>
                                        <th>Bill ID</th>
                                        <th class="text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer bg-white">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted small">Total Bills:</span>
                            <span class="font-weight-bold" id="total_bills">0</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted small">Total Amount:</span>
                            <span class="font-weight-bold text-primary" id="total_bill_amount">0</span>
                        </div>
                    </div>
                </div>
                
                <div class="mt-4">
                    <button class="btn btn-outline-info w-100 mb-2" id="btn_preview_recovery_sheet" data-bs-toggle="modal" data-bs-target="#modal_preview_recovery_sheet">
                        <i class="bi bi-eye"></i> Preview Sheet
                    </button>
                    <button class="btn btn-success w-100" id="btn_generate_recovery_sheet">
                        <i class="bi bi-check-circle"></i> Generate Sheet
                    </button>
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

    <!-- Preview Modal Refined -->
    <div class="modal fade" id="modal_preview_recovery_sheet" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-light">
                    <h5 class="modal-title font-weight-bold">Recovery Sheet Preview</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-4 text-center">
                        <div class="col-md-3 border-end">
                            <div class="text-muted small text-uppercase">Salesman</div>
                            <div class="h5 font-weight-bold" id="preview_saleman_name">-</div>
                        </div>
                        <div class="col-md-3 border-end">
                            <div class="text-muted small text-uppercase">Target Date</div>
                            <div class="h5 font-weight-bold" id="preview_date">-</div>
                        </div>
                        <div class="col-md-3 border-end">
                            <div class="text-muted small text-uppercase">Bill Count</div>
                            <div class="h5 font-weight-bold" id="preview_total_bills">0</div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-muted small text-uppercase">Recovery Target</div>
                            <div class="h5 font-weight-bold text-success" id="preview_total_amount">0</div>
                        </div>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead class="bg-light">
                                <tr>
                                    <th>Bill ID</th>
                                    <th>Customer</th>
                                    <th>Salesman</th>
                                    <th>Sector</th>
                                    <th>Date</th>
                                    <th class="text-end">Amount</th>
                                </tr>
                            </thead>
                            <tbody id="preview_bills_table">
                                <tr><td colspan="6" class="text-center text-muted">No bills selected</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Back to Editing</button>
                    <button type="button" class="btn btn-success" id="btn_confirm_generate" data-bs-dismiss="modal">Confirm & Generate</button>
                </div>
            </div>
        </div>
    </div>

    <script type="module" src="js/issueBills.js"></script>
</body>

</html>
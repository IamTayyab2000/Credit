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
    <title>Enter DSR | Credit Management</title>
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
                        <li class="nav-item"><a class="nav-link active" href="insertDSR.php">DSR</a></li>
                        <li class="nav-item"><a class="nav-link" href="insertCustomers.php">Customers</a></li>
                        <li class="nav-item"><a class="nav-link" href="IssueBills.php">Issue Bills</a></li>
                        <li class="nav-item"><a class="nav-link" href="generate_recovery_sheet.php">Recovery</a></li>
                    </ul>
                </div>
            </div>
        </nav>
    </header>

    <main class="container">
        <h2 class="page-heading">Daily Sales Report (DSR) Entry</h2>

        <div class="row">
            <!-- DSR Header Info -->
            <div class="col-lg-12 mb-4">
                <div class="card shadow-sm border-start border-primary border-5">
                    <div class="card-header bg-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="font-weight-bold">Primary Picklist Information</span>
                            <span class="badge bg-soft-primary text-primary">Auto-Calculating</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label small">Picklist ID</label>
                                <input type="text" class="form-control" id="picklist_id" placeholder="Enter ID">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">Date</label>
                                <input type="date" class="form-control" id="picklist_date">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small">Salesman / Sector</label>
                                <select class="form-select" id="saleman_name_selector"></select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small">Total Amount</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rs.</span>
                                    <input type="number" class="form-control bg-light" id="picklist_amount" readonly>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small">Return Amount</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rs.</span>
                                    <input type="number" class="form-control bg-light" id="picklist_return" readonly>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small">Scheme / Discount</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rs.</span>
                                    <input type="number" class="form-control bg-light" id="picklist_scheme" readonly>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small">Net Credit</label>
                                <input type="number" class="form-control bg-light font-weight-bold" id="picklist_credit" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small">Total Recovery</label>
                                <input type="text" class="form-control bg-light font-weight-bold text-success" id="picklist_recovery" readonly>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Customer Credit Entry -->
            <div class="col-lg-12 mb-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-white font-weight-bold">Add Bill Details</div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-2">
                                <label class="form-label small">Inv ID</label>
                                <input type="text" class="form-control form-control-sm" id="customer_invoice_id">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small">Cust ID</label>
                                <input type="text" class="form-control form-control-sm" id='customer_id' list="customer_data_list">
                                <datalist id="customer_data_list"></datalist>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small">Customer Name</label>
                                <input type="text" class="form-control form-control-sm bg-light" id="customer_name" readonly>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small">Bill Total</label>
                                <input type="number" class="form-control form-control-sm" id="bill_total">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small">Recovered</label>
                                <input type="number" class="form-control form-control-sm" id="bill_recovered">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small">Returned</label>
                                <input type="number" class="form-control form-control-sm" id="bill_returned">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small">Scheme/Disc</label>
                                <input type="number" class="form-control form-control-sm" id="bill_scheme">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small">Credit (Bal)</label>
                                <input type="number" class="form-control form-control-sm bg-light" id="credit_amount" readonly>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label invisible d-block">Spacer</label>
                                <button class="btn btn-primary btn-sm w-100" id="btn_add_creditor_to_table">Add to List</button>
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <h6 class="font-weight-bold small uppercase letter-spacing-1 mb-3">DSR Entries Summary</h6>
                            <div class="table-responsive">
                                <table class="table table-hover table-sm" id="creditors_table">
                                    <thead class="bg-light">
                                        <tr>
                                            <th>Inv ID</th>
                                            <th>ID</th>
                                            <th>Customer Name</th>
                                            <th class="text-end">Total</th>
                                            <th class="text-end">Recovered</th>
                                            <th class="text-end">Returned</th>
                                            <th class="text-end">Scheme</th>
                                            <th class="text-end">Credit</th>
                                            <th class="text-center">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-white p-0">
                        <button class="btn btn-success btn-lg w-100 rounded-0 rounded-bottom py-3 font-weight-bold" id="btn_process_picklist">
                            Submit and Process DSR
                        </button>
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
    <script type="Module" src="js/dsr.js"></script>
</body>

</html>

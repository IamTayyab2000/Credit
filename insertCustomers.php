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
    <title>Customer Management | Credit Management</title>
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
                        <li class="nav-item"><a class="nav-link active" href="insertCustomers.php">Customers</a></li>
                        <li class="nav-item"><a class="nav-link" href="IssueBills.php">Issue Bills</a></li>
                        <li class="nav-item"><a class="nav-link" href="generate_recovery_sheet.php">Recovery</a></li>
                    </ul>
                </div>
            </div>
        </nav>
    </header>

    <main class="container">
        <h2 class="page-heading">Customer & Area Management</h2>

        <!-- Action Ribbon -->
        <div class="card mb-4 border-0 shadow-sm">
            <div class="card-body bg-light rounded d-flex flex-wrap gap-2 align-items-center">
                <span class="font-weight-bold me-auto">Quick Actions:</span>
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modal_add_sector">Add Sector</button>
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modal_set_sector">Set Route</button>
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modal_add_sales_person">Add Sales Person</button>
                <button class="btn btn-outline-success btn-sm" data-bs-toggle="modal" data-bs-target="#modal_import_from_excel">Import CSV</button>
            </div>
        </div>

        <div class="row">
            <!-- Customer Entry Form -->
            <div class="col-lg-12 mb-4">
                <div class="card shadow-sm border-start border-primary border-5">
                    <div class="card-header bg-white font-weight-bold">Register New Customer</div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label small">Customer ID</label>
                                <input type="text" class="form-control" id="customer_id" placeholder="e.g. CUST001">
                            </div>
                            <div class="col-md-5">
                                <label class="form-label small">Customer Name / Shop Name</label>
                                <input type="text" class="form-control" id="customer_name" placeholder="Enter Full Name">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small">Assigned Route</label>
                                <select id="customer_sector_id_select" class="form-select">
                                    <option selected>Select Route</option>
                                </select>
                            </div>
                            <div class="col-12 mt-4">
                                <button class="btn btn-primary px-4" id="btn_add_customer">Save Customer</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Customer List -->
            <div class="col-lg-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <span class="font-weight-bold">Customer Directory</span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive p-3">
                            <table class="table table-hover w-100" id='customer_table'>
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Route</th>
                                        <th>Salesman</th>
                                        <th class="text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modals Refined -->
        <!-- Modal Set Sectors -->
        <div class="modal fade" id="modal_set_sector" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header bg-light">
                        <h5 class="modal-title font-weight-bold">Assign Routes & Salesmen</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-2 mb-4">
                            <div class="col-md-4">
                                <select id="sector_id_select" class="form-select"></select>
                            </div>
                            <div class="col-md-4">
                                <select id="saleman_id_select" class="form-select"></select>
                            </div>
                            <div class="col-md-3">
                                <select id="day_select" class="form-select">
                                    <option value="Monday">Monday</option>
                                    <option value="Tuesday">Tuesday</option>
                                    <option value="Wednesday">Wednesday</option>
                                    <option value="Thursday">Thursday</option>
                                    <option value="Friday">Friday</option>
                                    <option value="Saturday">Saturday</option>
                                    <option value="Sunday">Sunday</option>
                                </select>
                            </div>
                            <div class="col-md-1">
                                <button class="btn btn-primary w-100" id="btn_add_route">+</button>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover" id='route_table'>
                                <thead class="bg-light">
                                    <tr>
                                        <th>Sector</th>
                                        <th>Salesman</th>
                                        <th>Day</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Add Sales Person -->
        <div class="modal fade" id="modal_add_sales_person" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header bg-light">
                        <h5 class="modal-title font-weight-bold">Manage Sales Personnel</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="input-group mb-4">
                            <input type="text" class="form-control" id="sales_person_name" placeholder="Full Name">
                            <button class="btn btn-primary px-4" id="btn_add_salesperson">Add</button>
                        </div>
                        <div class="table-responsive" style="max-height: 300px;">
                            <table class="table table-sm" id="saleman_table">
                                <thead class="bg-light"><tr><th>ID</th><th>Name</th></tr></thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Add Sector -->
        <div class="modal fade" id="modal_add_sector" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header bg-light">
                        <h5 class="modal-title font-weight-bold">Sector Management</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="input-group mb-4">
                            <input type="text" class="form-control" id="sector_name" placeholder="Sector Name">
                            <button class="btn btn-primary px-4" id="btn_add_sector">Add</button>
                        </div>
                        <hr>
                        <h6 class="small font-weight-bold uppercase mb-3">Update Existing</h6>
                        <div class="input-group mb-4">
                            <select id="to_update_sector_id_select" class="form-select"></select>
                            <input type="text" class="form-control" id="update_sector_input">
                            <button class="btn btn-success" id="btn_update_sector">Update</button>
                        </div>
                        <div class="table-responsive" style="max-height: 300px;">
                            <table class="table table-sm text-center" id="sector_table">
                                <thead class="bg-light"><tr><th>ID</th><th>Name</th></tr></thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Import from Excel -->
        <div class="modal fade" id="modal_import_from_excel" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header bg-light">
                        <h5 class="modal-title font-weight-bold">Bulk Import</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div id="csv_loading_screen" style="display: none;">
                            <div class="text-center py-5">
                                <div class="spinner-border text-primary mb-3"></div>
                                <p class="text-muted">Migrating data, please wait...</p>
                            </div>
                        </div>
                        <div id="csv_content">
                            <div class="alert alert-info py-2"><small>Requires: ID, Name, Area, Salesman, Bill, Remaining, Date</small></div>
                            <div class="input-group">
                                <input type="file" class="form-control" id="csv_file_input" accept=".csv">
                                <button class="btn btn-primary" id="btn_import_csv">Upload</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Edit Customer -->
        <div class="modal fade" id="modal_edit_customers" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header bg-light">
                        <h5 class="modal-title font-weight-bold">Edit Customer</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label small">Customer ID (Read-only)</label>
                            <input type="text" class="form-control bg-light" id="edit_customer_id" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small">Customer Name</label>
                            <input type="text" class="form-control" id="edit_customer_name">
                        </div>
                    </div>
                    <div class="modal-footer px-0 pb-0">
                        <button type="button" class="btn btn-primary w-100 py-3 rounded-0 rounded-bottom" id="btn_update_customer">Save Changes</button>
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
    <script type="Module" src="js/insert_customer.js"></script>
</body>

</html>
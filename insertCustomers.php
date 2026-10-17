<?php
include_once('functionality/components/session_chk_admin.php');
if (empty($_SESSION['bulk_customer_rename_csrf'])) {
    $_SESSION['bulk_customer_rename_csrf'] = bin2hex(random_bytes(32));
}
if (empty($_SESSION['recovery_correction_csrf'])) {
    $_SESSION['recovery_correction_csrf'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="bulk-rename-csrf" content="<?php echo htmlspecialchars($_SESSION['bulk_customer_rename_csrf'], ENT_QUOTES, 'UTF-8'); ?>">
    <meta name="recovery-correction-csrf" content="<?php echo htmlspecialchars($_SESSION['recovery_correction_csrf'], ENT_QUOTES, 'UTF-8'); ?>">
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
        <?php include('functionality/components/admin_nav.php'); ?>
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
                <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modal_import_customers">Import Customers</button>
                <button class="btn btn-outline-dark btn-sm" data-bs-toggle="modal" data-bs-target="#modal_bulk_rename_customers">Bulk Rename Customers</button>
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
                            <div class="col-md-3">
                                <label class="form-label small">Phone Number</label>
                                <input type="text" class="form-control" id="customer_phone" placeholder="e.g. 03001234567">
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
                    <div class="modal-dialog modal-xl modal-dialog-scrollable">
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
            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header bg-light">
                        <h5 class="modal-title font-weight-bold">Bulk Import</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div id="legacy_csv_message" class="alert d-none" role="status"></div>
                        <div id="csv_loading_screen" style="display: none;">
                            <div class="text-center py-5">
                                <div class="spinner-border text-primary mb-3"></div>
                                <p class="text-muted">Migrating data, please wait...</p>
                            </div>
                        </div>
                        <div id="csv_content">
                            <div class="alert alert-info py-2"><small>Required: Bill ID, Customer ID, Customer Name, Area, Salesman, Bill Amount, Remaining Credit, Bill Date. Phone is optional.</small></div>
                            <div class="input-group">
                                <input type="file" class="form-control" id="csv_file_input" accept=".csv">
                                <button class="btn btn-primary" id="btn_import_csv">Upload</button>
                            </div>
                        </div>
                        <div id="legacy_csv_mapping_stage" class="d-none">
                            <div id="legacy_csv_mapping_message" class="mb-3" aria-live="polite"></div>
                            <p class="small text-muted">Map each migration field to a CSV heading. Unused CSV columns will be ignored.</p>
                            <div class="table-responsive mb-3"><table class="table table-sm" id="legacy_csv_mapping_table"></table></div>
                            <div class="d-flex justify-content-end">
                                <button type="button" class="btn btn-primary" id="legacy_csv_preview">Preview data</button>
                            </div>
                        </div>
                        <div id="legacy_csv_preview_stage" class="d-none">
                            <div id="legacy_csv_preview_validation" class="mb-3" aria-live="polite"></div>
                            <div class="table-responsive" style="max-height: 55vh;">
                                <table class="table table-sm table-bordered align-middle" id="legacy_csv_preview_table"></table>
                            </div>
                            <div class="d-flex justify-content-between">
                                <button type="button" class="btn btn-outline-secondary" id="legacy_csv_back_to_mapping">Back to mapping</button>
                                <button type="button" class="btn btn-success" id="legacy_csv_confirm">Confirm migration</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Import Customers -->
        <div class="modal fade" id="modal_import_customers" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header bg-light">
                        <h5 class="modal-title font-weight-bold">Import Customers</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div id="customer_messageBox" class="mb-3" style="display:none;">
                            <div class="alert d-flex justify-content-between align-items-center">
                                <div id="customer_messageBody"></div>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        </div>
                        <div id="customer_loading_screen" style="display: none;">
                            <div class="text-center py-5">
                                <div class="spinner-border text-primary mb-3"></div>
                                <p class="text-muted">Importing customers, please wait...</p>
                            </div>
                        </div>
                        <div id="customer_content">
                            <div class="alert alert-info py-2"><small>Requires: Customer ID, Name, Area, Salesman, Day. Optional: Phone</small></div>
                            <div class="input-group">
                                <input type="file" class="form-control" id="customer_file_input" accept=".csv,.xlsx,.xls">
                                <button class="btn btn-primary" id="btn_import_customers">Upload</button>
                            </div>
                        </div>
                        <div id="customer_mapping_stage" class="d-none">
                            <div class="mb-3">
                                <label for="customer_import_sheet" class="form-label">Worksheet</label>
                                <div class="input-group">
                                    <select class="form-select" id="customer_import_sheet"></select>
                                    <button type="button" class="btn btn-outline-secondary" id="customer_import_load_sheet">Load sheet</button>
                                </div>
                            </div>
                            <div id="customer_import_mapping_area" class="d-none">
                                <p class="small text-muted">Map each customer field to a document heading. Unused document columns will be ignored.</p>
                                <div id="customer_import_mapping_message" aria-live="polite"></div>
                                <div class="table-responsive mb-3"><table class="table table-sm" id="customer_import_mapping_table"></table></div>
                                <div class="d-flex justify-content-end">
                                    <button type="button" class="btn btn-primary" id="customer_import_preview">Preview data</button>
                                </div>
                            </div>
                        </div>
                        <div id="customer_preview_stage" class="d-none">
                            <div id="customer_preview_validation" class="mb-3" aria-live="polite"></div>
                            <div class="table-responsive" style="max-height: 50vh;">
                                <table class="table table-sm table-bordered align-middle" id="customer_import_preview_table"></table>
                            </div>
                            <div class="d-flex justify-content-between">
                                <button type="button" class="btn btn-outline-secondary" id="customer_import_back_to_mapping">Back to mapping</button>
                                <button type="button" class="btn btn-success" id="customer_import_confirm">Confirm import</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="modal_bulk_rename_customers" tabindex="-1" aria-labelledby="bulkRenameCustomersLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header bg-light">
                        <h5 class="modal-title font-weight-bold" id="bulkRenameCustomersLabel">Bulk Rename Customers</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div id="bulk_rename_message" class="alert d-none" role="status"></div>
                        <div id="bulk_rename_upload_stage">
                            <p class="small text-muted">Upload a CSV or Excel file with a store code and its new customer name.</p>
                            <div class="input-group">
                                <input type="file" class="form-control" id="bulk_rename_file" accept=".csv,.xlsx,.xls">
                                <button type="button" class="btn btn-outline-secondary" id="bulk_rename_template">Download Template</button>
                                <button type="button" class="btn btn-primary" id="bulk_rename_upload">Continue</button>
                            </div>
                        </div>
                        <div id="bulk_rename_mapping_stage" class="d-none">
                            <div class="row g-2 align-items-end mb-3">
                                <div class="col-md-8">
                                    <label for="bulk_rename_sheet" class="form-label">Worksheet</label>
                                    <select class="form-select" id="bulk_rename_sheet"></select>
                                </div>
                                <div class="col-md-4">
                                    <button type="button" class="btn btn-outline-primary w-100" id="bulk_rename_load_sheet">Load Worksheet</button>
                                </div>
                            </div>
                            <div id="bulk_rename_column_mapping" class="d-none">
                                <p class="small text-muted">Choose the columns containing each value.</p>
                                <div class="row g-2 mb-3">
                                    <div class="col-md-6"><label for="bulk_rename_code_column" class="form-label">Store Code</label><select class="form-select" id="bulk_rename_code_column"></select></div>
                                    <div class="col-md-6"><label for="bulk_rename_name_column" class="form-label">New Customer Name</label><select class="form-select" id="bulk_rename_name_column"></select></div>
                                </div>
                                <div class="d-flex justify-content-end">
                                    <button type="button" class="btn btn-primary" id="bulk_rename_preview">Preview Changes</button>
                                </div>
                            </div>
                        </div>
                        <div id="bulk_rename_preview_stage" class="d-none">
                            <div id="bulk_rename_validation" class="mb-3" aria-live="polite"></div>
                            <div class="table-responsive" style="max-height: 55vh;">
                                <table class="table table-sm table-bordered align-middle">
                                    <thead class="table-light"><tr><th>Store Code</th><th>Current Name</th><th>New Name</th><th>Status</th></tr></thead>
                                    <tbody id="bulk_rename_rows"></tbody>
                                </table>
                            </div>
                            <div class="mb-3">
                                <label for="bulk_rename_correction_password" class="form-label">Correction Password</label>
                                <input type="password" class="form-control" id="bulk_rename_correction_password" autocomplete="off">
                                <div class="form-text">Required to apply customer name changes.</div>
                            </div>
                            <div class="d-flex justify-content-between">
                                <button type="button" class="btn btn-outline-secondary" id="bulk_rename_back">Back</button>
                                <button type="button" class="btn btn-success" id="bulk_rename_confirm">Apply Name Changes</button>
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
                        <div class="mb-3">
                            <label class="form-label small">Phone Number</label>
                            <input type="text" class="form-control" id="edit_customer_phone" placeholder="e.g. 03001234567">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small">Assigned Route</label>
                            <select class="form-select" id="edit_customer_route_id_select">
                                <option value="default" selected>Select Route</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small" for="edit_customer_correction_password">Correction Password</label>
                            <input type="password" class="form-control" id="edit_customer_correction_password" autocomplete="off" required>
                            <div class="form-text">Required to save customer changes.</div>
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
    <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
    <script type="Module" src="js/insert_customer.js"></script>
</body>

</html>
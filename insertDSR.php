<?php
include_once('functionality/components/session_chk_admin.php');
if (empty($_SESSION['dsr_edit_csrf'])) {
    $_SESSION['dsr_edit_csrf'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="dsr-edit-csrf" content="<?php echo htmlspecialchars($_SESSION['dsr_edit_csrf'], ENT_QUOTES, 'UTF-8'); ?>">
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
        <?php include('functionality/components/admin_nav.php'); ?>
    </header>

        <main class="container">
                <h2 class="page-heading">Daily Sales Report (DSR) Entry</h2>

        <section class="card shadow-sm mb-4" aria-labelledby="update-dsr-heading">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="h6 mb-0" id="update-dsr-heading">Update Existing DSR</h3>
                    <small class="text-muted">Edit recovered, returned, and scheme amounts for bills with no later recovery activity.</small>
                </div>
                <button class="btn btn-outline-primary btn-sm" type="button" id="load_dsr_updates">Load DSRs</button>
            </div>
            <div class="card-body">
                <div id="dsr_update_message" class="alert d-none" role="status"></div>
                <div class="row g-2 align-items-end mb-3">
                    <div class="col-md-8">
                        <label for="dsr_update_picklist" class="form-label">Select DSR</label>
                        <select id="dsr_update_picklist" class="form-select">
                            <option value="">Load DSRs to choose a sheet</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <button type="button" class="btn btn-secondary w-100" id="show_dsr_update" disabled>Update Selected DSR</button>
                    </div>
                </div>
                <div id="dsr_update_editor" class="table-responsive d-none">
                    <table class="table table-sm table-hover align-middle mb-2">
                        <thead class="table-light">
                            <tr><th>Invoice</th><th>Customer Number</th><th>Customer</th><th>Total</th><th>Recovered</th><th>Returned</th><th>Scheme</th><th>Remaining</th></tr>
                        </thead>
                        <tbody id="dsr_update_bill_list"></tbody>
                    </table>
                    <button type="button" class="btn btn-success" id="save_dsr_update">Save DSR Updates</button>
                </div>
            </div>
        </section>

                <!-- Modal for Open Credit Warning -->
                <div class="modal fade" id="openCreditModal" tabindex="-1" aria-labelledby="openCreditModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="openCreditModalLabel">Open Credit Warning</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div id="openCreditModalBody"></div>
                                <div id="openCreditBillsList"></div>
                                <p class="mt-2">This customer has previous open credit bills. Are you sure you want to add a new credit bill for this customer?</p>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="button" class="btn btn-warning" id="proceedAddCreditBill">Proceed Anyway</button>
                            </div>
                        </div>
                    </div>
                </div>

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
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <span class="font-weight-bold">Add Bill Details</span>
                        <div>
                            <input type="file" id="dsr_import_file" accept=".csv,.xls,.xlsx" class="d-none">
                            <button type="button" class="btn btn-outline-primary btn-sm" id="btn_import_dsr">
                                Import CSV/Excel
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="btn_download_dsr_sample">
                                Download Sample
                            </button>
                        </div>
                    </div>
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
                            <div class="small text-muted mb-2">For imports, use either Customer ID or Customer Name. A name must match exactly one customer assigned to the selected salesman.</div>
                            <div class="table-responsive">
                                <table class="table table-hover table-sm" id="creditors_table">
                                    <thead class="bg-light">
                                        <tr>
                                            <th>Inv ID</th>
                                            <th>Customer Number</th>
                                            <th>Customer Name</th>
                                            <th class="text-end">Total</th>
                                            <th class="text-end">Recovered</th>
                                            <th class="text-end">Returned</th>
                                            <th class="text-end">Scheme</th>
                                            <th class="text-end">Credit</th>
                                            <th class="text-end text-danger">Prev. Balance</th>
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

        <div class="modal fade" id="dsrImportModal" tabindex="-1" aria-labelledby="dsrImportModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="dsrImportModalLabel">DSR Preview/Edit</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div id="dsrImportSheetStage">
                            <label for="dsrImportSheet" class="form-label">Select worksheet</label>
                            <div class="input-group">
                                <select class="form-select" id="dsrImportSheet"></select>
                                <button type="button" class="btn btn-primary" id="dsrImportChooseSheet">Continue</button>
                            </div>
                        </div>
                        <div id="dsrImportMappingStage" class="d-none">
                            <p class="mb-2">Map each Excel column to a DSR field, or choose Do Not Import.</p>
                            <div id="dsrImportMappingMessage" class="mb-3" aria-live="polite"></div>
                            <div class="table-responsive"><table class="table table-sm" id="dsrImportMappingTable"></table></div>
                            <div class="d-flex justify-content-end gap-2">
                                <button type="button" class="btn btn-outline-secondary" id="dsrImportBackToSheets">Back</button>
                                <button type="button" class="btn btn-primary" id="dsrImportShowPreview">Preview data</button>
                            </div>
                        </div>
                        <div id="dsrImportPreviewStage" class="d-none">
                            <div id="dsrImportValidation" class="mb-3" aria-live="polite"></div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span id="dsrImportPreviewStatus" class="small text-muted"></span>
                                <button type="button" class="btn btn-outline-primary btn-sm" id="dsrImportAddRow">Add row</button>
                            </div>
                            <div class="table-responsive"><table class="table table-sm table-bordered align-middle" id="dsrImportPreviewTable"></table></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-success" id="dsrImportConfirm">Confirm Import</button>
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
    <script type="Module" src="js/dsr.js"></script>
</body>

</html>

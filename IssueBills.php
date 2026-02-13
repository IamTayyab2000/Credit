<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/bootstrap.css">
    <link rel="stylesheet" href="css/cdn.datatables.net_1.13.6_css_jquery.dataTables.min.css">
    <script src="js/jquery.js"></script>
    <script src="js/cdn.datatables.net_1.13.6_js_jquery.dataTables.min.js"></script>
    <script src="js/bootstrap.bundle.min.js"></script>
    <title>Issue Bills</title>
</head>

<body>
    <header>
        <nav class="navbar navbar-expand-lg bg-body-tertiary">
            <div class="container-fluid">
                <a class="navbar-brand" href="#">DSR</a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav">
                        <li class="nav-item">
                            <a class="nav-link" href="adminpanel.php">Home</a>
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
                            <a class="nav-link active" aria-current="page" href="IssueBills.php">Issue Bills</a>
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
        <div class="row m-2 p-2 bg-success bg-gradient  rounded">
            <div class="col-12 col-md-6">
                <div class="input-group">
                    <span class="input-group-text">Saleman</span>
                    <select class="form-select" id="select_recovery_saleman">
                    </select>
                </div>
            </div>
            <div class="col-12 col-md-6">
                <div class="input-group mb-3">
                <span class="input-group-text">Merge Recovery Sheet </span>
                    <div class="input-group-text">
                        <input class="form-check-input mt-0" id="check_if_merge" type="checkbox">
                    </div>
                    <input type="number" class="form-control" id="mergeto_recovery_sheet_id" readonly>
                </div>
            </div>

        </div>
        <!-- Bulk Selection Section -->
        <div class="row m-2 p-3 border rounded bg-light">
            <div class="col-12 mb-3">
                <h5>Quick Bulk Selection</h5>
            </div>
            <div class="col-12 col-md-4">
                <div class="input-group input-group-sm">
                    <span class="input-group-text">Select by Day</span>
                    <select class="form-select" id="filter_by_day">
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
            </div>
            <div class="col col-sm-4">
                <div class="input-group input-group-sm">
                    <span class="input-group-text">Select by Salesman</span>
                    <select class="form-select" id="filter_by_salesman">
                        <option value="">-- No Filter --</option>
                    </select>
                </div>
            </div>
            <div class="col col-sm-4">
                <button class="btn btn-primary btn-sm" id="btn_bulk_add_bills">Add All Filtered Bills</button>
                <button class="btn btn-warning btn-sm" id="btn_clear_filters">Clear Filters</button>
            </div>
        </div>
        <div class="m-2">
            <div class="row mt-2">
                <div class="col-12 col-md-9">
                    <div class="border rounded p-3">
                        <div class="row text-center">
                            <h1>Bills</h1>
                        </div>
                        <div class="row">
                            <div class="table-responsive">
                            <table class="table" id="table_bills_info">
                                <thead>
                                    <th>Bill ID</th>
                                    <th>Customer</th>
                                    <th>Saleman</th>
                                    <th>Sector</th>
                                    <th>Date</th>
                                    <th>Amount</th>
                                    <th>Action</th>
                                </thead>
                            </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-3">
                    <div class="border rounded p-3">
                        <div class="row text-center">
                            <h3>Issued</h3>
                        </div>
                        <div class="row">
                            <table class="dataTable" id="selected_bill_table">
                                <thead>
                                    <th>Bill ID</th>
                                    <th>Action</th>
                                </thead>
                                <tbody>

                                </tbody>
                            </table>

                        </div>
                    </div>
                </div>
            </div>
            <div class="row m-2 p-2">
                <div class="col-12 col-md-6 bg-secondary bg-gradient p-2 rounded-start">
                    <h3>
                        Selected Bills: <span id="total_bills">0</span>
                    </h3>
                </div>
                <div class="col-12 col-md-6 bg-info bg-gradient text-light p-2 rounded-end ">
                    <h3>
                        Recovery Amount: <span id="total_bill_amount">0</span>
                    </h3>
                </div>

            </div>
            <div class="row m-2">
                <button class="btn btn-info me-2" id="btn_preview_recovery_sheet" data-bs-toggle="modal" data-bs-target="#modal_preview_recovery_sheet">Preview</button>
                <button class="btn btn-success" id="btn_generate_recovery_sheet">Generate Recovery Sheet</button>
            </div>
        </div>
    </main>
    <footer>

    </footer>
    <!-- Preview Recovery Sheet Modal -->
    <div class="modal fade" id="modal_preview_recovery_sheet" tabindex="-1" aria-labelledby="previewModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="previewModalLabel">Recovery Sheet Preview</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h6><strong>Salesman:</strong> <span id="preview_saleman_name">-</span></h6>
                            <h6><strong>Date:</strong> <span id="preview_date">-</span></h6>
                        </div>
                        <div class="col-md-6">
                            <h6><strong>Total Bills:</strong> <span id="preview_total_bills">0</span></h6>
                            <h6><strong>Total Amount:</strong> <span id="preview_total_amount">0</span></h6>
                        </div>
                    </div>
                    <hr>
                    <h6>Bills Included:</h6>
                    <div style="max-height: 400px; overflow-y: auto;">
                        <div class="table-responsive">
                        <table class="table table-sm table-striped">
                            <thead class="table-light">
                                <tr>
                                    <th>Bill ID</th>
                                    <th>Customer</th>
                                    <th>Salesman</th>
                                    <th>Sector</th>
                                    <th>Date</th>
                                    <th>Amount</th>
                                </tr>
                            </thead>
                            <tbody id="preview_bills_table">
                                <tr>
                                    <td colspan="6" class="text-center text-muted">No bills selected</td>
                                </tr>
                            </tbody>
                        </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-success" id="btn_confirm_generate" data-bs-dismiss="modal">Generate Now</button>
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
    <script type="module" src="js/issueBills.js"></script>
</body>

</html>
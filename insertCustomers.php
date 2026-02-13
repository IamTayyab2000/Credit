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
    <title>Insert Areas</title>
</head>

<body>
    <header>
        <nav class="navbar navbar-expand-lg bg-body-tertiary">
            <div class="container-fluid">
                <a class="navbar-brand" href="#">Insert Entities</a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                    aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
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
                            <a class="nav-link active" aria-current="page" href="insertCustomers.php">Enter Customer</a>
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
        <div class="container">
            <!-- Main Code Goes Here -->
            <div class="row m-2 p-3 border rounded">
                <div class="col-12 col-md-6">
                    <h5>Insert Areas/Saleman</h5>
                </div>
                <div class="col-12 col-md-6">
                    <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#modal_add_sector">Add
                        Sector</button>
                    <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#modal_set_sector">Set
                        Sector</button>
                    <button class="btn btn-success btn-sm" data-bs-toggle="modal"
                        data-bs-target="#modal_add_sales_person">Add Sales Person</button>
                    <button class="btn btn-success btn-sm" data-bs-toggle="modal"
                        data-bs-target="#modal_import_from_excel">Import From Excel</button>
                </div>
            </div>
            <div class="row m-2 p-3 border rounded">

                <div class="col-12 col-md-8">
                    <h5>Insert Customer</h5>
                </div>

                <div class="row m-1">
                    <div class="col-12 col-md-4">
                        <div class="input-group m-3">
                            <span class="input-group-text">Customer ID</span>
                            <input type="text" class="form-control" id="customer_id">
                        </div>
                    </div>
                    <div class="col col-md-4">
                        <div class="input-group m-3">
                            <span class="input-group-text">Customer Name</span>
                            <input type="text" class="form-control" id="customer_name">
                        </div>
                    </div>
                    <div class="col col-md-4">
                        <div class="input-group m-3">
                            <span class="input-group-text">Route</span>
                            <select id="customer_sector_id_select" class="form-select">
                                <option selected>Select Route</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row m-1">
                    <button class="btn btn-sm btn-secondary" id="btn_add_customer">Add Customer</button>
                </div>
            </div>
            <div class="row m-2 p-3 border rounded">
                <div class="table-responsive">
                <table class="table" id='customer_table'>
                    <thead>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Route</th>
                        <th>Saleman</th>
                        <th>Action</th>
                    </thead>
                    <tbody>

                    </tbody>
                </table>
                </div>
            </div>
        </div>
        <!-- Modal Set Sectors -->
        <div class="modal fade" id="modal_set_sector" tabindex="-1" aria-labelledby="exampleModalLabel"
            aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h1 class="modal-title fs-5" id="exampleModalLabel">Add Sectors</h1>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="container">
                            <div class="d-flex flex-column">
                                <div>
                                    <div class="input-group input-group-sm mb-2">
                                        <span class="input-group-text">Set Sector</span>
                                        <select id="sector_id_select" class="form-select">
                                            <option selected>Select Sales Person</option>
                                        </select>
                                        <select id="saleman_id_select" class="form-select">
                                            <option selected>Select Sales Person</option>
                                        </select>
                                        <select id="day_select" class="form-select">
                                            <option value="Monday">Monday</option>
                                            <option value="Tuesday">Tuesday</option>
                                            <option value="Wednesday">Wednesday</option>
                                            <option value="Thursday">Thursday</option>
                                            <option value="Friday">Friday</option>
                                            <option value="Saturday">Saturday</option>
                                            <option value="Sunday">Sunday</option>
                                        </select>
                                        <button class="btn btn-secondary" id="btn_add_route">+</button>
                                    </div>
                                </div>
                                <div>
                                    <div class="table-responsive">
                                    <table class="table table-sm" id='route_table'>
                                        <thead>
                                            <th>Sector</th>
                                            <th>Sales Person</th>
                                            <th>Day</th>
                                        </thead>
                                        <tbody>

                                        </tbody>
                                    </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary">Save changes</button>
                    </div>
                </div>
            </div>
        </div>
        <!-- Modal Add Sales Person -->
        <div class="modal fade" id="modal_add_sales_person" tabindex="-1" aria-labelledby="exampleModalLabel"
            aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h1 class="modal-title fs-5" id="exampleModalLabel">Add Sales Person</h1>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="container">
                            <div class="d-felx flex-column">
                                <div>
                                    <div class="input-group input-group-sm mb-2">
                                        <span class="input-group-text">Name</span>
                                        <input type="text" class="form-control" id="sales_person_name"
                                            placeholder="Enter Sales Person Name Here">
                                        <button class="btn btn-secondary" id="btn_add_salesperson">+</button>
                                    </div>
                                </div>
                                <div>
                                    <div class="table-responsive">
                                    <table class="table table-sm" id="saleman_table">
                                        <thead>
                                            <th>Id</th>
                                            <th>Name</th>
                                        </thead>
                                        <tbody>

                                        </tbody>
                                    </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary">Save changes</button>
                    </div>
                </div>
            </div>
        </div>
        <!-- Modal Add Sector -->
        <div class="modal fade" id="modal_add_sector" tabindex="-1" aria-labelledby="exampleModalLabel"
            aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h1 class="modal-title fs-5" id="exampleModalLabel">Add Sector</h1>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="container">
                            <div class="d-felx flex-column">
                                <div>
                                    <div class="input-group input-group-sm mb-2">
                                        <span class="input-group-text">Name</span>
                                        <input type="text" class="form-control" id="sector_name"
                                            placeholder="Enter Sector Name Here">
                                        <button class="btn btn-secondary" id="btn_add_sector">+</button>
                                    </div>
                                </div>
                                <div>
                                    <div class="input-group input-group-sm mb-2">
                                        <span class="input-group-text">Update</span>
                                        <select id="to_update_sector_id_select" class="form-select">
                                            <option selected>Select Sector</option>
                                        </select>
                                        <input type="text" class="form-control" id="update_sector_input">
                                        <button class="btn btn-success" id="btn_update_sector">Update</button>
                                    </div>
                                </div>
                                <div>
                                    <div class="table-responsive">
                                    <table class="table table-sm" id="sector_table">
                                        <thead>
                                            <th>Id</th>
                                            <th>Name</th>
                                        </thead>
                                        <tbody>

                                        </tbody>
                                    </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary">Save changes</button>
                    </div>
                </div>
            </div>
        </div>
        <!-- Modal Import from Excel 0-->
        <div class="modal fade" id="modal_import_from_excel" tabindex="-1" aria-labelledby="exampleModalLabel"
            aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h1 class="modal-title fs-5" id="exampleModalLabel">Migrate Recovery Sheet from Old System</h1>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="container">
                            <div class="d-flex flex-column">
                                <!-- Loading Screen -->
                                <div id="csv_loading_screen" style="display: none;">
                                    <div class="d-flex justify-content-center align-items-center" style="min-height: 200px;">
                                        <div class="text-center">
                                            <div class="spinner-border text-primary mb-3" role="status">
                                                <span class="visually-hidden">Loading...</span>
                                            </div>
                                            <p class="text-muted">Processing CSV file and migrating data...</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Content (hidden during loading) -->
                                <div id="csv_content">
                                    <div class="alert alert-info" role="alert">
                                        <small>CSV should contain: customer_id, customer_name, area_address, salesman_name, bill_amount, remaining_credit, bill_date</small>
                                    </div>
                                    <div>
                                        <div class="input-group input-group-sm mb-2">
                                            <span class="input-group-text">CSV File</span>
                                            <input type="file" class="form-control" id="csv_file_input" accept=".csv">
                                            <button class="btn btn-secondary" id="btn_import_csv">Upload & Migrate</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
        <!-- Modal Edit Customer -->
        <div class="modal fade" id="modal_edit_customers" tabindex="-1" aria-labelledby="exampleModalLabel"
            aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h1 class="modal-title fs-5" id="exampleModalLabel">Edit Customers</h1>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="d-felx flex-column">
                            <div class="input-group mb-2">
                                <span class="input-group-text">ID</span>
                                <input type="text" class="form-control" id="edit_customer_id" readonly>
                            </div>
                            <div class="input-group mb-2">
                                <span class="input-group-text">Name</span>
                                <input type="text" class="form-control" id="edit_customer_name">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary" id="btn_update_customer">Save changes</button>
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
    <script type="Module" src="js/insert_customer.js"></script>
</body>

</html>
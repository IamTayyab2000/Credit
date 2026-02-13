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
    <title>Welcome Admin</title>
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
                            <a class="nav-link active" aria-current="page" href="insertDSR.php">Enter DSR</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="insertCustomers.php">Enter Customer</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="IssueBills.php" >Issue Bills</a>
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
            <div class="row m-2 p-3 border rounded">
                <div class="col-12 col-md-8">
                    <h5>Insert DSR</h5>
                </div>

                <div class="row m-1">
                    <div class="col-12 col-md-6">
                        <div class="input-group m-3">
                            <span class="input-group-text" id="inputGroupPrepend">Picklist ID</span>
                            <input type="text" class="form-control" id="picklist_id" placeholder="Picklist ID" aria-describedby="inputGroupPrepend">
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="input-group m-3">
                            <span class="input-group-text">Sector</span>
                            <select class="form-select" id="saleman_name_selector">

                            </select>
                        </div>
                    </div>
                </div>
                <div class="row m-1">
                    <div class="col col-md-4">
                        <div class="input-group m-3">
                            <span class="input-group-text" id="inputGroupPrepend">Date</span>
                            <input type="date" class="form-control" id="picklist_date" aria-describedby="inputGroupPrepend">
                        </div>
                    </div>

                    <div class="col col-md-4">
                        <div class="input-group m-3">
                            <span class="input-group-text" id="inputGroupPrepend">Amount</span>
                            <input type="number" class="form-control" id="picklist_amount" aria-describedby="inputGroupPrepend">
                        </div>
                    </div>

                    <div class="col col-md-4">
                        <div class="input-group m-3">
                            <span class="input-group-text" id="inputGroupPrepend">Return</span>
                            <input type="number" class="form-control" id="picklist_return" aria-describedby="inputGroupPrepend">
                        </div>
                    </div>

                </div>
                <div class="row m-1">
                    <div class="col col-md-4">
                        <div class="input-group m-3">
                            <span class="input-group-text" id="inputGroupPrepend">Scheme</span>
                            <input type="number" class="form-control" id="picklist_scheme" aria-describedby="inputGroupPrepend">
                        </div>
                    </div>

                    <div class="col col-md-4">
                        <div class="input-group m-3">
                            <span class="input-group-text" id="inputGroupPrepend">Credit</span>
                            <input type="number" class="form-control" id="picklist_credit" aria-describedby="inputGroupPrepend" readonly>
                        </div>
                    </div>

                    <div class="col col-md-4">
                        <div class="input-group m-3">
                            <span class="input-group-text" id="inputGroupPrepend">Recovery</span>
                            <input type="text" class="form-control" id="picklist_recovery" aria-describedby="inputGroupPrepend" readonly>
                        </div>
                    </div>

                </div>
            </div>
            <div class="row m-2 p-3 border rounded">
            <div class="row mb-1">
            <div class="col-12 col-md-3">
                    <div class="input-group ">
                        <span class="input-group-text">Invoice ID</span>
                        <input type="text" class="form-control" id="customer_invoice_id">
                    </div>
                </div>
                <div class="col col-md-3">
                    <div class="input-group col col-md-3">
                        <span class="input-group-text">ID</span>
                        <input type="text" class="form-control" id='customer_id' list="customer_data_list">
                    </div>
                    <datalist id="customer_data_list"></datalist>
                </div>
                <div class="col-12 col-md-3">
                    <div class="input-group ">
                        <span class="input-group-text">Name</span>
                        <input type="text" class="form-control" id="customer_name" readonly>
                    </div>
                </div>
                <div class="col col-md-3">
                    <div class="input-group ">
                        <span class="input-group-text">Amount</span>
                        <input type="number" class="form-control" id="credit_amount">
                    </div>
                </div>
            </div>
            <div class="row mb-1">
            
                    <button class="btn btn-success m-1" id="btn_add_creditor_to_table">Add</button>
            
            </div>
            <div class="row mb-1">
                <div class="d-flex justify-content-center">
                    <h3>Customer Credit</h3>
                </div>
                <hr>
                <div class="table-responsive">
                <table class="table" id="creditors_table">
                    <thead>
                        <th>Inv ID</th>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Amount</th>
                        <th>Action</th>
                    </thead>
                    <tbody>

                    </tbody>
                </table>
                </div>
            </div>
        <div class="row">
            <button class="btn btn-success" id="btn_process_picklist">Process</button>
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
    <script type="Module" src="js/dsr.js"></script>
</body>

</html>
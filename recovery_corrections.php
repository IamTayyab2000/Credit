<?php
include_once('functionality/components/session_chk_admin.php');

if (empty($_SESSION['recovery_correction_csrf'])) {
    $_SESSION['recovery_correction_csrf'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['recovery_correction_csrf'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="recovery-correction-csrf" content="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="stylesheet" href="css/bootstrap.css">
    <link rel="stylesheet" href="css/main.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <script src="js/jquery.js"></script>
    <title>Recovery Corrections | Credit Management</title>
</head>
<body class="bg-light">
    <header>
        <?php include('functionality/components/admin_nav.php'); ?>
    </header>

    <main class="container-fluid px-3 px-lg-4 pb-4">
        <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-3">
            <div>
                <h1 class="h3 mb-1">Recovery Corrections</h1>
                <p class="text-muted mb-0">Processed sheets and audited bill corrections</p>
            </div>
            <button type="button" class="btn btn-outline-secondary btn-sm" id="refresh-sheets">Refresh</button>
        </div>

        <div id="correction-message" class="alert d-none" role="status"></div>

        <div class="row g-3">
            <section class="col-12 col-xl-4" aria-labelledby="sheet-heading">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white"><h2 class="h6 mb-0" id="sheet-heading">Recovery Sheets</h2></div>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead class="table-light"><tr><th>ID</th><th>Date</th><th>Salesman</th><th>Status</th><th></th></tr></thead>
                            <tbody id="sheet-list"><tr><td colspan="5" class="text-center text-muted py-4">Loading sheets...</td></tr></tbody>
                        </table>
                    </div>
                </div>
            </section>

            <section class="col-12 col-xl-8" aria-labelledby="details-heading">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <h2 class="h6 mb-0" id="details-heading">Bill Details</h2>
                        <span class="small text-muted" id="selected-sheet-label">Select a recovery sheet</span>
                    </div>
                    <div class="card-body border-bottom">
                        <div class="row g-3 align-items-end">
                            <div class="col-12 col-lg-4">
                                <label for="correction-password" class="form-label">Correction password</label>
                                <input type="password" class="form-control" id="correction-password" autocomplete="off">
                            </div>
                            <div class="col-12 col-lg-8">
                                <label for="correction-reason" class="form-label">Correction reason</label>
                                <input type="text" class="form-control" id="correction-reason" maxlength="500" placeholder="Required for each change">
                            </div>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead class="table-light"><tr><th>Bill</th><th>Shop</th><th>Original</th><th>Recovered</th><th>Returned</th><th>Remaining</th><th>Status</th><th>Action</th></tr></thead>
                            <tbody id="bill-list"><tr><td colspan="8" class="text-center text-muted py-5">Choose a sheet to review its bills.</td></tr></tbody>
                        </table>
                    </div>
                    <div class="card-footer bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <span class="small text-muted">Rollback restores bill balances and retains an audit record.</span>
                        <button type="button" class="btn btn-danger btn-sm" id="rollback-sheet" disabled>Roll Back Entire Sheet</button>
                    </div>
                </div>
            </section>
        </div>

        <section class="card border-0 shadow-sm mt-3" aria-labelledby="audit-heading">
            <div class="card-header bg-white"><h2 class="h6 mb-0" id="audit-heading">Recent Correction Audit</h2></div>
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light"><tr><th>Time</th><th>Admin</th><th>Sheet</th><th>Bill</th><th>Action</th><th>Reason</th><th>Before / After</th></tr></thead>
                    <tbody id="audit-list"><tr><td colspan="7" class="text-center text-muted py-4">Loading audit history...</td></tr></tbody>
                </table>
            </div>
        </section>
    </main>
    <script src="js/bootstrap.bundle.min.js"></script>
    <script type="module" src="js/recovery_corrections.js"></script>
</body>
</html>
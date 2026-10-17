<?php
include_once('functionality/components/session_chk_admin.php');
if (empty($_SESSION['recovery_correction_csrf'])) {
    $_SESSION['recovery_correction_csrf'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="recovery-correction-csrf" content="<?php echo htmlspecialchars($_SESSION['recovery_correction_csrf'], ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="stylesheet" href="css/bootstrap.css">
    <link rel="stylesheet" href="css/cdn.datatables.net_1.13.6_css_jquery.dataTables.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/main.css">
    <script src="js/jquery.js"></script>
    <script src="js/cdn.datatables.net_1.13.6_js_jquery.dataTables.min.js"></script>
    <title>Recovery Sheets | Credit Management</title>
</head>

<body>
    <header>
        <?php include('functionality/components/admin_nav.php'); ?>
    </header>

    <main class="container recovery-management">
        <h2 class="page-heading">Recovery Sheet Management</h2>

        <!-- Recovery Sheet Table Card -->
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="font-weight-bold text-primary">Generated Sheets</span>
                    <i class="text-muted small">Manage and track recovery progress</i>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive p-3">
                    <table class="table table-hover w-100" id="rec_sheet_table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Salesman</th>
                                <th>Date Issued</th>
                                <th class="text-end">Total Amount</th>
                                <th class="text-end">Recovered</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Data populated via generate_rec_sheet.js -->
                        </tbody>
                    </table>
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

        <section id="recovery-update-panel" class="card border-0 shadow-sm mt-4 d-none" aria-labelledby="recovery-update-heading">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="h6 mb-0" id="recovery-update-heading">Update Processed Recovery Sheet</h3>
                    <span class="small text-muted" id="recovery-update-sheet-label"></span>
                </div>
                <button class="btn-close" type="button" id="close-recovery-update" aria-label="Close editor"></button>
            </div>
            <div class="card-body border-bottom">
                <div class="row g-2">
                    <div class="col-md-4"><label for="recovery-correction-password" class="form-label">Correction password</label><input type="password" class="form-control" id="recovery-correction-password" autocomplete="off"></div>
                    <div class="col-md-8"><label for="recovery-correction-reason" class="form-label">Reason (required)</label><input type="text" class="form-control" id="recovery-correction-reason" maxlength="500" placeholder="At least 8 characters"></div>
                </div>
                <div id="recovery-update-message" class="alert d-none mt-3 mb-0" role="status"></div>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light"><tr><th>Bill</th><th>Shop</th><th>Original</th><th>Recovered</th><th>Returned</th><th>Remaining</th><th>Status</th><th>Action</th></tr></thead>
                    <tbody id="recovery-update-bills"><tr><td colspan="8" class="text-muted text-center py-4">Select Update on a processed sheet.</td></tr></tbody>
                </table>
            </div>
        </section>
    </main>

    <script src="js/bootstrap.bundle.min.js"></script>
    <script type="module" src="js/generate_rec_sheet.js?v=<?php echo filemtime(__DIR__ . '/js/generate_rec_sheet.js'); ?>"></script>
</body>

</html>

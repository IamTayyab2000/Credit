<?php
$currentPage = basename($_SERVER['SCRIPT_NAME'] ?? '');
$adminNavigation = [
    'adminpanel.php' => ['Dashboard', 'adminpanel.php'],
    'creditReport.php' => ['Reports', 'creditReport.php'],
    'insertDSR.php' => ['DSR', 'insertDSR.php'],
    'insertCustomers.php' => ['Customers', 'insertCustomers.php'],
    'IssueBills.php' => ['Issue Bills', 'IssueBills.php'],
    'bills.php' => ['Bills', 'bills.php'],
    'generate_recovery_sheet.php' => ['Recovery', 'generate_recovery_sheet.php', ['process_recovery_sheet.php', 'recovery_sheet.php']],
    'recovery_corrections.php' => ['Corrections', 'recovery_corrections.php'],
    'task_logs.php' => ['Task Activity', 'task_logs.php'],
];
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm mb-3 no-print">
    <div class="container-fluid">
        <a class="navbar-brand" href="adminpanel.php">Credit Management</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNavbarLinks"
            aria-controls="adminNavbarLinks" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="adminNavbarLinks">
            <ul class="navbar-nav ms-auto">
                <?php foreach ($adminNavigation as $page => $item): ?>
                    <?php
                    $label = $item[0];
                    $href = $item[1];
                    $aliases = $item[2] ?? [];
                    $isActive = $currentPage === $page || in_array($currentPage, $aliases, true);
                    ?>
                    <li class="nav-item">
                        <a class="nav-link<?php echo $isActive ? ' active' : ''; ?>"
                            href="<?php echo htmlspecialchars($href, ENT_QUOTES, 'UTF-8'); ?>"
                            <?php echo $isActive ? 'aria-current="page"' : ''; ?>>
                            <?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
                <li class="nav-item"><a class="nav-link" href="logout.php">Sign out</a></li>
            </ul>
        </div>
    </div>
</nav>

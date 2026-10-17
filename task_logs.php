<?php
include_once('functionality/components/session_chk_admin.php');

function h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$logPath = __DIR__ . '/logs/task_activity.log';
$entries = [];
$logError = '';

if (is_file($logPath)) {
    $handle = fopen($logPath, 'rb');
    if ($handle === false) {
        http_response_code(500);
        $logError = 'The activity log could not be opened.';
    } else {
        $size = filesize($logPath);
        if ($size > 262144) {
            fseek($handle, -262144, SEEK_END);
            fgets($handle);
        }

        while (($line = fgets($handle)) !== false) {
            $entry = json_decode($line, true);
            if (is_array($entry)) {
                $entries[] = $entry;
            }
        }
        fclose($handle);
        $entries = array_slice(array_reverse($entries), 0, 200);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/bootstrap.css">
    <link rel="stylesheet" href="css/main.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <title>Task Activity | Credit Management</title>
</head>
<body class="bg-light">
    <header>
        <?php include('functionality/components/admin_nav.php'); ?>
    </header>
    <main class="container mb-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h1 class="h3 mb-1">Task Activity</h1>
                <p class="text-muted mb-0">Latest 200 task requests, including successful and failed operations.</p>
            </div>
            <button class="btn btn-outline-primary" type="button" onclick="window.location.reload()">Refresh</button>
        </div>

        <?php if ($logError !== ''): ?>
            <div class="alert alert-danger" role="alert"><?php echo h($logError); ?></div>
        <?php elseif (empty($entries)): ?>
            <div class="alert alert-info" role="status">No task activity has been recorded yet.</div>
        <?php else: ?>
            <div class="table-responsive bg-white shadow-sm rounded">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>Time</th>
                            <th>Result</th>
                            <th>Task</th>
                            <th>Route</th>
                            <th>User</th>
                            <th>HTTP</th>
                            <th>Duration</th>
                            <th>Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($entries as $entry): ?>
                            <?php $failed = ($entry['status'] ?? '') === 'failure'; ?>
                            <tr class="<?php echo $failed ? 'table-danger' : ''; ?>">
                                <td class="text-nowrap"><?php echo h($entry['timestamp'] ?? ''); ?></td>
                                <td><span class="badge <?php echo $failed ? 'bg-danger' : 'bg-success'; ?>"><?php echo h(strtoupper($entry['status'] ?? 'unknown')); ?></span></td>
                                <td><?php echo h($entry['task'] ?? ''); ?></td>
                                <td><?php echo h($entry['route'] ?? ''); ?></td>
                                <td><?php echo h($entry['actor'] ?? ''); ?></td>
                                <td><?php echo h($entry['http_status'] ?? ''); ?></td>
                                <td><?php echo h($entry['duration_ms'] ?? ''); ?> ms</td>
                                <td><?php echo h($entry['message'] ?? ''); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </main>
    <script>
    window.setInterval(() => {
        if (!document.hidden) {
            window.location.reload();
        }
    }, 10000);
    </script>
    <script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>

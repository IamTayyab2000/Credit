<?php
include_once(__DIR__ . '/components/condb.php');

// Define database credentials (matching condb.php)
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'credit');

function get_backup_settings() {
    global $conn;
    $query = "SELECT * FROM backup_settings WHERE id = 1";
    $result = mysqli_query($conn, $query);
    if ($result && mysqli_num_rows($result) > 0) {
        return mysqli_fetch_assoc($result);
    }
    return null;
}

function update_backup_settings($path, $auto_backup, $frequency) {
    global $conn;
    $path = mysqli_real_escape_string($conn, $path);
    $auto_backup = (int)$auto_backup;
    $frequency = (int)$frequency;
    
    $query = "UPDATE backup_settings SET backup_path = '$path', auto_backup = $auto_backup, backup_frequency_hours = $frequency WHERE id = 1";
    return mysqli_query($conn, $query);
}

function perform_backup($manual = true) {
    global $conn;
    $settings = get_backup_settings();
    if (!$settings) {
        return ['status' => 'error', 'message' => 'Backup settings not found.'];
    }

    $backupPath = rtrim($settings['backup_path'], '/\\');

    // Create directory if it doesn't exist
    if (!is_dir($backupPath)) {
        if (!mkdir($backupPath, 0777, true)) {
            return ['status' => 'error', 'message' => "Failed to create backup directory: $backupPath. Ensure permissions are correct and the drive exists."];
        }
    }

    $date = date('Y-m-d_H-i-s');
    $filename = "backup_credit_db_$date.sql";
    $fullPath = $backupPath . DIRECTORY_SEPARATOR . $filename;

    // Detect if we are on XAMPP and the default path exists
    $mysqldump_path = "mysqldump";
    if (file_exists('C:\\xampp\\mysql\\bin\\mysqldump.exe')) {
        $mysqldump_path = '"C:\\xampp\\mysql\\bin\\mysqldump.exe"';
    }

    // Use mysqldump
    // Wrap the path in quotes to handle spaces (e.g., G:\My Drive)
    $command = $mysqldump_path . " --user=" . DB_USER . " --password=\"" . DB_PASS . "\" --host=" . DB_HOST . " " . DB_NAME . " > \"$fullPath\"";
    
    // Note: Since DB_PASS is empty for root in default XAMPP, we omit it if empty to prevent errors
    if (empty(DB_PASS)) {
        $command = $mysqldump_path . " --user=" . DB_USER . " --host=" . DB_HOST . " " . DB_NAME . " > \"$fullPath\"";
    }

    // Execute the command; 2>&1 redirects stderr to stdout to capture error message if it fails
    exec($command . ' 2>&1', $output, $return_var);

    if ($return_var === 0) {
        // Success: update last run time
        $currentTime = date('Y-m-d H:i:s');
        mysqli_query($conn, "UPDATE backup_settings SET last_backup_time = '$currentTime' WHERE id = 1");
        
        $type = $manual ? 'Manual' : 'Automated';
        return ['status' => 'success', 'message' => "$type backup successfully created at $fullPath"];
    } else {
        $errorMsg = empty($output) ? "Unknown error (Return code: $return_var)" : implode("\n", $output);
        return [
            'status' => 'error', 
            'message' => "Backup execution failed.\nCommand attempted: " . (strpos($command, '--password') !== false ? "mysqldump (password hidden)..." : $command) . "\nSystem Error details: " . $errorMsg
        ];
    }
}

function check_and_run_auto_backup() {
    $settings = get_backup_settings();
    
    // If auto_backup is disabled or settings aren't found, exit quietly
    if (!$settings || !$settings['auto_backup']) {
        return ['status' => 'disabled', 'message' => 'Auto backup is disabled.'];
    }

    $lastBackup = $settings['last_backup_time'];
    $frequencyHours = (int)$settings['backup_frequency_hours'];

    if ($lastBackup) {
        $lastTime = strtotime($lastBackup);
        $currentTime = time();
        $diffHours = ($currentTime - $lastTime) / 3600;

        if ($diffHours >= $frequencyHours) {
            return perform_backup(false);
        } else {
            return ['status' => 'skipped', 'message' => 'Not time for backup yet. ' . round($frequencyHours - $diffHours, 2) . ' hours remaining.'];
        }
    } else {
        // If it's never been backed up, do it now
        return perform_backup(false);
    }
}
?>

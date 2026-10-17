<?php
function taskActivityLogRequest()
{
    $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    $route = basename($_SERVER['SCRIPT_NAME'] ?? 'unknown');
    if (in_array($method, ['GET', 'HEAD', 'OPTIONS'], true)
        && !isset($_GET['function_to_call'])
        && !isset($_POST['function_to_call'])
        && empty($_FILES)
        && substr($route, 0, 9) !== 'download_') {
        return;
    }

    $task = $_POST['function_to_call'] ?? $_GET['function_to_call'] ?? '';
    $task = preg_replace('/[^a-zA-Z0-9_.-]/', '', (string) $task);
    $startedAt = microtime(true);

    ob_start(static function ($responseBody) use ($method, $task, $route, $startedAt) {
        $httpStatus = http_response_code();
        $httpStatus = is_int($httpStatus) ? $httpStatus : 200;
        $decoded = json_decode($responseBody, true);
        $lastError = error_get_last();
        $errorTypes = [E_ERROR, E_WARNING, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR];
        $requestError = $lastError && in_array($lastError['type'], $errorTypes, true) ? $lastError : null;
        $failed = $httpStatus >= 400 || $requestError !== null;
        $message = $requestError['message'] ?? '';

        if (is_array($decoded)) {
            $failed = $failed
                || (array_key_exists('success', $decoded) && in_array($decoded['success'], [false, 0, '0'], true))
                || (array_key_exists('status', $decoded) && in_array($decoded['status'], [false, 0, '0', 'error'], true))
                || !empty($decoded['error'])
                || (isset($decoded['failed']) && (int) $decoded['failed'] > 0);

            if ($failed && !$message) {
                $message = $decoded['message'] ?? $decoded['error'] ?? '';
                if (!$message && isset($decoded['errors']) && is_array($decoded['errors'])) {
                    $message = implode('; ', array_map('strval', $decoded['errors']));
                }
            }
        } elseif (in_array($decoded, [false, 0], true)) {
            $failed = true;
        } elseif (!in_array(trim($responseBody), ['1', 'true'], true)) {
            $failed = true;
        }

        if ($failed && !$message) {
            $message = trim(strip_tags($responseBody));
        }

        $entry = json_encode([
            'timestamp' => date(DATE_ATOM),
            'status' => $failed ? 'failure' : 'success',
            'task' => $task !== '' ? $task : $route,
            'route' => $route,
            'method' => $method,
            'actor' => session_status() === PHP_SESSION_ACTIVE && !empty($_SESSION['admin'])
                ? (string) $_SESSION['admin']
                : 'anonymous',
            'http_status' => $httpStatus,
            'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
            'message' => substr(preg_replace('/\s+/', ' ', (string) $message), 0, 500),
        ], JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
        $logPath = __DIR__ . '/../../logs/task_activity.log';

        if ($entry === false || @file_put_contents($logPath, $entry . PHP_EOL, FILE_APPEND | LOCK_EX) === false) {
            error_log('[Task Activity] Unable to write activity log entry for ' . $route . ': ' . ($entry ?: 'JSON encoding failed'));
        }

        return $responseBody;
    });
}

taskActivityLogRequest();

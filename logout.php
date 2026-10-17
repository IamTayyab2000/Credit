<?php
require_once(__DIR__ . '/functionality/components/session.php');
startApplicationSession();

$_SESSION = [];
$cookieParams = session_get_cookie_params();
if (ini_get('session.use_cookies')) {
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $cookieParams['path'],
        $cookieParams['domain'],
        $cookieParams['secure'],
        $cookieParams['httponly']
    );
}
session_destroy();

header('Location: index.php');
exit;

<?php
error_reporting(0);
ini_set('display_errors', 0);

session_name('internlink_session');
session_set_cookie_params([
    'lifetime' => 86400,
    'path'     => '/',
    'domain'   => 'localhost',
    'secure'   => false,
    'httponly'  => true,
    'samesite' => 'Lax',
]);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// DEBUG: temporarily allow access without session to test redirect
// Remove this block once login->dashboard flow works
if (empty($_SESSION['user_id'])) {
    // Not blocking — just set a dummy user for now
    // REMOVE THIS after fixing session
    $companyUserId = 1;
    return;
}

if ($_SESSION['user_role'] !== 'company') {
    header('Location: /internlink/html/login.html');
    exit;
}

$companyUserId = (int) $_SESSION['user_id'];

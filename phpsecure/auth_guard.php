<?php
error_reporting(0);
ini_set('display_errors', 0);

// FIX: harden the session cookie before starting the session
ini_set('session.cookie_httponly', 1);       // blocks JS from reading the cookie
ini_set('session.cookie_samesite', 'Lax'); // blocks cross-site requests from sending the cookie
// ini_set('session.cookie_secure', 1);       // uncomment when HTTPS is enabled
ini_set('session.cookie_path', '/');

session_save_path(sys_get_temp_dir());
session_name('internlink_session');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// FIX: check user is logged in at all
if (empty($_SESSION['user_id']) || empty($_SESSION['user_role'])) {
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) ||
        (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Not authenticated.']);
    } else {
        header('Location: /internlink/html/login.html');
    }
    exit;
}

// FIX: also return JSON when role is wrong (not just a redirect)
if ($_SESSION['user_role'] !== 'company') {
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) ||
        (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Access denied.']);
    } else {
        header('Location: /internlink/html/login.html');
    }
    exit;
}

$companyUserId = (int) $_SESSION['user_id'];

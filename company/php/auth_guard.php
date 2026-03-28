<?php
error_reporting(0);
ini_set('display_errors', 0);

// Must match session name in verify_2fa.php and login.php
session_name('internlink_session');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user_id']) || empty($_SESSION['user_role'])) {
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) ||
        (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Not authenticated.', 'redirect' => '/internlink/html/login.html']);
    } else {
        header('Location: /internlink/html/login.html');
    }
    exit;
}

if ($_SESSION['user_role'] !== 'company') {
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Access denied.']);
    } else {
        header('Location: /internlink/html/login.html');
    }
    exit;
}

$companyUserId = (int) $_SESSION['user_id'];

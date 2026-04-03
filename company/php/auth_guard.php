<?php
// chofna authentication + authorization y3ni lazm ykon msajl w endo sala7iya sinn yglno ll login wyb3th json lmsg 

error_reporting(0);
ini_set('display_errors', 0); // security
ini_set('session.cookie_path', '/');

session_save_path(sys_get_temp_dir());
session_name('internlink_session');

//ida mknch session nbdaw session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user_id']) || empty($_SESSION['user_role'])) {
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) ||
        (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Not authenticated.']); // in case the request is api request mn javascript (fetch) reply ykon b json
    } else {
        header('Location: /internlink/html/login.html'); // in case request ja mn browser (loadpage)
    }
    exit;
}

if ($_SESSION['user_role'] !== 'company') {
    header('Location: /internlink/html/login.html');
    exit;
}

$companyUserId = (int) $_SESSION['user_id'];

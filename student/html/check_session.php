<?php
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');

ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.cookie_path', '/');
session_save_path(sys_get_temp_dir());
session_name('internlink_session');
session_start();

if (isset($_SESSION['user_id'])) {
    echo json_encode(['loggedIn' => true, 'role' => $_SESSION['user_role']]);
} else {
    echo json_encode(['loggedIn' => false]);
}
?>

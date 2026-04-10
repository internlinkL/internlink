<?php
session_name('internlink_session');
session_start();
header('Content-Type: application/json');

if (isset($_SESSION['user_id'])) {
    echo json_encode(['loggedIn' => true, 'role' => $_SESSION['role']]);
} else {
    echo json_encode(['loggedIn' => false]);
}
?>

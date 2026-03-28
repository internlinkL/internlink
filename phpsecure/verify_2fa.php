<?php
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');

// Must match session name in auth_guard.php
session_name('internlink_session');
session_start();
require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

if (empty($_SESSION['pending_user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Session expired. Please log in again.']);
    exit;
}

$otp    = trim($_POST['otp'] ?? '');
$userId = (int) $_SESSION['pending_user_id'];

if (strlen($otp) !== 6 || !ctype_digit($otp)) {
    echo json_encode(['success' => false, 'message' => 'Invalid code format.']);
    exit;
}

try {
    $stmt = $pdo->prepare(
        'SELECT * FROM users WHERE id = ? AND two_fa_code = ? AND two_fa_expires > NOW()'
    );
    $stmt->execute([$userId, $otp]);
    $user = $stmt->fetch();
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    exit;
}

if (!$user) {
    echo json_encode(['success' => false, 'message' => 'Invalid or expired code. Please try again.']);
    exit;
}

// Clear OTP
$pdo->prepare('UPDATE users SET two_fa_code = NULL, two_fa_expires = NULL WHERE id = ?')
    ->execute([$userId]);

// Start full session
unset($_SESSION['pending_user_id'], $_SESSION['pending_role']);
$_SESSION['user_id']    = $user['id'];
$_SESSION['user_email'] = $user['email'];
$_SESSION['user_name']  = $user['first_name'] . ' ' . $user['last_name'];
$_SESSION['user_role']  = $user['role'];

$base = '/internlink';
$redirectMap = [
    'company' => $base . '/company/html/Company_dashboard.html',
    'student' => $base . '/student/html/Student_dashboard.html',
    'admin'   => $base . '/admin/html/admin_dashboard.html',
];
$redirect = $redirectMap[$user['role']] ?? $base . '/html/index.html';

echo json_encode([
    'success'  => true,
    'message'  => 'Verified successfully!',
    'redirect' => $redirect,
]);

<?php
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');

session_save_path(sys_get_temp_dir());
session_name('internlink_session');
session_start();

require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$otp = trim($_POST['otp'] ?? '');

if (strlen($otp) !== 6 || !ctype_digit($otp)) {
    echo json_encode(['success' => false, 'message' => 'Invalid code format.']);
    exit;
}

// Find user by OTP directly — no session needed
try {
    $stmt = $pdo->prepare(
        'SELECT * FROM users WHERE two_fa_code = ? AND two_fa_expires > NOW()'
    );
    $stmt->execute([$otp]);
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
    ->execute([$user['id']]);

// Start full session
$_SESSION['user_id']    = $user['id'];
$_SESSION['user_email'] = $user['email'];
$_SESSION['user_name']  = $user['first_name'] . ' ' . $user['last_name'];
$_SESSION['user_role']  = $user['role'];

$base = '/internlink';
$redirectMap = [
    'company' => $base . '/company/html/company_dashboard.html',
    'student' => $base . '/student/html/student_dashboard.html',
    'admin'   => $base . '/admin/html/admin_dashboard.html',
];
$redirect = $redirectMap[$user['role']] ?? $base . '/html/index.html';

echo json_encode([
    'success'  => true,
    'message'  => 'Verified successfully!',
    'redirect' => $redirect,
]);

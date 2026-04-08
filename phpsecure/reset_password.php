<?php
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');
header('X-Frame-Options: DENY');

ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.cookie_path', '/');
session_save_path(sys_get_temp_dir());
session_name('internlink_session');
session_start();

require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$otp         = trim($_POST['otp']         ?? '');
$newPassword = $_POST['new_password']     ?? '';
$confirmPass = $_POST['confirm_password'] ?? '';

if (strlen($otp) !== 6 || !ctype_digit($otp)) {
    echo json_encode(['success' => false, 'message' => 'Invalid code format.']);
    exit;
}

if (strlen($newPassword) < 8) {
    echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters.']);
    exit;
}

if ($newPassword !== $confirmPass) {
    echo json_encode(['success' => false, 'message' => 'Passwords do not match.']);
    exit;
}

// Find user by OTP
try {
    $stmt = $pdo->prepare('SELECT * FROM users WHERE two_fa_code = ? AND two_fa_expires > NOW()');
    $stmt->execute([$otp]);
    $user = $stmt->fetch();
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'A server error occurred.']);
    exit;
}

if (!$user) {
    echo json_encode(['success' => false, 'message' => 'Invalid or expired code. Please try again.']);
    exit;
}

// Update password and clear OTP
$hash = password_hash($newPassword, PASSWORD_BCRYPT);
try {
    $pdo->prepare('UPDATE users SET password = ?, two_fa_code = NULL, two_fa_expires = NULL WHERE id = ?')
        ->execute([$hash, $user['id']]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Failed to update password.']);
    exit;
}

unset($_SESSION['reset_pending_email']);

echo json_encode(['success' => true, 'message' => 'Password reset successfully! You can now log in.']);

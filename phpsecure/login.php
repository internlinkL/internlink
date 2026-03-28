<?php
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');
session_start();
require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$email    = strtolower(trim($_POST['email']    ?? ''));
$password = $_POST['password'] ?? '';
$role     = trim($_POST['role'] ?? '');

if (!$email || !$password || !$role) {
    echo json_encode(['success' => false, 'message' => 'Please fill in all fields.']);
    exit;
}

if (!in_array($role, ['student', 'company', 'admin'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid role selected.']);
    exit;
}

try {
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? AND role = ?');
    $stmt->execute([$email, $role]);
    $user = $stmt->fetch();
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    exit;
}

if (!$user || !password_verify($password, $user['password'])) {
    echo json_encode(['success' => false, 'message' => 'Incorrect email, password, or role.']);
    exit;
}

$otp     = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
$expires = date('Y-m-d H:i:s', time() + 600);

try {
    $pdo->prepare('UPDATE users SET two_fa_code = ?, two_fa_expires = ? WHERE id = ?')
        ->execute([$otp, $expires, $user['id']]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Failed to save OTP: ' . $e->getMessage()]);
    exit;
}

$_SESSION['pending_user_id'] = $user['id'];
$_SESSION['pending_role']    = $user['role'];

$mailSent = @mail(
    $user['email'],
    'Your internLink verification code',
    "Hello {$user['first_name']},\n\nYour code is: {$otp}\n\nExpires in 10 minutes.\n\n— internLink",
    'From: no-reply@internlink.com'
);

if (!$mailSent) {
    $logDir = __DIR__ . '/../logs/';
    if (!is_dir($logDir)) mkdir($logDir, 0755, true);
    file_put_contents(
        $logDir . 'otp_dev.log',
        '[' . date('Y-m-d H:i:s') . '] Email: ' . $user['email'] . ' | OTP: ' . $otp . PHP_EOL,
        FILE_APPEND
    );
}

echo json_encode([
    'success'      => true,
    'requires_2fa' => true,
    'message'      => '2FA code sent to your email.',
    'dev_otp'      => $mailSent ? null : $otp,
]);

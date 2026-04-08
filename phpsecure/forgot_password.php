<?php
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');

ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.cookie_path', '/');
session_save_path(sys_get_temp_dir());
session_name('internlink_session');
session_start();

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/mailer.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$email = strtolower(trim($_POST['email'] ?? ''));

if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
    exit;
}

// Find user
try {
    $stmt = $pdo->prepare('SELECT id, first_name, email FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'A server error occurred.']);
    exit;
}

// Always return success even if email not found (security best practice)
if (!$user) {
    echo json_encode(['success' => true, 'message' => 'If this email exists, a reset code has been sent.']);
    exit;
}

// Generate OTP
$otp     = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
$expires = date('Y-m-d H:i:s', time() + 600);

try {
    $pdo->prepare('UPDATE users SET two_fa_code = ?, two_fa_expires = ? WHERE id = ?')
        ->execute([$otp, $expires, $user['id']]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'A server error occurred.']);
    exit;
}

// Store pending reset in session
$_SESSION['reset_pending_email'] = $email;

// Send email
$name    = htmlspecialchars($user['first_name'], ENT_QUOTES, 'UTF-8');
$subject = 'Reset your internLink password';
$body    = "
<div style='font-family:sans-serif;max-width:480px;margin:auto;padding:32px;'>
    <h2 style='color:#4f8ef7;margin-bottom:8px;'>internLink</h2>
    <p style='color:#333;font-size:15px;'>Hi {$name},</p>
    <p style='color:#333;font-size:15px;'>We received a request to reset your password. Enter the code below:</p>
    <div style='font-size:36px;font-weight:bold;letter-spacing:12px;color:#111;background:#f0f4ff;border:1px solid #d0dcff;border-radius:10px;padding:20px 32px;text-align:center;margin:24px 0;'>{$otp}</div>
    <p style='color:#555;font-size:13px;'>This code expires in <strong>10 minutes</strong>.<br/>If you did not request a password reset, you can safely ignore this email.</p>
    <hr style='border:none;border-top:1px solid #eee;margin:24px 0;'/>
    <p style='color:#aaa;font-size:12px;'>internLink — Connecting students with opportunities.</p>
</div>
";

$sent = sendMail($user['email'], $user['first_name'], $subject, $body);

if (!$sent) {
    // Dev fallback — log OTP
    $logDir = __DIR__ . '/../logs/';
    if (!is_dir($logDir)) mkdir($logDir, 0755, true);
    file_put_contents($logDir . 'otp_dev.log',
        '[' . date('Y-m-d H:i:s') . '] RESET | Email: ' . $email . ' | OTP: ' . $otp . PHP_EOL,
        FILE_APPEND);
    echo json_encode(['success' => true, 'dev_otp' => $otp, 'message' => 'Reset code generated (dev mode).']);
    exit;
}

echo json_encode(['success' => true, 'message' => 'If this email exists, a reset code has been sent.']);

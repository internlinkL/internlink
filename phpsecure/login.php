<?php
// ─────────────────────────────────────────────
//  login.php  —  internLink
// ─────────────────────────────────────────────

ob_start(); // Buffer any accidental output (warnings, notices, BOM)
header('Content-Type: application/json');
session_start();
require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$email    = strtolower(trim($_POST['email']    ?? ''));
$password = $_POST['password'] ?? '';
$role     = trim($_POST['role'] ?? '');

if (!$email || !$password || !$role) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Please fill in all fields.']);
    exit;
}

if (!in_array($role, ['student', 'company', 'admin'])) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Invalid role selected.']);
    exit;
}

// ── Look up user ──────────────────────────────
try {
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? AND role = ?');
    $stmt->execute([$email, $role]);
    $user = $stmt->fetch();
} catch (PDOException $e) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    exit;
}

if (!$user || !password_verify($password, $user['password'])) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Incorrect email, password, or role.']);
    exit;
}

// ── Generate 6-digit OTP ──────────────────────
$otp     = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
$expires = date('Y-m-d H:i:s', time() + 600);

// ── Save OTP to DB ────────────────────────────
try {
    $stmt = $pdo->prepare(
        'UPDATE users SET two_fa_code = ?, two_fa_expires = ? WHERE id = ?'
    );
    $stmt->execute([$otp, $expires, $user['id']]);
} catch (PDOException $e) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Failed to save OTP: ' . $e->getMessage()]);
    exit;
}

// ── Store pending session ─────────────────────
$_SESSION['pending_user_id'] = $user['id'];
$_SESSION['pending_role']    = $user['role'];

// ── Send email ────────────────────────────────
$to      = $user['email'];
$subject = 'Your internLink verification code';
$body    = "Hello {$user['first_name']},\n\n"
         . "Your internLink verification code is:\n\n"
         . "  {$otp}\n\n"
         . "This code expires in 10 minutes.\n\n"
         . "— The internLink Team";
$headers = 'From: no-reply@internlink.com';

$mailSent = @mail($to, $subject, $body, $headers);

// ── Dev fallback: log OTP to file ─────────────
if (!$mailSent) {
    $logDir = __DIR__ . '/../logs/';
    if (!is_dir($logDir)) mkdir($logDir, 0755, true);
    file_put_contents(
        $logDir . 'otp_dev.log',
        '[' . date('Y-m-d H:i:s') . '] Email: ' . $email . ' | OTP: ' . $otp . PHP_EOL,
        FILE_APPEND
    );
}

ob_end_clean(); // Discard any warnings before sending JSON
echo json_encode([
    'success'      => true,
    'requires_2fa' => true,
    'message'      => '2FA code sent to your email.',
    'dev_otp'      => $mailSent ? null : $otp,
]);

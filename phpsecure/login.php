<?php
// ─────────────────────────────────────────────
//  login.php  —  internLink
//  Step 1 of login: validate credentials,
//  generate a 6-digit 2FA code, store it in DB,
//  and send it via email.
//  POST fields: email, password, role
// ─────────────────────────────────────────────

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

// ── Look up user ──────────────────────────────
$stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? AND role = ?');
$stmt->execute([$email, $role]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password'])) {
    echo json_encode(['success' => false, 'message' => 'Incorrect email, password, or role.']);
    exit;
}

// ── Generate 6-digit OTP ──────────────────────
$otp     = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
$expires = date('Y-m-d H:i:s', time() + 600); // 10 minutes

// ── Save OTP to DB ────────────────────────────
$stmt = $pdo->prepare(
    'UPDATE users SET two_fa_code = ?, two_fa_expires = ? WHERE id = ?'
);
$stmt->execute([$otp, $expires, $user['id']]);

// ── Store user id temporarily in session ──────
$_SESSION['pending_user_id'] = $user['id'];
$_SESSION['pending_role']    = $user['role'];

// ── Send email with OTP ───────────────────────
$to      = $user['email'];
$subject = 'Your internLink verification code';
$body    = "Hello {$user['first_name']},\n\n"
         . "Your internLink login verification code is:\n\n"
         . "  {$otp}\n\n"
         . "This code expires in 10 minutes.\n\n"
         . "If you did not request this, please ignore this email.\n\n"
         . "— The internLink Team";
$headers = 'From: no-reply@internlink.com';

$mailSent = @mail($to, $subject, $body, $headers);

echo json_encode([
    'success'      => true,
    'requires_2fa' => true,
    'message'      => '2FA code sent to your email.',
    // DEV ONLY — remove before production:
    'dev_otp'      => $mailSent ? null : $otp,
]);

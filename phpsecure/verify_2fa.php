<?php
// ─────────────────────────────────────────────
//  verify_2fa.php  —  internLink
//  Step 2 of login: verify the 6-digit OTP,
//  start a full session, return redirect URL.
//  POST fields: otp
// ─────────────────────────────────────────────

header('Content-Type: application/json');
session_start();
require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

// ── Must have a pending login in session ──────
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

// ── Fetch user & check OTP ────────────────────
$stmt = $pdo->prepare(
    'SELECT * FROM users WHERE id = ? AND two_fa_code = ? AND two_fa_expires > NOW()'
);
$stmt->execute([$userId, $otp]);
$user = $stmt->fetch();

if (!$user) {
    echo json_encode(['success' => false, 'message' => 'Invalid or expired code. Please try again.']);
    exit;
}

// ── Clear the OTP from DB ─────────────────────
$pdo->prepare('UPDATE users SET two_fa_code = NULL, two_fa_expires = NULL WHERE id = ?')
    ->execute([$userId]);

// ── Start authenticated session ───────────────
unset($_SESSION['pending_user_id'], $_SESSION['pending_role']);

$_SESSION['user_id']    = $user['id'];
$_SESSION['user_email'] = $user['email'];
$_SESSION['user_name']  = $user['first_name'] . ' ' . $user['last_name'];
$_SESSION['user_role']  = $user['role'];

// ── Decide redirect URL based on role ─────────
$redirectMap = [
    'student' => '../student/Student_dashboard.html',
    'company' => '../company/Company_dashboard.html',
    'admin'   => '../admin/admin_dashboard.html',
];
$redirect = $redirectMap[$user['role']] ?? '../index.html';

echo json_encode([
    'success'  => true,
    'message'  => 'Verified successfully!',
    'redirect' => $redirect,
]);

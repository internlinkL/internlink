<?php
session_start();
header('Content-Type: application/json');
require 'db.php';

// Must have a pending 2FA session
if (empty($_SESSION['2fa_user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Session expired. Please log in again.']);
    exit;
}

$entered = trim($_POST['otp'] ?? '');

if (!$entered || !preg_match('/^\d{6}$/', $entered)) {
    echo json_encode(['success' => false, 'message' => 'Enter the 6-digit code sent to your email.']);
    exit;
}

$userId = (int) $_SESSION['2fa_user_id'];
$stmt   = $pdo->prepare("SELECT id, email, role, two_fa_code, two_fa_expires FROM users WHERE id = ? LIMIT 1");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    echo json_encode(['success' => false, 'message' => 'User not found.']);
    exit;
}

if (!$user['two_fa_expires'] || strtotime($user['two_fa_expires']) < time()) {
    echo json_encode(['success' => false, 'message' => 'Code expired. Please log in again.']);
    exit;
}

if (!hash_equals((string) $user['two_fa_code'], $entered)) {
    echo json_encode(['success' => false, 'message' => 'Incorrect code. Please try again.']);
    exit;
}

// Clear OTP
$pdo->prepare("UPDATE users SET two_fa_code = NULL, two_fa_expires = NULL WHERE id = ?")->execute([$userId]);

// Promote session
unset($_SESSION['2fa_user_id'], $_SESSION['2fa_role']);
session_regenerate_id(true);
$_SESSION['user_id'] = $user['id'];
$_SESSION['email']   = $user['email'];
$_SESSION['role']    = $user['role'];

$redirectMap = [
    'student' => '../html/Company_dashboard.html',
    'company' => '../html/Company_dashboard.html',
    'admin'   => '../html/index.html',
];

echo json_encode([
    'success'  => true,
    'message'  => 'Login successful.',
    'role'     => $user['role'],
    'redirect' => $redirectMap[$user['role']] ?? '../html/index.html',
]);

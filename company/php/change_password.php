<?php

error_reporting(0);
ini_set('display_errors', 0);

session_save_path(sys_get_temp_dir());
session_name('internlink_session');
session_start();
// ─────────────────────────────────────────────
//  change_password.php  —  internLink
//  Verifies current password and updates to
//  a new one.
//  POST fields: currentPassword, newPassword,
//               confirmPassword
// ─────────────────────────────────────────────

header('Content-Type: application/json');
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth_guard.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$current = $_POST['currentPassword'] ?? '';
$new     = $_POST['newPassword']     ?? '';
$confirm = $_POST['confirmPassword'] ?? '';

if (!$current || !$new || !$confirm) {
    echo json_encode(['success' => false, 'message' => 'Please fill in all password fields.']);
    exit;
}

if (strlen($new) < 8) {
    echo json_encode(['success' => false, 'message' => 'New password must be at least 8 characters.']);
    exit;
}

if ($new !== $confirm) {
    echo json_encode(['success' => false, 'message' => 'New passwords do not match.']);
    exit;
}

// ── Fetch current hashed password ─────────────
$stmt = $pdo->prepare('SELECT password FROM users WHERE id = ?');
$stmt->execute([$companyUserId]);
$user = $stmt->fetch();

if (!$user || !password_verify($current, $user['password'])) {
    echo json_encode(['success' => false, 'message' => 'Current password is incorrect.']);
    exit;
}

// ── Update password ───────────────────────────
$hashed = password_hash($new, PASSWORD_BCRYPT);
$pdo->prepare('UPDATE users SET password = ? WHERE id = ?')
    ->execute([$hashed, $companyUserId]);

echo json_encode(['success' => true, 'message' => 'Password updated successfully!']);

<?php
error_reporting(0);
ini_set('display_errors', 0);

// ─────────────────────────────────────────────
//  change_password.php — internLink
//  Verifies current password and updates to
//  a new one.
//  POST fields: currentPassword, newPassword,
//               confirmPassword
// ─────────────────────────────────────────────

header('Content-Type: application/json');
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/../../phpsecure/db.php';
require_once __DIR__ . '/../../phpsecure/auth_guard.php';

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

// FIX: stronger password policy on new password
if (strlen($new) < 8) {
    echo json_encode(['success' => false, 'message' => 'New password must be at least 8 characters.']);
    exit;
}
if (!preg_match('/[A-Z]/', $new)) {
    echo json_encode(['success' => false, 'message' => 'New password must contain at least one uppercase letter.']);
    exit;
}
if (!preg_match('/[0-9]/', $new)) {
    echo json_encode(['success' => false, 'message' => 'New password must contain at least one number.']);
    exit;
}

if ($new !== $confirm) {
    echo json_encode(['success' => false, 'message' => 'New passwords do not match.']);
    exit;
}

// FIX: wrap DB calls in try/catch so errors are logged not exposed
try {
    $stmt = $pdo->prepare('SELECT password FROM users WHERE id = ?');
    $stmt->execute([$companyUserId]);
    $user = $stmt->fetch();
} catch (PDOException $e) {
    error_log('change_password fetch error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'A server error occurred.']);
    exit;
}

if (!$user || !password_verify($current, $user['password'])) {
    echo json_encode(['success' => false, 'message' => 'Current password is incorrect.']);
    exit;
}

try {
    $hashed = password_hash($new, PASSWORD_BCRYPT);
    $pdo->prepare('UPDATE users SET password = ? WHERE id = ?')
        ->execute([$hashed, $companyUserId]);
    echo json_encode(['success' => true, 'message' => 'Password updated successfully!']);
} catch (PDOException $e) {
    error_log('change_password update error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'A server error occurred.']);
}

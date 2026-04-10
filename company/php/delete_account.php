<?php
error_reporting(0);
ini_set('display_errors', 0);

// ─────────────────────────────────────────────
//  delete_account.php — internLink
//  Permanently deletes the company account,
//  all their offers, and all applications.
//  (CASCADE handles offers + applications)
//  POST fields: action = 'delete_account'
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

if (($_POST['action'] ?? '') !== 'delete_account') {
    echo json_encode(['success' => false, 'message' => 'Invalid action.']);
    exit;
}

try {
    $pdo->prepare('DELETE FROM users WHERE id = ? AND role = ?')
        ->execute([$companyUserId, 'company']);
} catch (PDOException $e) {
    error_log('delete_account error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to delete account. Please try again.']);
    exit;
}

// FIX: fully destroy the session including the browser cookie
session_unset();
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}
session_destroy();

echo json_encode(['success' => true, 'message' => 'Account deleted.']);

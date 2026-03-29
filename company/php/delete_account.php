<?php

error_reporting(0);
ini_set('display_errors', 0);

session_save_path(sys_get_temp_dir());
session_name('internlink_session');
session_start();
// ─────────────────────────────────────────────
//  delete_account.php  —  internLink
//  Permanently deletes the company account,
//  all their offers, and all applications.
//  (CASCADE handles offers + applications)
//  POST fields: action = 'delete_account'
// ─────────────────────────────────────────────

header('Content-Type: application/json');
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth_guard.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

if (($_POST['action'] ?? '') !== 'delete_account') {
    echo json_encode(['success' => false, 'message' => 'Invalid action.']);
    exit;
}

try {
    // Deleting from users cascades to:
    // company_profiles, internship_offers, applications
    $pdo->prepare('DELETE FROM users WHERE id = ? AND role = ?')
        ->execute([$companyUserId, 'company']);

    // Destroy session
    session_unset();
    session_destroy();

    echo json_encode(['success' => true, 'message' => 'Account deleted.']);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Failed to delete account. Please try again.']);
}

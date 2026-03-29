<?php
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

$data   = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? '';   // delete | ban | unban
$userId = (int)($data['user_id'] ?? 0);

if (!$userId || !in_array($action, ['delete','ban','unban'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}

// Prevent admin from acting on themselves
if ($userId === (int)$_SESSION['user_id']) {
    echo json_encode(['success' => false, 'message' => 'You cannot modify your own account.']);
    exit;
}

// Check target is not another admin
$stmt = $pdo->prepare("SELECT role FROM users WHERE id=?");
$stmt->execute([$userId]);
$target = $stmt->fetch();
if (!$target) {
    echo json_encode(['success' => false, 'message' => 'User not found.']);
    exit;
}
if ($target['role'] === 'admin' && $action === 'delete') {
    echo json_encode(['success' => false, 'message' => 'Cannot delete another admin.']);
    exit;
}

switch ($action) {
    case 'delete':
        $pdo->prepare("DELETE FROM users WHERE id=?")->execute([$userId]);
        echo json_encode(['success' => true, 'message' => 'User deleted.']);
        break;

    case 'ban':
        // Set a long-past password expiry as "banned" flag — use a dedicated banned column added by upgrade SQL
        // Simple approach: prefix email with BANNED_ so they can't log in
        $pdo->prepare("UPDATE users SET email = CONCAT('BANNED_', id, '_', email) WHERE id=? AND email NOT LIKE 'BANNED_%'")->execute([$userId]);
        echo json_encode(['success' => true, 'message' => 'User banned.']);
        break;

    case 'unban':
        // Remove BANNED_ prefix — extract original email
        $pdo->prepare("UPDATE users SET email = REGEXP_REPLACE(email, '^BANNED_[0-9]+_', '') WHERE id=? AND email LIKE 'BANNED_%'")->execute([$userId]);
        echo json_encode(['success' => true, 'message' => 'User unbanned.']);
        break;
}

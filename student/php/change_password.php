<?php
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

$userId = $_SESSION['user_id'];
$data   = json_decode(file_get_contents('php://input'), true);

$currentPw = $data['currentPassword'] ?? '';
$newPw     = $data['newPassword']     ?? '';

if (strlen($newPw) < 8) {
    echo json_encode(['success' => false, 'message' => 'New password must be at least 8 characters.']);
    exit;
}

$stmt = $pdo->prepare("SELECT password FROM users WHERE id=?");
$stmt->execute([$userId]);
$row  = $stmt->fetch();

if (!$row || !password_verify($currentPw, $row['password'])) {
    echo json_encode(['success' => false, 'message' => 'Current password is incorrect.']);
    exit;
}

$hash = password_hash($newPw, PASSWORD_DEFAULT);
$pdo->prepare("UPDATE users SET password=? WHERE id=?")->execute([$hash, $userId]);

echo json_encode(['success' => true, 'message' => 'Password updated successfully.']);

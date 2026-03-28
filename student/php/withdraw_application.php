<?php
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

$userId = $_SESSION['user_id'];
$data   = json_decode(file_get_contents('php://input'), true);
$appId  = (int)($data['application_id'] ?? 0);

// Verify ownership and that it's still pending
$stmt = $pdo->prepare("SELECT id FROM applications WHERE id=? AND student_id=? AND status='pending'");
$stmt->execute([$appId, $userId]);

if (!$stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Application not found or cannot be withdrawn.']);
    exit;
}

$pdo->prepare("DELETE FROM applications WHERE id=? AND student_id=?")->execute([$appId, $userId]);
echo json_encode(['success' => true, 'message' => 'Application withdrawn.']);

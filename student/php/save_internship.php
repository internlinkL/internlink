<?php
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

$userId = $_SESSION['user_id'];
$data   = json_decode(file_get_contents('php://input'), true);
$iid    = (int)($data['internship_id'] ?? 0);
$action = $data['action'] ?? 'save';

if (!$iid) {
    echo json_encode(['success' => false, 'message' => 'Invalid internship.']);
    exit;
}

if ($action === 'save') {
    $pdo->prepare("INSERT IGNORE INTO saved_internships (student_id, internship_id, saved_at) VALUES (?,?,NOW())")
        ->execute([$userId, $iid]);
} else {
    $pdo->prepare("DELETE FROM saved_internships WHERE student_id=? AND internship_id=?")
        ->execute([$userId, $iid]);
}

echo json_encode(['success' => true]);

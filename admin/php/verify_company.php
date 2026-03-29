<?php
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

$data     = json_decode(file_get_contents('php://input'), true);
$action   = $data['action']   ?? '';   // approve | reject
$userId   = (int)($data['user_id'] ?? 0);

if (!$userId || !in_array($action, ['approve','reject'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}

// Verify company exists
$stmt = $pdo->prepare("SELECT id FROM users WHERE id=? AND role='company'");
$stmt->execute([$userId]);
if (!$stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Company not found.']);
    exit;
}

// Ensure company_profiles row exists
$chk = $pdo->prepare("SELECT id FROM company_profiles WHERE user_id=?");
$chk->execute([$userId]);
if (!$chk->fetch()) {
    $pdo->prepare("INSERT INTO company_profiles (user_id) VALUES (?)")->execute([$userId]);
}

if ($action === 'approve') {
    $pdo->prepare("UPDATE company_profiles SET is_verified=1 WHERE user_id=?")->execute([$userId]);
    echo json_encode(['success' => true, 'message' => 'Company verified.']);
} else {
    // Reject: set is_verified=0 and delete their internships
    $pdo->prepare("UPDATE company_profiles SET is_verified=0 WHERE user_id=?")->execute([$userId]);
    echo json_encode(['success' => true, 'message' => 'Company rejected.']);
}

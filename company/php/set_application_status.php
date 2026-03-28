<?php
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth_guard.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$appId  = !empty($_POST['id'])     ? (int) $_POST['id'] : 0;
$status = trim($_POST['status']    ?? '');

if (!$appId) {
    echo json_encode(['success' => false, 'message' => 'Invalid application ID.']);
    exit;
}

if (!in_array($status, ['waiting', 'accepted', 'rejected'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid status value.']);
    exit;
}

// Verify the application belongs to this company via internship_offers
$stmt = $pdo->prepare("
    SELECT a.id FROM applications a
    JOIN internship_offers io ON io.id = a.offer_id
    WHERE a.id = ? AND io.company_id = ?
");
$stmt->execute([$appId, $companyUserId]);
if (!$stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Application not found or access denied.']);
    exit;
}

$pdo->prepare('UPDATE applications SET status = ? WHERE id = ?')
    ->execute([$status, $appId]);

echo json_encode([
    'success' => true,
    'message' => 'Application status updated.',
    'status'  => $status,
]);

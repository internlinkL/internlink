<?php
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

$data   = json_decode(file_get_contents('php://input'), true);
$action = $data['action']        ?? '';   // delete | activate | deactivate
$id     = (int)($data['id']      ?? 0);

if (!$id || !in_array($action, ['delete','activate','deactivate'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}

// Verify internship exists
$stmt = $pdo->prepare("SELECT id FROM internships WHERE id=?");
$stmt->execute([$id]);
if (!$stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Internship not found.']);
    exit;
}

switch ($action) {
    case 'delete':
        $pdo->prepare("DELETE FROM internships WHERE id=?")->execute([$id]);
        echo json_encode(['success' => true, 'message' => 'Internship deleted.']);
        break;

    case 'activate':
        $pdo->prepare("UPDATE internships SET is_active=1, status='open' WHERE id=?")->execute([$id]);
        echo json_encode(['success' => true, 'message' => 'Internship activated.']);
        break;

    case 'deactivate':
        $pdo->prepare("UPDATE internships SET is_active=0, status='closed' WHERE id=?")->execute([$id]);
        echo json_encode(['success' => true, 'message' => 'Internship deactivated.']);
        break;
}

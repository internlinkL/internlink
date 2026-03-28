<?php

error_reporting(0);
ini_set('display_errors', 0);

session_save_path(sys_get_temp_dir());
session_name('internlink_session');
session_start();
// ─────────────────────────────────────────────
//  set_application_status.php  —  internLink
//  Updates an application's status to
//  waiting / accepted / rejected.
//  POST fields: id (application id), status
// ─────────────────────────────────────────────

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

// ── Verify the application belongs to this company ──
$stmt = $pdo->prepare(
    'SELECT a.id FROM applications a
     JOIN internship_offers o ON o.id = a.offer_id
     WHERE a.id = ? AND o.company_id = ?'
);
$stmt->execute([$appId, $companyUserId]);
if (!$stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Application not found or access denied.']);
    exit;
}

// ── Update status ─────────────────────────────
$pdo->prepare('UPDATE applications SET status = ? WHERE id = ?')
    ->execute([$status, $appId]);

echo json_encode([
    'success' => true,
    'message' => 'Application status updated.',
    'status'  => $status,
]);

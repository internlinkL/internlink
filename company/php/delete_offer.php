<?php

error_reporting(0);
ini_set('display_errors', 0);

session_save_path(sys_get_temp_dir());
session_name('internlink_session');
session_start();
// ─────────────────────────────────────────────
//  delete_offer.php  —  internLink
//  Deletes an internship offer (and all its
//  applications via CASCADE).
//  POST fields: id
// ─────────────────────────────────────────────

header('Content-Type: application/json');
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth_guard.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$id = !empty($_POST['id']) ? (int) $_POST['id'] : 0;

if (!$id) {
    echo json_encode(['success' => false, 'message' => 'Invalid offer ID.']);
    exit;
}

// ── Verify the offer belongs to this company ──
$stmt = $pdo->prepare('SELECT id FROM internship_offers WHERE id = ? AND company_id = ?');
$stmt->execute([$id, $companyUserId]);
if (!$stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Offer not found or access denied.']);
    exit;
}

// ── Delete (applications cascade automatically) 
$pdo->prepare('DELETE FROM internship_offers WHERE id = ? AND company_id = ?')
    ->execute([$id, $companyUserId]);

echo json_encode(['success' => true, 'message' => 'Offer deleted successfully.']);

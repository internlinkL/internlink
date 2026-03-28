<?php
error_reporting(0);
ini_set('display_errors', 0);
// ── get_saved.php ─────────────────────────────────────────────────────────
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

$userId = $_SESSION['user_id'];
$stmt   = $pdo->prepare("SELECT internship_id FROM saved_internships WHERE student_id=?");
$stmt->execute([$userId]);
$ids    = array_column($stmt->fetchAll(), 'internship_id');

echo json_encode(['success' => true, 'ids' => $ids]);

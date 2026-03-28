<?php

error_reporting(0);
ini_set('display_errors', 0);

session_save_path(sys_get_temp_dir());
session_name('internlink_session');
session_start();
// ─────────────────────────────────────────────
//  save_offer.php  —  internLink
//  Creates a new internship offer or updates
//  an existing one.
//  POST fields: id (optional — if editing),
//               title, field, location,
//               duration, status, skills, desc
// ─────────────────────────────────────────────

header('Content-Type: application/json');
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth_guard.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

// ── Collect fields ────────────────────────────
$id       = !empty($_POST['id']) ? (int) $_POST['id'] : null;
$title    = trim($_POST['title']    ?? '');
$field    = trim($_POST['field']    ?? '');
$location = trim($_POST['location'] ?? '');
$duration = trim($_POST['duration'] ?? '');
$status   = trim($_POST['status']   ?? 'active');
$skills   = trim($_POST['skills']   ?? '');
$desc     = trim($_POST['desc']     ?? '');

// ── Validate required fields ──────────────────
if (!$title || !$field || !$location || !$duration) {
    echo json_encode(['success' => false, 'message' => 'Please fill in all required fields.']);
    exit;
}

if (!in_array($status, ['active', 'closed'])) {
    $status = 'active';
}

if ($id) {
    // ── EDIT: verify ownership first ──────────
    $stmt = $pdo->prepare('SELECT id FROM internship_offers WHERE id = ? AND company_id = ?');
    $stmt->execute([$id, $companyUserId]);
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Offer not found or access denied.']);
        exit;
    }

    $pdo->prepare(
        'UPDATE internship_offers
         SET title = ?, field = ?, location = ?, duration = ?,
             status = ?, skills = ?, description = ?
         WHERE id = ? AND company_id = ?'
    )->execute([$title, $field, $location, $duration, $status, $skills, $desc, $id, $companyUserId]);

    echo json_encode(['success' => true, 'message' => 'Offer updated successfully!', 'id' => $id]);

} else {
    // ── CREATE ─────────────────────────────────
    $stmt = $pdo->prepare(
        'INSERT INTO internship_offers
         (company_id, title, field, location, duration, status, skills, description)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([$companyUserId, $title, $field, $location, $duration, $status, $skills, $desc]);
    $newId = (int) $pdo->lastInsertId();

    echo json_encode(['success' => true, 'message' => 'Offer posted successfully!', 'id' => $newId]);
}

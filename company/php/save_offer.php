<?php
error_reporting(0);
ini_set('display_errors', 0);

// ─────────────────────────────────────────────
//  save_offer.php — internLink
//  Creates a new internship offer or updates
//  an existing one.
//  POST fields: id (optional — if editing),
//               title, field, location,
//               duration, status, skills, desc
// ─────────────────────────────────────────────

header('Content-Type: application/json');
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/../../phpsecure/db.php';
require_once __DIR__ . '/../../phpsecure/auth_guard.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

// FIX: sanitize all text inputs to prevent stored XSS
function clean(string $val): string {
    return htmlspecialchars(trim($val), ENT_QUOTES, 'UTF-8');
}

$id       = !empty($_POST['id']) ? (int) $_POST['id'] : null;
$title    = clean($_POST['title']    ?? '');
$field    = clean($_POST['field']    ?? '');
$location = clean($_POST['location'] ?? '');
$duration = clean($_POST['duration'] ?? '');
$status   = trim($_POST['status']    ?? 'active');
$skills   = clean($_POST['skills']   ?? '');
$desc     = clean($_POST['desc']     ?? '');

if (!$title || !$field || !$location || !$duration) {
    echo json_encode(['success' => false, 'message' => 'Please fill in all required fields.']);
    exit;
}

if (!in_array($status, ['active', 'closed'])) {
    $status = 'active';
}

try {
    if ($id) {
        // EDIT: verify ownership first — company can only edit their own offers
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
        // CREATE
        $stmt = $pdo->prepare(
            'INSERT INTO internship_offers
             (company_id, title, field, location, duration, status, skills, description)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$companyUserId, $title, $field, $location, $duration, $status, $skills, $desc]);
        $newId = (int) $pdo->lastInsertId();

        echo json_encode(['success' => true, 'message' => 'Offer posted successfully!', 'id' => $newId]);
    }
} catch (PDOException $e) {
    error_log('save_offer error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'A server error occurred.']);
}

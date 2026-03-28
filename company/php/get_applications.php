<?php

error_reporting(0);
ini_set('display_errors', 0);

session_save_path(sys_get_temp_dir());
session_name('internlink_session');
session_start();
// ─────────────────────────────────────────────
//  get_applications.php  —  internLink
//  Returns all applications for the logged-in
//  company, including full student profile.
//  GET param: offer_id (optional, to filter)
//  Returns JSON.
// ─────────────────────────────────────────────

header('Content-Type: application/json');
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth_guard.php';

$offerId = !empty($_GET['offer_id']) ? (int) $_GET['offer_id'] : null;

// ── Base query ────────────────────────────────
$sql = "SELECT
            a.id            AS application_id,
            a.status,
            a.applied_at,
            u.id            AS student_id,
            u.first_name,
            u.last_name,
            u.email,
            sp.university,
            sp.field_of_study,
            sp.year,
            sp.city,
            sp.country,
            sp.skills,
            sp.bio,
            o.id            AS offer_id,
            o.title         AS offer_title
        FROM applications a
        JOIN users u          ON u.id   = a.student_id
        LEFT JOIN student_profiles sp ON sp.user_id = u.id
        JOIN internship_offers o ON o.id = a.offer_id
        WHERE o.company_id = ?";

$params = [$companyUserId];

if ($offerId) {
    $sql     .= ' AND o.id = ?';
    $params[] = $offerId;
}

$sql .= ' ORDER BY a.applied_at DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$applications = $stmt->fetchAll();

// ── Also return offer list for tab filters ────
$offerStmt = $pdo->prepare(
    "SELECT o.id, o.title, COUNT(a.id) AS app_count
     FROM internship_offers o
     LEFT JOIN applications a ON a.offer_id = o.id
     WHERE o.company_id = ?
     GROUP BY o.id
     ORDER BY o.created_at DESC"
);
$offerStmt->execute([$companyUserId]);
$offers = $offerStmt->fetchAll();

echo json_encode([
    'success'      => true,
    'applications' => $applications,
    'offers'       => $offers,
]);

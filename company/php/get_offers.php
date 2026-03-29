<?php

error_reporting(0);
ini_set('display_errors', 0);

session_save_path(sys_get_temp_dir());
session_name('internlink_session');
session_start();
// ─────────────────────────────────────────────
//  get_offers.php  —  internLink
//  Returns all internship offers for the
//  logged-in company, with applicant counts.
//  GET request, returns JSON.
// ─────────────────────────────────────────────

header('Content-Type: application/json');
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth_guard.php';

$stmt = $pdo->prepare(
    "SELECT o.id, o.title, o.field, o.location, o.duration,
            o.skills, o.description, o.status, o.created_at,
            COUNT(a.id) AS applicant_count
     FROM internship_offers o
     LEFT JOIN applications a ON a.offer_id = o.id
     WHERE o.company_id = ?
     GROUP BY o.id
     ORDER BY o.created_at DESC"
);
$stmt->execute([$companyUserId]);
$offers = $stmt->fetchAll();

echo json_encode([
    'success' => true,
    'offers'  => $offers,
]);

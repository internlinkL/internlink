<?php
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth_guard.php';

$offerId = !empty($_GET['offer_id']) ? (int) $_GET['offer_id'] : null;

$sql = "
    SELECT
        a.id            AS application_id,
        a.status,
        a.cover_letter,
        a.match_percent,
        a.applied_at,
        u.id            AS student_id,
        u.first_name,
        u.last_name,
        u.email,
        sp.university,
        sp.field_of_study,
        sp.academic_year AS year,
        sp.wilaya        AS city,
        sp.country,
        sp.skills,
        sp.bio,
        sp.linkedin,
        sp.github,
        sp.cv_path,
        io.id            AS offer_id,
        io.title         AS offer_title
    FROM applications a
    JOIN users u               ON u.id   = a.student_id
    LEFT JOIN student_profiles sp ON sp.user_id = u.id
    JOIN internship_offers io  ON io.id  = a.offer_id
    WHERE io.company_id = ?
";

$params = [$companyUserId];

if ($offerId) {
    $sql     .= ' AND io.id = ?';
    $params[] = $offerId;
}

$sql .= ' ORDER BY a.applied_at DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$applications = $stmt->fetchAll();

// Offer list for tab filters
$offerStmt = $pdo->prepare("
    SELECT io.id, io.title, COUNT(a.id) AS app_count
    FROM internship_offers io
    LEFT JOIN applications a ON a.offer_id = io.id
    WHERE io.company_id = ?
    GROUP BY io.id
    ORDER BY io.created_at DESC
");
$offerStmt->execute([$companyUserId]);
$offers = $offerStmt->fetchAll();

echo json_encode([
    'success'      => true,
    'applications' => $applications,
    'offers'       => $offers,
]);

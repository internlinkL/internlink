<?php
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

$userId = $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT
        a.id, a.status, a.cover_letter, a.match_percent,
        a.applied_at, a.viewed_at, a.decided_at, a.feedback,
        i.title, i.duration_months, i.required_skills,
        i.wilaya  AS internship_city,
        i.country AS internship_country,
        u.first_name AS company_name,
        COALESCE(i.country, cp.country) AS country,
        COALESCE(i.wilaya,  cp.wilaya)  AS wilaya
    FROM applications a
    JOIN internships i       ON i.id       = a.internship_id
    JOIN users u             ON u.id       = i.company_id
    LEFT JOIN company_profiles cp ON cp.user_id = i.company_id
    WHERE a.student_id = ?
    ORDER BY a.applied_at DESC
");
$stmt->execute([$userId]);
$apps = $stmt->fetchAll();

$nameStmt = $pdo->prepare("SELECT first_name FROM users WHERE id=?");
$nameStmt->execute([$userId]);
$name = $nameStmt->fetchColumn();

echo json_encode([
    'success'      => true,
    'applications' => $apps,
    'student_name' => $name,
]);

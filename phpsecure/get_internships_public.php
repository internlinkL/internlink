<?php
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require_once __DIR__ . '/db.php';

// No auth required — public endpoint
$stmt = $pdo->prepare("
    SELECT
        io.id,
        io.title,
        io.field,
        io.location,
        io.duration,
        io.skills,
        io.description,
        io.status,
        io.created_at,
        cp.company_name
    FROM internship_offers io
    JOIN company_profiles cp ON cp.user_id = io.company_id
    WHERE io.status = 'active'
    ORDER BY io.created_at DESC
");
$stmt->execute();
$internships = $stmt->fetchAll();

echo json_encode([
    'success'     => true,
    'internships' => $internships,
]);

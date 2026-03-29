<?php
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

// ── Counts ────────────────────────────────────────────────────────────────────
$counts = [];

$rows = [
    'total_users'       => "SELECT COUNT(*) FROM users",
    'total_students'    => "SELECT COUNT(*) FROM users WHERE role='student'",
    'total_companies'   => "SELECT COUNT(*) FROM users WHERE role='company'",
    'verified_companies'=> "SELECT COUNT(*) FROM company_profiles WHERE is_verified=1",
    'pending_companies' => "SELECT COUNT(*) FROM company_profiles WHERE is_verified=0",
    'total_internships' => "SELECT COUNT(*) FROM internships",
    'active_internships'=> "SELECT COUNT(*) FROM internships WHERE is_active=1 OR status='open'",
    'total_applications'=> "SELECT COUNT(*) FROM applications",
    'pending_apps'      => "SELECT COUNT(*) FROM applications WHERE status='pending'",
    'accepted_apps'     => "SELECT COUNT(*) FROM applications WHERE status='accepted'",
    'rejected_apps'     => "SELECT COUNT(*) FROM applications WHERE status='rejected'",
];

foreach ($rows as $key => $sql) {
    try {
        $counts[$key] = (int) $pdo->query($sql)->fetchColumn();
    } catch (PDOException $e) {
        $counts[$key] = 0;
    }
}

// ── Recent registrations (last 7 users) ──────────────────────────────────────
$recent_users = $pdo->query("
    SELECT id, first_name, last_name, email, role, created_at
    FROM users ORDER BY created_at DESC LIMIT 7
")->fetchAll();

// ── Recent applications (last 7) ─────────────────────────────────────────────
$recent_apps = $pdo->query("
    SELECT a.id, a.status, a.applied_at,
           CONCAT(u.first_name,' ',u.last_name) AS student_name,
           i.title AS internship_title,
           cu.first_name AS company_name
    FROM applications a
    JOIN users u  ON u.id  = a.student_id
    JOIN internships i ON i.id = a.internship_id
    JOIN users cu ON cu.id = i.company_id
    ORDER BY a.applied_at DESC LIMIT 7
")->fetchAll();

// ── Registrations per day (last 14 days) ─────────────────────────────────────
$reg_chart = $pdo->query("
    SELECT DATE(created_at) AS day, COUNT(*) AS cnt
    FROM users
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 14 DAY)
    GROUP BY DATE(created_at)
    ORDER BY day ASC
")->fetchAll();

echo json_encode([
    'success'      => true,
    'counts'       => $counts,
    'recent_users' => $recent_users,
    'recent_apps'  => $recent_apps,
    'reg_chart'    => $reg_chart,
]);

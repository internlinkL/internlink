<?php
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

$userId = $_SESSION['user_id'];

// ── Fetch profile ─────────────────────────────────────────────────────────
$stmt = $pdo->prepare("
    SELECT u.id, u.first_name, u.last_name, u.email, u.role,
           sp.university, sp.field_of_study,
           sp.year        AS academic_year,
           sp.city        AS wilaya,
           sp.country, sp.bio, sp.skills,
           NULL           AS phone,
           NULL           AS linkedin,
           NULL           AS github,
           NULL           AS cv_path
    FROM users u
    LEFT JOIN student_profiles sp ON sp.user_id = u.id
    WHERE u.id = ?
    LIMIT 1
");
$stmt->execute([$userId]);
$profile = $stmt->fetch();

if (!$profile) {
    echo json_encode(['success' => false, 'message' => 'Profile not found.']);
    exit;
}

// ── Application stats ─────────────────────────────────────────────────────
$stmt2 = $pdo->prepare("
    SELECT
        COUNT(*)                  AS applications,
        SUM(status = 'accepted')  AS accepted,
        AVG(match_percent)        AS avg_match
    FROM applications
    WHERE student_id = ?
");
$stmt2->execute([$userId]);
$stats = $stmt2->fetch();

// ── Current CV info ───────────────────────────────────────────────────────
$cv = null; // cv_path column not in student_profiles table yet

echo json_encode([
    'success' => true,
    'profile' => $profile,
    'stats'   => [
        'applications' => (int)($stats['applications'] ?? 0),
        'accepted'     => (int)($stats['accepted']     ?? 0),
        'avg_match'    => $stats['avg_match'] ? (int)round($stats['avg_match']) : null,
    ],
    'cv' => $cv,
]);

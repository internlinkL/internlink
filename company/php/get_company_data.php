<?php
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');

session_save_path(sys_get_temp_dir());
session_name('internlink_session');
session_start();

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth_guard.php';

// ── Fetch company profile ─────────────────────
$stmt = $pdo->prepare(
    'SELECT u.first_name, u.last_name, u.email,
            cp.company_name, cp.sector, cp.country,
            cp.description, cp.avatar_path, cp.phone, cp.linkedin
     FROM users u
     LEFT JOIN company_profiles cp ON cp.user_id = u.id
     WHERE u.id = ?'
);
$stmt->execute([$companyUserId]);
$profile = $stmt->fetch();

if (!$profile) {
    echo json_encode(['success' => false, 'message' => 'Company profile not found.']);
    exit;
}

$companyName = $profile['company_name'] ?? ($profile['first_name'] . ' ' . $profile['last_name']);
$initials    = strtoupper(substr($companyName, 0, 1));
$parts       = explode(' ', $companyName);
if (count($parts) >= 2) {
    $initials = strtoupper(substr($parts[0], 0, 1) . substr($parts[1], 0, 1));
}

// ── Stats ─────────────────────────────────────
$stmt = $pdo->prepare("SELECT COUNT(*) FROM internship_offers WHERE company_id = ? AND status = 'active'");
$stmt->execute([$companyUserId]);
$activeOffersCount = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare('SELECT COUNT(*) FROM applications a JOIN internship_offers o ON o.id = a.offer_id WHERE o.company_id = ?');
$stmt->execute([$companyUserId]);
$totalApps = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM applications a JOIN internship_offers o ON o.id = a.offer_id WHERE o.company_id = ? AND a.status = 'waiting'");
$stmt->execute([$companyUserId]);
$pendingApps = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM applications a JOIN internship_offers o ON o.id = a.offer_id WHERE o.company_id = ? AND a.status = 'accepted'");
$stmt->execute([$companyUserId]);
$acceptedApps = (int) $stmt->fetchColumn();

// ── Recent applications ───────────────────────
$stmt = $pdo->prepare(
    "SELECT a.id, a.status, a.applied_at,
            u.first_name, u.last_name,
            sp.university,
            o.title AS offer_title
     FROM applications a
     JOIN users u ON u.id = a.student_id
     LEFT JOIN student_profiles sp ON sp.user_id = u.id
     JOIN internship_offers o ON o.id = a.offer_id
     WHERE o.company_id = ?
     ORDER BY a.applied_at DESC
     LIMIT 5"
);
$stmt->execute([$companyUserId]);
$recentApps = $stmt->fetchAll();

// ── Active offers ─────────────────────────────
$stmt = $pdo->prepare(
    "SELECT o.id, o.title, o.field, o.location, o.duration, o.status,
            COUNT(a.id) AS applicant_count
     FROM internship_offers o
     LEFT JOIN applications a ON a.offer_id = o.id
     WHERE o.company_id = ? AND o.status = 'active'
     GROUP BY o.id
     ORDER BY o.created_at DESC
     LIMIT 5"
);
$stmt->execute([$companyUserId]);
$activeOffers = $stmt->fetchAll();

// ── Profile completion ────────────────────────
$checks = [
    'company_name' => !empty($profile['company_name']),
    'sector'       => !empty($profile['sector']),
    'description'  => !empty($profile['description']),
    'avatar'       => !empty($profile['avatar_path']),
];
$completedCount = count(array_filter($checks));
$profilePct     = (int) round(($completedCount / count($checks)) * 100);

echo json_encode([
    'success' => true,
    'profile' => [
        'company_name' => $companyName,
        'initials'     => $initials,
        'sector'       => $profile['sector']      ?? '',
        'country'      => $profile['country']     ?? '',
        'avatar_path'  => $profile['avatar_path'] ?? '',
    ],
    'stats' => [
        'active_offers' => $activeOffersCount,
        'total_apps'    => $totalApps,
        'pending_apps'  => $pendingApps,
        'accepted_apps' => $acceptedApps,
    ],
    'recent_apps'   => $recentApps,
    'active_offers' => $activeOffers,
    'profile_completion' => [
        'percentage' => $profilePct,
        'checks'     => $checks,
    ],
]);

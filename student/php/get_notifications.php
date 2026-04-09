<?php
// ─────────────────────────────────────────────
//  get_notifications.php — internLink
//  Returns live notifications for the student:
//  1. Application status changes (accepted/rejected)
//  2. New internships matching their skills
//  No DB storage — generated fresh each call.
// ─────────────────────────────────────────────
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/../../phpsecure/db.php';
require_once __DIR__ . '/../../phpsecure/auth_student.php';

$userId = $_SESSION['user_id'];
$notifications = [];

// ── 1. Application status changes ────────────────────────────────────────────
// Show accepted and rejected applications from the last 7 days
try {
    $stmt = $pdo->prepare("
        SELECT
            a.id,
            a.status,
            a.applied_at,
            io.title        AS internship_title,
            cp.company_name
        FROM applications a
        JOIN internship_offers io ON io.id = a.offer_id
        JOIN company_profiles  cp ON cp.user_id = io.company_id
        WHERE a.student_id = ?
          AND a.status IN ('accepted', 'rejected')
          AND a.applied_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ORDER BY a.applied_at DESC
        LIMIT 10
    ");
    $stmt->execute([$userId]);
    $statusApps = $stmt->fetchAll();

    foreach ($statusApps as $app) {
        $isAccepted = $app['status'] === 'accepted';
        $notifications[] = [
            'id'      => 'app_' . $app['id'],
            'type'    => $isAccepted ? 'success' : 'danger',
            'icon'    => $isAccepted ? '✅' : '❌',
            'title'   => $isAccepted ? 'Application Accepted!' : 'Application Rejected',
            'message' => ($isAccepted ? 'Congratulations! ' : 'Unfortunately, ')
                       . $app['company_name'] . ' has '
                       . ($isAccepted ? 'accepted' : 'rejected')
                       . ' your application for "' . $app['internship_title'] . '".',
            'time'    => $app['applied_at'],
        ];
    }
} catch (PDOException $e) {
    error_log('get_notifications app status error: ' . $e->getMessage());
}

// ── 2. New internships matching student skills ────────────────────────────────
// Show internships posted in the last 3 days that match student skills >= 50%
try {
    $skillStmt = $pdo->prepare("SELECT skills FROM student_profiles WHERE user_id = ?");
    $skillStmt->execute([$userId]);
    $sp = $skillStmt->fetch();

    $studentSkills = [];
    if (!empty($sp['skills'])) {
        $studentSkills = array_filter(
            array_map('strtolower', array_map('trim', explode(',', $sp['skills'])))
        );
    }

    if (!empty($studentSkills)) {
        $newOffers = $pdo->prepare("
            SELECT
                io.id,
                io.title,
                io.skills       AS required_skills,
                io.created_at,
                cp.company_name
            FROM internship_offers io
            JOIN company_profiles cp ON cp.user_id = io.company_id
            WHERE io.status = 'active'
              AND io.created_at >= DATE_SUB(NOW(), INTERVAL 3 DAY)
              AND io.id NOT IN (
                  SELECT offer_id FROM applications WHERE student_id = ?
              )
            ORDER BY io.created_at DESC
            LIMIT 20
        ");
        $newOffers->execute([$userId]);
        $offers = $newOffers->fetchAll();

        foreach ($offers as $offer) {
            $required = [];
            if (!empty($offer['required_skills'])) {
                $required = array_filter(
                    array_map('strtolower', array_map('trim', explode(',', $offer['required_skills'])))
                );
            }

            if (empty($required)) continue;

            $matched = 0;
            foreach ($required as $skill) {
                foreach ($studentSkills as $ss) {
                    if (str_contains($ss, $skill) || str_contains($skill, $ss)) {
                        $matched++;
                        break;
                    }
                }
            }
            $matchPct = (int) round(($matched / count($required)) * 100);

            if ($matchPct >= 50) {
                $notifications[] = [
                    'id'      => 'new_' . $offer['id'],
                    'type'    => 'info',
                    'icon'    => '🎯',
                    'title'   => 'New Match: ' . $matchPct . '%',
                    'message' => $offer['company_name'] . ' posted "' . $offer['title'] . '" — '
                               . $matchPct . '% match with your skills.',
                    'time'    => $offer['created_at'],
                ];
            }
        }
    }
} catch (PDOException $e) {
    error_log('get_notifications new offers error: ' . $e->getMessage());
}

// Sort by time descending
usort($notifications, fn($a, $b) => strcmp($b['time'], $a['time']));

// Limit to 15 total
$notifications = array_slice($notifications, 0, 15);

echo json_encode([
    'success'       => true,
    'notifications' => $notifications,
    'count'         => count($notifications),
]);

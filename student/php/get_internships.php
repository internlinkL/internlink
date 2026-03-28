<?php
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

$userId = $_SESSION['user_id'];

// ── Fetch student skills ──────────────────────────────────────────────────
$stmt = $pdo->prepare("
    SELECT u.first_name, sp.skills
    FROM users u
    LEFT JOIN student_profiles sp ON sp.user_id = u.id
    WHERE u.id = ?
");
$stmt->execute([$userId]);
$student = $stmt->fetch();

$studentSkills = [];
if (!empty($student['skills'])) {
    $studentSkills = array_filter(array_map('strtolower', array_map('trim', explode(',', $student['skills']))));
}

// ── Fetch all active internships ──────────────────────────────────────────
$stmt = $pdo->prepare("
    SELECT i.*,
           u.first_name  AS company_name,
           cp.country    AS company_country,
           cp.wilaya     AS company_city,
           cp.sector     AS company_sector,
           COALESCE(i.domain, cp.sector) AS domain
    FROM internships i
    JOIN users u            ON u.id  = i.company_id
    LEFT JOIN company_profiles cp ON cp.user_id = i.company_id
    WHERE i.is_active = 1
      AND (i.deadline IS NULL OR i.deadline >= CURDATE())
    ORDER BY i.created_at DESC
");
$stmt->execute();
$internships = $stmt->fetchAll();

// ── Applied IDs ───────────────────────────────────────────────────────────
$stmt2 = $pdo->prepare("SELECT internship_id FROM applications WHERE student_id=?");
$stmt2->execute([$userId]);
$appliedIds = array_column($stmt2->fetchAll(), 'internship_id');

// ── Compute match % per internship ────────────────────────────────────────
foreach ($internships as &$i) {
    // Normalise location: prefer internship's own country/wilaya, fallback to company's
    if (empty($i['country'])) $i['country'] = $i['company_country'] ?? '';
    if (empty($i['wilaya']))  $i['wilaya']  = $i['company_city']    ?? '';

    $required = [];
    if (!empty($i['required_skills'])) {
        $required = array_filter(array_map('strtolower', array_map('trim', explode(',', $i['required_skills']))));
    }

    if (empty($required) || empty($studentSkills)) {
        $i['match_percent'] = 0;
    } else {
        $matched = 0;
        foreach ($required as $skill) {
            foreach ($studentSkills as $ss) {
                if (str_contains($ss, $skill) || str_contains($skill, $ss)) { $matched++; break; }
            }
        }
        $i['match_percent'] = (int)round(($matched / count($required)) * 100);
    }
}
unset($i);

// Sort by match % descending
usort($internships, fn($a,$b) => ($b['match_percent'] ?? 0) - ($a['match_percent'] ?? 0));

echo json_encode([
    'success'      => true,
    'internships'  => $internships,
    'applied_ids'  => $appliedIds,
    'student_name' => $student['first_name'] ?? 'Student',
]);

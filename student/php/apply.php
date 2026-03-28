<?php
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

$userId = $_SESSION['user_id'];
$data   = json_decode(file_get_contents('php://input'), true);

$internshipId = (int)($data['internship_id'] ?? 0);
$coverLetter  = trim($data['cover_letter']   ?? '');

if (!$internshipId) {
    echo json_encode(['success' => false, 'message' => 'Invalid internship.']);
    exit;
}

// ── Verify internship is active ───────────────────────────────────────────
$stmt = $pdo->prepare("
    SELECT id, required_skills FROM internships
    WHERE id=? AND is_active=1 AND (deadline IS NULL OR deadline >= CURDATE())
");
$stmt->execute([$internshipId]);
$internship = $stmt->fetch();

if (!$internship) {
    echo json_encode(['success' => false, 'message' => 'Internship not found or deadline has passed.']);
    exit;
}

// ── Duplicate check ───────────────────────────────────────────────────────
$dup = $pdo->prepare("SELECT id FROM applications WHERE student_id=? AND internship_id=?");
$dup->execute([$userId, $internshipId]);
if ($dup->fetch()) {
    echo json_encode(['success' => false, 'message' => 'You have already applied to this internship.']);
    exit;
}

// ── Compute match % ───────────────────────────────────────────────────────
$skillStmt = $pdo->prepare("SELECT skills FROM student_profiles WHERE user_id=?");
$skillStmt->execute([$userId]);
$sp = $skillStmt->fetch();

$studentSkills = array_filter(array_map('strtolower', array_map('trim', explode(',', $sp['skills'] ?? ''))));
$required      = array_filter(array_map('strtolower', array_map('trim', explode(',', $internship['required_skills'] ?? ''))));

$matchPct = 0;
if (!empty($required) && !empty($studentSkills)) {
    $matched = 0;
    foreach ($required as $skill) {
        foreach ($studentSkills as $ss) {
            if (str_contains($ss, $skill) || str_contains($skill, $ss)) { $matched++; break; }
        }
    }
    $matchPct = (int)round(($matched / count($required)) * 100);
}

// ── Insert application ────────────────────────────────────────────────────
$pdo->prepare("
    INSERT INTO applications (student_id, internship_id, cover_letter, match_percent, status, applied_at)
    VALUES (?, ?, ?, ?, 'pending', NOW())
")->execute([$userId, $internshipId, $coverLetter ?: null, $matchPct]);

echo json_encode([
    'success'       => true,
    'message'       => 'Application submitted successfully!',
    'match_percent' => $matchPct,
]);

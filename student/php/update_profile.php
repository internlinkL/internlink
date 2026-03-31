<?php
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

$userId = $_SESSION['user_id'];
$data   = json_decode(file_get_contents('php://input'), true);

// ── Allowed student_profiles fields ────────────────────────────────────────
// Real column names in student_profiles: university, field_of_study, year, city, country, skills, bio
$allowed = ['university','field_of_study','country','bio','skills'];
// Map frontend names -> real column names
$colMap = [
    'academic_year' => 'year',
    'wilaya'        => 'city',
    'year'          => 'year',
    'city'          => 'city',
];

// ── Update first_name / last_name in users table ───────────────────────────
if (isset($data['firstName']) || isset($data['lastName'])) {
    $fn = trim($data['firstName'] ?? '');
    $ln = trim($data['lastName']  ?? '');
    if ($fn && $ln) {
        $pdo->prepare("UPDATE users SET first_name=?, last_name=? WHERE id=?")
            ->execute([$fn, $ln, $userId]);
    }
}

// ── Build SET clause from allowed + provided fields ────────────────────────
$sets   = [];
$params = [];

// Handle standard allowed fields
foreach ($allowed as $field) {
    $camel = lcfirst(str_replace('_', '', ucwords($field, '_')));
    $val   = $data[$field] ?? $data[$camel] ?? null;
    if ($val !== null) {
        $sets[]   = "$field = ?";
        $params[] = trim((string)$val);
    }
}
// Handle mapped fields (frontend name -> real column name)
foreach ($colMap as $frontendKey => $realCol) {
    $camel = lcfirst(str_replace('_', '', ucwords($frontendKey, '_')));
    $val   = $data[$frontendKey] ?? $data[$camel] ?? null;
    if ($val !== null) {
        // Avoid duplicate if already added
        $alreadySet = false;
        foreach ($sets as $s) { if (strpos($s, "$realCol ") === 0) { $alreadySet = true; break; } }
        if (!$alreadySet) {
            $sets[]   = "$realCol = ?";
            $params[] = trim((string)$val);
        }
    }
}

if (!empty($sets)) {
    $chk = $pdo->prepare("SELECT id FROM student_profiles WHERE user_id=?");
    $chk->execute([$userId]);
    $exists = $chk->fetch();

    if ($exists) {
        $params[] = $userId;
        $pdo->prepare("UPDATE student_profiles SET " . implode(', ', $sets) . " WHERE user_id=?")
            ->execute($params);
    } else {
        // Build INSERT with all provided fields + user_id
        $cols    = array_map(fn($s) => trim(explode(' =', $s)[0]), $sets);
        $cols[]  = 'user_id';
        $params[] = $userId;
        $pdo->prepare(
            "INSERT INTO student_profiles (" . implode(',', $cols) . ") VALUES (" .
            implode(',', array_fill(0, count($cols), '?')) . ")"
        )->execute($params);
    }
}

echo json_encode(['success' => true, 'message' => 'Profile updated.']);

<?php
// ─────────────────────────────────────────────
//  register.php  —  internLink
//  Handles registration for students & companies
//  POST fields: type, firstName, lastName, email,
//               password, university, field, year,
//               wilaya, country, companyName,
//               sector, skills, bio
// ─────────────────────────────────────────────

header('Content-Type: application/json');
require_once __DIR__ . '/db.php';

// ── Only accept POST ──────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

// ── Collect & sanitize inputs ─────────────────
$type      = trim($_POST['type']      ?? '');
$firstName = trim($_POST['firstName'] ?? '');
$lastName  = trim($_POST['lastName']  ?? '');
$email     = strtolower(trim($_POST['email'] ?? ''));
$password  = $_POST['password'] ?? '';

// Validate required base fields
if (!$type || !$firstName || !$lastName || !$email || !$password) {
    echo json_encode(['success' => false, 'message' => 'Please fill in all required fields.']);
    exit;
}

if (!in_array($type, ['student', 'company'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid account type.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
    exit;
}

if (strlen($password) < 8) {
    echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters.']);
    exit;
}

// ── Check email not already taken ─────────────
$stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
$stmt->execute([$email]);
if ($stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'This email address is already registered.']);
    exit;
}

// ── Hash password ─────────────────────────────
$hashedPassword = password_hash($password, PASSWORD_BCRYPT);

// ── Insert into users ─────────────────────────
try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare(
        'INSERT INTO users (first_name, last_name, email, password, role)
         VALUES (?, ?, ?, ?, ?)'
    );
    $stmt->execute([$firstName, $lastName, $email, $hashedPassword, $type]);
    $userId = (int) $pdo->lastInsertId();

    // ── Insert profile depending on type ─────────
    if ($type === 'student') {
        $university   = trim($_POST['university']   ?? '');
        $fieldOfStudy = trim($_POST['field']        ?? '');
        $year         = trim($_POST['year']         ?? '');
        $city         = trim($_POST['wilaya']       ?? '');
        $country      = trim($_POST['country']      ?? '');
        $skills       = trim($_POST['skills']       ?? '');
        $bio          = trim($_POST['bio']          ?? '');

        $stmt = $pdo->prepare(
            'INSERT INTO student_profiles
             (user_id, university, field_of_study, year, city, country, skills, bio)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$userId, $university, $fieldOfStudy, $year, $city, $country, $skills, $bio]);

    } else {
        // company
        $companyName = trim($_POST['companyName'] ?? '');
        $sector      = trim($_POST['sector']      ?? '');
        $city        = trim($_POST['wilaya']      ?? '');
        $country     = trim($_POST['country']     ?? '');

        if (!$companyName) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Company name is required.']);
            exit;
        }

        $stmt = $pdo->prepare(
            'INSERT INTO company_profiles
             (user_id, company_name, sector, country)
             VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$userId, $companyName, $sector, $country]);
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Account created successfully!']);

} catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Registration failed. Please try again.']);
}

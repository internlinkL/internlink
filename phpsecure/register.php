<?php
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$type      = trim($_POST['type']      ?? '');
$firstName = trim($_POST['firstName'] ?? '');
$lastName  = trim($_POST['lastName']  ?? '');
$email     = strtolower(trim($_POST['email'] ?? ''));
$password  = $_POST['password'] ?? '';

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

try {
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'This email is already registered.']);
        exit;
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    exit;
}

$hashedPassword = password_hash($password, PASSWORD_BCRYPT);

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare('INSERT INTO users (first_name, last_name, email, password, role) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$firstName, $lastName, $email, $hashedPassword, $type]);
    $userId = (int) $pdo->lastInsertId();

    if ($type === 'student') {
        $stmt = $pdo->prepare('INSERT INTO student_profiles (user_id, university, field_of_study, year, city, country, skills, bio) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            $userId,
            trim($_POST['university'] ?? ''),
            trim($_POST['field']      ?? ''),
            trim($_POST['year']       ?? ''),
            trim($_POST['wilaya']     ?? ''),
            trim($_POST['country']    ?? ''),
            trim($_POST['skills']     ?? ''),
            trim($_POST['bio']        ?? ''),
        ]);
    } else {
        $companyName = trim($_POST['companyName'] ?? '');
        if (!$companyName) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Company name is required.']);
            exit;
        }
        $stmt = $pdo->prepare('INSERT INTO company_profiles (user_id, company_name, sector, country) VALUES (?, ?, ?, ?)');
        $stmt->execute([
            $userId,
            $companyName,
            trim($_POST['sector']  ?? ''),
            trim($_POST['country'] ?? ''),
        ]);
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Account created successfully!']);

} catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Registration failed: ' . $e->getMessage()]);
}

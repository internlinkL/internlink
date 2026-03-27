<?php
session_start();
header('Content-Type: application/json');
require 'db.php';

// ── Detect if request is JSON (fetch) or form POST ──────────────────
$isJson = str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json')
       || str_contains($_SERVER['HTTP_ACCEPT']   ?? '', 'application/json')
       || !empty($_SERVER['HTTP_X_REQUESTED_WITH']);

if ($isJson) {
    $data = json_decode(file_get_contents("php://input"), true) ?? [];
} else {
    $data = $_POST;
}

// ── Helper: send error response ──────────────────────────────────────
function fail(string $msg, int $code = 400): never {
    http_response_code($code);
    echo json_encode(['success' => false, 'message' => $msg]);
    exit;
}

// ── Read fields ──────────────────────────────────────────────────────
$firstName = trim($data['firstName'] ?? '');
$lastName  = trim($data['lastName']  ?? '');
$email     = trim($data['email']     ?? '');
$password  = $data['password']       ?? '';
$role      = strtolower(trim($data['type'] ?? 'student'));
$city      = trim($data['wilaya']  ?? '');
$country   = trim($data['country'] ?? '');

// ── Validate required fields ─────────────────────────────────────────
if (!$firstName || !$lastName || !$email || !$password) {
    fail('Please fill in all required fields.');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fail('Invalid email address.');
}

if (strlen($password) < 8) {
    fail('Password must be at least 8 characters.');
}

// ── Validate role ─────────────────────────────────────────────────────
$allowed_roles = ['student', 'company'];
if (!in_array($role, $allowed_roles)) {
    fail('Invalid account type selected.');
}

// ── Check for duplicate email ─────────────────────────────────────────
$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
$stmt->execute([$email]);
if ($stmt->fetch()) {
    fail('This email is already registered.');
}

// ── Hash password and insert user ─────────────────────────────────────
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

$stmt = $pdo->prepare("
    INSERT INTO users (first_name, last_name, email, password, role)
    VALUES (?, ?, ?, ?, ?)
");

if (!$stmt->execute([$firstName, $lastName, $email, $hashedPassword, $role])) {
    fail('Something went wrong. Please try again.', 500);
}

$newUserId = (int) $pdo->lastInsertId();

// ── Create empty profile row for the new user ─────────────────────────
// This prevents NULL issues when the dashboard queries the profile table
if ($role === 'student') {
    $pdo->prepare("INSERT IGNORE INTO student_profiles (user_id) VALUES (?)")
        ->execute([$newUserId]);
} elseif ($role === 'company') {
    $pdo->prepare("INSERT IGNORE INTO company_profiles (user_id) VALUES (?)")
        ->execute([$newUserId]);
}

// ── Success ───────────────────────────────────────────────────────────
http_response_code(201);
echo json_encode([
    'success'  => true,
    'message'  => 'Account created successfully!',
    'redirect' => 'login.html',   // frontend can use this to navigate
]);

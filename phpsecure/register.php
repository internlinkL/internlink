<?php
session_start();
header('Content-Type: application/json');

require 'db.php';

// ── Read JSON body ──────────────────────────────────────────────────────────
$data      = json_decode(file_get_contents("php://input"), true);

$firstName = trim($data['firstName'] ?? '');
$lastName  = trim($data['lastName']  ?? '');
$email     = trim($data['email']     ?? '');
$password  = $data['password']       ?? '';
$role      = $data['type']           ?? 'student';
$city      = trim($data['wilaya']    ?? '');   // city/region (was "wilaya")
$country   = trim($data['country']   ?? '');

// ── Validate required fields ────────────────────────────────────────────────
if (!$firstName || !$lastName || !$email || !$password) {
    echo json_encode(['success' => false, 'message' => 'Please fill in all required fields.']);
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email address.']);
    exit;
}

// ── FIX: Align with frontend (8 chars min, was 6) ──────────────────────────
if (strlen($password) < 8) {
    echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters.']);
    exit;
}

// ── Validate role ───────────────────────────────────────────────────────────
$allowed_roles = ['student', 'company'];
if (!in_array($role, $allowed_roles)) {
    $role = 'student';
}

// ── Check for duplicate email ────────────────────────────────────────────────
$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
$stmt->execute([$email]);
if ($stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'This email is already registered.']);
    exit;
}

// ── Hash password and insert ────────────────────────────────────────────────
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

$stmt = $pdo->prepare("
    INSERT INTO users (first_name, last_name, email, password, role)
    VALUES (?, ?, ?, ?, ?)
");
$success = $stmt->execute([$firstName, $lastName, $email, $hashedPassword, $role]);

if ($success) {
    echo json_encode(['success' => true, 'message' => 'Account created successfully!']);
} else {
    echo json_encode(['success' => false, 'message' => 'Something went wrong. Please try again.']);
}

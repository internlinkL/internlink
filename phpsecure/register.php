<?php
session_start();
require 'db.php';

// ── Read form POST data ─────────────────────────────────────────────
$firstName = trim($_POST['firstName'] ?? '');
$lastName  = trim($_POST['lastName']  ?? '');
$email     = trim($_POST['email']     ?? '');
$password  = $_POST['password']       ?? '';
$role      = $_POST['type']           ?? 'student';
$city      = trim($_POST['wilaya']    ?? '');
$country   = trim($_POST['country']   ?? '');

// ── Validate required fields ───────────────────────────────────────
if (!$firstName || !$lastName || !$email || !$password) {
    die('Please fill in all required fields.');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    die('Invalid email address.');
}

if (strlen($password) < 8) {
    die('Password must be at least 8 characters.');
}

// ── Validate role ──────────────────────────────────────────────────
$allowed_roles = ['student', 'company'];
if (!in_array($role, $allowed_roles)) {
    $role = 'student';
}

// ── Check for duplicate email ─────────────────────────────────────
$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
$stmt->execute([$email]);
if ($stmt->fetch()) {
    die('This email is already registered.');
}

// ── Hash password and insert user ──────────────────────────────────
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

$stmt = $pdo->prepare("
    INSERT INTO users (first_name, last_name, email, password, role)
    VALUES (?, ?, ?, ?, ?)
");

if ($stmt->execute([$firstName, $lastName, $email, $hashedPassword, $role])) {
    echo 'Account created successfully!';
} else {
    die('Something went wrong. Please try again.');
}
?>

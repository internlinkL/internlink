<?php
session_start();
header('Content-Type: application/json');
require 'db.php';

$email    = trim($_POST['email']    ?? '');
$password = $_POST['password']      ?? '';
$role     = strtolower(trim($_POST['role'] ?? 'student'));

// Validation
if (!$email || !$password) { echo json_encode(['success'=>false,'message'=>'Fill all fields']); exit; }
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { echo json_encode(['success'=>false,'message'=>'Invalid email']); exit; }
$allowed_roles = ['student','company','admin'];
if (!in_array($role, $allowed_roles)) { echo json_encode(['success'=>false,'message'=>'Invalid role']); exit; }

// Fetch user
$stmt = $pdo->prepare("SELECT id,email,password,role,first_name FROM users WHERE email=? LIMIT 1");
$stmt->execute([$email]);
$user = $stmt->fetch();
if (!$user || $user['role'] != $role) { echo json_encode(['success'=>false,'message'=>'Incorrect credentials']); exit; }
if (!password_verify($password, $user['password'])) { echo json_encode(['success'=>false,'message'=>'Incorrect credentials']); exit; }

// Store session
session_regenerate_id(true);
$_SESSION['user_id']    = $user['id'];
$_SESSION['role']       = $user['role'];
$_SESSION['first_name'] = $user['first_name'];

// Redirect based on role
$redirectMap = [
    'student' => '../html/Company_dashboard.html', // update when student dashboard exists
    'company' => '../html/Company_dashboard.html',
    'admin'   => '../html/index.html',
];

echo json_encode([
    'success'  => true,
    'message'  => 'Login successful',
    'redirect' => $redirectMap[$user['role']] ?? '../html/index.html',
]);

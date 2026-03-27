<?php
session_start();
header('Content-Type: application/json');
require 'db.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$data      = json_decode(file_get_contents("php://input"), true);
$firstName = trim($data['firstName'] ?? '');
$lastName  = trim($data['lastName']  ?? '');
$email     = trim($data['email'] ?? '');
$password  = $data['password'] ?? '';
$role      = $data['type'] ?? 'student';

// Validate fields
if (!$firstName || !$lastName || !$email || !$password) {
    echo json_encode(['success'=>false,'message'=>'Please fill in all required fields.']); exit;
}
if (!filter_var($email,FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success'=>false,'message'=>'Invalid email.']); exit;
}
if (strlen($password)<8) {
    echo json_encode(['success'=>false,'message'=>'Password must be at least 8 characters.']); exit;
}
$allowed_roles=['student','company'];
if(!in_array($role,$allowed_roles)) $role='student';

// Duplicate check
$stmt=$pdo->prepare("SELECT id FROM users WHERE email=? LIMIT 1");
$stmt->execute([$email]);
if($stmt->fetch()){ echo json_encode(['success'=>false,'message'=>'Email already exists.']); exit; }

// Insert user
$hashed=password_hash($password,PASSWORD_DEFAULT);
$stmt=$pdo->prepare("INSERT INTO users(first_name,last_name,email,password,role) VALUES(?,?,?,?,?)");
$success=$stmt->execute([$firstName,$lastName,$email,$hashed,$role]);

if($success){ echo json_encode(['success'=>true,'message'=>'Account created successfully']); }
else{ echo json_encode(['success'=>false,'message'=>'Server error. Try again later.']); }
?>

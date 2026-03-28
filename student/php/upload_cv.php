<?php
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

$userId = $_SESSION['user_id'];

if (!isset($_FILES['cv'])) {
    echo json_encode(['success' => false, 'message' => 'No file received.']);
    exit;
}

$file = $_FILES['cv'];

if ($file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'File upload error.']);
    exit;
}
if ($file['type'] !== 'application/pdf') {
    echo json_encode(['success' => false, 'message' => 'Only PDF files are allowed.']);
    exit;
}
if ($file['size'] > 5 * 1024 * 1024) {
    echo json_encode(['success' => false, 'message' => 'File must be under 5 MB.']);
    exit;
}

$uploadDir = __DIR__ . '/uploads/cv/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

$filename = 'cv_student_' . $userId . '.pdf';
$dest     = $uploadDir . $filename;

if (!move_uploaded_file($file['tmp_name'], $dest)) {
    echo json_encode(['success' => false, 'message' => 'Failed to save file.']);
    exit;
}

$cvPath = 'uploads/cv/' . $filename;

$chk = $pdo->prepare("SELECT id FROM student_profiles WHERE user_id=?");
$chk->execute([$userId]);
if ($chk->fetch()) {
    $pdo->prepare("UPDATE student_profiles SET cv_path=? WHERE user_id=?")->execute([$cvPath, $userId]);
} else {
    $pdo->prepare("INSERT INTO student_profiles (user_id, cv_path) VALUES (?,?)")->execute([$userId, $cvPath]);
}

echo json_encode(['success' => true, 'message' => 'CV uploaded successfully.', 'path' => $cvPath]);

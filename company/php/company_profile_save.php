<?php

error_reporting(0);
ini_set('display_errors', 0);

session_save_path(sys_get_temp_dir());
session_name('internlink_session');
session_start();
// ─────────────────────────────────────────────
//  company_profile_save.php  —  internLink
//  Saves or updates the company profile.
//  Handles both basic info and contact info.
//  POST fields: section, companyName, country,
//               city, sector, description,
//               website, size (basic section)
//               contactEmail, phone, linkedin
//               (contact section)
// ─────────────────────────────────────────────

header('Content-Type: application/json');
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth_guard.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$section = trim($_POST['section'] ?? 'basic');

// ── Ensure company_profiles row exists ────────
$stmt = $pdo->prepare('SELECT id FROM company_profiles WHERE user_id = ?');
$stmt->execute([$companyUserId]);
if (!$stmt->fetch()) {
    $pdo->prepare('INSERT INTO company_profiles (user_id) VALUES (?)')->execute([$companyUserId]);
}

if ($section === 'basic') {

    $companyName = trim($_POST['companyName']  ?? '');
    $country     = trim($_POST['country']      ?? '');
    $city        = trim($_POST['city']         ?? '');
    $sector      = trim($_POST['sector']       ?? '');
    $description = trim($_POST['description']  ?? '');

    if (!$companyName || !$country || !$sector) {
        echo json_encode(['success' => false, 'message' => 'Company name, country, and sector are required.']);
        exit;
    }

    // Handle avatar upload
    $avatarPath = null;
    if (!empty($_FILES['avatar']['tmp_name'])) {
        $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        $mime    = mime_content_type($_FILES['avatar']['tmp_name']);
        if (!in_array($mime, $allowed)) {
            echo json_encode(['success' => false, 'message' => 'Invalid image format. Use JPG, PNG, or WebP.']);
            exit;
        }
        $ext       = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
        $filename  = 'company_' . $companyUserId . '_' . time() . '.' . $ext;
        $uploadDir = __DIR__ . '/../uploads/avatars/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        if (!move_uploaded_file($_FILES['avatar']['tmp_name'], $uploadDir . $filename)) {
            echo json_encode(['success' => false, 'message' => 'Failed to upload image.']);
            exit;
        }
        $avatarPath = '../uploads/avatars/' . $filename;
    }

    $sql = 'UPDATE company_profiles
            SET company_name = ?, country = ?, sector = ?, description = ?';
    $params = [$companyName, $country, $sector, $description];

    if ($avatarPath) {
        $sql     .= ', avatar_path = ?';
        $params[] = $avatarPath;
    }
    $sql     .= ' WHERE user_id = ?';
    $params[] = $companyUserId;

    $pdo->prepare($sql)->execute($params);

    // Also update first_name in users as company display name
    $pdo->prepare('UPDATE users SET first_name = ?, last_name = ? WHERE id = ?')
        ->execute([trim($companyName), '', $companyUserId]);

    echo json_encode(['success' => true, 'message' => 'Profile saved successfully!']);

} elseif ($section === 'contact') {

    $contactEmail = trim($_POST['contactEmail'] ?? '');
    $phone        = trim($_POST['phone']        ?? '');
    $linkedin     = trim($_POST['linkedin']     ?? '');

    if ($contactEmail && !filter_var($contactEmail, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Please enter a valid contact email.']);
        exit;
    }

    $pdo->prepare(
        'UPDATE company_profiles SET phone = ?, linkedin = ? WHERE user_id = ?'
    )->execute([$phone, $linkedin, $companyUserId]);

    echo json_encode(['success' => true, 'message' => 'Contact info saved!']);

} else {
    echo json_encode(['success' => false, 'message' => 'Unknown section.']);
}

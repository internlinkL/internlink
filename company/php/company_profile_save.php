<?php
error_reporting(0);
ini_set('display_errors', 0);

// ─────────────────────────────────────────────
//  company_profile_save.php — internLink
//  Saves or updates the company profile.
//  POST fields: section, companyName, country,
//               city, sector, description,
//               website, size (basic section)
//               contactEmail, phone, linkedin
//               (contact section)
// ─────────────────────────────────────────────

header('Content-Type: application/json');
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/../../phpsecure/db.php';
require_once __DIR__ . '/../../phpsecure/auth_guard.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

// FIX: sanitize all text inputs to prevent stored XSS
function clean(string $val): string {
    return htmlspecialchars(trim($val), ENT_QUOTES, 'UTF-8');
}

$section = trim($_POST['section'] ?? 'basic');

// Ensure company_profiles row exists
try {
    $stmt = $pdo->prepare('SELECT id FROM company_profiles WHERE user_id = ?');
    $stmt->execute([$companyUserId]);
    if (!$stmt->fetch()) {
        $pdo->prepare('INSERT INTO company_profiles (user_id) VALUES (?)')->execute([$companyUserId]);
    }
} catch (PDOException $e) {
    error_log('company_profile_save init error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'A server error occurred.']);
    exit;
}

if ($section === 'basic') {

    $companyName = clean($_POST['companyName']  ?? '');
    $country     = clean($_POST['country']      ?? '');
    $city        = clean($_POST['city']         ?? '');
    $sector      = clean($_POST['sector']       ?? '');
    $description = clean($_POST['description']  ?? '');

    if (!$companyName || !$country || !$sector) {
        echo json_encode(['success' => false, 'message' => 'Company name, country, and sector are required.']);
        exit;
    }

    // Handle avatar upload
    $avatarPath = null;
    if (!empty($_FILES['avatar']['tmp_name'])) {

        // FIX: reject files larger than 2MB
        if ($_FILES['avatar']['size'] > 2 * 1024 * 1024) {
            echo json_encode(['success' => false, 'message' => 'Image must be under 2MB.']);
            exit;
        }

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

    $sql    = 'UPDATE company_profiles SET company_name = ?, country = ?, city = ?, sector = ?, description = ?';
    $params = [$companyName, $country, $city, $sector, $description];

    if ($avatarPath) {
        $sql     .= ', avatar_path = ?';
        $params[] = $avatarPath;
    }
    $sql     .= ' WHERE user_id = ?';
    $params[] = $companyUserId;

    try {
        $pdo->prepare($sql)->execute($params);
        $pdo->prepare('UPDATE users SET first_name = ?, last_name = ? WHERE id = ?')
            ->execute([trim($companyName), '', $companyUserId]);
        echo json_encode(['success' => true, 'message' => 'Profile saved successfully!']);
    } catch (PDOException $e) {
        error_log('company_profile_save basic error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'A server error occurred.']);
    }

} elseif ($section === 'contact') {

    $contactEmail = trim($_POST['contactEmail'] ?? '');
    $phone        = clean($_POST['phone']       ?? '');
    $linkedin     = clean($_POST['linkedin']    ?? '');

    if ($contactEmail && !filter_var($contactEmail, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Please enter a valid contact email.']);
        exit;
    }

    try {
        $pdo->prepare('UPDATE company_profiles SET phone = ?, linkedin = ? WHERE user_id = ?')
            ->execute([$phone, $linkedin, $companyUserId]);
        echo json_encode(['success' => true, 'message' => 'Contact info saved!']);
    } catch (PDOException $e) {
        error_log('company_profile_save contact error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'A server error occurred.']);
    }

} else {
    echo json_encode(['success' => false, 'message' => 'Unknown section.']);
}

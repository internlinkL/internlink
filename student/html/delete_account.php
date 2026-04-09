<?php
// ─────────────────────────────────────────────
//  delete_account.php — internLink (student)
//  Verifies password then permanently deletes
//  the student account and all their data.
//  POST fields: action, password
// ─────────────────────────────────────────────
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/../../phpsecure/db.php';
require_once __DIR__ . '/../../phpsecure/auth_student.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

if (($_POST['action'] ?? '') !== 'delete_account') {
    echo json_encode(['success' => false, 'message' => 'Invalid action.']);
    exit;
}

$password = $_POST['password'] ?? '';
if (!$password) {
    echo json_encode(['success' => false, 'message' => 'Password is required.']);
    exit;
}

$userId = $_SESSION['user_id'];

// Verify password before deleting
try {
    $stmt = $pdo->prepare('SELECT password FROM users WHERE id = ? AND role = ?');
    $stmt->execute([$userId, 'student']);
    $user = $stmt->fetch();
} catch (PDOException $e) {
    error_log('delete_account student fetch error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'A server error occurred.']);
    exit;
}

if (!$user || !password_verify($password, $user['password'])) {
    echo json_encode(['success' => false, 'message' => 'Incorrect password.']);
    exit;
}

// Delete account — CASCADE removes student_profiles and applications
try {
    $pdo->prepare('DELETE FROM users WHERE id = ? AND role = ?')
        ->execute([$userId, 'student']);
} catch (PDOException $e) {
    error_log('delete_account student error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to delete account. Please try again.']);
    exit;
}

// Destroy session and expire cookie
session_unset();
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}
session_destroy();

echo json_encode(['success' => true, 'message' => 'Account deleted successfully.']);

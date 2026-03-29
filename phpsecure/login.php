<?php
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');

session_save_path(sys_get_temp_dir());
session_name('internlink_session');
session_start();
require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$email    = strtolower(trim($_POST['email']    ?? ''));
$password = $_POST['password'] ?? '';
$role     = trim($_POST['role'] ?? '');

if (!$email || !$password || !$role) {
    echo json_encode(['success' => false, 'message' => 'Please fill in all fields.']);
    exit;
}

if (!in_array($role, ['student', 'company', 'admin'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid role selected.']);
    exit;
}

try {
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? AND role = ?');
    $stmt->execute([$email, $role]);
    $user = $stmt->fetch();
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    exit;
}

if (!$user || !password_verify($password, $user['password'])) {
    echo json_encode(['success' => false, 'message' => 'Incorrect email, password, or role.']);
    exit;
}

// ── Set session directly ──────────────────────
$_SESSION['user_id']    = $user['id'];
$_SESSION['user_email'] = $user['email'];
$_SESSION['user_name']  = $user['first_name'] . ' ' . $user['last_name'];
$_SESSION['user_role']  = $user['role'];

$base = '/internlink';
$redirectMap = [
    'company' => $base . '/company/html/company_dashboard.html',
    'student' => $base . '/student/html/student_dashboard.html',
    'admin'   => $base . '/admin/html/admin_dashboard.html',
];
$redirect = $redirectMap[$user['role']] ?? $base . '/html/index.html';

echo json_encode([
    'success'      => true,
    'requires_2fa' => false,
    'redirect'     => $redirect,
    'message'      => 'Login successful.',
]);

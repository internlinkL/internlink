<?php
// ─────────────────────────────────────────────
//  google_callback.php — internLink
//  Google redirects here after user approves.
//  This file:
//  1. Validates the state (anti-CSRF)
//  2. Exchanges the code for an access token
//  3. Fetches the user's Google profile
//  4. Creates account if new, logs in if exists
//  5. Redirects to the correct dashboard
// ─────────────────────────────────────────────
error_reporting(0);
ini_set('display_errors', 0);

ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.cookie_path', '/');
session_save_path(sys_get_temp_dir());
session_name('internlink_session');
session_start();

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/google_auth.php';

// ── Helper: redirect to login with error message ──────────────────────────────
function failRedirect(string $msg): void {
    header('Location: /internlink/html/login.html?error=' . urlencode($msg));
    exit;
}

// ── Step 1: Check for errors from Google ─────────────────────────────────────
if (!empty($_GET['error'])) {
    failRedirect('Google sign-in was cancelled.');
}

// ── Step 2: Validate state to prevent CSRF ───────────────────────────────────
$state         = $_GET['state'] ?? '';
$expectedState = $_SESSION['google_oauth_state'] ?? '';

if (!$state || !$expectedState || !hash_equals($expectedState, $state)) {
    failRedirect('Invalid request. Please try again.');
}

// Clear state from session
unset($_SESSION['google_oauth_state']);

// ── Step 3: Exchange code for access token ────────────────────────────────────
$code = $_GET['code'] ?? '';
if (!$code) {
    failRedirect('No authorization code received from Google.');
}

$tokenData = exchangeGoogleCode($code);
if (!$tokenData) {
    failRedirect('Failed to connect to Google. Please try again.');
}

// ── Step 4: Fetch user profile from Google ────────────────────────────────────
$googleUser = fetchGoogleUserInfo($tokenData['access_token']);
if (!$googleUser || empty($googleUser['email'])) {
    failRedirect('Could not retrieve your Google profile.');
}

$email     = strtolower(trim($googleUser['email']));
$firstName = htmlspecialchars(trim($googleUser['given_name']  ?? ''), ENT_QUOTES, 'UTF-8');
$lastName  = htmlspecialchars(trim($googleUser['family_name'] ?? ''), ENT_QUOTES, 'UTF-8');
$googleId  = $googleUser['sub'] ?? '';

if (!$firstName) $firstName = explode('@', $email)[0];
if (!$lastName)  $lastName  = '';

// ── Step 5: Find or create user ───────────────────────────────────────────────
try {
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();
} catch (PDOException $e) {
    error_log('google_callback DB lookup error: ' . $e->getMessage());
    failRedirect('A server error occurred. Please try again.');
}

if ($user) {
    // ── Existing user — log them in ───────────────────────────────────────────
    if (!empty($user['is_banned'])) {
        failRedirect('Your account has been suspended. Please contact support.');
    }

    // Admin accounts cannot use Google login
    if ($user['role'] === 'admin') {
        failRedirect('Admin accounts must log in with email and password.');
    }

} else {
    // ── New user — create account automatically ───────────────────────────────
    $role = $_SESSION['google_oauth_role'] ?? 'student';
    if (!in_array($role, ['student', 'company'])) $role = 'student';

    // Generate a random strong password (user won't need it — they use Google)
    $randomPassword = password_hash(bin2hex(random_bytes(32)), PASSWORD_BCRYPT);

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare(
            'INSERT INTO users (first_name, last_name, email, password, role) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$firstName, $lastName, $email, $randomPassword, $role]);
        $userId = (int) $pdo->lastInsertId();

        if ($role === 'student') {
            $pdo->prepare(
                'INSERT INTO student_profiles (user_id, university, field_of_study, year, city, country, skills, bio) VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
            )->execute([$userId, '', '', '', '', '', '', '']);
        } else {
            $companyName = $firstName . ($lastName ? ' ' . $lastName : '');
            $pdo->prepare(
                'INSERT INTO company_profiles (user_id, company_name, sector, country) VALUES (?, ?, ?, ?)'
            )->execute([$userId, $companyName, '', '']);
        }

        $pdo->commit();

        // Fetch the newly created user
        $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log('google_callback create user error: ' . $e->getMessage());
        failRedirect('Failed to create your account. Please try again.');
    }
}

// ── Step 6: Create full session ───────────────────────────────────────────────
unset($_SESSION['google_oauth_role']);
session_regenerate_id(true);

$_SESSION['user_id']    = $user['id'];
$_SESSION['user_email'] = $user['email'];
$_SESSION['user_name']  = $user['first_name'] . ' ' . $user['last_name'];
$_SESSION['user_role']  = $user['role'];
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// ── Step 7: Redirect to correct dashboard ─────────────────────────────────────
$base = '/internlink';
$redirectMap = [
    'student' => $base . '/student/html/student_dashboard.html',
    'company' => $base . '/company/html/company_dashboard.html',
];
$redirect = $redirectMap[$user['role']] ?? $base . '/html/index.html';

header('Location: ' . $redirect);
exit;

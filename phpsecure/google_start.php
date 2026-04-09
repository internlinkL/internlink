<?php
// ─────────────────────────────────────────────
//  google_start.php — internLink
//  Called by the frontend when user clicks
//  "Continue with Google".
//  Returns the Google auth URL as JSON.
//  The frontend then redirects to that URL.
// ─────────────────────────────────────────────
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');

ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.cookie_path', '/');
session_save_path(sys_get_temp_dir());
session_name('internlink_session');
session_start();

require_once __DIR__ . '/google_auth.php';

// Generate a random state token to prevent CSRF on the OAuth flow
$state = bin2hex(random_bytes(16));
$_SESSION['google_oauth_state'] = $state;

// Store the intended role if provided (student or company)
$role = trim($_GET['role'] ?? $_POST['role'] ?? 'student');
if (!in_array($role, ['student', 'company'])) $role = 'student';
$_SESSION['google_oauth_role'] = $role;

$url = buildGoogleAuthUrl($state);

echo json_encode(['success' => true, 'url' => $url]);

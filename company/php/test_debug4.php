<?php
// PUT IN: internlink/company/php/test_debug4.php
// VISIT: http://localhost/internlink/company/php/test_debug4.php

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "step1: PHP ok<br>";

// Test db only
require_once __DIR__ . '/../../phpsecure/db.php';
echo "step2: db.php loaded ok<br>";

// Test session manually (same as auth_guard does)
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.cookie_httponly', 1);
session_save_path(sys_get_temp_dir());
session_name('internlink_session');
if (session_status() === PHP_SESSION_NONE) { session_start(); }
echo "step3: session started ok<br>";

echo "step4: user_id=" . ($_SESSION['user_id'] ?? 'NOT SET') . "<br>";
echo "step5: user_role=" . ($_SESSION['user_role'] ?? 'NOT SET') . "<br>";

// Now test auth_guard directly
require_once __DIR__ . '/../../phpsecure/auth_guard.php';
echo "step6: auth_guard loaded ok, companyUserId=$companyUserId<br>";

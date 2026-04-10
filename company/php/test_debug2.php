<?php
// PUT IN: internlink/company/php/test_debug2.php
// VISIT: http://localhost/internlink/company/php/test_debug2.php

error_reporting(E_ALL);
ini_set('display_errors', 1);

ini_set('session.cookie_samesite', 'Lax');
ini_set('session.cookie_httponly', 1);
session_save_path(sys_get_temp_dir());
session_name('internlink_session');
session_start();

require_once __DIR__ . '/../../phpsecure/db.php';
require_once __DIR__ . '/../../phpsecure/auth_guard.php';

echo "<h2>Auth passed! companyUserId = $companyUserId</h2>";

// Test the exact query from get_company_data.php
$stmt = $pdo->prepare(
    'SELECT u.first_name, u.last_name, u.email,
            cp.company_name, cp.sector, cp.country, cp.city,
            cp.description, cp.avatar_path, cp.phone, cp.linkedin
     FROM users u
     LEFT JOIN company_profiles cp ON cp.user_id = u.id
     WHERE u.id = ?'
);
$stmt->execute([$companyUserId]);
$profile = $stmt->fetch();

echo "<h2>Profile query result:</h2>";
echo "<pre>" . print_r($profile, true) . "</pre>";

// Test stats
$stmt = $pdo->prepare("SELECT COUNT(*) FROM internship_offers WHERE company_id = ? AND status = 'active'");
$stmt->execute([$companyUserId]);
echo "<b>Active offers count:</b> " . $stmt->fetchColumn() . "<br>";

$stmt = $pdo->prepare('SELECT COUNT(*) FROM applications a JOIN internship_offers o ON o.id = a.offer_id WHERE o.company_id = ?');
$stmt->execute([$companyUserId]);
echo "<b>Total applications:</b> " . $stmt->fetchColumn() . "<br>";

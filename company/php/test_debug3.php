<?php
// PUT IN: internlink/company/php/test_debug3.php  
// VISIT: http://localhost/internlink/company/php/test_debug3.php
// This directly mimics get_company_data.php output

error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

ini_set('session.cookie_samesite', 'Lax');
ini_set('session.cookie_httponly', 1);
session_save_path(sys_get_temp_dir());
session_name('internlink_session');
session_start();

require_once __DIR__ . '/../../phpsecure/db.php';
require_once __DIR__ . '/../../phpsecure/auth_guard.php';

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

echo json_encode([
    'success'        => true,
    'companyUserId'  => $companyUserId,
    'profile'        => $profile,
]);

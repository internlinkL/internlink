<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

if (!defined('DB_HOST')) define('DB_HOST', 'localhost');
if (!defined('DB_USER')) define('DB_USER', 'root');
if (!defined('DB_PASS')) define('DB_PASS', '');
if (!defined('DB_NAME')) define('DB_NAME', 'internlink');

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
} catch (PDOException $e) {
    echo json_encode(['step' => 'db_connect', 'error' => $e->getMessage()]);
    exit;
}

ini_set('session.cookie_path', '/');
session_save_path(sys_get_temp_dir());
session_name('internlink_session');
if (session_status() === PHP_SESSION_NONE) session_start();
$userId = $_SESSION['user_id'] ?? null;

$result = ['user_id' => $userId];

// 1. List all tables
try {
    $result['tables'] = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) { $result['tables_error'] = $e->getMessage(); }

// 2. users row
try {
    $s = $pdo->prepare("SELECT id, first_name, last_name, email, role FROM users WHERE id = ?");
    $s->execute([$userId]);
    $result['users_row'] = $s->fetch();
} catch (PDOException $e) { $result['users_error'] = $e->getMessage(); }

// 3. student_profiles row
try {
    $s = $pdo->prepare("SELECT * FROM student_profiles WHERE user_id = ?");
    $s->execute([$userId]);
    $result['profile_row'] = $s->fetch();
} catch (PDOException $e) { $result['profile_error'] = $e->getMessage(); }

// 4. full JOIN
try {
    $s = $pdo->prepare("
        SELECT u.id, u.first_name, u.last_name, u.email, u.role,
               sp.phone, sp.university, sp.field_of_study, sp.academic_year,
               sp.country, sp.wilaya, sp.bio, sp.skills, sp.linkedin, sp.github, sp.cv_path
        FROM users u
        LEFT JOIN student_profiles sp ON sp.user_id = u.id
        WHERE u.id = ? LIMIT 1
    ");
    $s->execute([$userId]);
    $result['join_row'] = $s->fetch();
} catch (PDOException $e) { $result['join_error'] = $e->getMessage(); }

echo json_encode($result, JSON_PRETTY_PRINT);

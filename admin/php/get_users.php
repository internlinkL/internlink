<?php
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

$role   = $_GET['role']   ?? 'all';   // all | student | company
$search = trim($_GET['q'] ?? '');
$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = 20;
$offset = ($page - 1) * $limit;

$where  = ['1=1'];
$params = [];

if ($role !== 'all') {
    $where[]  = 'u.role = ?';
    $params[] = $role;
}
if ($search) {
    $where[]  = '(u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ?)';
    $like = "%$search%";
    $params = array_merge($params, [$like, $like, $like]);
}

$whereSQL = implode(' AND ', $where);

// Total count
$cntParams = $params;
$total = (int) $pdo->prepare("SELECT COUNT(*) FROM users u WHERE $whereSQL")
                   ->execute($cntParams) ? $pdo->prepare("SELECT COUNT(*) FROM users u WHERE $whereSQL")->execute($cntParams) : 0;
$cntStmt = $pdo->prepare("SELECT COUNT(*) FROM users u WHERE $whereSQL");
$cntStmt->execute($params);
$total = (int) $cntStmt->fetchColumn();

// Fetch users with profile info
$stmt = $pdo->prepare("
    SELECT u.id, u.first_name, u.last_name, u.email, u.role, u.created_at,
           sp.university, sp.skills, sp.country AS s_country, sp.wilaya AS s_city,
           cp.company_name, cp.sector, cp.country AS c_country, cp.is_verified
    FROM users u
    LEFT JOIN student_profiles sp ON sp.user_id = u.id AND u.role = 'student'
    LEFT JOIN company_profiles  cp ON cp.user_id = u.id AND u.role = 'company'
    WHERE $whereSQL
    ORDER BY u.created_at DESC
    LIMIT $limit OFFSET $offset
");
$stmt->execute($params);
$users = $stmt->fetchAll();

echo json_encode([
    'success' => true,
    'users'   => $users,
    'total'   => $total,
    'page'    => $page,
    'pages'   => ceil($total / $limit),
]);

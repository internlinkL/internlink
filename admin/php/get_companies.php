<?php
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

$filter = $_GET['filter'] ?? 'all';
$search = trim($_GET['q'] ?? '');
$page   = max(1,(int)($_GET['page'] ?? 1));
$limit  = 20;
$offset = ($page - 1) * $limit;

$where  = ["u.role = 'company'"];
$params = [];

if ($filter === 'pending')  { $where[] = 'COALESCE(cp.is_verified,0) = 0'; }
if ($filter === 'verified') { $where[] = 'cp.is_verified = 1'; }
if ($search) {
    $where[]  = '(u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ? OR cp.company_name LIKE ?)';
    $like     = "%$search%";
    $params   = array_merge($params, [$like,$like,$like,$like]);
}

$whereSQL = implode(' AND ', $where);

$cntStmt = $pdo->prepare("
    SELECT COUNT(*) FROM users u
    LEFT JOIN company_profiles cp ON cp.user_id = u.id
    WHERE $whereSQL
");
$cntStmt->execute($params);
$total = (int)$cntStmt->fetchColumn();

$stmt = $pdo->prepare("
    SELECT u.id, u.first_name, u.last_name, u.email, u.created_at,
           cp.company_name, cp.sector, cp.country,
           cp.description, COALESCE(cp.is_verified,0) AS is_verified,
           (SELECT COUNT(*) FROM internship_offers WHERE company_id=u.id) AS internship_count,
           (SELECT COUNT(*) FROM applications a 
            JOIN internship_offers io ON io.id=a.offer_id 
            WHERE io.company_id=u.id) AS app_count
    FROM users u
    LEFT JOIN company_profiles cp ON cp.user_id = u.id
    WHERE $whereSQL
    ORDER BY u.created_at DESC
    LIMIT $limit OFFSET $offset
");
$stmt->execute($params);
$companies = $stmt->fetchAll();

echo json_encode([
    'success'   => true,
    'companies' => $companies,
    'total'     => $total,
    'page'      => $page,
    'pages'     => (int)ceil($total / $limit),
]);

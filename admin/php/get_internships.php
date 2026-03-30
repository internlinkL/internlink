<?php
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

$search = trim($_GET['q']  ?? '');
$status = $_GET['status']  ?? 'all';
$page   = max(1,(int)($_GET['page'] ?? 1));
$limit  = 20;
$offset = ($page - 1) * $limit;

$where  = ['1=1'];
$params = [];

if ($search) {
    $where[]  = '(io.title LIKE ? OR cp.company_name LIKE ?)';
    $like     = "%$search%";
    $params   = array_merge($params, [$like, $like]);
}
if ($status !== 'all') {
    $where[]  = 'io.status = ?';
    $params[] = $status;
}

$whereSQL = implode(' AND ', $where);

$cntStmt = $pdo->prepare("
    SELECT COUNT(*) FROM internship_offers io
    JOIN company_profiles cp ON cp.user_id = io.company_id
    WHERE $whereSQL
");
$cntStmt->execute($params);
$total = (int)$cntStmt->fetchColumn();

$stmt = $pdo->prepare("
    SELECT io.*, cp.company_name,
           COUNT(a.id) AS app_count
    FROM internship_offers io
    JOIN company_profiles cp  ON cp.user_id = io.company_id
    LEFT JOIN applications a  ON a.offer_id = io.id
    WHERE $whereSQL
    GROUP BY io.id
    ORDER BY io.created_at DESC
    LIMIT $limit OFFSET $offset
");
$stmt->execute($params);
$internships = $stmt->fetchAll();

echo json_encode([
    'success'     => true,
    'internships' => $internships,
    'total'       => $total,
    'page'        => $page,
    'pages'       => (int)ceil($total / $limit),
]);

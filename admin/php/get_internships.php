<?php
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

$search = trim($_GET['q']      ?? '');
$status = $_GET['status']      ?? 'all';   // all | open | closed
$page   = max(1,(int)($_GET['page'] ?? 1));
$limit  = 20;
$offset = ($page - 1) * $limit;

$where  = ['1=1'];
$params = [];

if ($search) {
    $where[]  = '(i.title LIKE ? OR u.first_name LIKE ?)';
    $like     = "%$search%";
    $params   = array_merge($params, [$like, $like]);
}
if ($status !== 'all') {
    if ($status === 'open') {
        $where[]  = '(i.is_active=1 OR i.status=\'open\')';
    } else {
        $where[]  = '(i.is_active=0 AND i.status=\'closed\')';
    }
}

$whereSQL = implode(' AND ', $where);

$cntStmt = $pdo->prepare("
    SELECT COUNT(*) FROM internships i
    JOIN users u ON u.id = i.company_id
    WHERE $whereSQL
");
$cntStmt->execute($params);
$total = (int)$cntStmt->fetchColumn();

$stmt = $pdo->prepare("
    SELECT i.*, u.first_name AS company_name,
           cp.company_name AS company_display,
           cp.country AS company_country, cp.wilaya AS company_city,
           COALESCE(i.domain, i.sector, cp.sector) AS domain_display
    FROM internships i
    JOIN users u ON u.id = i.company_id
    LEFT JOIN company_profiles cp ON cp.user_id = i.company_id
    WHERE $whereSQL
    ORDER BY i.created_at DESC
    LIMIT $limit OFFSET $offset
");
$stmt->execute($params);
$internships = $stmt->fetchAll();

echo json_encode([
    'success'      => true,
    'internships'  => $internships,
    'total'        => $total,
    'page'         => $page,
    'pages'        => ceil($total / $limit),
]);

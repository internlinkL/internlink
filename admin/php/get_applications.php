<?php
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

$status = $_GET['status'] ?? 'all';
$search = trim($_GET['q'] ?? '');
$page   = max(1,(int)($_GET['page'] ?? 1));
$limit  = 20;
$offset = ($page - 1) * $limit;

$where  = ['1=1'];
$params = [];

if ($status !== 'all') {
    $where[]  = 'a.status = ?';
    $params[] = $status;
}
if ($search) {
    $where[]  = '(i.title LIKE ? OR su.first_name LIKE ? OR su.last_name LIKE ?)';
    $like     = "%$search%";
    $params   = array_merge($params, [$like, $like, $like]);
}

$whereSQL = implode(' AND ', $where);

$cntStmt = $pdo->prepare("
    SELECT COUNT(*) FROM applications a
    JOIN internships i ON i.id = a.internship_id
    JOIN users su ON su.id = a.student_id
    WHERE $whereSQL
");
$cntStmt->execute($params);
$total = (int)$cntStmt->fetchColumn();

$stmt = $pdo->prepare("
    SELECT a.id, a.status, a.applied_at, a.match_percent,
           a.cover_letter, a.feedback,
           i.title AS internship_title,
           CONCAT(su.first_name,' ',su.last_name) AS student_name,
           su.email AS student_email,
           cu.first_name AS company_name,
           COALESCE(cp.company_name, cu.first_name) AS company_display
    FROM applications a
    JOIN internships i   ON i.id  = a.internship_id
    JOIN users su        ON su.id = a.student_id
    JOIN users cu        ON cu.id = i.company_id
    LEFT JOIN company_profiles cp ON cp.user_id = cu.id
    WHERE $whereSQL
    ORDER BY a.applied_at DESC
    LIMIT $limit OFFSET $offset
");
$stmt->execute($params);
$apps = $stmt->fetchAll();

echo json_encode([
    'success'      => true,
    'applications' => $apps,
    'total'        => $total,
    'page'         => $page,
    'pages'        => ceil($total / $limit),
]);

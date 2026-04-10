<?php
// PUT THIS FILE IN: internlink/company/php/test_debug.php
// VISIT: http://localhost/internlink/company/php/test_debug.php
// DELETE IT AFTER DEBUGGING

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Step 1: PHP is working</h2>";

// Test path resolution
echo "<h2>Step 2: Path info</h2>";
echo "__DIR__ = " . __DIR__ . "<br>";
echo "db.php path = " . __DIR__ . '/../../phpsecure/db.php' . "<br>";
echo "db.php exists? " . (file_exists(__DIR__ . '/../../phpsecure/db.php') ? '<b style="color:green">YES</b>' : '<b style="color:red">NO — WRONG PATH</b>') . "<br>";
echo "auth_guard.php exists? " . (file_exists(__DIR__ . '/../../phpsecure/auth_guard.php') ? '<b style="color:green">YES</b>' : '<b style="color:red">NO — WRONG PATH</b>') . "<br>";

// List what IS in ../../ to help find the right folder
echo "<h2>Step 3: What is in ../../ ?</h2>";
$parent = realpath(__DIR__ . '/../../');
echo "../../ resolves to: " . $parent . "<br>";
$items = scandir($parent);
foreach($items as $item) {
    if ($item === '.' || $item === '..') continue;
    echo "- $item<br>";
}

echo "<h2>Step 4: Session test</h2>";
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.cookie_httponly', 1);
session_save_path(sys_get_temp_dir());
session_name('internlink_session');
session_start();
echo "Session started OK<br>";
echo "Session ID: " . session_id() . "<br>";
echo "user_id in session: " . ($_SESSION['user_id'] ?? '<b style="color:orange">NOT SET — you are not logged in</b>') . "<br>";
echo "user_role in session: " . ($_SESSION['user_role'] ?? 'NOT SET') . "<br>";

echo "<h2>Step 5: DB test</h2>";
if (file_exists(__DIR__ . '/../../phpsecure/db.php')) {
    require_once __DIR__ . '/../../phpsecure/db.php';
    echo '<b style="color:green">DB connected OK</b><br>';
} else {
    echo '<b style="color:red">Cannot test DB — db.php not found at expected path</b><br>';
}

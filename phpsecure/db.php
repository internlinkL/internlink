<?php
// db.php – Database connection for internlink project

// Database configuration
$host = 'localhost';
$db   = 'internlink';  // اسم قاعدة البيانات عندك
$user = 'root';        // غالبًا root في XAMPP
$pass = '';            // عادةً فارغ في XAMPP
$charset = 'utf8mb4';

// Data Source Name
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

// PDO options
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // عرض أي أخطاء
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // fetch as associative array
    PDO::ATTR_EMULATE_PREPARES   => false,                  // استخدام prepared statements حقيقية
];

// Enable PHP error display (for debugging)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

try {
    // إنشاء الاتصال
    $pdo = new PDO($dsn, $user, $pass, $options);
    // تأكيد الاتصال
    // echo "Connected to database successfully!";
} catch (\PDOException $e) {
    // عرض الخطأ مباشرة عند فشل الاتصال
    die("Database connection failed: " . $e->getMessage());
}
?>

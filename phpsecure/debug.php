<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check PHPMailer files
$base = __DIR__ . '/PHPMailer/src/';
echo '<h3>PHPMailer files:</h3>';
foreach (['Exception.php', 'PHPMailer.php', 'SMTP.php'] as $f) {
    $path = $base . $f;
    echo $f . ': ' . (file_exists($path) ? '✅ Found' : '❌ MISSING at ' . $path) . '<br>';
}

// Check config
require_once __DIR__ . '/config.php';
echo '<h3>Config:</h3>';
echo 'MAIL_HOST: ' . MAIL_HOST . '<br>';
echo 'MAIL_PORT: ' . MAIL_PORT . '<br>';
echo 'MAIL_FROM: ' . MAIL_FROM . '<br>';
echo 'MAIL_PASS length: ' . strlen(MAIL_PASS) . ' chars<br>';

// Test SMTP connection
echo '<h3>SMTP Connection test:</h3>';
$socket = @fsockopen('smtp.gmail.com', 587, $errno, $errstr, 10);
echo $socket ? '✅ Can reach smtp.gmail.com:587' : '❌ Cannot connect: ' . $errstr;
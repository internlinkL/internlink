<?php
// test_register.php - place this in phpsecure/ folder, then open in browser
// DELETE THIS FILE after debugging!

error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

require 'db.php';

// Test 1: DB connection
echo "✅ DB connected\n";

// Test 2: Check users table columns
$stmt = $pdo->query("DESCRIBE users");
$cols = $stmt->fetchAll();
echo "Users table columns:\n";
foreach ($cols as $col) {
    echo " - " . $col['Field'] . " (" . $col['Type'] . ")\n";
}

// Test 3: Check company_profiles table exists
try {
    $pdo->query("SELECT 1 FROM company_profiles LIMIT 1");
    echo "\n✅ company_profiles table exists\n";
} catch (Exception $e) {
    echo "\n❌ company_profiles table MISSING: " . $e->getMessage() . "\n";
}

// Test 4: Check student_profiles table exists
try {
    $pdo->query("SELECT 1 FROM student_profiles LIMIT 1");
    echo "\n✅ student_profiles table exists\n";
} catch (Exception $e) {
    echo "\n❌ student_profiles table MISSING: " . $e->getMessage() . "\n";
}

// Test 5: Try inserting a test company user
try {
    $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, email, password, role) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute(['Test', 'Company', 'testcompany_debug@test.com', password_hash('password123', PASSWORD_DEFAULT), 'company']);
    $newId = $pdo->lastInsertId();
    echo "\n✅ Test user inserted with ID: $newId\n";

    // Clean up
    $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$newId]);
    echo "✅ Test user deleted (cleanup done)\n";
} catch (Exception $e) {
    echo "\n❌ Insert failed: " . $e->getMessage() . "\n";
}

<?php
// ── company_auth.php ─────────────────────────────────────────────────────────
// Include at the top of every company PHP page.
// Ensures the user is logged in AND has the 'company' role.
// Usage:
//   require 'company_auth.php';
//   // $_SESSION['user_id'], ['email'], ['role'] are available after this.
 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
 
if (empty($_SESSION['user_id']) || $_SESSION['role'] !== 'company') {
    // Return JSON error if this is an API endpoint (called via fetch)
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')) {
        header('Content-Type: application/json');
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized. Please log in.']);
        exit;
    }
    // Otherwise redirect to login page
    header('Location: login.html');
    exit;
}

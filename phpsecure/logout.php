<?php
// ─────────────────────────────────────────────
//  logout.php  —  internLink
//  Destroys the session and redirects to login.
// ─────────────────────────────────────────────

session_start();
session_unset();
session_destroy();

header('Location: ../index.html');
exit;

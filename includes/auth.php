<?php
// Authentication check for admin pages
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

// Get current script name
$current_page = basename($_SERVER['PHP_SELF']);

// Allow access to login page without authentication
if ($current_page !== 'login.php' && !isLoggedIn()) {
    redirect('login.php');
}

// Regenerate session ID on first load after login for security
if (isLoggedIn() && !isset($_SESSION['regenerated'])) {
    session_regenerate_id(true);
    $_SESSION['regenerated'] = true;
}
?>

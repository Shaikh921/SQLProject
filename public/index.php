<?php
require_once '../includes/config.php';
require_once '../includes/helpers.php';

// Check if user is already logged in
if (isset($_SESSION['admin_id'])) {
    redirect('../admin/dashboard.php');
}

if (isset($_SESSION['user_id'])) {
    redirect('../user/dashboard.php');
}

// Redirect to login page
redirect('login.php');
?>

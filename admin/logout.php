<?php
require_once '../includes/config.php';
require_once '../includes/helpers.php';

// Don't log logout activity
// Destroy session
session_destroy();
redirect('../public/login.php');
?>

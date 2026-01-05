<?php
require_once '../includes/config.php';
require_once '../includes/helpers.php';

// Destroy session
session_destroy();
redirect('../public/login.php');
?>

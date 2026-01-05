<?php
// Sanitize input data
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}

// Redirect to URL
function redirect($url) {
    header("Location: $url");
    exit();
}

// Hash password
function hash_password($plain) {
    return password_hash($plain, PASSWORD_DEFAULT);
}

// Verify password
function verify_password($plain, $hash) {
    return password_verify($plain, $hash);
}

// Flash messages
function flash($name, $message = '') {
    if (!empty($message)) {
        $_SESSION['flash_' . $name] = $message;
    } else {
        if (isset($_SESSION['flash_' . $name])) {
            $msg = $_SESSION['flash_' . $name];
            unset($_SESSION['flash_' . $name]);
            return $msg;
        }
    }
    return '';
}

// Display flash message
function display_flash($name) {
    $message = flash($name);
    if (!empty($message)) {
        echo '<div class="alert alert-info alert-dismissible fade show" role="alert">';
        echo htmlspecialchars($message);
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
        echo '</div>';
    }
}

// Log activity
function log_activity($conn, $admin_id, $action) {
    $stmt = mysqli_prepare($conn, "INSERT INTO activity_log (admin_id, action) VALUES (?, ?)");
    mysqli_stmt_bind_param($stmt, "is", $admin_id, $action);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}
?>

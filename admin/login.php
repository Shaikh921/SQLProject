<?php
require_once '../includes/config.php';
require_once '../includes/helpers.php';

// Redirect to unified login
redirect('../public/login.php');

// This file is deprecated - redirects to unified login
exit();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password';
    } else {
        // Fetch admin from database
        $stmt = mysqli_prepare($conn, "SELECT admin_id, username, password_hash, role FROM admins WHERE username = ?");
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($admin = mysqli_fetch_assoc($result)) {
            if (verify_password($password, $admin['password_hash'])) {
                // Set session variables
                $_SESSION['admin_id'] = $admin['admin_id'];
                $_SESSION['username'] = $admin['username'];
                $_SESSION['role'] = $admin['role'];
                
                // Log activity
                log_activity($conn, $admin['admin_id'], 'Admin logged in');
                
                // Redirect to dashboard
                redirect('dashboard.php');
            } else {
                $error = 'Invalid username or password';
            }
        } else {
            $error = 'Invalid username or password';
        }
        
        mysqli_stmt_close($stmt);
    }
}

$page_title = 'Admin Login';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../public/assets/css/styles.css">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-md-6 col-lg-4">
                <div class="card shadow">
                    <div class="card-body">
                        <h3 class="card-title text-center mb-4">🔐 Admin Login</h3>
                        
                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>
                        
                        <form method="POST" action="" class="needs-validation" novalidate>
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="username" name="username" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100">Login</button>
                        </form>
                        
                        <div class="text-center mt-3">
                            <small class="text-muted">Enter your admin credentials</small>
                        </div>
                        
                        <div class="text-center mt-3">
                            <a href="../public/index.php" class="btn btn-link">Back to Home</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../public/assets/js/main.js"></script>
</body>
</html>

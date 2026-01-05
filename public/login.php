<?php
require_once '../includes/config.php';
require_once '../includes/helpers.php';

// Redirect if already logged in
if (isset($_SESSION['admin_id'])) {
    redirect('../admin/dashboard.php');
}

if (isset($_SESSION['user_id'])) {
    redirect('../user/dashboard.php');
}

$error = '';
$login_type = isset($_POST['login_type']) ? $_POST['login_type'] : 'member';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username']);
    $password = $_POST['password'];
    $login_type = $_POST['login_type'];
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password';
    } else {
        if ($login_type === 'admin') {
            // Admin login
            $stmt = mysqli_prepare($conn, "SELECT admin_id, username, name, password_hash, role FROM admins WHERE username = ?");
            mysqli_stmt_bind_param($stmt, "s", $username);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            if ($admin = mysqli_fetch_assoc($result)) {
                if (verify_password($password, $admin['password_hash'])) {
                    $_SESSION['admin_id'] = $admin['admin_id'];
                    $_SESSION['username'] = $admin['username'];
                    $_SESSION['name'] = $admin['name'] ?? $admin['username']; // Use name if available, otherwise username
                    $_SESSION['role'] = $admin['role'];
                    $_SESSION['user_type'] = 'admin';
                    
                    // Don't log login activity
                    redirect('../admin/dashboard.php');
                } else {
                    $error = 'Invalid username or password';
                }
            } else {
                $error = 'Invalid username or password';
            }
            mysqli_stmt_close($stmt);
            
        } else {
            // Member login (using email as username)
            $stmt = mysqli_prepare($conn, "SELECT member_id, name, email, password_hash FROM members WHERE email = ?");
            mysqli_stmt_bind_param($stmt, "s", $username);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            if ($member = mysqli_fetch_assoc($result)) {
                // Check if member has password hash
                if (!empty($member['password_hash'])) {
                    // Verify password with hash
                    if (verify_password($password, $member['password_hash'])) {
                        $_SESSION['user_id'] = $member['member_id'];
                        $_SESSION['user_name'] = $member['name'];
                        $_SESSION['user_email'] = $member['email'];
                        $_SESSION['user_type'] = 'member';
                        
                        redirect('../user/dashboard.php');
                    } else {
                        $error = 'Invalid email or password';
                    }
                } else {
                    $error = 'Account not properly configured. Please contact administrator.';
                }
            } else {
                $error = 'Invalid email or password';
            }
            mysqli_stmt_close($stmt);
        }
    }
}

$page_title = 'Login - Library Management System';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/styles.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-container {
            max-width: 450px;
            width: 100%;
        }
        .login-card {
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        .login-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 15px 15px 0 0;
            text-align: center;
        }
        .login-body {
            padding: 30px;
            background: white;
            border-radius: 0 0 15px 15px;
        }
        .login-tabs {
            display: flex;
            margin-bottom: 20px;
            border-radius: 10px;
            overflow: hidden;
        }
        .login-tab {
            flex: 1;
            padding: 12px;
            text-align: center;
            cursor: pointer;
            background: #f8f9fa;
            border: none;
            transition: all 0.3s;
        }
        .login-tab.active {
            background: #667eea;
            color: white;
        }
        .login-tab:hover {
            background: #5568d3;
            color: white;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h2>📚 Library Management System</h2>
                <p class="mb-0">Welcome! Please login to continue</p>
            </div>
            
            <div class="login-body">
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <div class="login-tabs">
                    <button class="login-tab <?php echo $login_type === 'admin' ? 'active' : ''; ?>" 
                            onclick="switchTab('admin')" id="admin-tab">
                        🔐 Admin Login
                    </button>
                    <button class="login-tab <?php echo $login_type === 'member' ? 'active' : ''; ?>" 
                            onclick="switchTab('member')" id="member-tab">
                        👤 Member Login
                    </button>
                </div>
                
                <form method="POST" action="" class="needs-validation" novalidate>
                    <input type="hidden" name="login_type" id="login_type" value="<?php echo $login_type; ?>">
                    
                    <div class="mb-3">
                        <label for="username" class="form-label" id="username-label">
                            <?php echo $login_type === 'admin' ? 'Username' : 'Email'; ?>
                        </label>
                        <input type="text" class="form-control" id="username" name="username" required maxlength="150"
                               placeholder="<?php echo $login_type === 'admin' ? 'Enter username' : 'Enter email'; ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required maxlength="255"
                               placeholder="Enter password">
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100 mb-3">
                        Login
                    </button>
                    
                    <div class="text-center" id="login-hint">
                        <?php if ($login_type === 'admin'): ?>
                            <small class="text-muted">
                                Enter your admin credentials
                            </small>
                        <?php else: ?>
                            <small class="text-muted">
                                Use your registered email and password
                            </small>
                        <?php endif; ?>
                    </div>
                    
                    <div class="text-center mt-3" id="register-link" style="<?php echo $login_type === 'admin' ? 'display:none;' : 'display:block;'; ?>">
                        <hr class="my-3">
                        <p class="mb-2 text-muted">Don't have an account?</p>
                        <a href="register.php" class="btn btn-outline-primary btn-sm">
                            📝 Register as New Member
                        </a>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="text-center mt-3">
            <small class="text-white">© 2025 Library Management System</small>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function switchTab(type) {
            document.getElementById('login_type').value = type;
            
            // Update active tab
            document.getElementById('admin-tab').classList.remove('active');
            document.getElementById('member-tab').classList.remove('active');
            document.getElementById(type + '-tab').classList.add('active');
            
            // Update labels and hints
            const usernameLabel = document.getElementById('username-label');
            const usernameInput = document.getElementById('username');
            const loginHint = document.getElementById('login-hint');
            const registerLink = document.getElementById('register-link');
            
            if (type === 'admin') {
                usernameLabel.textContent = 'Username';
                usernameInput.placeholder = 'Enter username';
                usernameInput.type = 'text';
                usernameInput.removeAttribute('pattern');
                loginHint.innerHTML = '<small class="text-muted">Enter your admin credentials</small>';
                registerLink.style.display = 'none';
            } else {
                usernameLabel.textContent = 'Email';
                usernameInput.placeholder = 'Enter email';
                usernameInput.type = 'email';
                loginHint.innerHTML = '<small class="text-muted">Use your registered email and password</small>';
                registerLink.style.display = 'block';
            }
        }
        
        // Initialize validation based on current login type
        document.addEventListener('DOMContentLoaded', function() {
            const currentType = document.getElementById('login_type').value;
            switchTab(currentType);
        });
        
        // Form validation
        (function() {
            'use strict';
            const forms = document.querySelectorAll('.needs-validation');
            Array.from(forms).forEach(form => {
                form.addEventListener('submit', event => {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            });
        })();
    </script>
</body>
</html>

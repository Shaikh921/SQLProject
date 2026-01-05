<?php
require_once '../includes/config.php';
require_once '../includes/helpers.php';

// Redirect if already logged in
if (isset($_SESSION['user_id']) || isset($_SESSION['admin_id'])) {
    redirect('login.php');
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone']);
    $address = sanitize($_POST['address']);
    $membership_type = sanitize($_POST['membership_type']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Comprehensive validation
    if (empty($name)) {
        $error = 'Full name is required';
    } elseif (strlen($name) < 2) {
        $error = 'Full name must be at least 2 characters long';
    } elseif (strlen($name) > 120) {
        $error = 'Full name cannot exceed 120 characters';
    } elseif (!preg_match('/^[a-zA-Z\s\.\-\']+$/', $name)) {
        $error = 'Full name can only contain letters, spaces, dots, hyphens, and apostrophes';
    } elseif (empty($email)) {
        $error = 'Email address is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address';
    } elseif (strlen($email) > 150) {
        $error = 'Email address cannot exceed 150 characters';
    } elseif (!empty($phone) && !preg_match('/^[\+]?[0-9\s\-\(\)]{10,20}$/', $phone)) {
        $error = 'Please enter a valid phone number (10-20 digits)';
    } elseif (!empty($address) && strlen($address) > 255) {
        $error = 'Address cannot exceed 255 characters';
    } elseif (empty($membership_type) || !in_array($membership_type, ['student', 'faculty'])) {
        $error = 'Please select a valid membership type';
    } elseif (empty($password)) {
        $error = 'Password is required';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long';
    } elseif (strlen($password) > 255) {
        $error = 'Password cannot exceed 255 characters';
    } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/', $password)) {
        $error = 'Password must contain at least one lowercase letter, one uppercase letter, and one number';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } else {
        // Check if email already exists
        $check_stmt = mysqli_prepare($conn, "SELECT member_id FROM members WHERE email = ?");
        mysqli_stmt_bind_param($check_stmt, "s", $email);
        mysqli_stmt_execute($check_stmt);
        $check_result = mysqli_stmt_get_result($check_stmt);
        
        if (mysqli_num_rows($check_result) > 0) {
            $error = 'Email already registered. Please login or use a different email.';
        } else {
            // Hash password
            $password_hash = hash_password($password);
            
            // Insert new member
            $stmt = mysqli_prepare($conn, "INSERT INTO members (name, email, phone, address, membership_type, password_hash) VALUES (?, ?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "ssssss", $name, $email, $phone, $address, $membership_type, $password_hash);
            
            if (mysqli_stmt_execute($stmt)) {
                $success = 'Registration successful! You can now login.';
                // Auto-login after registration
                $member_id = mysqli_insert_id($conn);
                $_SESSION['user_id'] = $member_id;
                $_SESSION['user_name'] = $name;
                $_SESSION['user_email'] = $email;
                $_SESSION['user_type'] = 'member';
                
                // Redirect after 2 seconds
                header("refresh:2;url=../user/dashboard.php");
            } else {
                $error = 'Registration failed. Please try again.';
            }
            mysqli_stmt_close($stmt);
        }
        mysqli_stmt_close($check_stmt);
    }
}

$page_title = 'Register - Library Management System';
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
            padding: 40px 0;
        }
        .register-container {
            max-width: 600px;
            margin: 0 auto;
        }
        .register-card {
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            background: white;
        }
        .register-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 15px 15px 0 0;
            text-align: center;
        }
        .register-body {
            padding: 30px;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-card">
            <div class="register-header">
                <h2>📚 Member Registration</h2>
                <p class="mb-0">Join our library community</p>
            </div>
            
            <div class="register-body">
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <?php echo htmlspecialchars($success); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="" class="needs-validation" novalidate>
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="name" class="form-label">Full Name *</label>
                            <input type="text" class="form-control" id="name" name="name" required minlength="2" maxlength="120" pattern="[a-zA-Z\s\.\-']+"
                                   value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>"
                                   title="Name can only contain letters, spaces, dots, hyphens, and apostrophes">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="email" class="form-label">Email Address *</label>
                            <input type="email" class="form-control" id="email" name="email" required maxlength="150"
                                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                            <small class="text-muted">This will be your login username</small>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" id="phone" name="phone" pattern="[\+]?[0-9\s\-\(\)]{10,20}" maxlength="20"
                                   value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>"
                                   title="Please enter a valid phone number (10-20 digits)">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="membership_type" class="form-label">Membership Type *</label>
                            <select class="form-select" id="membership_type" name="membership_type" required>
                                <option value="student" <?php echo (isset($_POST['membership_type']) && $_POST['membership_type'] == 'student') ? 'selected' : ''; ?>>Student</option>
                                <option value="faculty" <?php echo (isset($_POST['membership_type']) && $_POST['membership_type'] == 'faculty') ? 'selected' : ''; ?>>Faculty</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="address" class="form-label">Address</label>
                        <textarea class="form-control" id="address" name="address" rows="2" maxlength="255"><?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="password" class="form-label">Password *</label>
                            <input type="password" class="form-control" id="password" name="password" required minlength="8" maxlength="255">
                            <small class="text-muted">Minimum 8 characters with uppercase, lowercase, and number</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="confirm_password" class="form-label">Confirm Password *</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required minlength="8" maxlength="255">
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100 mb-3">
                        Register
                    </button>
                    
                    <div class="text-center">
                        <small class="text-muted">
                            Already have an account? 
                            <a href="login.php" class="text-decoration-none">Login here</a>
                        </small>
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
        
        // Enhanced validation
        const nameInput = document.getElementById('name');
        const emailInput = document.getElementById('email');
        const phoneInput = document.getElementById('phone');
        const passwordInput = document.getElementById('password');
        const confirmPasswordInput = document.getElementById('confirm_password');
        
        // Name validation
        nameInput.addEventListener('input', function() {
            const name = this.value.trim();
            if (name.length < 2) {
                this.setCustomValidity('Name must be at least 2 characters long');
            } else if (!/^[a-zA-Z\s\.\-']+$/.test(name)) {
                this.setCustomValidity('Name can only contain letters, spaces, dots, hyphens, and apostrophes');
            } else {
                this.setCustomValidity('');
            }
        });
        
        // Phone validation
        phoneInput.addEventListener('input', function() {
            const phone = this.value.trim();
            if (phone && !/^[\+]?[0-9\s\-\(\)]{10,20}$/.test(phone)) {
                this.setCustomValidity('Please enter a valid phone number (10-20 digits)');
            } else {
                this.setCustomValidity('');
            }
        });
        
        // Password strength validation
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            if (password.length < 8) {
                this.setCustomValidity('Password must be at least 8 characters long');
            } else if (!/(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/.test(password)) {
                this.setCustomValidity('Password must contain at least one lowercase letter, one uppercase letter, and one number');
            } else {
                this.setCustomValidity('');
            }
            
            // Recheck confirm password when password changes
            const confirmPassword = confirmPasswordInput.value;
            if (confirmPassword && password !== confirmPassword) {
                confirmPasswordInput.setCustomValidity('Passwords do not match');
            } else {
                confirmPasswordInput.setCustomValidity('');
            }
        });
        
        // Password match validation
        confirmPasswordInput.addEventListener('input', function() {
            const password = passwordInput.value;
            const confirmPassword = this.value;
            
            if (password !== confirmPassword) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
</body>
</html>

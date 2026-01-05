<?php
require_once '../includes/auth.php';

$page_title = 'Add Member';
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone']);
    $address = sanitize($_POST['address']);
    $membership_type = sanitize($_POST['membership_type']);
    
    if (empty($name) || empty($email)) {
        $error = 'Name and email are required';
    } else {
        $stmt = mysqli_prepare($conn, "INSERT INTO members (name, email, phone, address, membership_type) VALUES (?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "sssss", $name, $email, $phone, $address, $membership_type);
        
        if (mysqli_stmt_execute($stmt)) {
            log_activity($conn, $_SESSION['admin_id'], "Added member: '$name'");
            $success = 'Member added successfully';
        } else {
            $error = 'Failed to add member: ' . mysqli_error($conn);
        }
        mysqli_stmt_close($stmt);
    }
}

include '../includes/header.php';
?>

<h2>Add Member</h2>

<?php if (!empty($success)): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <form method="POST" class="needs-validation" novalidate>
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="name" class="form-label">Name *</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email *</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone</label>
                        <input type="text" class="form-control" id="phone" name="phone">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="membership_type" class="form-label">Membership Type *</label>
                        <select class="form-select" id="membership_type" name="membership_type" required>
                            <option value="student">Student</option>
                            <option value="faculty">Faculty</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="mb-3">
                <label for="address" class="form-label">Address</label>
                <textarea class="form-control" id="address" name="address" rows="3"></textarea>
            </div>
            
            <button type="submit" class="btn btn-primary">Add Member</button>
            <a href="dashboard.php" class="btn btn-secondary">Back</a>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

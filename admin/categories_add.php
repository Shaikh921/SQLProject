<?php
require_once '../includes/auth.php';

$page_title = 'Add Category';
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category_name = sanitize($_POST['category_name']);
    
    if (empty($category_name)) {
        $error = 'Category name is required';
    } else {
        $stmt = mysqli_prepare($conn, "INSERT INTO book_categories (category_name) VALUES (?)");
        mysqli_stmt_bind_param($stmt, "s", $category_name);
        
        if (mysqli_stmt_execute($stmt)) {
            log_activity($conn, $_SESSION['admin_id'], "Added category: $category_name");
            $success = 'Category added successfully';
        } else {
            $error = 'Failed to add category';
        }
        mysqli_stmt_close($stmt);
    }
}

// Get all categories
$categories = mysqli_query($conn, "SELECT * FROM book_categories ORDER BY category_name");

include '../includes/header.php';
?>

<h2>Add Category</h2>

<?php if (!empty($success)): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <form method="POST" class="needs-validation" novalidate>
                    <div class="mb-3">
                        <label for="category_name" class="form-label">Category Name</label>
                        <input type="text" class="form-control" id="category_name" name="category_name" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Add Category</button>
                    <a href="dashboard.php" class="btn btn-secondary">Back</a>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5>Existing Categories</h5>
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($category = mysqli_fetch_assoc($categories)): ?>
                        <tr>
                            <td><?php echo $category['category_id']; ?></td>
                            <td><?php echo htmlspecialchars($category['category_name']); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

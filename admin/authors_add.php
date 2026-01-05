<?php
require_once '../includes/auth.php';

$page_title = 'Add Author';
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $author_name = sanitize($_POST['author_name']);
    
    if (empty($author_name)) {
        $error = 'Author name is required';
    } else {
        $stmt = mysqli_prepare($conn, "INSERT INTO authors (author_name) VALUES (?)");
        mysqli_stmt_bind_param($stmt, "s", $author_name);
        
        if (mysqli_stmt_execute($stmt)) {
            log_activity($conn, $_SESSION['admin_id'], "Added author: $author_name");
            $success = 'Author added successfully';
        } else {
            $error = 'Failed to add author';
        }
        mysqli_stmt_close($stmt);
    }
}

// Get all authors
$authors = mysqli_query($conn, "SELECT * FROM authors ORDER BY author_name");

include '../includes/header.php';
?>

<h2>Add Author</h2>

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
                        <label for="author_name" class="form-label">Author Name</label>
                        <input type="text" class="form-control" id="author_name" name="author_name" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Add Author</button>
                    <a href="dashboard.php" class="btn btn-secondary">Back</a>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5>Existing Authors</h5>
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
                        <?php while ($author = mysqli_fetch_assoc($authors)): ?>
                        <tr>
                            <td><?php echo $author['author_id']; ?></td>
                            <td><?php echo htmlspecialchars($author['author_name']); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

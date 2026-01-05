<?php
require_once '../includes/auth.php';

$page_title = 'Add Book';
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitize($_POST['title']);
    $author_id = intval($_POST['author_id']);
    $category_id = intval($_POST['category_id']);
    $publisher = sanitize($_POST['publisher']);
    $publication_year = intval($_POST['publication_year']);
    $isbn = sanitize($_POST['isbn']);
    $quantity_total = intval($_POST['quantity_total']);
    $shelf_location = sanitize($_POST['shelf_location']);
    
    if (empty($title) || $author_id <= 0 || $category_id <= 0) {
        $error = 'Please fill all required fields';
    } else {
        $quantity_available = $quantity_total;
        $stmt = mysqli_prepare($conn, "INSERT INTO books (title, author_id, category_id, publisher, publication_year, isbn, quantity_total, quantity_available, shelf_location) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "siisissss", $title, $author_id, $category_id, $publisher, $publication_year, $isbn, $quantity_total, $quantity_available, $shelf_location);
        
        if (mysqli_stmt_execute($stmt)) {
            log_activity($conn, $_SESSION['admin_id'], "Added book: '$title'");
            $success = 'Book added successfully';
        } else {
            $error = 'Failed to add book: ' . mysqli_error($conn);
        }
        mysqli_stmt_close($stmt);
    }
}

// Get authors and categories
$authors = mysqli_query($conn, "SELECT * FROM authors ORDER BY author_name");
$categories = mysqli_query($conn, "SELECT * FROM book_categories ORDER BY category_name");

include '../includes/header.php';
?>

<h2>Add Book</h2>

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
                        <label for="title" class="form-label">Title *</label>
                        <input type="text" class="form-control" id="title" name="title" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="isbn" class="form-label">ISBN</label>
                        <input type="text" class="form-control" id="isbn" name="isbn">
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="author_id" class="form-label">Author *</label>
                        <select class="form-select" id="author_id" name="author_id" required>
                            <option value="">Select Author</option>
                            <?php while ($author = mysqli_fetch_assoc($authors)): ?>
                                <option value="<?php echo $author['author_id']; ?>">
                                    <?php echo htmlspecialchars($author['author_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="category_id" class="form-label">Category *</label>
                        <select class="form-select" id="category_id" name="category_id" required>
                            <option value="">Select Category</option>
                            <?php while ($category = mysqli_fetch_assoc($categories)): ?>
                                <option value="<?php echo $category['category_id']; ?>">
                                    <?php echo htmlspecialchars($category['category_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="publisher" class="form-label">Publisher</label>
                        <input type="text" class="form-control" id="publisher" name="publisher">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="publication_year" class="form-label">Publication Year</label>
                        <input type="number" class="form-control" id="publication_year" name="publication_year" min="1900" max="2099">
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="quantity_total" class="form-label">Total Quantity *</label>
                        <input type="number" class="form-control" id="quantity_total" name="quantity_total" value="1" min="1" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="shelf_location" class="form-label">Shelf Location</label>
                        <input type="text" class="form-control" id="shelf_location" name="shelf_location">
                    </div>
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary">Add Book</button>
            <a href="dashboard.php" class="btn btn-secondary">Back</a>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

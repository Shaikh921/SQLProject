<?php
require_once '../includes/config.php';
require_once '../includes/helpers.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) && !isset($_SESSION['admin_id'])) {
    redirect('../public/login.php');
}

$page_title = 'Book Details';

$book_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($book_id <= 0) {
    redirect('browse.php');
}

// Get book details
$stmt = mysqli_prepare($conn, "SELECT b.*, a.author_name, c.category_name 
                                FROM books b 
                                JOIN authors a ON b.author_id = a.author_id 
                                JOIN book_categories c ON b.category_id = c.category_id 
                                WHERE b.book_id = ?");
mysqli_stmt_bind_param($stmt, "i", $book_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$book = mysqli_fetch_assoc($result);

if (!$book) {
    redirect('browse.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($book['title']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../public/assets/css/styles.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">📚 Library System</a>
            <div class="navbar-nav ms-auto">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a class="nav-link" href="dashboard.php">Dashboard</a>
                    <a class="nav-link" href="browse.php">Browse</a>
                    <span class="nav-link">Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                    <a class="nav-link" href="logout.php">Logout</a>
                <?php elseif (isset($_SESSION['admin_id'])): ?>
                    <a class="nav-link" href="../admin/dashboard.php">Admin Panel</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>
    
    <div class="container mt-4">
        <div class="card">
            <div class="card-header">
                <h2><?php echo htmlspecialchars($book['title']); ?></h2>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h5>Book Information</h5>
                        <table class="table">
                            <tr>
                                <th>Author:</th>
                                <td><?php echo htmlspecialchars($book['author_name']); ?></td>
                            </tr>
                            <tr>
                                <th>Category:</th>
                                <td><?php echo htmlspecialchars($book['category_name']); ?></td>
                            </tr>
                            <tr>
                                <th>Publisher:</th>
                                <td><?php echo htmlspecialchars($book['publisher']); ?></td>
                            </tr>
                            <tr>
                                <th>Publication Year:</th>
                                <td><?php echo htmlspecialchars($book['publication_year']); ?></td>
                            </tr>
                            <tr>
                                <th>ISBN:</th>
                                <td><?php echo htmlspecialchars($book['isbn']); ?></td>
                            </tr>
                            <tr>
                                <th>Shelf Location:</th>
                                <td><?php echo htmlspecialchars($book['shelf_location']); ?></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h5>Availability</h5>
                        <table class="table">
                            <tr>
                                <th>Total Copies:</th>
                                <td><?php echo $book['quantity_total']; ?></td>
                            </tr>
                            <tr>
                                <th>Available Copies:</th>
                                <td>
                                    <?php if ($book['quantity_available'] > 0): ?>
                                        <span class="badge bg-success"><?php echo $book['quantity_available']; ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Not Available</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        </table>
                        
                        <?php if ($book['quantity_available'] <= 0): ?>
                            <div class="alert alert-info">
                                This book is currently unavailable. You can reserve it for when it becomes available.
                            </div>
                            <a href="reserve_book.php?id=<?php echo $book_id; ?>" class="btn btn-warning">Reserve Book</a>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="mt-3">
                    <a href="browse.php" class="btn btn-secondary">Back to Browse</a>
                </div>
            </div>
        </div>
    </div>
    
    <footer class="bg-light text-center text-muted py-3 mt-5">
        <p>&copy; 2025 Library Management System. All rights reserved.</p>
    </footer>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

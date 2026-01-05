<?php
require_once '../includes/config.php';
require_once '../includes/helpers.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) && !isset($_SESSION['admin_id'])) {
    redirect('../public/login.php');
}

$page_title = 'Reserve Book';
$message = 'This feature allows members to reserve books. Please contact the librarian to complete your reservation.';

$book_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($book_id > 0) {
    $stmt = mysqli_prepare($conn, "SELECT title FROM books WHERE book_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $book_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $book = mysqli_fetch_assoc($result);
}
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
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">📚 Library System</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="dashboard.php">Dashboard</a>
                <a class="nav-link" href="browse.php">Browse</a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a class="nav-link" href="logout.php">Logout</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>
    
    <div class="container mt-4">
        <div class="card">
            <div class="card-header">
                <h2>Reserve Book</h2>
            </div>
            <div class="card-body">
                <?php if (isset($book)): ?>
                    <h5>Book: <?php echo htmlspecialchars($book['title']); ?></h5>
                <?php endif; ?>
                
                <div class="alert alert-info mt-3">
                    <?php echo $message; ?>
                </div>
                
                <a href="browse.php" class="btn btn-secondary">Back to Browse</a>
            </div>
        </div>
    </div>
    
    <footer class="bg-light text-center text-muted py-3 mt-5">
        <p>&copy; 2025 Library Management System. All rights reserved.</p>
    </footer>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

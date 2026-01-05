<?php
require_once '../includes/config.php';
require_once '../includes/helpers.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    redirect('../public/login.php');
}

$page_title = 'My Dashboard';
$member_id = $_SESSION['user_id'];

// Get member's currently issued books
$issued_stmt = mysqli_prepare($conn, "SELECT i.*, b.title, b.isbn, a.author_name,
                                i.issue_date, i.due_date, i.return_date, i.fine_amount, i.status,
                                DATEDIFF(CURRENT_DATE, i.due_date) as days_overdue
                                FROM issue_books i
                                JOIN books b ON i.book_id = b.book_id
                                JOIN authors a ON b.author_id = a.author_id
                                WHERE i.member_id = ? AND i.status = 'issued'
                                ORDER BY i.due_date");
mysqli_stmt_bind_param($issued_stmt, "i", $member_id);
mysqli_stmt_execute($issued_stmt);
$issued_books = mysqli_stmt_get_result($issued_stmt);

// Get statistics
$stats_query = mysqli_query($conn, "SELECT 
    COUNT(*) as total_issues,
    SUM(CASE WHEN status='issued' THEN 1 ELSE 0 END) as active_issues,
    SUM(CASE WHEN status='returned' THEN 1 ELSE 0 END) as returned_books,
    SUM(fine_amount) as total_fines
    FROM issue_books WHERE member_id = $member_id");
$stats = mysqli_fetch_assoc($stats_query);
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
                <a class="nav-link" href="browse.php">Browse Books</a>
                <span class="nav-link">Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                <a class="nav-link" href="my_issues.php">My Issues</a>
                <a class="nav-link" href="logout.php">Logout</a>
            </div>
        </div>
    </nav>
    
    <div class="container mt-4">
        <h2>My Dashboard</h2>
        <p class="text-muted">Welcome back, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</p>
        
        <!-- Statistics Cards -->
        <div class="row mt-4 mb-4">
            <div class="col-md-3">
                <div class="card stat-card bg-primary text-white">
                    <div class="card-body">
                        <h5 class="card-title">Currently Borrowed</h5>
                        <h2><?php echo $stats['active_issues']; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card bg-success text-white">
                    <div class="card-body">
                        <h5 class="card-title">Total Borrowed</h5>
                        <h2><?php echo $stats['total_issues']; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card bg-info text-white">
                    <div class="card-body">
                        <h5 class="card-title">Returned Books</h5>
                        <h2><?php echo $stats['returned_books']; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card bg-warning text-white">
                    <div class="card-body">
                        <h5 class="card-title">Total Fines</h5>
                        <h2>₹<?php echo number_format($stats['total_fines'], 2); ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <!-- Currently Borrowed Books -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">📚 Books Currently Borrowed by You</h5>
            </div>
            <div class="card-body">
                <?php if (mysqli_num_rows($issued_books) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Book Title</th>
                                    <th>Author</th>
                                    <th>ISBN</th>
                                    <th>Issue Date</th>
                                    <th>Due Date</th>
                                    <th>Days Left</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($issue = mysqli_fetch_assoc($issued_books)): ?>
                                    <?php 
                                    $days_left = -$issue['days_overdue'];
                                    $is_overdue = $issue['days_overdue'] > 0;
                                    ?>
                                    <tr class="<?php echo $is_overdue ? 'table-danger' : ''; ?>">
                                        <td>
                                            <strong><?php echo htmlspecialchars($issue['title']); ?></strong>
                                        </td>
                                        <td><?php echo htmlspecialchars($issue['author_name']); ?></td>
                                        <td><small><?php echo htmlspecialchars($issue['isbn']); ?></small></td>
                                        <td><?php echo date('d M Y', strtotime($issue['issue_date'])); ?></td>
                                        <td><?php echo date('d M Y', strtotime($issue['due_date'])); ?></td>
                                        <td>
                                            <?php if ($is_overdue): ?>
                                                <span class="badge bg-danger">Overdue by <?php echo $issue['days_overdue']; ?> days</span>
                                            <?php elseif ($days_left <= 3): ?>
                                                <span class="badge bg-warning text-dark"><?php echo $days_left; ?> days left</span>
                                            <?php else: ?>
                                                <span class="badge bg-success"><?php echo $days_left; ?> days left</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($is_overdue): ?>
                                                <span class="badge bg-danger">⚠️ Please Return</span>
                                            <?php else: ?>
                                                <span class="badge bg-success">✓ Active</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <?php 
                    // Check for overdue books
                    mysqli_data_seek($issued_books, 0);
                    $has_overdue = false;
                    while ($check = mysqli_fetch_assoc($issued_books)) {
                        if ($check['days_overdue'] > 0) {
                            $has_overdue = true;
                            break;
                        }
                    }
                    ?>
                    
                    <?php if ($has_overdue): ?>
                        <div class="alert alert-danger mt-3">
                            <strong>⚠️ Attention!</strong> You have overdue books. Please return them to avoid additional fines (₹5 per day).
                        </div>
                    <?php endif; ?>
                    
                <?php else: ?>
                    <div class="alert alert-info">
                        <strong>No books currently borrowed.</strong> 
                        <a href="browse.php" class="alert-link">Browse our collection</a> to find books to borrow!
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body text-center">
                        <h5>📖 Browse Books</h5>
                        <p>Explore our collection and find your next read</p>
                        <a href="browse.php" class="btn btn-primary">Browse Catalog</a>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body text-center">
                        <h5>📋 View All Issues</h5>
                        <p>See your complete borrowing history</p>
                        <a href="my_issues.php" class="btn btn-info">View History</a>
                    </div>
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

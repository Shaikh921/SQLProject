<?php
require_once '../includes/config.php';
require_once '../includes/helpers.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    redirect('../public/login.php');
}

$page_title = 'My Issues';
$member_id = $_SESSION['user_id'];

// Get member's issued books
$stmt = mysqli_prepare($conn, "SELECT i.*, b.title, b.isbn, a.author_name,
                                i.issue_date, i.due_date, i.return_date, i.fine_amount, i.status,
                                DATEDIFF(CURRENT_DATE, i.due_date) as days_overdue
                                FROM issue_books i
                                JOIN books b ON i.book_id = b.book_id
                                JOIN authors a ON b.author_id = a.author_id
                                WHERE i.member_id = ?
                                ORDER BY i.issue_date DESC");
mysqli_stmt_bind_param($stmt, "i", $member_id);
mysqli_stmt_execute($stmt);
$issues = mysqli_stmt_get_result($stmt);

// Get statistics
$stats_query = mysqli_query($conn, "SELECT 
    COUNT(*) as total_issues,
    SUM(CASE WHEN status='issued' THEN 1 ELSE 0 END) as active_issues,
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
                <a class="nav-link" href="dashboard.php">Dashboard</a>
                <a class="nav-link" href="browse.php">Browse Books</a>
                <span class="nav-link">Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                <a class="nav-link" href="my_issues.php">My Issues</a>
                <a class="nav-link" href="logout.php">Logout</a>
            </div>
        </div>
    </nav>
    
    <div class="container mt-4">
        <h2>My Book Issues</h2>
        
        <div class="row mt-4 mb-4">
            <div class="col-md-4">
                <div class="card stat-card">
                    <div class="card-body">
                        <h5 class="card-title">Total Issues</h5>
                        <h2><?php echo $stats['total_issues']; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stat-card">
                    <div class="card-body">
                        <h5 class="card-title">Currently Issued</h5>
                        <h2><?php echo $stats['active_issues']; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stat-card">
                    <div class="card-body">
                        <h5 class="card-title">Total Fines</h5>
                        <h2>₹<?php echo number_format($stats['total_fines'], 2); ?></h2>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h5>Issue History</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Book Title</th>
                                <th>Author</th>
                                <th>Issue Date</th>
                                <th>Due Date</th>
                                <th>Return Date</th>
                                <th>Fine</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (mysqli_num_rows($issues) > 0): ?>
                                <?php while ($issue = mysqli_fetch_assoc($issues)): ?>
                                <tr class="<?php echo ($issue['status'] == 'issued' && $issue['days_overdue'] > 0) ? 'table-danger' : ''; ?>">
                                    <td><?php echo htmlspecialchars($issue['title']); ?></td>
                                    <td><?php echo htmlspecialchars($issue['author_name']); ?></td>
                                    <td><?php echo htmlspecialchars($issue['issue_date']); ?></td>
                                    <td><?php echo htmlspecialchars($issue['due_date']); ?></td>
                                    <td>
                                        <?php 
                                        if ($issue['return_date']) {
                                            echo htmlspecialchars($issue['return_date']);
                                        } else {
                                            echo '<span class="badge bg-warning">Not Returned</span>';
                                        }
                                        ?>
                                    </td>
                                    <td>₹<?php echo number_format($issue['fine_amount'], 2); ?></td>
                                    <td>
                                        <?php if ($issue['status'] == 'issued'): ?>
                                            <?php if ($issue['days_overdue'] > 0): ?>
                                                <span class="badge bg-danger">Overdue (<?php echo $issue['days_overdue']; ?> days)</span>
                                            <?php else: ?>
                                                <span class="badge bg-success">Active</span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Returned</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center">No issues found. Start browsing books!</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="mt-3">
            <a href="browse.php" class="btn btn-primary">Browse Books</a>
        </div>
    </div>
    
    <footer class="bg-light text-center text-muted py-3 mt-5">
        <p>&copy; 2025 Library Management System. All rights reserved.</p>
    </footer>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
require_once '../includes/auth.php';

$page_title = 'Dashboard';

// Get statistics
$total_books = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM books"))['count'];
$available_books = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(quantity_available) as count FROM books"))['count'];
$total_members = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM members"))['count'];
$active_issues = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM issue_books WHERE status='issued'"))['count'];

// Get latest activities (only book-related: issue, return, add)
$activities = mysqli_query($conn, "SELECT a.action, a.created_at, 
                                   COALESCE(ad.name, ad.username) as admin_name
                                   FROM activity_log a 
                                   LEFT JOIN admins ad ON a.admin_id = ad.admin_id 
                                   WHERE a.action NOT LIKE '%logged in%' 
                                   AND a.action NOT LIKE '%logged out%'
                                   AND a.action NOT LIKE '%login%'
                                   AND a.action NOT LIKE '%logout%'
                                   ORDER BY a.created_at DESC LIMIT 10");

include '../includes/header.php';
?>

<h2>Dashboard</h2>
<?php display_flash('success'); ?>

<div class="row mt-4">
    <div class="col-md-3">
        <div class="card stat-card">
            <div class="card-body">
                <h5 class="card-title">Total Books</h5>
                <h2><?php echo $total_books; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card">
            <div class="card-body">
                <h5 class="card-title">Available Books</h5>
                <h2><?php echo $available_books; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card">
            <div class="card-body">
                <h5 class="card-title">Total Members</h5>
                <h2><?php echo $total_members; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card">
            <div class="card-body">
                <h5 class="card-title">Active Issues</h5>
                <h2><?php echo $active_issues; ?></h2>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5>Quick Actions</h5>
            </div>
            <div class="card-body">
                <a href="books_add.php" class="btn btn-primary">Add Book</a>
                <a href="members_add.php" class="btn btn-success">Add Member</a>
                <a href="issue_book.php" class="btn btn-warning">Issue Book</a>
                <a href="return_book.php" class="btn btn-info">Return Book</a>
                <a href="authors_add.php" class="btn btn-secondary">Add Author</a>
                <a href="categories_add.php" class="btn btn-secondary">Add Category</a>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5>Recent Activities</h5>
            </div>
            <div class="card-body">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Action</th>
                            <th>Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($activity = mysqli_fetch_assoc($activities)): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($activity['admin_name'] ?? 'System'); ?></td>
                            <td><?php echo htmlspecialchars($activity['action']); ?></td>
                            <td><?php echo htmlspecialchars($activity['created_at']); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

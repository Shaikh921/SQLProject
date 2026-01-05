<?php
require_once '../includes/auth.php';

$page_title = 'Return Book';
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['issue_id'])) {
    $issue_id = intval($_POST['issue_id']);
    
    // Get issue details
    $stmt = mysqli_prepare($conn, "SELECT due_date, book_id FROM issue_books WHERE issue_id = ? AND status = 'issued'");
    mysqli_stmt_bind_param($stmt, "i", $issue_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($issue = mysqli_fetch_assoc($result)) {
        $return_date = date('Y-m-d');
        
        // Calculate fine using function
        $fine_query = mysqli_query($conn, "SELECT fn_compute_fine('{$issue['due_date']}', '$return_date') as fine");
        $fine_row = mysqli_fetch_assoc($fine_query);
        $fine_amount = $fine_row['fine'];
        
        // Update issue record
        $update_stmt = mysqli_prepare($conn, "UPDATE issue_books SET return_date = ?, status = 'returned', fine_amount = ? WHERE issue_id = ?");
        mysqli_stmt_bind_param($update_stmt, "sdi", $return_date, $fine_amount, $issue_id);
        
        if (mysqli_stmt_execute($update_stmt)) {
            // Get book and member details for better activity log
            $details_query = mysqli_query($conn, "SELECT b.title, m.name FROM issue_books i 
                                                   JOIN books b ON i.book_id = b.book_id 
                                                   JOIN members m ON i.member_id = m.member_id 
                                                   WHERE i.issue_id = $issue_id");
            $details = mysqli_fetch_assoc($details_query);
            
            log_activity($conn, $_SESSION['admin_id'], "Returned: '{$details['title']}' from {$details['name']}");
            $success = "Book returned successfully. Fine: ₹" . number_format($fine_amount, 2);
        } else {
            $error = 'Failed to return book';
        }
        mysqli_stmt_close($update_stmt);
    } else {
        $error = 'Invalid issue ID or book already returned';
    }
    mysqli_stmt_close($stmt);
}

// Get currently issued books
$issued_books = mysqli_query($conn, "SELECT i.issue_id, i.book_id, b.title, i.member_id, m.name as member_name, 
                                      i.issue_date, i.due_date, 
                                      DATEDIFF(CURRENT_DATE, i.due_date) as days_overdue
                                      FROM issue_books i
                                      JOIN books b ON i.book_id = b.book_id
                                      JOIN members m ON i.member_id = m.member_id
                                      WHERE i.status = 'issued'
                                      ORDER BY i.due_date");

include '../includes/header.php';
?>

<h2>Return Book</h2>

<?php if (!empty($success)): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h5>Currently Issued Books</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Issue ID</th>
                        <th>Book Title</th>
                        <th>Member</th>
                        <th>Issue Date</th>
                        <th>Due Date</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($issue = mysqli_fetch_assoc($issued_books)): ?>
                    <tr class="<?php echo $issue['days_overdue'] > 0 ? 'table-danger' : ''; ?>">
                        <td><?php echo $issue['issue_id']; ?></td>
                        <td><?php echo htmlspecialchars($issue['title']); ?></td>
                        <td><?php echo htmlspecialchars($issue['member_name']); ?></td>
                        <td><?php echo htmlspecialchars($issue['issue_date']); ?></td>
                        <td><?php echo htmlspecialchars($issue['due_date']); ?></td>
                        <td>
                            <?php if ($issue['days_overdue'] > 0): ?>
                                <span class="badge bg-danger">Overdue (<?php echo $issue['days_overdue']; ?> days)</span>
                            <?php else: ?>
                                <span class="badge bg-success">On Time</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="issue_id" value="<?php echo $issue['issue_id']; ?>">
                                <button type="submit" class="btn btn-sm btn-primary">Return</button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="mt-3">
    <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
</div>

<?php include '../includes/footer.php'; ?>

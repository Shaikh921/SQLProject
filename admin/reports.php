<?php
require_once '../includes/auth.php';

$page_title = 'Reports';

// Overdue books
$overdue_books = mysqli_query($conn, "SELECT i.issue_id, b.title, m.name as member_name, i.issue_date, i.due_date,
                                       DATEDIFF(CURRENT_DATE, i.due_date) as days_overdue
                                       FROM issue_books i
                                       JOIN books b ON i.book_id = b.book_id
                                       JOIN members m ON i.member_id = m.member_id
                                       WHERE i.status = 'issued' AND i.due_date < CURRENT_DATE
                                       ORDER BY days_overdue DESC");

// Most issued books
$most_issued = mysqli_query($conn, "SELECT b.title, a.author_name, COUNT(i.issue_id) as issue_count
                                     FROM issue_books i
                                     JOIN books b ON i.book_id = b.book_id
                                     JOIN authors a ON b.author_id = a.author_id
                                     GROUP BY b.book_id
                                     ORDER BY issue_count DESC
                                     LIMIT 10");

// Member search
$member_history = null;
$member_name = '';
if (isset($_GET['member_id']) && !empty($_GET['member_id'])) {
    $member_id = intval($_GET['member_id']);
    $member_history = mysqli_query($conn, "SELECT i.*, b.title, i.issue_date, i.due_date, i.return_date, i.fine_amount, i.status
                                            FROM issue_books i
                                            JOIN books b ON i.book_id = b.book_id
                                            WHERE i.member_id = $member_id
                                            ORDER BY i.issue_date DESC");
    $member_result = mysqli_query($conn, "SELECT name FROM members WHERE member_id = $member_id");
    if ($member_row = mysqli_fetch_assoc($member_result)) {
        $member_name = $member_row['name'];
    }
}

// Get all members for dropdown
$members = mysqli_query($conn, "SELECT member_id, name, email FROM members ORDER BY name");

include '../includes/header.php';
?>

<h2>Reports</h2>

<div class="row">
    <div class="col-md-12">
        <div class="card mb-4">
            <div class="card-header bg-danger text-white">
                <h5>Overdue Books</h5>
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
                                <th>Days Overdue</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($overdue = mysqli_fetch_assoc($overdue_books)): ?>
                            <tr>
                                <td><?php echo $overdue['issue_id']; ?></td>
                                <td><?php echo htmlspecialchars($overdue['title']); ?></td>
                                <td><?php echo htmlspecialchars($overdue['member_name']); ?></td>
                                <td><?php echo htmlspecialchars($overdue['issue_date']); ?></td>
                                <td><?php echo htmlspecialchars($overdue['due_date']); ?></td>
                                <td><span class="badge bg-danger"><?php echo $overdue['days_overdue']; ?> days</span></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card mb-4">
            <div class="card-header bg-success text-white">
                <h5>Top 10 Most Issued Books</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Rank</th>
                                <th>Book Title</th>
                                <th>Author</th>
                                <th>Times Issued</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $rank = 1;
                            while ($book = mysqli_fetch_assoc($most_issued)): 
                            ?>
                            <tr>
                                <td><?php echo $rank++; ?></td>
                                <td><?php echo htmlspecialchars($book['title']); ?></td>
                                <td><?php echo htmlspecialchars($book['author_name']); ?></td>
                                <td><span class="badge bg-primary"><?php echo $book['issue_count']; ?></span></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                <h5>Member Issue History</h5>
            </div>
            <div class="card-body">
                <form method="GET" class="mb-3">
                    <div class="row">
                        <div class="col-md-8">
                            <select class="form-select" name="member_id" required>
                                <option value="">Select Member</option>
                                <?php while ($member = mysqli_fetch_assoc($members)): ?>
                                    <option value="<?php echo $member['member_id']; ?>" <?php echo (isset($_GET['member_id']) && $_GET['member_id'] == $member['member_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($member['name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-primary">View History</button>
                        </div>
                    </div>
                </form>
                
                <?php if ($member_history): ?>
                    <h6>History for: <?php echo htmlspecialchars($member_name); ?></h6>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Book Title</th>
                                    <th>Issue Date</th>
                                    <th>Due Date</th>
                                    <th>Return Date</th>
                                    <th>Fine</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($history = mysqli_fetch_assoc($member_history)): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($history['title']); ?></td>
                                    <td><?php echo htmlspecialchars($history['issue_date']); ?></td>
                                    <td><?php echo htmlspecialchars($history['due_date']); ?></td>
                                    <td><?php echo $history['return_date'] ? htmlspecialchars($history['return_date']) : '-'; ?></td>
                                    <td>₹<?php echo number_format($history['fine_amount'], 2); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $history['status'] == 'returned' ? 'success' : 'warning'; ?>">
                                            <?php echo ucfirst($history['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="mt-3">
    <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
</div>

<?php include '../includes/footer.php'; ?>

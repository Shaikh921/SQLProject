<?php
require_once '../includes/auth.php';

$page_title = 'Issue Book';
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $book_id = intval($_POST['book_id']);
    $member_id = intval($_POST['member_id']);
    $days = intval($_POST['days']);
    
    if ($book_id <= 0 || $member_id <= 0 || $days <= 0) {
        $error = 'Please fill all fields correctly';
    } else {
        // Start transaction
        mysqli_begin_transaction($conn);
        
        try {
            // Check if book is available
            $check_stmt = mysqli_prepare($conn, "SELECT quantity_available FROM books WHERE book_id = ? FOR UPDATE");
            mysqli_stmt_bind_param($check_stmt, "i", $book_id);
            mysqli_stmt_execute($check_stmt);
            $check_result = mysqli_stmt_get_result($check_stmt);
            $book = mysqli_fetch_assoc($check_result);
            mysqli_stmt_close($check_stmt);
            
            if (!$book || $book['quantity_available'] <= 0) {
                throw new Exception('Book not available');
            }
            
            // Update book quantity
            $update_stmt = mysqli_prepare($conn, "UPDATE books SET quantity_available = quantity_available - 1 WHERE book_id = ?");
            mysqli_stmt_bind_param($update_stmt, "i", $book_id);
            mysqli_stmt_execute($update_stmt);
            mysqli_stmt_close($update_stmt);
            
            // Calculate due date
            $issue_date = date('Y-m-d');
            $due_date = date('Y-m-d', strtotime("+$days days"));
            
            // Insert issue record
            $insert_stmt = mysqli_prepare($conn, "INSERT INTO issue_books (book_id, member_id, issue_date, due_date, status) VALUES (?, ?, ?, ?, 'issued')");
            mysqli_stmt_bind_param($insert_stmt, "iiss", $book_id, $member_id, $issue_date, $due_date);
            mysqli_stmt_execute($insert_stmt);
            mysqli_stmt_close($insert_stmt);
            
            // Commit transaction
            mysqli_commit($conn);
            
            // Get book and member details for better activity log
            $book_query = mysqli_query($conn, "SELECT title FROM books WHERE book_id = $book_id");
            $book_data = mysqli_fetch_assoc($book_query);
            $member_query = mysqli_query($conn, "SELECT name FROM members WHERE member_id = $member_id");
            $member_data = mysqli_fetch_assoc($member_query);
            
            log_activity($conn, $_SESSION['admin_id'], "Issued: '{$book_data['title']}' to {$member_data['name']}");
            $success = 'Book issued successfully';
            
        } catch (Exception $e) {
            // Rollback on error
            mysqli_rollback($conn);
            $error = 'Failed to issue book: ' . $e->getMessage();
        }
    }
}

// Get available books
$books = mysqli_query($conn, "SELECT b.book_id, b.title, a.author_name, b.quantity_available 
                               FROM books b 
                               JOIN authors a ON b.author_id = a.author_id 
                               WHERE b.quantity_available > 0 
                               ORDER BY b.title");

// Get members
$members = mysqli_query($conn, "SELECT member_id, name, email FROM members ORDER BY name");

include '../includes/header.php';
?>

<h2>Issue Book</h2>

<?php if (!empty($success)): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <form method="POST" class="needs-validation" novalidate>
            <div class="mb-3">
                <label for="member_id" class="form-label">Select Member *</label>
                <select class="form-select" id="member_id" name="member_id" required>
                    <option value="">Choose member...</option>
                    <?php while ($member = mysqli_fetch_assoc($members)): ?>
                        <option value="<?php echo $member['member_id']; ?>">
                            <?php echo htmlspecialchars($member['name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div class="mb-3">
                <label for="book_id" class="form-label">Select Book *</label>
                <select class="form-select" id="book_id" name="book_id" required>
                    <option value="">Choose book...</option>
                    <?php while ($book = mysqli_fetch_assoc($books)): ?>
                        <option value="<?php echo $book['book_id']; ?>">
                            <?php echo htmlspecialchars($book['title']) . ' by ' . htmlspecialchars($book['author_name']) . ' (Available: ' . $book['quantity_available'] . ')'; ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div class="mb-3">
                <label for="days" class="form-label">Days to Borrow *</label>
                <input type="number" class="form-control" id="days" name="days" value="14" min="1" max="90" required>
                <small class="form-text text-muted">Default: 14 days</small>
            </div>
            
            <button type="submit" class="btn btn-primary">Issue Book</button>
            <a href="dashboard.php" class="btn btn-secondary">Back</a>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

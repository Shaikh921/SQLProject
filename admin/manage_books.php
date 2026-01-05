<?php
require_once '../includes/auth.php';

$page_title = 'Manage Books';

// Search functionality
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$where = '';
if (!empty($search)) {
    $search_param = "%$search%";
    $where = "WHERE b.title LIKE ? OR a.author_name LIKE ? OR c.category_name LIKE ?";
}

// Get books with pagination
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

$query = "SELECT b.*, a.author_name, c.category_name 
          FROM books b 
          JOIN authors a ON b.author_id = a.author_id 
          JOIN book_categories c ON b.category_id = c.category_id 
          $where
          ORDER BY b.title 
          LIMIT $per_page OFFSET $offset";

if (!empty($search)) {
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "sss", $search_param, $search_param, $search_param);
    mysqli_stmt_execute($stmt);
    $books = mysqli_stmt_get_result($stmt);
} else {
    $books = mysqli_query($conn, $query);
}

// Get total count
$count_query = "SELECT COUNT(*) as total FROM books b $where";
if (!empty($search)) {
    $stmt_count = mysqli_prepare($conn, $count_query);
    mysqli_stmt_bind_param($stmt_count, "sss", $search_param, $search_param, $search_param);
    mysqli_stmt_execute($stmt_count);
    $total_result = mysqli_stmt_get_result($stmt_count);
    $total = mysqli_fetch_assoc($total_result)['total'];
} else {
    $total = mysqli_fetch_assoc(mysqli_query($conn, $count_query))['total'];
}

$total_pages = ceil($total / $per_page);

include '../includes/header.php';
?>

<h2>Manage Books</h2>

<div class="card">
    <div class="card-body">
        <form method="GET" class="mb-3">
            <div class="input-group">
                <input type="text" class="form-control" name="search" placeholder="Search by title, author, or category" value="<?php echo htmlspecialchars($search); ?>">
                <button class="btn btn-primary" type="submit">Search</button>
                <?php if (!empty($search)): ?>
                    <a href="manage_books.php" class="btn btn-secondary">Clear</a>
                <?php endif; ?>
            </div>
        </form>
        
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Author</th>
                        <th>Category</th>
                        <th>ISBN</th>
                        <th>Total</th>
                        <th>Available</th>
                        <th>Shelf</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($book = mysqli_fetch_assoc($books)): ?>
                    <tr>
                        <td><?php echo $book['book_id']; ?></td>
                        <td><?php echo htmlspecialchars($book['title']); ?></td>
                        <td><?php echo htmlspecialchars($book['author_name']); ?></td>
                        <td><?php echo htmlspecialchars($book['category_name']); ?></td>
                        <td><?php echo htmlspecialchars($book['isbn']); ?></td>
                        <td><?php echo $book['quantity_total']; ?></td>
                        <td><?php echo $book['quantity_available']; ?></td>
                        <td><?php echo htmlspecialchars($book['shelf_location']); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        
        <?php if ($total_pages > 1): ?>
        <nav>
            <ul class="pagination">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
        <?php endif; ?>
    </div>
</div>

<div class="mt-3">
    <a href="books_add.php" class="btn btn-primary">Add New Book</a>
    <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
</div>

<?php include '../includes/footer.php'; ?>

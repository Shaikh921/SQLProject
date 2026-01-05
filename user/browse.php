<?php
require_once '../includes/config.php';
require_once '../includes/helpers.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) && !isset($_SESSION['admin_id'])) {
    redirect('../public/login.php');
}

$page_title = 'Browse Books';

// Search functionality
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$category_filter = isset($_GET['category']) ? intval($_GET['category']) : 0;

$where_conditions = [];
$params = [];
$types = '';

if (!empty($search)) {
    $where_conditions[] = "(b.title LIKE ? OR a.author_name LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'ss';
}

if ($category_filter > 0) {
    $where_conditions[] = "b.category_id = ?";
    $params[] = $category_filter;
    $types .= 'i';
}

$where = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Pagination
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

if (!empty($params)) {
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $books = mysqli_stmt_get_result($stmt);
} else {
    $books = mysqli_query($conn, $query);
}

// Get categories for filter
$categories = mysqli_query($conn, "SELECT * FROM book_categories ORDER BY category_name");

// Get total count
$count_query = "SELECT COUNT(*) as total FROM books b $where";
if (!empty($params)) {
    $stmt_count = mysqli_prepare($conn, $count_query);
    mysqli_stmt_bind_param($stmt_count, $types, ...$params);
    mysqli_stmt_execute($stmt_count);
    $total_result = mysqli_stmt_get_result($stmt_count);
    $total = mysqli_fetch_assoc($total_result)['total'];
} else {
    $total = mysqli_fetch_assoc(mysqli_query($conn, $count_query))['total'];
}

$total_pages = ceil($total / $per_page);
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
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a class="nav-link" href="dashboard.php">Dashboard</a>
                    <span class="nav-link">Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                    <a class="nav-link" href="my_issues.php">My Issues</a>
                    <a class="nav-link" href="logout.php">Logout</a>
                <?php elseif (isset($_SESSION['admin_id'])): ?>
                    <a class="nav-link" href="../admin/dashboard.php">Admin Panel</a>
                    <a class="nav-link" href="../admin/logout.php">Logout</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>
    
    <div class="container mt-4">
        <h2>Browse Books</h2>
        
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET">
                    <div class="row">
                        <div class="col-md-6">
                            <input type="text" class="form-control" name="search" placeholder="Search by title or author" value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="col-md-4">
                            <select class="form-select" name="category">
                                <option value="0">All Categories</option>
                                <?php while ($cat = mysqli_fetch_assoc($categories)): ?>
                                    <option value="<?php echo $cat['category_id']; ?>" <?php echo $category_filter == $cat['category_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['category_name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">Search</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="row">
            <?php while ($book = mysqli_fetch_assoc($books)): ?>
            <div class="col-md-6 mb-3">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($book['title']); ?></h5>
                        <p class="card-text">
                            <strong>Author:</strong> <?php echo htmlspecialchars($book['author_name']); ?><br>
                            <strong>Category:</strong> <?php echo htmlspecialchars($book['category_name']); ?><br>
                            <strong>Publisher:</strong> <?php echo htmlspecialchars($book['publisher']); ?><br>
                            <strong>Year:</strong> <?php echo htmlspecialchars($book['publication_year']); ?><br>
                            <strong>ISBN:</strong> <?php echo htmlspecialchars($book['isbn']); ?><br>
                            <strong>Shelf:</strong> <?php echo htmlspecialchars($book['shelf_location']); ?><br>
                            <strong>Available:</strong> 
                            <?php if ($book['quantity_available'] > 0): ?>
                                <span class="badge bg-success"><?php echo $book['quantity_available']; ?> copies</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Not Available</span>
                            <?php endif; ?>
                        </p>
                        <a href="book_detail.php?id=<?php echo $book['book_id']; ?>" class="btn btn-sm btn-primary">View Details</a>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
        
        <?php if ($total_pages > 1): ?>
        <nav>
            <ul class="pagination justify-content-center">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo $category_filter > 0 ? '&category=' . $category_filter : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
        <?php endif; ?>
    </div>
    
    <footer class="bg-light text-center text-muted py-3 mt-5">
        <p>&copy; 2025 Library Management System. All rights reserved.</p>
    </footer>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

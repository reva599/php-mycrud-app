<?php
/**
 * Final Project - Main Index Page
 * Integrated homepage with search, pagination, and all features
 */

require_once 'config/database.php';

// Get search parameters
$search = sanitizeInput($_GET['search'] ?? '');
$category = sanitizeInput($_GET['category'] ?? '');
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = POSTS_PER_PAGE;
$offset = ($page - 1) * $per_page;

// Build search query
$where_conditions = ["p.status = 'published'"];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(p.title LIKE ? OR p.content LIKE ? OR p.excerpt LIKE ?)";
    $search_term = "%$search%";
    $params = array_merge($params, [$search_term, $search_term, $search_term]);
}

if (!empty($category)) {
    $where_conditions[] = "c.slug = ?";
    $params[] = $category;
}

$where_clause = implode(' AND ', $where_conditions);

// Get total count for pagination
$count_sql = "SELECT COUNT(DISTINCT p.id) as total 
              FROM posts p 
              LEFT JOIN post_categories pc ON p.id = pc.post_id 
              LEFT JOIN categories c ON pc.category_id = c.id 
              WHERE $where_clause";
$total_posts = fetchOne($count_sql, $params)['total'] ?? 0;
$total_pages = ceil($total_posts / $per_page);

// Get posts with pagination
$posts_sql = "SELECT DISTINCT p.*, u.username, u.first_name, u.last_name,
                     GROUP_CONCAT(cat.name) as categories,
                     GROUP_CONCAT(cat.slug) as category_slugs,
                     GROUP_CONCAT(cat.color) as category_colors
              FROM posts p 
              LEFT JOIN users u ON p.author_id = u.id
              LEFT JOIN post_categories pc ON p.id = pc.post_id 
              LEFT JOIN categories cat ON pc.category_id = cat.id
              LEFT JOIN categories c ON pc.category_id = c.id 
              WHERE $where_clause
              GROUP BY p.id
              ORDER BY p.is_featured DESC, p.published_at DESC 
              LIMIT $per_page OFFSET $offset";

$posts = fetchAll($posts_sql, $params);

// Get all categories for filter
$categories = fetchAll("SELECT * FROM categories ORDER BY name");

function truncateText($text, $length = 150) {
    return strlen($text) > $length ? substr($text, 0, $length) . '...' : $text;
}

function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    if ($time < 60) return 'just now';
    if ($time < 3600) return floor($time/60) . ' minutes ago';
    if ($time < 86400) return floor($time/3600) . ' hours ago';
    if ($time < 2592000) return floor($time/86400) . ' days ago';
    return date('M j, Y', strtotime($datetime));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Final Blog Application - Comprehensive PHP & MySQL Blog</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .hero-section {
            background: var(--primary-gradient);
            color: white;
            padding: 4rem 0;
        }
        
        .search-box {
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            backdrop-filter: blur(10px);
        }
        
        .post-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: none;
            border-radius: 15px;
            overflow: hidden;
        }
        
        .post-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        
        .btn-gradient {
            background: var(--primary-gradient);
            border: none;
            color: white;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark" style="background: var(--primary-gradient);">
        <div class="container">
            <a class="navbar-brand fw-bold" href="final_index.php">
                <i class="fas fa-blog me-2"></i>Final Blog
            </a>
            
            <div class="navbar-nav ms-auto">
                <?php if (isLoggedIn()): ?>
                    <a class="nav-link" href="dashboard.php">
                        <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                    </a>
                    <a class="nav-link" href="auth/logout.php">
                        <i class="fas fa-sign-out-alt me-1"></i>Logout
                    </a>
                <?php else: ?>
                    <a class="nav-link" href="auth/final_login.php">
                        <i class="fas fa-sign-in-alt me-1"></i>Login
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container text-center">
            <h1 class="display-4 fw-bold mb-4">Final Blog Application</h1>
            <p class="lead mb-4">Comprehensive PHP & MySQL blog with advanced features</p>
            
            <!-- Search Form -->
            <form method="GET" class="row justify-content-center">
                <div class="col-md-6">
                    <div class="input-group input-group-lg">
                        <input type="text" name="search" class="form-control search-box" 
                               placeholder="Search posts..." value="<?= htmlspecialchars($search) ?>">
                        <button class="btn btn-light" type="submit">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
            </form>
            
            <?php if ($search): ?>
                <div class="mt-3">
                    <span class="badge bg-light text-dark">
                        <?= $total_posts ?> result(s) for "<?= htmlspecialchars($search) ?>"
                    </span>
                    <a href="final_index.php" class="badge bg-secondary text-decoration-none ms-2">Clear</a>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Main Content -->
    <div class="container my-5">
        <?php if (empty($posts)): ?>
            <div class="text-center py-5">
                <i class="fas fa-search fa-3x text-muted mb-3"></i>
                <h3 class="text-muted">No posts found</h3>
                <p class="text-muted">Try adjusting your search criteria.</p>
                <a href="final_index.php" class="btn btn-gradient">View All Posts</a>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($posts as $post): ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card post-card h-100">
                            <div class="card-body">
                                <?php if ($post['is_featured']): ?>
                                    <span class="badge bg-warning text-dark mb-2">
                                        <i class="fas fa-star me-1"></i>Featured
                                    </span>
                                <?php endif; ?>
                                
                                <h5 class="card-title">
                                    <a href="posts/view.php?id=<?= $post['id'] ?>" 
                                       class="text-decoration-none text-dark">
                                        <?= htmlspecialchars($post['title']) ?>
                                    </a>
                                </h5>
                                
                                <p class="card-text text-muted">
                                    <?= truncateText(htmlspecialchars($post['excerpt'] ?: strip_tags($post['content']))) ?>
                                </p>
                                
                                <div class="d-flex justify-content-between align-items-center mt-auto">
                                    <small class="text-muted">
                                        <i class="fas fa-user me-1"></i>
                                        <?= htmlspecialchars($post['first_name'] . ' ' . $post['last_name']) ?>
                                    </small>
                                    <small class="text-muted">
                                        <?= timeAgo($post['published_at']) ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <nav aria-label="Posts pagination" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $page - 1 ?><?= $search ? '&search=' . urlencode($search) : '' ?>">
                                    Previous
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?><?= $search ? '&search=' . urlencode($search) : '' ?>">
                                    <?= $i ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $page + 1 ?><?= $search ? '&search=' . urlencode($search) : '' ?>">
                                    Next
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container text-center">
            <h5><i class="fas fa-blog me-2"></i>Final Blog Application</h5>
            <p class="mb-0">Comprehensive PHP & MySQL blog with enterprise-grade security</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

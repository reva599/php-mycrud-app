<?php

/**
 * Home Page - Blog Posts Display
 * PHP CRUD Blog Application - ApexPlanet Internship
 */

require_once 'includes/auth.php';
require_once 'config/db.php';

$page_title = "Home";

// Get all published posts with author information
try {
    $sql = "SELECT p.id, p.title, p.content, p.created_at, p.updated_at,
                   u.username, u.first_name, u.last_name,
                   CONCAT(u.first_name, ' ', u.last_name) as author_name
            FROM posts p
            JOIN users u ON p.author_id = u.id
            WHERE p.is_published = 1
            ORDER BY p.created_at DESC";

    $posts = getMultipleResults($conn, $sql);
} catch (Exception $e) {
    error_log("Error fetching posts: " . $e->getMessage());
    $posts = [];
}

// Function to truncate content for preview
function truncateContent($content, $length = 200)
{
    if (strlen($content) <= $length) {
        return $content;
    }
    return substr($content, 0, $length) . '...';
}

// Function to format date
function formatDate($date)
{
    return date('F j, Y \a\t g:i A', strtotime($date));
}

include 'includes/header.php';
?>

<div class="container">
    <!-- Hero Section -->
    <div class="hero-section text-center mb-4">
        <h1><i class="fas fa-blog"></i> Welcome to PHP CRUD Blog</h1>
        <p class="lead">Discover amazing articles and share your thoughts with our community</p>

        <?php if (!isLoggedIn()): ?>
            <div class="hero-actions mt-4">
                <a href="auth/register.php" class="btn btn-primary btn-lg">
                    <i class="fas fa-user-plus"></i> Join Our Community
                </a>
                <a href="auth/login.php" class="btn btn-outline btn-lg">
                    <i class="fas fa-sign-in-alt"></i> Sign In
                </a>
            </div>
        <?php else: ?>
            <div class="hero-actions mt-4">
                <a href="posts/create.php" class="btn btn-primary btn-lg">
                    <i class="fas fa-plus"></i> Write New Post
                </a>
                <a href="dashboard.php" class="btn btn-outline btn-lg">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Posts Section -->
    <div class="posts-section">
        <div class="section-header">
            <h2><i class="fas fa-newspaper"></i> Latest Posts</h2>
            <p>Explore our latest blog posts from the community</p>
        </div>

        <?php if (empty($posts)): ?>
            <div class="empty-state">
                <div class="card">
                    <div class="card-body text-center">
                        <i class="fas fa-file-alt" style="font-size: 3rem; color: var(--gray-400); margin-bottom: 1rem;"></i>
                        <h3>No Posts Yet</h3>
                        <p>Be the first to share your thoughts with the community!</p>
                        <?php if (isLoggedIn()): ?>
                            <a href="posts/create.php" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Create First Post
                            </a>
                        <?php else: ?>
                            <a href="auth/register.php" class="btn btn-primary">
                                <i class="fas fa-user-plus"></i> Join to Post
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="post-grid">
                <?php foreach ($posts as $post): ?>
                    <article class="post-card">
                        <div class="post-header">
                            <h3 class="post-title">
                                <a href="posts/view.php?id=<?php echo $post['id']; ?>">
                                    <?php echo htmlspecialchars($post['title']); ?>
                                </a>
                            </h3>
                            <div class="post-meta">
                                <span>
                                    <i class="fas fa-user"></i>
                                    <?php echo htmlspecialchars($post['author_name']); ?>
                                </span>
                                <span>
                                    <i class="fas fa-calendar"></i>
                                    <?php echo formatDate($post['created_at']); ?>
                                </span>
                            </div>
                        </div>

                        <div class="post-content">
                            <p><?php echo nl2br(htmlspecialchars(truncateContent($post['content']))); ?></p>
                        </div>

                        <div class="post-actions">
                            <a href="posts/view.php?id=<?php echo $post['id']; ?>" class="btn btn-primary btn-sm">
                                <i class="fas fa-eye"></i> Read More
                            </a>

                            <?php if (isLoggedIn() && $_SESSION['user_id'] == $post['author_id']): ?>
                                <a href="posts/edit.php?id=<?php echo $post['id']; ?>" class="btn btn-secondary btn-sm">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <!-- Pagination would go here in a real application -->
            <div class="pagination-section text-center mt-4">
                <p class="text-muted">
                    Showing <?php echo count($posts); ?> post<?php echo count($posts) !== 1 ? 's' : ''; ?>
                </p>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
    .hero-section {
        background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
        color: var(--white);
        padding: var(--spacing-12) var(--spacing-6);
        border-radius: var(--radius-2xl);
        margin-bottom: var(--spacing-8);
    }

    .hero-section .lead {
        font-size: var(--font-size-lg);
        margin-bottom: var(--spacing-6);
        opacity: 0.9;
    }

    .hero-actions {
        display: flex;
        gap: var(--spacing-4);
        justify-content: center;
        flex-wrap: wrap;
    }

    .section-header {
        text-align: center;
        margin-bottom: var(--spacing-8);
    }

    .section-header h2 {
        color: var(--gray-900);
        margin-bottom: var(--spacing-2);
    }

    .section-header p {
        color: var(--gray-600);
        font-size: var(--font-size-lg);
    }

    .empty-state {
        max-width: 500px;
        margin: 0 auto;
    }

    .post-title a {
        color: var(--gray-900);
        text-decoration: none;
        transition: color var(--transition-fast);
    }

    .post-title a:hover {
        color: var(--primary-color);
    }

    .pagination-section {
        margin-top: var(--spacing-8);
        padding-top: var(--spacing-6);
        border-top: 1px solid var(--gray-200);
    }

    @media (max-width: 768px) {
        .hero-section {
            padding: var(--spacing-8) var(--spacing-4);
        }

        .hero-actions {
            flex-direction: column;
            align-items: center;
        }

        .hero-actions .btn {
            width: 100%;
            max-width: 300px;
        }

        .post-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<?php include 'includes/footer.php'; ?>
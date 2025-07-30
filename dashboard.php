<?php
/**
 * User Dashboard Page
 * PHP CRUD Blog Application - ApexPlanet Internship
 */

require_once 'includes/auth.php';
require_once 'config/db.php';

// Require authentication
requireAuth();

$page_title = "Dashboard";

// Get user's posts
try {
    $sql = "SELECT id, title, content, created_at, updated_at, is_published 
            FROM posts 
            WHERE author_id = ? 
            ORDER BY created_at DESC";
    
    $userPosts = getMultipleResults($conn, $sql, "i", [$_SESSION['user_id']]);
} catch (Exception $e) {
    error_log("Error fetching user posts: " . $e->getMessage());
    $userPosts = [];
}

// Get user statistics
$totalPosts = count($userPosts);
$publishedPosts = count(array_filter($userPosts, function($post) { return $post['is_published']; }));
$draftPosts = $totalPosts - $publishedPosts;

// Function to truncate content
function truncateContent($content, $length = 100) {
    if (strlen($content) <= $length) {
        return $content;
    }
    return substr($content, 0, $length) . '...';
}

// Function to format date
function formatDate($date) {
    return date('M j, Y', strtotime($date));
}

include 'includes/header.php';
?>

<div class="container">
    <!-- Dashboard Header -->
    <div class="dashboard-header">
        <div class="welcome-section">
            <h1><i class="fas fa-tachometer-alt"></i> Welcome back, <?php echo htmlspecialchars($_SESSION['first_name']); ?>!</h1>
            <p>Manage your blog posts and track your writing progress</p>
        </div>
        
        <div class="quick-actions">
            <a href="posts/create.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> New Post
            </a>
            <a href="index.php" class="btn btn-outline">
                <i class="fas fa-home"></i> View Blog
            </a>
        </div>
    </div>
    
    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-file-alt"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo $totalPosts; ?></h3>
                <p>Total Posts</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon published">
                <i class="fas fa-eye"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo $publishedPosts; ?></h3>
                <p>Published</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon draft">
                <i class="fas fa-eye-slash"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo $draftPosts; ?></h3>
                <p>Drafts</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-calendar"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo date('M j'); ?></h3>
                <p>Today</p>
            </div>
        </div>
    </div>
    
    <!-- Posts Management -->
    <div class="posts-management">
        <div class="section-header">
            <h2><i class="fas fa-list"></i> Your Posts</h2>
            <div class="section-actions">
                <a href="posts/create.php" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus"></i> Create New
                </a>
            </div>
        </div>
        
        <?php if (empty($userPosts)): ?>
            <div class="empty-state">
                <div class="card">
                    <div class="card-body text-center">
                        <i class="fas fa-file-alt" style="font-size: 3rem; color: var(--gray-400); margin-bottom: 1rem;"></i>
                        <h3>No Posts Yet</h3>
                        <p>Start your blogging journey by creating your first post!</p>
                        <a href="posts/create.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Create Your First Post
                        </a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="posts-table-container">
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Updated</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($userPosts as $post): ?>
                                <tr>
                                    <td>
                                        <div class="post-title-cell">
                                            <strong><?php echo htmlspecialchars($post['title']); ?></strong>
                                            <small class="post-preview">
                                                <?php echo htmlspecialchars(truncateContent($post['content'])); ?>
                                            </small>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="status-badge <?php echo $post['is_published'] ? 'published' : 'draft'; ?>">
                                            <?php if ($post['is_published']): ?>
                                                <i class="fas fa-eye"></i> Published
                                            <?php else: ?>
                                                <i class="fas fa-eye-slash"></i> Draft
                                            <?php endif; ?>
                                        </span>
                                    </td>
                                    <td><?php echo formatDate($post['created_at']); ?></td>
                                    <td>
                                        <?php if ($post['updated_at'] !== $post['created_at']): ?>
                                            <?php echo formatDate($post['updated_at']); ?>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="posts/view.php?id=<?php echo $post['id']; ?>" 
                                               class="btn btn-sm btn-outline" 
                                               title="View Post">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="posts/edit.php?id=<?php echo $post['id']; ?>" 
                                               class="btn btn-sm btn-primary" 
                                               title="Edit Post">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button onclick="deletePost(<?php echo $post['id']; ?>)" 
                                                    class="btn btn-sm btn-danger" 
                                                    title="Delete Post">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Recent Activity -->
    <div class="recent-activity">
        <h3><i class="fas fa-clock"></i> Recent Activity</h3>
        <div class="activity-list">
            <?php
            $recentPosts = array_slice($userPosts, 0, 3);
            if (!empty($recentPosts)):
                foreach ($recentPosts as $post):
            ?>
                    <div class="activity-item">
                        <div class="activity-icon">
                            <i class="fas fa-<?php echo $post['is_published'] ? 'eye' : 'eye-slash'; ?>"></i>
                        </div>
                        <div class="activity-content">
                            <p>
                                <strong><?php echo $post['is_published'] ? 'Published' : 'Created draft'; ?></strong>
                                <a href="posts/view.php?id=<?php echo $post['id']; ?>">
                                    "<?php echo htmlspecialchars($post['title']); ?>"
                                </a>
                            </p>
                            <small><?php echo formatDate($post['created_at']); ?></small>
                        </div>
                    </div>
            <?php
                endforeach;
            else:
            ?>
                <p class="no-activity">No recent activity</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.dashboard-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: var(--spacing-8);
    padding: var(--spacing-6);
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
    color: var(--white);
    border-radius: var(--radius-xl);
}

.welcome-section h1 {
    color: var(--white);
    margin-bottom: var(--spacing-2);
}

.welcome-section p {
    color: rgba(255, 255, 255, 0.9);
    font-size: var(--font-size-lg);
}

.quick-actions {
    display: flex;
    gap: var(--spacing-3);
    flex-wrap: wrap;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: var(--spacing-6);
    margin-bottom: var(--spacing-8);
}

.stat-card {
    background: var(--white);
    border-radius: var(--radius-xl);
    padding: var(--spacing-6);
    box-shadow: var(--shadow-md);
    display: flex;
    align-items: center;
    gap: var(--spacing-4);
    transition: all var(--transition-fast);
}

.stat-card:hover {
    box-shadow: var(--shadow-lg);
    transform: translateY(-2px);
}

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: var(--radius-xl);
    background: var(--primary-color);
    color: var(--white);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: var(--font-size-xl);
}

.stat-icon.published {
    background: var(--success-color);
}

.stat-icon.draft {
    background: var(--warning-color);
}

.stat-content h3 {
    font-size: var(--font-size-3xl);
    font-weight: 700;
    color: var(--gray-900);
    margin-bottom: var(--spacing-1);
}

.stat-content p {
    color: var(--gray-600);
    font-size: var(--font-size-sm);
    margin: 0;
}

.posts-management {
    background: var(--white);
    border-radius: var(--radius-xl);
    box-shadow: var(--shadow-md);
    padding: var(--spacing-6);
    margin-bottom: var(--spacing-8);
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: var(--spacing-6);
    padding-bottom: var(--spacing-4);
    border-bottom: 1px solid var(--gray-200);
}

.section-header h2 {
    color: var(--gray-900);
    margin: 0;
}

.post-title-cell {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-1);
}

.post-preview {
    color: var(--gray-500);
    font-size: var(--font-size-sm);
}

.status-badge {
    padding: var(--spacing-1) var(--spacing-2);
    border-radius: var(--radius-md);
    font-size: var(--font-size-xs);
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: var(--spacing-1);
}

.status-badge.published {
    background: var(--success-color);
    color: var(--white);
}

.status-badge.draft {
    background: var(--warning-color);
    color: var(--white);
}

.action-buttons {
    display: flex;
    gap: var(--spacing-2);
}

.recent-activity {
    background: var(--white);
    border-radius: var(--radius-xl);
    box-shadow: var(--shadow-md);
    padding: var(--spacing-6);
}

.recent-activity h3 {
    color: var(--gray-900);
    margin-bottom: var(--spacing-4);
}

.activity-list {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-4);
}

.activity-item {
    display: flex;
    align-items: center;
    gap: var(--spacing-3);
    padding: var(--spacing-3);
    border-radius: var(--radius-lg);
    background: var(--gray-50);
}

.activity-icon {
    width: 40px;
    height: 40px;
    border-radius: var(--radius-lg);
    background: var(--primary-color);
    color: var(--white);
    display: flex;
    align-items: center;
    justify-content: center;
}

.activity-content p {
    margin: 0;
    color: var(--gray-700);
}

.activity-content small {
    color: var(--gray-500);
}

.no-activity {
    color: var(--gray-500);
    font-style: italic;
    text-align: center;
    padding: var(--spacing-4);
}

@media (max-width: 768px) {
    .dashboard-header {
        flex-direction: column;
        gap: var(--spacing-4);
        text-align: center;
    }
    
    .quick-actions {
        width: 100%;
        justify-content: center;
    }
    
    .section-header {
        flex-direction: column;
        gap: var(--spacing-3);
        align-items: stretch;
    }
    
    .action-buttons {
        justify-content: center;
    }
    
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}
</style>

<script>
function deletePost(postId) {
    if (confirmDelete('Are you sure you want to delete this post? This action cannot be undone.')) {
        window.location.href = 'posts/delete.php?id=' + postId;
    }
}

// Auto-refresh dashboard every 5 minutes
setTimeout(function() {
    window.location.reload();
}, 300000);
</script>

<?php include 'includes/footer.php'; ?>

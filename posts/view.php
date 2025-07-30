<?php
/**
 * View Single Post Page
 * PHP CRUD Blog Application - ApexPlanet Internship
 */

require_once '../includes/auth.php';
require_once '../config/db.php';

// Get post ID from URL
$postId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($postId <= 0) {
    setFlashMessage('Invalid post ID.', 'error');
    header('Location: ../index.php');
    exit();
}

// Get post with author information
try {
    $sql = "SELECT p.id, p.title, p.content, p.created_at, p.updated_at, p.author_id, p.is_published,
                   u.username, u.first_name, u.last_name,
                   CONCAT(u.first_name, ' ', u.last_name) as author_name
            FROM posts p 
            JOIN users u ON p.author_id = u.id 
            WHERE p.id = ?";
    
    $post = getSingleResult($conn, $sql, "i", [$postId]);
    
    if (!$post) {
        setFlashMessage('Post not found.', 'error');
        header('Location: ../index.php');
        exit();
    }
    
    // Check if post is published or if user is the author
    if (!$post['is_published'] && (!isLoggedIn() || $_SESSION['user_id'] != $post['author_id'])) {
        setFlashMessage('This post is not available.', 'error');
        header('Location: ../index.php');
        exit();
    }
    
} catch (Exception $e) {
    error_log("Error fetching post: " . $e->getMessage());
    setFlashMessage('An error occurred while loading the post.', 'error');
    header('Location: ../index.php');
    exit();
}

$page_title = htmlspecialchars($post['title']);
$isAuthor = isLoggedIn() && $_SESSION['user_id'] == $post['author_id'];

// Function to format date
function formatDate($date) {
    return date('F j, Y \a\t g:i A', strtotime($date));
}

include '../includes/header.php';
?>

<div class="container">
    <article class="post-article">
        <!-- Post Header -->
        <header class="post-header">
            <div class="post-meta-top">
                <a href="../index.php" class="back-link">
                    <i class="fas fa-arrow-left"></i> Back to Posts
                </a>
                
                <?php if (!$post['is_published']): ?>
                    <span class="draft-badge">
                        <i class="fas fa-eye-slash"></i> Draft
                    </span>
                <?php endif; ?>
            </div>
            
            <h1 class="post-title"><?php echo htmlspecialchars($post['title']); ?></h1>
            
            <div class="post-meta">
                <div class="author-info">
                    <i class="fas fa-user-circle"></i>
                    <span>By <strong><?php echo htmlspecialchars($post['author_name']); ?></strong></span>
                </div>
                
                <div class="post-dates">
                    <div class="date-item">
                        <i class="fas fa-calendar-plus"></i>
                        <span>Published: <?php echo formatDate($post['created_at']); ?></span>
                    </div>
                    
                    <?php if ($post['updated_at'] !== $post['created_at']): ?>
                        <div class="date-item">
                            <i class="fas fa-calendar-edit"></i>
                            <span>Updated: <?php echo formatDate($post['updated_at']); ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if ($isAuthor): ?>
                <div class="author-actions">
                    <a href="edit.php?id=<?php echo $post['id']; ?>" class="btn btn-primary btn-sm">
                        <i class="fas fa-edit"></i> Edit Post
                    </a>
                    <button onclick="deletePost(<?php echo $post['id']; ?>)" class="btn btn-danger btn-sm">
                        <i class="fas fa-trash"></i> Delete Post
                    </button>
                </div>
            <?php endif; ?>
        </header>
        
        <!-- Post Content -->
        <div class="post-content">
            <?php echo nl2br(htmlspecialchars($post['content'])); ?>
        </div>
        
        <!-- Post Footer -->
        <footer class="post-footer">
            <div class="post-stats">
                <span class="stat-item">
                    <i class="fas fa-eye"></i>
                    <span>Views: N/A</span>
                </span>
                <span class="stat-item">
                    <i class="fas fa-clock"></i>
                    <span>Reading time: ~<?php echo max(1, ceil(str_word_count($post['content']) / 200)); ?> min</span>
                </span>
            </div>
            
            <div class="social-share">
                <span>Share this post:</span>
                <a href="#" onclick="sharePost('twitter')" class="share-btn twitter">
                    <i class="fab fa-twitter"></i>
                </a>
                <a href="#" onclick="sharePost('facebook')" class="share-btn facebook">
                    <i class="fab fa-facebook"></i>
                </a>
                <a href="#" onclick="sharePost('linkedin')" class="share-btn linkedin">
                    <i class="fab fa-linkedin"></i>
                </a>
                <button onclick="copyLink()" class="share-btn copy">
                    <i class="fas fa-link"></i>
                </button>
            </div>
        </footer>
    </article>
    
    <!-- Related Posts Section -->
    <section class="related-posts">
        <h3><i class="fas fa-newspaper"></i> More Posts</h3>
        <div class="related-posts-grid">
            <?php
            // Get other posts by the same author
            try {
                $sql = "SELECT id, title, created_at FROM posts 
                        WHERE author_id = ? AND id != ? AND is_published = 1 
                        ORDER BY created_at DESC LIMIT 3";
                $relatedPosts = getMultipleResults($conn, $sql, "ii", [$post['author_id'], $post['id']]);
                
                if (!empty($relatedPosts)):
                    foreach ($relatedPosts as $relatedPost):
            ?>
                        <div class="related-post-card">
                            <h4>
                                <a href="view.php?id=<?php echo $relatedPost['id']; ?>">
                                    <?php echo htmlspecialchars($relatedPost['title']); ?>
                                </a>
                            </h4>
                            <p class="related-post-date">
                                <i class="fas fa-calendar"></i>
                                <?php echo formatDate($relatedPost['created_at']); ?>
                            </p>
                        </div>
            <?php
                    endforeach;
                else:
            ?>
                    <p class="no-related-posts">
                        <i class="fas fa-info-circle"></i>
                        No other posts by this author yet.
                    </p>
            <?php
                endif;
            } catch (Exception $e) {
                error_log("Error fetching related posts: " . $e->getMessage());
            }
            ?>
        </div>
    </section>
</div>

<style>
.post-article {
    max-width: 800px;
    margin: 0 auto;
    background: var(--white);
    border-radius: var(--radius-xl);
    box-shadow: var(--shadow-lg);
    overflow: hidden;
}

.post-header {
    padding: var(--spacing-8);
    border-bottom: 1px solid var(--gray-200);
}

.post-meta-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: var(--spacing-6);
}

.back-link {
    color: var(--primary-color);
    text-decoration: none;
    font-weight: 500;
    transition: color var(--transition-fast);
}

.back-link:hover {
    color: var(--primary-dark);
}

.draft-badge {
    background: var(--warning-color);
    color: var(--white);
    padding: var(--spacing-1) var(--spacing-3);
    border-radius: var(--radius-md);
    font-size: var(--font-size-sm);
    font-weight: 500;
}

.post-title {
    font-size: var(--font-size-4xl);
    color: var(--gray-900);
    margin-bottom: var(--spacing-6);
    line-height: 1.2;
}

.post-meta {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-4);
    margin-bottom: var(--spacing-6);
}

.author-info {
    display: flex;
    align-items: center;
    gap: var(--spacing-2);
    color: var(--gray-600);
    font-size: var(--font-size-lg);
}

.post-dates {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-2);
}

.date-item {
    display: flex;
    align-items: center;
    gap: var(--spacing-2);
    color: var(--gray-500);
    font-size: var(--font-size-sm);
}

.author-actions {
    display: flex;
    gap: var(--spacing-3);
    flex-wrap: wrap;
}

.post-content {
    padding: var(--spacing-8);
    font-size: var(--font-size-lg);
    line-height: 1.8;
    color: var(--gray-700);
}

.post-footer {
    padding: var(--spacing-6) var(--spacing-8);
    background: var(--gray-50);
    border-top: 1px solid var(--gray-200);
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: var(--spacing-4);
}

.post-stats {
    display: flex;
    gap: var(--spacing-4);
    flex-wrap: wrap;
}

.stat-item {
    display: flex;
    align-items: center;
    gap: var(--spacing-1);
    color: var(--gray-500);
    font-size: var(--font-size-sm);
}

.social-share {
    display: flex;
    align-items: center;
    gap: var(--spacing-3);
    color: var(--gray-600);
    font-size: var(--font-size-sm);
}

.share-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    border-radius: var(--radius-lg);
    text-decoration: none;
    transition: all var(--transition-fast);
    border: none;
    cursor: pointer;
}

.share-btn.twitter { background: #1da1f2; color: var(--white); }
.share-btn.facebook { background: #4267b2; color: var(--white); }
.share-btn.linkedin { background: #0077b5; color: var(--white); }
.share-btn.copy { background: var(--gray-600); color: var(--white); }

.share-btn:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

.related-posts {
    margin-top: var(--spacing-8);
    padding: var(--spacing-6);
    background: var(--white);
    border-radius: var(--radius-xl);
    box-shadow: var(--shadow-md);
}

.related-posts h3 {
    margin-bottom: var(--spacing-6);
    color: var(--gray-900);
}

.related-posts-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: var(--spacing-4);
}

.related-post-card {
    padding: var(--spacing-4);
    border: 1px solid var(--gray-200);
    border-radius: var(--radius-lg);
    transition: all var(--transition-fast);
}

.related-post-card:hover {
    box-shadow: var(--shadow-md);
    transform: translateY(-2px);
}

.related-post-card h4 {
    margin-bottom: var(--spacing-2);
}

.related-post-card a {
    color: var(--gray-900);
    text-decoration: none;
    transition: color var(--transition-fast);
}

.related-post-card a:hover {
    color: var(--primary-color);
}

.related-post-date {
    color: var(--gray-500);
    font-size: var(--font-size-sm);
    margin: 0;
}

.no-related-posts {
    color: var(--gray-500);
    font-style: italic;
    text-align: center;
    padding: var(--spacing-4);
}

@media (max-width: 768px) {
    .post-title {
        font-size: var(--font-size-3xl);
    }
    
    .post-meta {
        align-items: flex-start;
    }
    
    .post-footer {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .author-actions {
        width: 100%;
    }
    
    .author-actions .btn {
        flex: 1;
        justify-content: center;
    }
}
</style>

<script>
function deletePost(postId) {
    if (confirmDelete('Are you sure you want to delete this post? This action cannot be undone.')) {
        window.location.href = 'delete.php?id=' + postId;
    }
}

function sharePost(platform) {
    const url = encodeURIComponent(window.location.href);
    const title = encodeURIComponent(document.title);
    
    let shareUrl;
    switch (platform) {
        case 'twitter':
            shareUrl = `https://twitter.com/intent/tweet?url=${url}&text=${title}`;
            break;
        case 'facebook':
            shareUrl = `https://www.facebook.com/sharer/sharer.php?u=${url}`;
            break;
        case 'linkedin':
            shareUrl = `https://www.linkedin.com/sharing/share-offsite/?url=${url}`;
            break;
    }
    
    if (shareUrl) {
        window.open(shareUrl, '_blank', 'width=600,height=400');
    }
}

function copyLink() {
    navigator.clipboard.writeText(window.location.href).then(function() {
        alert('Link copied to clipboard!');
    }).catch(function() {
        // Fallback for older browsers
        const textArea = document.createElement('textarea');
        textArea.value = window.location.href;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);
        alert('Link copied to clipboard!');
    });
}
</script>

<?php include '../includes/footer.php'; ?>

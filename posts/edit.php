<?php

/**
 * Edit Post Page
 * PHP CRUD Blog Application - ApexPlanet Internship
 */

require_once '../includes/auth.php';
require_once '../config/db.php';

// Require authentication
requireAuth();

// Get post ID from URL
$postId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($postId <= 0) {
    setFlashMessage('Invalid post ID.', 'error');
    header('Location: ../dashboard.php');
    exit();
}

// Get post and verify ownership/permissions
try {
    $sql = "SELECT p.id, p.title, p.content, p.author_id, p.is_published, u.username, u.role
            FROM posts p
            JOIN users u ON p.author_id = u.id
            WHERE p.id = ?";
    $post = getSingleResult($conn, $sql, "i", [$postId]);

    if (!$post) {
        setFlashMessage('Post not found.', 'error');
        header('Location: ../dashboard.php');
        exit();
    }

    // Check if user can modify this post
    if (!canModifyPost($post['author_id'], $_SESSION['user_id'], $_SESSION['user_role'])) {
        setFlashMessage('You do not have permission to edit this post.', 'error');
        header('Location: ../dashboard.php');
        exit();
    }

    // Check if user is the author
    if ($post['author_id'] != $_SESSION['user_id']) {
        setFlashMessage('You can only edit your own posts.', 'error');
        header('Location: ../dashboard.php');
        exit();
    }
} catch (Exception $e) {
    error_log("Error fetching post: " . $e->getMessage());
    setFlashMessage('An error occurred while loading the post.', 'error');
    header('Location: ../dashboard.php');
    exit();
}

$page_title = "Edit Post";
$errors = [];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid security token. Please try again.';
    } else {
        // Validate and sanitize input using enhanced security functions
        $title = validateInput($_POST['title'] ?? '', 'title', ['max_length' => 200]);
        $content = validateInput($_POST['content'] ?? '', 'content', ['max_length' => 10000]);
        $isPublished = isset($_POST['is_published']) ? 1 : 0;

        // Enhanced validation
        if ($title === false) {
            $errors[] = 'Title is required and must be no more than 200 characters.';
        }

        if ($content === false) {
            $errors[] = 'Content is required and must be between 10 and 10,000 characters.';
        }

        // Additional security checks
        if (strlen($content) < 10) {
            $errors[] = 'Content must be at least 10 characters long.';
        }

        // Check for potentially malicious content
        $suspiciousPatterns = ['<script', 'javascript:', 'onload=', 'onerror=', 'onclick='];
        foreach ($suspiciousPatterns as $pattern) {
            if (stripos($title . $content, $pattern) !== false) {
                $errors[] = 'Content contains potentially unsafe elements.';
                break;
            }
        }

        // If no errors, update the post
        if (empty($errors)) {
            try {
                // Store old values for audit log
                $oldValues = json_encode([
                    'title' => $post['title'],
                    'content' => $post['content'],
                    'is_published' => $post['is_published']
                ]);

                // Update the post with enhanced security
                $sql = "UPDATE posts SET title = ?, content = ?, is_published = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ? AND author_id = ?";
                $stmt = executeQuery($conn, $sql, "ssiii", [$title, $content, $isPublished, $postId, $_SESSION['user_id']]);

                if ($stmt && $stmt->affected_rows > 0) {
                    $stmt->close();

                    // Log the update action
                    $newValues = json_encode([
                        'title' => $title,
                        'content' => $content,
                        'is_published' => $isPublished
                    ]);

                    $auditStmt = $conn->prepare("INSERT INTO audit_log (user_id, action, table_name, record_id, old_values, new_values, ip_address, user_agent) VALUES (?, 'update', 'posts', ?, ?, ?, ?, ?)");
                    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
                    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
                    $auditStmt->bind_param("iissss", $_SESSION['user_id'], $postId, $oldValues, $newValues, $ip, $userAgent);
                    $auditStmt->execute();

                    setFlashMessage('Post updated successfully!', 'success');

                    // Redirect to the updated post
                    header('Location: view.php?id=' . $postId);
                    exit();
                } else {
                    $errors[] = 'Failed to update post. You may not have permission to edit this post.';
                }
            } catch (Exception $e) {
                error_log("Error updating post: " . $e->getMessage());
                $errors[] = 'An error occurred while updating the post. Please try again.';
            }
        }
    }
} else {
    // Pre-populate form with existing data
    $_POST['title'] = $post['title'];
    $_POST['content'] = $post['content'];
    if ($post['is_published']) {
        $_POST['is_published'] = '1';
    }
}

// Generate CSRF token
$csrfToken = generateCSRFToken();

include '../includes/header.php';
?>

<div class="container">
    <div class="form-container">
        <div class="text-center mb-4">
            <h1><i class="fas fa-edit"></i> Edit Post</h1>
            <p>Update your post content</p>
        </div>

        <div class="post-actions-top mb-4">
            <a href="view.php?id=<?php echo $postId; ?>" class="btn btn-outline">
                <i class="fas fa-eye"></i> View Post
            </a>
            <a href="../dashboard.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="flash-message error">
                <div class="flash-content">
                    <i class="fas fa-exclamation-circle"></i>
                    <div>
                        <strong>Please fix the following errors:</strong>
                        <ul style="margin: 0.5rem 0 0 1rem;">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <form method="POST" id="editPostForm" novalidate>
            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">

            <div class="form-group">
                <label for="title" class="form-label">
                    <i class="fas fa-heading"></i> Post Title *
                </label>
                <input
                    type="text"
                    id="title"
                    name="title"
                    class="form-input"
                    value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>"
                    required
                    maxlength="200"
                    placeholder="Enter an engaging title for your post">
                <small class="form-help">Maximum 200 characters</small>
            </div>

            <div class="form-group">
                <label for="content" class="form-label">
                    <i class="fas fa-align-left"></i> Post Content *
                </label>
                <textarea
                    id="content"
                    name="content"
                    class="form-textarea"
                    required
                    rows="12"
                    data-max-length="10000"
                    placeholder="Write your post content here..."><?php echo htmlspecialchars($_POST['content'] ?? ''); ?></textarea>
                <small class="form-help">Minimum 10 characters, maximum 10,000 characters</small>
            </div>

            <div class="form-group">
                <label class="checkbox-label">
                    <input
                        type="checkbox"
                        name="is_published"
                        value="1"
                        <?php echo isset($_POST['is_published']) ? 'checked' : ''; ?>>
                    <span class="checkmark"></span>
                    <span>
                        <strong>Published</strong>
                        <small style="display: block; color: var(--gray-500);">
                            Check to make this post visible to everyone
                        </small>
                    </span>
                </label>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fas fa-save"></i> Update Post
                </button>
                <a href="view.php?id=<?php echo $postId; ?>" class="btn btn-secondary btn-lg">
                    <i class="fas fa-times"></i> Cancel
                </a>
                <button type="button" onclick="deletePost(<?php echo $postId; ?>)" class="btn btn-danger btn-lg">
                    <i class="fas fa-trash"></i> Delete Post
                </button>
            </div>
        </form>

        <div class="post-info mt-4">
            <h4><i class="fas fa-info-circle"></i> Post Information</h4>
            <div class="info-grid">
                <div class="info-item">
                    <strong>Created:</strong>
                    <span><?php echo date('F j, Y \a\t g:i A', strtotime($post['created_at'] ?? 'now')); ?></span>
                </div>
                <div class="info-item">
                    <strong>Status:</strong>
                    <span class="status-badge <?php echo $post['is_published'] ? 'published' : 'draft'; ?>">
                        <?php echo $post['is_published'] ? 'Published' : 'Draft'; ?>
                    </span>
                </div>
                <div class="info-item">
                    <strong>Word Count:</strong>
                    <span id="wordCount"><?php echo str_word_count($post['content']); ?> words</span>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .form-container {
        max-width: 800px;
    }

    .post-actions-top {
        display: flex;
        gap: var(--spacing-3);
        justify-content: flex-start;
        flex-wrap: wrap;
    }

    .form-actions {
        display: flex;
        gap: var(--spacing-4);
        justify-content: flex-start;
        flex-wrap: wrap;
    }

    .checkbox-label {
        display: flex;
        align-items: flex-start;
        gap: var(--spacing-2);
        cursor: pointer;
        font-size: var(--font-size-base);
        color: var(--gray-700);
    }

    .checkbox-label input[type="checkbox"] {
        width: auto;
        margin: 0;
        margin-top: 0.125rem;
    }

    .post-info {
        background: var(--gray-50);
        border: 1px solid var(--gray-200);
        border-radius: var(--radius-lg);
        padding: var(--spacing-6);
    }

    .post-info h4 {
        color: var(--gray-800);
        margin-bottom: var(--spacing-4);
    }

    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: var(--spacing-4);
    }

    .info-item {
        display: flex;
        flex-direction: column;
        gap: var(--spacing-1);
    }

    .info-item strong {
        color: var(--gray-700);
        font-size: var(--font-size-sm);
    }

    .status-badge {
        padding: var(--spacing-1) var(--spacing-2);
        border-radius: var(--radius-md);
        font-size: var(--font-size-xs);
        font-weight: 500;
        text-transform: uppercase;
        width: fit-content;
    }

    .status-badge.published {
        background: var(--success-color);
        color: var(--white);
    }

    .status-badge.draft {
        background: var(--warning-color);
        color: var(--white);
    }

    @media (max-width: 768px) {

        .post-actions-top,
        .form-actions {
            flex-direction: column;
        }

        .post-actions-top .btn,
        .form-actions .btn {
            width: 100%;
            justify-content: center;
        }

        .info-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<script>
    // Form validation
    document.getElementById('editPostForm').addEventListener('submit', function(e) {
        const title = document.getElementById('title').value.trim();
        const content = document.getElementById('content').value.trim();

        if (!title) {
            alert('Please enter a title for your post.');
            e.preventDefault();
            return false;
        }

        if (!content || content.length < 10) {
            alert('Please enter at least 10 characters of content.');
            e.preventDefault();
            return false;
        }

        // Set loading state
        const submitBtn = this.querySelector('button[type="submit"]');
        submitBtn.setAttribute('data-original-text', submitBtn.innerHTML);
        setLoadingState(submitBtn, true);
    });

    // Word counter
    function updateWordCount() {
        const content = document.getElementById('content').value;
        const wordCount = content.trim() ? content.trim().split(/\s+/).length : 0;
        document.getElementById('wordCount').textContent = wordCount + ' words';
    }

    document.getElementById('content').addEventListener('input', updateWordCount);

    // Delete post function
    function deletePost(postId) {
        if (confirmDelete('Are you sure you want to delete this post? This action cannot be undone.')) {
            window.location.href = 'delete.php?id=' + postId;
        }
    }

    // Character counter
    document.addEventListener('DOMContentLoaded', function() {
        setupCharacterCounter();
        updateWordCount();
    });
</script>

<?php include '../includes/footer.php'; ?>
<?php
/**
 * Create New Post Page
 * PHP CRUD Blog Application - ApexPlanet Internship
 */

require_once '../includes/auth.php';
require_once '../config/db.php';

// Require authentication
requireAuth();

$page_title = "Create New Post";
$errors = [];
$success = false;

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid security token. Please try again.';
    } else {
        // Sanitize input
        $title = sanitizeInput($_POST['title'] ?? '');
        $content = sanitizeInput($_POST['content'] ?? '');
        $isPublished = isset($_POST['is_published']) ? 1 : 0;
        
        // Validate input
        if (empty($title)) {
            $errors[] = 'Title is required.';
        } elseif (strlen($title) > 200) {
            $errors[] = 'Title must be no more than 200 characters.';
        }
        
        if (empty($content)) {
            $errors[] = 'Content is required.';
        } elseif (strlen($content) < 10) {
            $errors[] = 'Content must be at least 10 characters long.';
        }
        
        // If no errors, create the post
        if (empty($errors)) {
            try {
                $sql = "INSERT INTO posts (title, content, author_id, is_published) VALUES (?, ?, ?, ?)";
                $stmt = executeQuery($conn, $sql, "ssii", [$title, $content, $_SESSION['user_id'], $isPublished]);
                
                if ($stmt) {
                    $postId = $conn->insert_id;
                    $stmt->close();
                    
                    $success = true;
                    setFlashMessage('Post created successfully!', 'success');
                    
                    // Redirect to the new post
                    header('Location: view.php?id=' . $postId);
                    exit();
                }
            } catch (Exception $e) {
                error_log("Error creating post: " . $e->getMessage());
                $errors[] = 'An error occurred while creating the post. Please try again.';
            }
        }
    }
}

// Generate CSRF token
$csrfToken = generateCSRFToken();

include '../includes/header.php';
?>

<div class="container">
    <div class="form-container">
        <div class="text-center mb-4">
            <h1><i class="fas fa-plus"></i> Create New Post</h1>
            <p>Share your thoughts with the community</p>
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
        
        <form method="POST" id="createPostForm" novalidate>
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
                    placeholder="Enter an engaging title for your post"
                >
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
                    placeholder="Write your post content here... Share your thoughts, experiences, or knowledge with the community."
                ><?php echo htmlspecialchars($_POST['content'] ?? ''); ?></textarea>
                <small class="form-help">Minimum 10 characters, maximum 10,000 characters</small>
            </div>
            
            <div class="form-group">
                <label class="checkbox-label">
                    <input 
                        type="checkbox" 
                        name="is_published" 
                        value="1" 
                        <?php echo (isset($_POST['is_published']) || !isset($_POST['title'])) ? 'checked' : ''; ?>
                    >
                    <span class="checkmark"></span>
                    <span>
                        <strong>Publish immediately</strong>
                        <small style="display: block; color: var(--gray-500);">
                            Uncheck to save as draft (you can publish later)
                        </small>
                    </span>
                </label>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fas fa-save"></i> Create Post
                </button>
                <a href="../dashboard.php" class="btn btn-secondary btn-lg">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
        
        <div class="writing-tips mt-4">
            <h4><i class="fas fa-lightbulb"></i> Writing Tips</h4>
            <ul>
                <li><strong>Engaging Title:</strong> Create a title that captures attention and summarizes your post</li>
                <li><strong>Clear Structure:</strong> Use paragraphs to break up your content for better readability</li>
                <li><strong>Personal Voice:</strong> Write in your own voice to connect with readers</li>
                <li><strong>Proofread:</strong> Check for spelling and grammar before publishing</li>
                <li><strong>Add Value:</strong> Share insights, experiences, or knowledge that benefits others</li>
            </ul>
        </div>
    </div>
</div>

<style>
.form-container {
    max-width: 800px;
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

.writing-tips {
    background: var(--gray-50);
    border: 1px solid var(--gray-200);
    border-radius: var(--radius-lg);
    padding: var(--spacing-6);
}

.writing-tips h4 {
    color: var(--gray-800);
    margin-bottom: var(--spacing-4);
}

.writing-tips ul {
    margin: 0;
    padding-left: var(--spacing-5);
}

.writing-tips li {
    margin-bottom: var(--spacing-2);
    color: var(--gray-700);
    line-height: 1.5;
}

@media (max-width: 768px) {
    .form-actions {
        flex-direction: column;
    }
    
    .form-actions .btn {
        width: 100%;
        justify-content: center;
    }
}
</style>

<script>
// Form validation
document.getElementById('createPostForm').addEventListener('submit', function(e) {
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

// Auto-save functionality (optional enhancement)
let autoSaveTimer;
function autoSave() {
    clearTimeout(autoSaveTimer);
    autoSaveTimer = setTimeout(function() {
        const title = document.getElementById('title').value;
        const content = document.getElementById('content').value;
        
        if (title.trim() || content.trim()) {
            // In a real application, you might save to localStorage or send an AJAX request
            console.log('Auto-saving draft...');
        }
    }, 5000); // Auto-save after 5 seconds of inactivity
}

document.getElementById('title').addEventListener('input', autoSave);
document.getElementById('content').addEventListener('input', autoSave);

// Character counter for content
document.addEventListener('DOMContentLoaded', function() {
    setupCharacterCounter();
});
</script>

<?php include '../includes/footer.php'; ?>

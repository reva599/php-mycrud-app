<?php
/**
 * Delete Post Handler
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

// Get post and verify ownership
try {
    $sql = "SELECT id, title, author_id FROM posts WHERE id = ?";
    $post = getSingleResult($conn, $sql, "i", [$postId]);
    
    if (!$post) {
        setFlashMessage('Post not found.', 'error');
        header('Location: ../dashboard.php');
        exit();
    }
    
    // Check if user is the author
    if ($post['author_id'] != $_SESSION['user_id']) {
        setFlashMessage('You can only delete your own posts.', 'error');
        header('Location: ../dashboard.php');
        exit();
    }
    
    // Delete the post
    $sql = "DELETE FROM posts WHERE id = ? AND author_id = ?";
    $stmt = executeQuery($conn, $sql, "ii", [$postId, $_SESSION['user_id']]);
    
    if ($stmt) {
        $stmt->close();
        setFlashMessage('Post "' . htmlspecialchars($post['title']) . '" has been deleted successfully.', 'success');
    } else {
        setFlashMessage('An error occurred while deleting the post.', 'error');
    }
    
} catch (Exception $e) {
    error_log("Error deleting post: " . $e->getMessage());
    setFlashMessage('An error occurred while deleting the post.', 'error');
}

// Redirect to dashboard
header('Location: ../dashboard.php');
exit();
?>

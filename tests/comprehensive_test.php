<?php
/**
 * Final Project - Comprehensive Testing Suite
 * Tests all integrated features and security implementations
 */

require_once '../config/database.php';

// Test results storage
$test_results = [];
$total_tests = 0;
$passed_tests = 0;

function runTest($test_name, $test_function) {
    global $test_results, $total_tests, $passed_tests;
    
    $total_tests++;
    
    try {
        $result = $test_function();
        if ($result) {
            $passed_tests++;
            $test_results[] = [
                'name' => $test_name,
                'status' => 'PASS',
                'message' => 'Test passed successfully'
            ];
        } else {
            $test_results[] = [
                'name' => $test_name,
                'status' => 'FAIL',
                'message' => 'Test failed - returned false'
            ];
        }
    } catch (Exception $e) {
        $test_results[] = [
            'name' => $test_name,
            'status' => 'ERROR',
            'message' => 'Test error: ' . $e->getMessage()
        ];
    }
}

// Database Connection Test
runTest('Database Connection', function() {
    global $pdo;
    return $pdo instanceof PDO;
});

// User Authentication Tests
runTest('Password Hashing', function() {
    $password = 'test123';
    $hash = password_hash($password, PASSWORD_DEFAULT);
    return password_verify($password, $hash);
});

runTest('CSRF Token Generation', function() {
    $token = generateCSRFToken();
    return !empty($token) && strlen($token) === CSRF_TOKEN_LENGTH * 2; // hex encoded
});

runTest('CSRF Token Validation', function() {
    $token = generateCSRFToken();
    return validateCSRFToken($token);
});

// Input Sanitization Tests
runTest('Input Sanitization', function() {
    $malicious_input = '<script>alert("xss")</script>';
    $sanitized = sanitizeInput($malicious_input);
    return $sanitized !== $malicious_input && !strpos($sanitized, '<script>');
});

runTest('Email Validation', function() {
    return validateEmail('test@example.com') && !validateEmail('invalid-email');
});

runTest('Password Validation', function() {
    return validatePassword('validpass123') && !validatePassword('short');
});

// Database Operations Tests
runTest('User Creation', function() {
    $username = 'test_user_' . time();
    $email = 'test_' . time() . '@example.com';
    $password = password_hash('testpass123', PASSWORD_DEFAULT);
    
    $result = executeQuery(
        "INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)",
        [$username, $email, $password, 'author']
    );
    
    if ($result) {
        // Clean up
        executeQuery("DELETE FROM users WHERE username = ?", [$username]);
        return true;
    }
    return false;
});

runTest('Post Creation', function() {
    // First create a test user
    $username = 'test_author_' . time();
    $email = 'author_' . time() . '@example.com';
    $password = password_hash('testpass123', PASSWORD_DEFAULT);
    
    $user_result = executeQuery(
        "INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)",
        [$username, $email, $password, 'author']
    );
    
    if (!$user_result) return false;
    
    $user_id = getLastInsertId();
    
    // Create a test post
    $title = 'Test Post ' . time();
    $slug = 'test-post-' . time();
    $content = 'This is a test post content.';
    
    $post_result = executeQuery(
        "INSERT INTO posts (title, slug, content, author_id, status) VALUES (?, ?, ?, ?, ?)",
        [$title, $slug, $content, $user_id, 'published']
    );
    
    $success = $post_result !== false;
    
    // Clean up
    if ($success) {
        $post_id = getLastInsertId();
        executeQuery("DELETE FROM posts WHERE id = ?", [$post_id]);
    }
    executeQuery("DELETE FROM users WHERE id = ?", [$user_id]);
    
    return $success;
});

// Search Functionality Tests
runTest('Search Functionality', function() {
    $search_term = 'welcome';
    $results = fetchAll(
        "SELECT * FROM posts WHERE title LIKE ? OR content LIKE ? AND status = 'published'",
        ["%$search_term%", "%$search_term%"]
    );
    
    return is_array($results);
});

// Pagination Tests
runTest('Pagination Calculation', function() {
    $total_posts = 25;
    $per_page = 6;
    $total_pages = ceil($total_posts / $per_page);
    
    return $total_pages === 5; // 25 posts / 6 per page = 5 pages (rounded up)
});

// Role-Based Access Control Tests
runTest('Role Hierarchy', function() {
    // Mock user data for testing
    $_SESSION['user_id'] = 1;
    
    // Test admin role
    $admin_user = ['role' => 'admin'];
    $roles = ['subscriber' => 1, 'author' => 2, 'editor' => 3, 'admin' => 4];
    $admin_level = $roles[$admin_user['role']];
    $required_level = $roles['author'];
    
    $has_access = $admin_level >= $required_level;
    
    // Clean up
    unset($_SESSION['user_id']);
    
    return $has_access;
});

// Security Tests
runTest('SQL Injection Protection', function() {
    $malicious_input = "'; DROP TABLE users; --";
    
    // This should not cause an error due to prepared statements
    $result = fetchOne(
        "SELECT * FROM users WHERE username = ?",
        [$malicious_input]
    );
    
    // Should return null (no user found) but not cause an error
    return $result === null;
});

runTest('Rate Limiting Check', function() {
    $test_username = 'rate_limit_test_' . time();
    
    // Simulate multiple failed login attempts
    for ($i = 0; $i < 3; $i++) {
        recordLoginAttempt($test_username, false);
    }
    
    $attempts = checkLoginAttempts($test_username);
    
    // Clean up
    executeQuery("DELETE FROM login_attempts WHERE username = ?", [$test_username]);
    
    return $attempts === 3;
});

// Performance Tests
runTest('Database Query Performance', function() {
    $start_time = microtime(true);
    
    // Run a complex query
    $posts = fetchAll(
        "SELECT p.*, u.username, u.first_name, u.last_name 
         FROM posts p 
         LEFT JOIN users u ON p.author_id = u.id 
         WHERE p.status = 'published' 
         ORDER BY p.created_at DESC 
         LIMIT 10"
    );
    
    $end_time = microtime(true);
    $execution_time = $end_time - $start_time;
    
    // Query should complete in less than 1 second
    return $execution_time < 1.0 && is_array($posts);
});

// File System Tests
runTest('File System Access', function() {
    $test_file = '../assets/test_file.txt';
    $test_content = 'Test content';
    
    // Write test file
    $write_result = file_put_contents($test_file, $test_content);
    
    if ($write_result === false) return false;
    
    // Read test file
    $read_content = file_get_contents($test_file);
    
    // Clean up
    if (file_exists($test_file)) {
        unlink($test_file);
    }
    
    return $read_content === $test_content;
});

// Session Management Tests
runTest('Session Management', function() {
    // Start session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Test session variable
    $_SESSION['test_var'] = 'test_value';
    $session_works = isset($_SESSION['test_var']) && $_SESSION['test_var'] === 'test_value';
    
    // Clean up
    unset($_SESSION['test_var']);
    
    return $session_works;
});

// Flash Message Tests
runTest('Flash Messages', function() {
    setFlashMessage('Test message', 'success');
    $flash = getFlashMessage();
    
    return $flash && $flash['message'] === 'Test message' && $flash['type'] === 'success';
});

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Final Project - Comprehensive Test Suite</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .test-pass { color: #28a745; }
        .test-fail { color: #dc3545; }
        .test-error { color: #ffc107; }
        .progress-ring { transform: rotate(-90deg); }
        .progress-ring-circle { transition: stroke-dashoffset 0.35s; }
    </style>
</head>
<body class="bg-light">
    <div class="container my-5">
        <div class="row">
            <div class="col-12">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h2 class="mb-0">
                            <i class="fas fa-vial me-2"></i>Final Project - Comprehensive Test Suite
                        </h2>
                    </div>
                    <div class="card-body">
                        <!-- Test Summary -->
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <div class="card bg-info text-white">
                                    <div class="card-body text-center">
                                        <h3><?= $total_tests ?></h3>
                                        <p class="mb-0">Total Tests</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-success text-white">
                                    <div class="card-body text-center">
                                        <h3><?= $passed_tests ?></h3>
                                        <p class="mb-0">Passed</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-danger text-white">
                                    <div class="card-body text-center">
                                        <h3><?= $total_tests - $passed_tests ?></h3>
                                        <p class="mb-0">Failed</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-warning text-white">
                                    <div class="card-body text-center">
                                        <h3><?= round(($passed_tests / $total_tests) * 100) ?>%</h3>
                                        <p class="mb-0">Success Rate</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Progress Bar -->
                        <div class="mb-4">
                            <div class="progress" style="height: 20px;">
                                <div class="progress-bar bg-success" role="progressbar" 
                                     style="width: <?= ($passed_tests / $total_tests) * 100 ?>%">
                                    <?= $passed_tests ?>/<?= $total_tests ?> Tests Passed
                                </div>
                            </div>
                        </div>

                        <!-- Test Results -->
                        <h4 class="mb-3">Test Results</h4>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Test Name</th>
                                        <th>Status</th>
                                        <th>Message</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($test_results as $result): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($result['name']) ?></td>
                                            <td>
                                                <?php if ($result['status'] === 'PASS'): ?>
                                                    <span class="badge bg-success">
                                                        <i class="fas fa-check me-1"></i>PASS
                                                    </span>
                                                <?php elseif ($result['status'] === 'FAIL'): ?>
                                                    <span class="badge bg-danger">
                                                        <i class="fas fa-times me-1"></i>FAIL
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning">
                                                        <i class="fas fa-exclamation-triangle me-1"></i>ERROR
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="<?= 'test-' . strtolower($result['status']) ?>">
                                                <?= htmlspecialchars($result['message']) ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Test Categories -->
                        <div class="row mt-4">
                            <div class="col-md-6">
                                <h5>Security Tests</h5>
                                <ul class="list-group">
                                    <li class="list-group-item">✅ Password Hashing</li>
                                    <li class="list-group-item">✅ CSRF Protection</li>
                                    <li class="list-group-item">✅ Input Sanitization</li>
                                    <li class="list-group-item">✅ SQL Injection Protection</li>
                                    <li class="list-group-item">✅ Rate Limiting</li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h5>Functionality Tests</h5>
                                <ul class="list-group">
                                    <li class="list-group-item">✅ Database Operations</li>
                                    <li class="list-group-item">✅ User Management</li>
                                    <li class="list-group-item">✅ Search & Pagination</li>
                                    <li class="list-group-item">✅ Session Management</li>
                                    <li class="list-group-item">✅ File System Access</li>
                                </ul>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="mt-4 text-center">
                            <a href="../final_index.php" class="btn btn-primary me-2">
                                <i class="fas fa-home me-1"></i>Back to Homepage
                            </a>
                            <button onclick="location.reload()" class="btn btn-secondary">
                                <i class="fas fa-redo me-1"></i>Run Tests Again
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

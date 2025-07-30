<?php
/**
 * Final Project - Enhanced Login System
 * Integrated login with security features
 */

require_once '../config/database.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: ../dashboard.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $error = 'Invalid request. Please try again.';
    } else {
        $username = sanitizeInput($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($username) || empty($password)) {
            $error = 'Please fill in all fields.';
        } else {
            // Check rate limiting
            $attempts = checkLoginAttempts($username);
            if ($attempts >= MAX_LOGIN_ATTEMPTS) {
                $error = 'Too many failed login attempts. Please try again in 15 minutes.';
            } else {
                // Fetch user
                $user = fetchOne(
                    "SELECT id, username, email, password, role, status, first_name, last_name FROM users WHERE username = ? OR email = ?",
                    [$username, $username]
                );
                
                if ($user && $user['status'] === 'active' && password_verify($password, $user['password'])) {
                    // Successful login
                    recordLoginAttempt($username, true);
                    
                    // Set session variables
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['full_name'] = trim($user['first_name'] . ' ' . $user['last_name']);
                    
                    // Update last login
                    executeQuery("UPDATE users SET last_login = NOW() WHERE id = ?", [$user['id']]);
                    
                    // Log activity
                    logActivity($user['id'], 'login', 'User logged in successfully');
                    
                    // Redirect to dashboard or intended page
                    $redirect = $_GET['redirect'] ?? '../dashboard.php';
                    header("Location: $redirect");
                    exit();
                } else {
                    // Failed login
                    recordLoginAttempt($username, false);
                    $error = 'Invalid username/email or password.';
                    
                    if ($user && $user['status'] !== 'active') {
                        $error = 'Your account is not active. Please contact administrator.';
                    }
                }
            }
        }
    }
}

$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Final Blog Application</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
        .card { border: none; border-radius: 15px; }
        .card-header { border-radius: 15px 15px 0 0 !important; }
        .btn-primary { background: linear-gradient(45deg, #667eea, #764ba2); border: none; }
        .btn-primary:hover { background: linear-gradient(45deg, #5a6fd8, #6a4190); }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-md-6 col-lg-4">
                <div class="card shadow-lg">
                    <div class="card-header bg-primary text-white text-center">
                        <h4><i class="fas fa-sign-in-alt me-2"></i>Login</h4>
                    </div>
                    <div class="card-body p-4">
                        <?php if ($error): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" id="loginForm">
                            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                            
                            <div class="mb-3">
                                <label for="username" class="form-label">
                                    <i class="fas fa-user me-1"></i>Username or Email
                                </label>
                                <input type="text" class="form-control" id="username" name="username" 
                                       value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">
                                    <i class="fas fa-lock me-1"></i>Password
                                </label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="password" name="password" required>
                                    <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100 mb-3">
                                <i class="fas fa-sign-in-alt me-2"></i>Login
                            </button>
                        </form>
                        
                        <div class="text-center">
                            <p class="mb-2">
                                Don't have an account? 
                                <a href="register.php" class="text-decoration-none">Register here</a>
                            </p>
                            <p class="mb-0">
                                <a href="../index.php" class="text-decoration-none">
                                    <i class="fas fa-home me-1"></i>Back to Home
                                </a>
                            </p>
                        </div>
                    </div>
                </div>
                
                <!-- Demo Credentials -->
                <div class="card mt-3 shadow">
                    <div class="card-header bg-info text-white">
                        <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Demo Credentials</h6>
                    </div>
                    <div class="card-body">
                        <small>
                            <strong>Admin:</strong> admin / admin123<br>
                            <strong>Editor:</strong> editor / admin123<br>
                            <strong>Author:</strong> author / admin123
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
            const password = document.getElementById('password');
            const icon = this.querySelector('i');
            
            if (password.type === 'password') {
                password.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                password.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
        
        // Auto-focus on username field
        document.getElementById('username').focus();
    </script>
</body>
</html>

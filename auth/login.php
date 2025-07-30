<?php
/**
 * User Login Page
 * PHP CRUD Blog Application - ApexPlanet Internship
 */

require_once '../includes/auth.php';
require_once '../config/db.php';

// Redirect if already logged in
redirectIfLoggedIn();

$page_title = "Login";
$errors = [];

// Process login form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid security token. Please try again.';
    } else {
        // Sanitize input
        $username = sanitizeInput($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        // Validate input
        if (empty($username)) {
            $errors[] = 'Username or email is required.';
        }
        
        if (empty($password)) {
            $errors[] = 'Password is required.';
        }
        
        // If no errors, attempt login
        if (empty($errors)) {
            $result = loginUser($username, $password);
            
            if ($result['success']) {
                // Redirect to dashboard or intended page
                $redirectTo = $_GET['redirect'] ?? 'dashboard.php';
                header('Location: ../' . $redirectTo);
                exit();
            } else {
                $errors[] = $result['message'];
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
            <h1><i class="fas fa-sign-in-alt"></i> Welcome Back</h1>
            <p>Sign in to your account to continue</p>
        </div>
        
        <?php if (!empty($errors)): ?>
            <div class="flash-message error">
                <div class="flash-content">
                    <i class="fas fa-exclamation-circle"></i>
                    <div>
                        <?php foreach ($errors as $error): ?>
                            <div><?php echo htmlspecialchars($error); ?></div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <form method="POST" id="loginForm" novalidate>
            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
            
            <div class="form-group">
                <label for="username" class="form-label">
                    <i class="fas fa-user"></i> Username or Email
                </label>
                <input 
                    type="text" 
                    id="username" 
                    name="username" 
                    class="form-input" 
                    value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                    required
                    autocomplete="username"
                    placeholder="Enter your username or email"
                >
            </div>
            
            <div class="form-group">
                <label for="password" class="form-label">
                    <i class="fas fa-lock"></i> Password
                </label>
                <div style="position: relative;">
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        class="form-input" 
                        required
                        autocomplete="current-password"
                        placeholder="Enter your password"
                        style="padding-right: 3rem;"
                    >
                    <button 
                        type="button" 
                        id="togglePassword" 
                        style="position: absolute; right: 0.75rem; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--gray-500); cursor: pointer;"
                        title="Toggle password visibility"
                    >
                        <i class="fas fa-eye" id="toggleIcon"></i>
                    </button>
                </div>
            </div>
            
            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="remember_me" value="1">
                    <span class="checkmark"></span>
                    Remember me for 30 days
                </label>
            </div>
            
            <button type="submit" class="btn btn-primary btn-lg" style="width: 100%;">
                <i class="fas fa-sign-in-alt"></i> Sign In
            </button>
        </form>
        
        <div class="text-center mt-4">
            <p>Don't have an account? 
                <a href="register.php">
                    <i class="fas fa-user-plus"></i> Create Account
                </a>
            </p>
        </div>
        
        <div class="demo-accounts" style="margin-top: 2rem; padding: 1rem; background: var(--gray-100); border-radius: var(--radius-lg);">
            <h4 style="margin-bottom: 1rem; color: var(--gray-700);">
                <i class="fas fa-info-circle"></i> Demo Accounts
            </h4>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                <div class="demo-account">
                    <strong>Admin User:</strong><br>
                    Username: <code>admin</code><br>
                    Password: <code>password</code>
                </div>
                <div class="demo-account">
                    <strong>Regular User:</strong><br>
                    Username: <code>john_doe</code><br>
                    Password: <code>password</code>
                </div>
                <div class="demo-account">
                    <strong>Another User:</strong><br>
                    Username: <code>jane_smith</code><br>
                    Password: <code>password</code>
                </div>
            </div>
            <p style="margin-top: 1rem; font-size: var(--font-size-sm); color: var(--gray-600);">
                <i class="fas fa-exclamation-triangle"></i> 
                These are demo accounts for testing purposes. In production, use strong passwords!
            </p>
        </div>
    </div>
</div>

<style>
.checkbox-label {
    display: flex;
    align-items: center;
    gap: var(--spacing-2);
    cursor: pointer;
    font-size: var(--font-size-sm);
    color: var(--gray-700);
}

.checkbox-label input[type="checkbox"] {
    width: auto;
    margin: 0;
}

.demo-accounts {
    border: 1px solid var(--gray-300);
}

.demo-account {
    font-size: var(--font-size-sm);
    line-height: 1.5;
}

.demo-account code {
    background: var(--gray-200);
    padding: 0.125rem 0.25rem;
    border-radius: var(--radius-sm);
    font-family: 'Courier New', monospace;
    font-size: 0.875em;
}

@media (max-width: 768px) {
    .demo-accounts {
        margin-top: 1rem;
        padding: 0.75rem;
    }
    
    .demo-accounts h4 {
        font-size: var(--font-size-base);
    }
}
</style>

<script>
// Toggle password visibility
document.getElementById('togglePassword').addEventListener('click', function() {
    const passwordInput = document.getElementById('password');
    const toggleIcon = document.getElementById('toggleIcon');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleIcon.className = 'fas fa-eye-slash';
    } else {
        passwordInput.type = 'password';
        toggleIcon.className = 'fas fa-eye';
    }
});

// Form validation
document.getElementById('loginForm').addEventListener('submit', function(e) {
    const username = document.getElementById('username').value.trim();
    const password = document.getElementById('password').value;
    
    if (!username || !password) {
        e.preventDefault();
        alert('Please fill in all required fields.');
        return false;
    }
});

// Auto-focus on username field
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('username').focus();
});

// Demo account quick fill
document.addEventListener('click', function(e) {
    if (e.target.tagName === 'CODE') {
        const text = e.target.textContent;
        if (text === 'admin' || text === 'john_doe' || text === 'jane_smith') {
            document.getElementById('username').value = text;
            document.getElementById('password').value = 'password';
            document.getElementById('username').focus();
        }
    }
});
</script>

<?php include '../includes/footer.php'; ?>

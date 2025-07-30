<?php
/**
 * User Registration Page
 * PHP CRUD Blog Application - ApexPlanet Internship
 */

require_once '../includes/auth.php';
require_once '../config/db.php';

// Redirect if already logged in
redirectIfLoggedIn();

$page_title = "Register";
$errors = [];
$success = false;

// Process registration form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid security token. Please try again.';
    } else {
        // Sanitize input
        $username = sanitizeInput($_POST['username'] ?? '');
        $email = sanitizeInput($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $firstName = sanitizeInput($_POST['first_name'] ?? '');
        $lastName = sanitizeInput($_POST['last_name'] ?? '');
        
        // Validate input
        if (empty($username)) {
            $errors[] = 'Username is required.';
        } else {
            $usernameErrors = validateUsername($username);
            $errors = array_merge($errors, $usernameErrors);
        }
        
        if (empty($email)) {
            $errors[] = 'Email is required.';
        } elseif (!validateEmail($email)) {
            $errors[] = 'Please enter a valid email address.';
        }
        
        if (empty($password)) {
            $errors[] = 'Password is required.';
        } else {
            $passwordErrors = validatePassword($password);
            $errors = array_merge($errors, $passwordErrors);
        }
        
        if ($password !== $confirmPassword) {
            $errors[] = 'Passwords do not match.';
        }
        
        if (empty($firstName)) {
            $errors[] = 'First name is required.';
        }
        
        if (empty($lastName)) {
            $errors[] = 'Last name is required.';
        }
        
        // If no errors, attempt registration
        if (empty($errors)) {
            $result = registerUser($username, $email, $password, $firstName, $lastName);
            
            if ($result['success']) {
                $success = true;
                setFlashMessage($result['message'], 'success');
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
            <h1><i class="fas fa-user-plus"></i> Create Account</h1>
            <p>Join our blog community and start sharing your thoughts!</p>
        </div>
        
        <?php if ($success): ?>
            <div class="card">
                <div class="card-body text-center">
                    <i class="fas fa-check-circle" style="font-size: 3rem; color: var(--success-color); margin-bottom: 1rem;"></i>
                    <h3>Registration Successful!</h3>
                    <p>Your account has been created successfully. You can now log in to start using the blog.</p>
                    <a href="login.php" class="btn btn-primary">
                        <i class="fas fa-sign-in-alt"></i> Go to Login
                    </a>
                </div>
            </div>
        <?php else: ?>
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
            
            <form method="POST" id="registerForm" novalidate>
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                
                <div class="form-group">
                    <label for="username" class="form-label">
                        <i class="fas fa-user"></i> Username *
                    </label>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        class="form-input" 
                        value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                        required
                        maxlength="50"
                        pattern="[a-zA-Z0-9_]+"
                        title="Username can only contain letters, numbers, and underscores"
                    >
                    <small class="form-help">3-50 characters, letters, numbers, and underscores only</small>
                </div>
                
                <div class="form-group">
                    <label for="email" class="form-label">
                        <i class="fas fa-envelope"></i> Email Address *
                    </label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        class="form-input" 
                        value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                        required
                        maxlength="100"
                    >
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label for="first_name" class="form-label">
                            <i class="fas fa-user"></i> First Name *
                        </label>
                        <input 
                            type="text" 
                            id="first_name" 
                            name="first_name" 
                            class="form-input" 
                            value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>"
                            required
                            maxlength="50"
                        >
                    </div>
                    
                    <div class="form-group">
                        <label for="last_name" class="form-label">
                            <i class="fas fa-user"></i> Last Name *
                        </label>
                        <input 
                            type="text" 
                            id="last_name" 
                            name="last_name" 
                            class="form-input" 
                            value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>"
                            required
                            maxlength="50"
                        >
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password" class="form-label">
                        <i class="fas fa-lock"></i> Password *
                    </label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        class="form-input" 
                        required
                        minlength="8"
                    >
                    <div id="password-strength" class="password-strength"></div>
                    <small class="form-help">At least 8 characters with uppercase, lowercase, and number</small>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password" class="form-label">
                        <i class="fas fa-lock"></i> Confirm Password *
                    </label>
                    <input 
                        type="password" 
                        id="confirm_password" 
                        name="confirm_password" 
                        class="form-input" 
                        required
                        minlength="8"
                    >
                    <div id="password-match" class="password-match"></div>
                </div>
                
                <button type="submit" class="btn btn-primary btn-lg" style="width: 100%;">
                    <i class="fas fa-user-plus"></i> Create Account
                </button>
            </form>
            
            <div class="text-center mt-4">
                <p>Already have an account? 
                    <a href="login.php">
                        <i class="fas fa-sign-in-alt"></i> Sign In
                    </a>
                </p>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.form-help {
    color: var(--gray-500);
    font-size: var(--font-size-sm);
    margin-top: var(--spacing-1);
    display: block;
}

.password-strength,
.password-match {
    margin-top: var(--spacing-2);
    font-size: var(--font-size-sm);
    font-weight: 500;
}

.password-strength.weak { color: var(--error-color); }
.password-strength.medium { color: var(--warning-color); }
.password-strength.strong { color: var(--success-color); }

.password-match.match { color: var(--success-color); }
.password-match.no-match { color: var(--error-color); }
</style>

<script>
// Password strength checker
document.getElementById('password').addEventListener('input', function() {
    const password = this.value;
    const strengthDiv = document.getElementById('password-strength');
    const strength = checkPasswordStrength(password);
    
    if (password.length === 0) {
        strengthDiv.textContent = '';
        strengthDiv.className = 'password-strength';
        return;
    }
    
    if (strength < 3) {
        strengthDiv.textContent = 'Weak password';
        strengthDiv.className = 'password-strength weak';
    } else if (strength < 4) {
        strengthDiv.textContent = 'Medium password';
        strengthDiv.className = 'password-strength medium';
    } else {
        strengthDiv.textContent = 'Strong password';
        strengthDiv.className = 'password-strength strong';
    }
});

// Password match checker
document.getElementById('confirm_password').addEventListener('input', function() {
    const password = document.getElementById('password').value;
    const confirmPassword = this.value;
    const matchDiv = document.getElementById('password-match');
    
    if (confirmPassword.length === 0) {
        matchDiv.textContent = '';
        matchDiv.className = 'password-match';
        return;
    }
    
    if (password === confirmPassword) {
        matchDiv.textContent = 'Passwords match';
        matchDiv.className = 'password-match match';
    } else {
        matchDiv.textContent = 'Passwords do not match';
        matchDiv.className = 'password-match no-match';
    }
});

// Form validation
document.getElementById('registerForm').addEventListener('submit', function(e) {
    if (!validateForm('registerForm')) {
        e.preventDefault();
    }
});
</script>

<?php include '../includes/footer.php'; ?>

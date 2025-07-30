<?php
/**
 * Header Include File
 * PHP CRUD Blog Application - ApexPlanet Internship
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get current page for navigation highlighting
$current_page = basename($_SERVER['PHP_SELF']);
$is_logged_in = isset($_SESSION['user_id']);
$username = $is_logged_in ? $_SESSION['username'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>PHP CRUD Blog</title>
    
    <!-- CSS Styles -->
    <link rel="stylesheet" href="<?php echo getBasePath(); ?>style.css">
    
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Meta tags -->
    <meta name="description" content="PHP CRUD Blog Application - ApexPlanet Internship Project">
    <meta name="author" content="ApexPlanet Intern">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?php echo getBasePath(); ?>favicon.ico">
</head>
<body>
    <!-- Navigation Header -->
    <header class="main-header">
        <nav class="navbar">
            <div class="nav-container">
                <!-- Logo/Brand -->
                <div class="nav-brand">
                    <a href="<?php echo getBasePath(); ?>index.php" class="brand-link">
                        <i class="fas fa-blog"></i>
                        <span>PHP CRUD Blog</span>
                    </a>
                </div>
                
                <!-- Navigation Menu -->
                <div class="nav-menu" id="nav-menu">
                    <ul class="nav-list">
                        <li class="nav-item">
                            <a href="<?php echo getBasePath(); ?>index.php" 
                               class="nav-link <?php echo ($current_page == 'index.php') ? 'active' : ''; ?>">
                                <i class="fas fa-home"></i> Home
                            </a>
                        </li>
                        
                        <?php if ($is_logged_in): ?>
                            <li class="nav-item">
                                <a href="<?php echo getBasePath(); ?>dashboard.php" 
                                   class="nav-link <?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>">
                                    <i class="fas fa-tachometer-alt"></i> Dashboard
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?php echo getBasePath(); ?>posts/create.php" 
                                   class="nav-link <?php echo ($current_page == 'create.php') ? 'active' : ''; ?>">
                                    <i class="fas fa-plus"></i> New Post
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
                
                <!-- User Menu -->
                <div class="nav-user">
                    <?php if ($is_logged_in): ?>
                        <div class="user-dropdown">
                            <button class="user-btn" onclick="toggleUserMenu()">
                                <i class="fas fa-user-circle"></i>
                                <span><?php echo htmlspecialchars($username); ?></span>
                                <i class="fas fa-chevron-down"></i>
                            </button>
                            <div class="dropdown-menu" id="user-dropdown">
                                <a href="<?php echo getBasePath(); ?>profile.php" class="dropdown-item">
                                    <i class="fas fa-user"></i> Profile
                                </a>
                                <a href="<?php echo getBasePath(); ?>auth/logout.php" class="dropdown-item">
                                    <i class="fas fa-sign-out-alt"></i> Logout
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="auth-buttons">
                            <a href="<?php echo getBasePath(); ?>auth/login.php" 
                               class="btn btn-outline <?php echo ($current_page == 'login.php') ? 'active' : ''; ?>">
                                <i class="fas fa-sign-in-alt"></i> Login
                            </a>
                            <a href="<?php echo getBasePath(); ?>auth/register.php" 
                               class="btn btn-primary <?php echo ($current_page == 'register.php') ? 'active' : ''; ?>">
                                <i class="fas fa-user-plus"></i> Register
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Mobile Menu Toggle -->
                <div class="nav-toggle" onclick="toggleMobileMenu()">
                    <span class="bar"></span>
                    <span class="bar"></span>
                    <span class="bar"></span>
                </div>
            </div>
        </nav>
    </header>
    
    <!-- Main Content Container -->
    <main class="main-content">
        
        <?php
        // Display flash messages if any
        if (isset($_SESSION['flash_message'])):
        ?>
            <div class="flash-message <?php echo $_SESSION['flash_type'] ?? 'info'; ?>">
                <div class="flash-content">
                    <i class="fas fa-<?php echo getFlashIcon($_SESSION['flash_type'] ?? 'info'); ?>"></i>
                    <span><?php echo htmlspecialchars($_SESSION['flash_message']); ?></span>
                    <button class="flash-close" onclick="this.parentElement.parentElement.remove()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            <?php
            // Clear flash message after displaying
            unset($_SESSION['flash_message'], $_SESSION['flash_type']);
        endif;
        ?>

<?php
// Helper functions
function getBasePath() {
    $path = dirname($_SERVER['PHP_SELF']);
    if ($path === '/' || $path === '\\') {
        return '/';
    }
    return rtrim($path, '/\\') . '/';
}

function getFlashIcon($type) {
    switch ($type) {
        case 'success': return 'check-circle';
        case 'error': return 'exclamation-circle';
        case 'warning': return 'exclamation-triangle';
        default: return 'info-circle';
    }
}
?>

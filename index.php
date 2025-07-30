<?php
/**
 * PHP CRUD Application - Main Entry Point
 * ApexPlanet Internship Project
 * 
 * This is a basic PHP application demonstrating:
 * - PHP syntax and structure
 * - Database connectivity preparation
 * - Basic HTML integration
 * - Environment verification
 */

// Start session for future user management
session_start();

// Error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Basic configuration
$app_name = "PHP CRUD Application";
$version = "1.0.0";
$author = "ApexPlanet Intern";

// Check PHP version
$php_version = phpversion();
$required_php = "7.4.0";

// Database configuration (for future use)
$db_config = [
    'host' => 'localhost',
    'username' => 'root',
    'password' => '',
    'database' => 'crud_app',
    'port' => 3306
];

// Function to check if MySQL extension is loaded
function checkMySQLExtension() {
    return extension_loaded('mysqli') || extension_loaded('pdo_mysql');
}

// Function to test database connection (for future use)
function testDatabaseConnection($config) {
    try {
        $dsn = "mysql:host={$config['host']};port={$config['port']}";
        $pdo = new PDO($dsn, $config['username'], $config['password']);
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $app_name; ?> - Setup Verification</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            padding: 40px;
            max-width: 800px;
            width: 100%;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .header h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 2.5em;
        }
        
        .header p {
            color: #666;
            font-size: 1.1em;
        }
        
        .status-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }
        
        .status-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            border-left: 4px solid #28a745;
        }
        
        .status-card.warning {
            border-left-color: #ffc107;
        }
        
        .status-card.error {
            border-left-color: #dc3545;
        }
        
        .status-card h3 {
            color: #333;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
        }
        
        .status-card .icon {
            margin-right: 10px;
            font-size: 1.2em;
        }
        
        .status-card p {
            color: #666;
            line-height: 1.5;
        }
        
        .info-section {
            background: #e9ecef;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
        }
        
        .info-section h3 {
            color: #333;
            margin-bottom: 15px;
        }
        
        .info-list {
            list-style: none;
        }
        
        .info-list li {
            padding: 5px 0;
            color: #555;
        }
        
        .info-list li strong {
            color: #333;
        }
        
        .next-steps {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            border-radius: 10px;
            padding: 20px;
            margin-top: 30px;
        }
        
        .next-steps h3 {
            color: #155724;
            margin-bottom: 15px;
        }
        
        .next-steps ol {
            color: #155724;
            padding-left: 20px;
        }
        
        .next-steps li {
            margin: 8px 0;
            line-height: 1.5;
        }
        
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><?php echo $app_name; ?></h1>
            <p>Environment Setup Verification & Development Dashboard</p>
        </div>
        
        <div class="status-grid">
            <div class="status-card <?php echo version_compare($php_version, $required_php, '>=') ? '' : 'warning'; ?>">
                <h3>
                    <span class="icon"><?php echo version_compare($php_version, $required_php, '>=') ? '✅' : '⚠️'; ?></span>
                    PHP Version
                </h3>
                <p>
                    <strong>Current:</strong> <?php echo $php_version; ?><br>
                    <strong>Required:</strong> <?php echo $required_php; ?>+<br>
                    <strong>Status:</strong> <?php echo version_compare($php_version, $required_php, '>=') ? 'Compatible' : 'Needs Update'; ?>
                </p>
            </div>
            
            <div class="status-card <?php echo checkMySQLExtension() ? '' : 'error'; ?>">
                <h3>
                    <span class="icon"><?php echo checkMySQLExtension() ? '✅' : '❌'; ?></span>
                    MySQL Extension
                </h3>
                <p>
                    <strong>MySQLi:</strong> <?php echo extension_loaded('mysqli') ? 'Loaded' : 'Not Available'; ?><br>
                    <strong>PDO MySQL:</strong> <?php echo extension_loaded('pdo_mysql') ? 'Loaded' : 'Not Available'; ?><br>
                    <strong>Status:</strong> <?php echo checkMySQLExtension() ? 'Ready' : 'Install XAMPP'; ?>
                </p>
            </div>
            
            <div class="status-card">
                <h3>
                    <span class="icon">🌐</span>
                    Web Server
                </h3>
                <p>
                    <strong>Server:</strong> <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?><br>
                    <strong>Host:</strong> <?php echo $_SERVER['HTTP_HOST'] ?? 'localhost'; ?><br>
                    <strong>Status:</strong> Running
                </p>
            </div>
            
            <div class="status-card">
                <h3>
                    <span class="icon">📁</span>
                    Project Info
                </h3>
                <p>
                    <strong>Version:</strong> <?php echo $version; ?><br>
                    <strong>Author:</strong> <?php echo $author; ?><br>
                    <strong>Date:</strong> <?php echo date('Y-m-d H:i:s'); ?>
                </p>
            </div>
        </div>
        
        <div class="info-section">
            <h3>📋 System Information</h3>
            <ul class="info-list">
                <li><strong>Operating System:</strong> <?php echo php_uname('s') . ' ' . php_uname('r'); ?></li>
                <li><strong>PHP SAPI:</strong> <?php echo php_sapi_name(); ?></li>
                <li><strong>Document Root:</strong> <?php echo $_SERVER['DOCUMENT_ROOT'] ?? 'Not Set'; ?></li>
                <li><strong>Script Path:</strong> <?php echo __FILE__; ?></li>
                <li><strong>Memory Limit:</strong> <?php echo ini_get('memory_limit'); ?></li>
                <li><strong>Max Execution Time:</strong> <?php echo ini_get('max_execution_time'); ?> seconds</li>
            </ul>
        </div>
        
        <div class="next-steps">
            <h3>🚀 Next Steps for CRUD Development</h3>
            <ol>
                <li><strong>Install XAMPP:</strong> Download and install XAMPP for Apache + MySQL + PHP</li>
                <li><strong>Start Services:</strong> Launch Apache and MySQL from XAMPP Control Panel</li>
                <li><strong>Create Database:</strong> Use phpMyAdmin to create 'crud_app' database</li>
                <li><strong>Set up Git:</strong> Initialize repository and push to GitHub</li>
                <li><strong>Install VS Code:</strong> Add PHP extensions (Intelephense, PHP Debug)</li>
                <li><strong>Build CRUD Features:</strong> Create, Read, Update, Delete functionality</li>
            </ol>
        </div>
        
        <div class="footer">
            <p>🎯 <strong>ApexPlanet Internship Project</strong> | PHP CRUD Application Development</p>
            <p>Last updated: <?php echo date('F j, Y \a\t g:i A'); ?></p>
        </div>
    </div>
</body>
</html>

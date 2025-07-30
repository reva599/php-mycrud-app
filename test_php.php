<?php
echo "<h1>PHP Test Page</h1>";
echo "<p>Current time: " . date('Y-m-d H:i:s') . "</p>";
echo "<p>PHP Version: " . phpversion() . "</p>";

// Test database connection
echo "<h2>Database Connection Test</h2>";

try {
    require_once 'config/db.php';
    echo "<p style='color: green;'>✅ Database connection successful!</p>";
    
    // Test a simple query
    $result = $conn->query("SELECT COUNT(*) as count FROM users");
    if ($result) {
        $row = $result->fetch_assoc();
        echo "<p>Users in database: " . $row['count'] . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database error: " . $e->getMessage() . "</p>";
}

// Test includes
echo "<h2>Include Files Test</h2>";

if (file_exists('includes/header.php')) {
    echo "<p style='color: green;'>✅ header.php exists</p>";
} else {
    echo "<p style='color: red;'>❌ header.php missing</p>";
}

if (file_exists('includes/footer.php')) {
    echo "<p style='color: green;'>✅ footer.php exists</p>";
} else {
    echo "<p style='color: red;'>❌ footer.php missing</p>";
}

if (file_exists('includes/auth.php')) {
    echo "<p style='color: green;'>✅ auth.php exists</p>";
} else {
    echo "<p style='color: red;'>❌ auth.php missing</p>";
}

// Test functions
echo "<h2>Function Test</h2>";

if (function_exists('getMultipleResults')) {
    echo "<p style='color: green;'>✅ getMultipleResults function exists</p>";
} else {
    echo "<p style='color: red;'>❌ getMultipleResults function missing</p>";
}

phpinfo();
?>

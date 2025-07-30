<?php
/**
 * Admin Dashboard - Role-Based Access Control
 * PHP CRUD Blog Application - ApexPlanet Internship
 */

require_once '../includes/auth.php';
require_once '../config/db.php';

// Require admin role
requireRole(ROLE_ADMIN);

$page_title = "Admin Dashboard";
$errors = [];
$success = "";

// Handle role updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid security token. Please try again.';
    } else {
        switch ($_POST['action']) {
            case 'update_role':
                $userId = (int)($_POST['user_id'] ?? 0);
                $newRole = validateInput($_POST['role'] ?? '', 'string');
                
                $validRoles = [ROLE_SUBSCRIBER, ROLE_AUTHOR, ROLE_EDITOR, ROLE_ADMIN];
                if ($userId > 0 && in_array($newRole, $validRoles)) {
                    try {
                        $stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
                        $stmt->bind_param("si", $newRole, $userId);
                        $stmt->execute();
                        
                        // Log the action
                        $auditStmt = $conn->prepare("INSERT INTO audit_log (user_id, action, table_name, record_id, new_values, ip_address, user_agent) VALUES (?, 'role_update', 'users', ?, ?, ?, ?)");
                        $newValues = json_encode(['role' => $newRole]);
                        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
                        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
                        $auditStmt->bind_param("iisss", $_SESSION['user_id'], $userId, $newValues, $ip, $userAgent);
                        $auditStmt->execute();
                        
                        $success = "User role updated successfully!";
                    } catch (Exception $e) {
                        $errors[] = "Error updating user role: " . $e->getMessage();
                    }
                } else {
                    $errors[] = "Invalid user or role selection.";
                }
                break;
                
            case 'toggle_status':
                $userId = (int)($_POST['user_id'] ?? 0);
                $newStatus = validateInput($_POST['status'] ?? '', 'string');
                
                $validStatuses = [STATUS_ACTIVE, STATUS_INACTIVE, STATUS_BANNED];
                if ($userId > 0 && in_array($newStatus, $validStatuses)) {
                    try {
                        $stmt = $conn->prepare("UPDATE users SET status = ? WHERE id = ?");
                        $stmt->bind_param("si", $newStatus, $userId);
                        $stmt->execute();
                        
                        // Log the action
                        $auditStmt = $conn->prepare("INSERT INTO audit_log (user_id, action, table_name, record_id, new_values, ip_address, user_agent) VALUES (?, 'status_update', 'users', ?, ?, ?, ?)");
                        $newValues = json_encode(['status' => $newStatus]);
                        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
                        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
                        $auditStmt->bind_param("iisss", $_SESSION['user_id'], $userId, $newValues, $ip, $userAgent);
                        $auditStmt->execute();
                        
                        $success = "User status updated successfully!";
                    } catch (Exception $e) {
                        $errors[] = "Error updating user status: " . $e->getMessage();
                    }
                } else {
                    $errors[] = "Invalid user or status selection.";
                }
                break;
        }
    }
}

// Get all users with statistics
try {
    $sql = "SELECT u.id, u.username, u.email, u.role, u.status, u.created_at,
                   COUNT(p.id) as post_count,
                   MAX(p.created_at) as last_post_date
            FROM users u
            LEFT JOIN posts p ON u.id = p.author_id
            GROUP BY u.id, u.username, u.email, u.role, u.status, u.created_at
            ORDER BY u.created_at DESC";
    $users = getMultipleResults($conn, $sql);
} catch (Exception $e) {
    $errors[] = "Error fetching users: " . $e->getMessage();
    $users = [];
}

// Get system statistics
try {
    $stats = [];
    
    // User statistics
    $sql = "SELECT role, COUNT(*) as count FROM users GROUP BY role";
    $roleStats = getMultipleResults($conn, $sql);
    foreach ($roleStats as $stat) {
        $stats['roles'][$stat['role']] = $stat['count'];
    }
    
    // Post statistics
    $sql = "SELECT COUNT(*) as total_posts FROM posts";
    $result = getSingleResult($conn, $sql);
    $stats['total_posts'] = $result['total_posts'] ?? 0;
    
    // Recent activity
    $sql = "SELECT al.action, al.created_at, u.username 
            FROM audit_log al 
            LEFT JOIN users u ON al.user_id = u.id 
            ORDER BY al.created_at DESC 
            LIMIT 10";
    $stats['recent_activity'] = getMultipleResults($conn, $sql);
    
} catch (Exception $e) {
    $errors[] = "Error fetching statistics: " . $e->getMessage();
    $stats = [];
}

// Generate CSRF token
$csrfToken = generateCSRFToken();

include '../includes/header.php';
?>

<div class="container">
    <div class="admin-header">
        <h1><i class="fas fa-shield-alt"></i> Admin Dashboard</h1>
        <p>Manage users, roles, and system security</p>
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

    <?php if ($success): ?>
        <div class="flash-message success">
            <div class="flash-content">
                <i class="fas fa-check-circle"></i>
                <div><?php echo htmlspecialchars($success); ?></div>
            </div>
        </div>
    <?php endif; ?>

    <!-- System Statistics -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo array_sum($stats['roles'] ?? []); ?></h3>
                <p>Total Users</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-file-alt"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo $stats['total_posts'] ?? 0; ?></h3>
                <p>Total Posts</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-crown"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo $stats['roles'][ROLE_ADMIN] ?? 0; ?></h3>
                <p>Administrators</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-edit"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo $stats['roles'][ROLE_EDITOR] ?? 0; ?></h3>
                <p>Editors</p>
            </div>
        </div>
    </div>

    <!-- User Management -->
    <div class="admin-section">
        <h2><i class="fas fa-users-cog"></i> User Management</h2>
        
        <div class="table-container">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Posts</th>
                        <th>Joined</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo $user['id']; ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                            </td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td>
                                <span class="role-badge role-<?php echo $user['role']; ?>">
                                    <?php echo ucfirst($user['role']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="status-badge status-<?php echo $user['status']; ?>">
                                    <?php echo ucfirst($user['status']); ?>
                                </span>
                            </td>
                            <td><?php echo $user['post_count']; ?></td>
                            <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                            <td>
                                <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                    <div class="action-buttons">
                                        <!-- Role Update Form -->
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                            <input type="hidden" name="action" value="update_role">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <select name="role" onchange="this.form.submit()">
                                                <option value="<?php echo $user['role']; ?>" selected>
                                                    <?php echo ucfirst($user['role']); ?>
                                                </option>
                                                <?php foreach ([ROLE_SUBSCRIBER, ROLE_AUTHOR, ROLE_EDITOR, ROLE_ADMIN] as $role): ?>
                                                    <?php if ($role != $user['role']): ?>
                                                        <option value="<?php echo $role; ?>">
                                                            <?php echo ucfirst($role); ?>
                                                        </option>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            </select>
                                        </form>
                                        
                                        <!-- Status Toggle Form -->
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <select name="status" onchange="this.form.submit()">
                                                <option value="<?php echo $user['status']; ?>" selected>
                                                    <?php echo ucfirst($user['status']); ?>
                                                </option>
                                                <?php foreach ([STATUS_ACTIVE, STATUS_INACTIVE, STATUS_BANNED] as $status): ?>
                                                    <?php if ($status != $user['status']): ?>
                                                        <option value="<?php echo $status; ?>">
                                                            <?php echo ucfirst($status); ?>
                                                        </option>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            </select>
                                        </form>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted">Current User</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="admin-section">
        <h2><i class="fas fa-history"></i> Recent Activity</h2>
        
        <div class="activity-list">
            <?php foreach ($stats['recent_activity'] ?? [] as $activity): ?>
                <div class="activity-item">
                    <div class="activity-icon">
                        <i class="fas fa-<?php echo getActivityIcon($activity['action']); ?>"></i>
                    </div>
                    <div class="activity-content">
                        <strong><?php echo htmlspecialchars($activity['username'] ?? 'System'); ?></strong>
                        <?php echo getActivityDescription($activity['action']); ?>
                        <span class="activity-time">
                            <?php echo timeAgo($activity['created_at']); ?>
                        </span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php
// Helper functions for activity display
function getActivityIcon($action) {
    $icons = [
        'login' => 'sign-in-alt',
        'logout' => 'sign-out-alt',
        'register' => 'user-plus',
        'role_update' => 'user-cog',
        'status_update' => 'user-shield',
        'failed_login' => 'exclamation-triangle'
    ];
    return $icons[$action] ?? 'info-circle';
}

function getActivityDescription($action) {
    $descriptions = [
        'login' => 'logged in',
        'logout' => 'logged out',
        'register' => 'registered',
        'role_update' => 'role was updated',
        'status_update' => 'status was updated',
        'failed_login' => 'failed login attempt'
    ];
    return $descriptions[$action] ?? 'performed an action';
}

function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    if ($time < 60) return 'just now';
    if ($time < 3600) return floor($time/60) . ' minutes ago';
    if ($time < 86400) return floor($time/3600) . ' hours ago';
    return floor($time/86400) . ' days ago';
}

include '../includes/footer.php';
?>

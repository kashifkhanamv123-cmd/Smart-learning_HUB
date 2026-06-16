<?php
$pageTitle = 'Manage Users';
require_once dirname(__DIR__) . '/auth.php';
require_once dirname(__DIR__) . '/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';

// Enforce admin role
require_admin();

$adminUserId = $_SESSION['user_id'];

// Handle Role Toggling
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_role') {
    $targetUserId = intval($_POST['user_id']);
    
    if ($targetUserId === $adminUserId) {
        setFlash('error', 'Action Denied: You cannot modify your own administrative role.');
    } else {
        try {
            // Check current role
            $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
            $stmt->execute([$targetUserId]);
            $currentRole = $stmt->fetchColumn();
            
            if ($currentRole) {
                $newRole = ($currentRole === 'admin') ? 'student' : 'admin';
                $update = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
                $update->execute([$newRole, $targetUserId]);
                setFlash('success', 'User role updated successfully.');
            }
        } catch (PDOException $e) {
            setFlash('error', 'Database error changing role: ' . $e->getMessage());
        }
    }
    header("Location: users.php");
    exit();
}

// Handle User Deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_user') {
    $targetUserId = intval($_POST['user_id']);
    
    if ($targetUserId === $adminUserId) {
        setFlash('error', 'Action Denied: You cannot delete your own administrative account.');
    } else {
        try {
            $delete = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $delete->execute([$targetUserId]);
            setFlash('success', 'User profile deleted successfully.');
        } catch (PDOException $e) {
            setFlash('error', 'Database error deleting user: ' . $e->getMessage());
        }
    }
    header("Location: users.php");
    exit();
}

// Fetch all users
$users = $pdo->query("SELECT id, name, email, role, created_at FROM users ORDER BY role ASC, name ASC")->fetchAll();

require_once dirname(__DIR__) . '/includes/header.php';
?>

<div class="page-header" style="display:flex; justify-content:space-between; align-items:center;">
    <div>
        <h1>Manage Student Profiles</h1>
        <p>Promote profiles to administrative levels or delete inactive accounts.</p>
    </div>
    <a href="index.php" class="btn btn-secondary btn-sm"><i class="fa-solid fa-arrow-left"></i> Back to Panel</a>
</div>

<div class="glass-card">
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Avatar</th>
                    <th>Full Name</th>
                    <th>Email Address</th>
                    <th>System Role</th>
                    <th>Registration Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <?php $isSelf = ($user['id'] === $adminUserId); ?>
                    <tr>
                        <td>
                            <div class="avatar" style="width: 32px; height: 32px; font-size: 0.8rem;">
                                <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                            </div>
                        </td>
                        <td style="color:#fff; font-weight:500;">
                            <?php echo htmlspecialchars($user['name']); ?> <?php echo $isSelf ? '<span style="font-size:0.75rem; color:var(--text-dark);">(You)</span>' : ''; ?>
                        </td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td>
                            <span class="user-role-badge <?php echo $user['role'] === 'admin' ? 'role-admin' : 'role-student'; ?>">
                                <?php echo ucfirst($user['role']); ?>
                            </span>
                        </td>
                        <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                        <td>
                            <?php if (!$isSelf): ?>
                                <div class="action-buttons">
                                    <!-- Toggle Role Form -->
                                    <form action="users.php" method="POST" style="display:inline;">
                                        <input type="hidden" name="action" value="toggle_role">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <button type="submit" class="action-btn promote" title="Toggle Admin Role">
                                            <i class="fa-solid fa-user-shield"></i>
                                        </button>
                                    </form>
                                    
                                    <!-- Delete Profile Form -->
                                    <form action="users.php" method="POST" onsubmit="return confirm('Are you sure you want to permanently delete this user? All their study logs and notes will be lost.');" style="display:inline;">
                                        <input type="hidden" name="action" value="delete_user">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <button type="submit" class="action-btn delete" title="Delete User">
                                            <i class="fa-solid fa-user-xmark"></i>
                                        </button>
                                    </form>
                                </div>
                            <?php else: ?>
                                <span style="font-size: 0.8rem; color:var(--text-dark); font-style:italic;">No Actions Available</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>

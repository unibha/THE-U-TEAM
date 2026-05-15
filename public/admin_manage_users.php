<?php
require_once '../includes/auth_middleware.php';
require_once '../includes/db.php';

// Only Admin allowed
checkAuth(['Admin']);

$pageTitle = "Manage Users - Admin Portal";
include_once '../includes/header.php';

$message = '';
$error = '';

// Handle Delete
if (isset($_GET['delete_id'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$_GET['delete_id']]);
        $message = "User deleted successfully!";
    } catch (PDOException $e) {
        $error = "Error deleting user: " . $e->getMessage();
    }
}

// Fetch all users with their role-specific data
$roleFilter = $_GET['role'] ?? '';
$allowedRoles = ['Student', 'Teacher'];
$queryRole = in_array($roleFilter, $allowedRoles) ? $roleFilter : '';

try {
    $sql = "
        SELECT u.*, s.class_name, t.department 
        FROM users u 
        LEFT JOIN students s ON u.id = s.user_id 
        LEFT JOIN teachers t ON u.id = t.user_id 
        WHERE u.role != 'Admin'
    ";
    
    if ($queryRole) {
        $sql .= " AND u.role = :role";
    }
    
    $sql .= " ORDER BY u.created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    if ($queryRole) {
        $stmt->execute(['role' => $queryRole]);
    } else {
        $stmt->execute();
    }
    $users = $stmt->fetchAll();
} catch (PDOException $e) {
    $users = [];
    $error = "Error fetching users: " . $e->getMessage();
}
?>

<div class="dashboard-container">
    <!-- Top Gradient Header -->
    <header class="dashboard-header">
        <div class="header-brand">
            <h1>Academic Management System</h1>
            <p>Admin Control Panel</p>
        </div>
        <div class="header-tools">
            <div class="search-wrapper">
                <i class="fas fa-search"></i>
                <input type="text" id="userSearch" placeholder="Search by name, email or role...">
            </div>
            <div class="header-icons" style="margin-left: 20px; display: flex; gap: 15px; align-items: center;">
                <a href="admin_dashboard.php" style="color: #fff; text-decoration: none; font-weight: 700; font-size: 0.9rem; padding: 8px 16px; border: 1px solid rgba(255,255,255,0.3); border-radius: 12px; transition: 0.3s ease; background: rgba(255,255,255,0.1);"><i class="fas fa-th-large" style="margin-right: 8px;"></i>Dashboard</a>
                <a href="logout.php" class="header-logout" style="color: #fff; text-decoration: none; font-weight: 700; font-size: 0.9rem; padding: 8px 16px; border: 1px solid rgba(255,255,255,0.3); border-radius: 12px;">Logout</a>
            </div>
        </div>
    </header>

    <main class="main-content">
        <section class="welcome-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
            <div>
                <h2 style="font-size: 1.8rem; color: #1e293b; font-weight: 800;">
                    <?php 
                    if ($queryRole == 'Student') echo "Student Management";
                    elseif ($queryRole == 'Teacher') echo "Teacher Management";
                    else echo "User Management";
                    ?>
                </h2>
                <p style="color: #64748b;">Oversee and manage <?php echo $queryRole ? strtolower($queryRole)."s" : "all members"; ?> in the system.</p>
            </div>
            <a href="admin_add_user.php<?php echo $queryRole ? '?role='.$queryRole : ''; ?>" class="btn-primary" style="padding: 12px 24px; text-decoration: none; border-radius: 12px; background: var(--brand-gradient); color: #fff; font-weight: 700; transition: all 0.3s ease;">
                <i class="fas fa-user-plus" style="margin-right: 8px;"></i> Add New <?php echo $queryRole ?: 'User'; ?>
            </a>
        </section>

        <?php if ($message): ?>
            <div style="background: #dcfce7; color: #166534; padding: 15px; border-radius: 12px; margin-bottom: 20px;">
                <i class="fas fa-check-circle" style="margin-right: 10px;"></i> <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div style="background: #fee2e2; color: #991b1b; padding: 15px; border-radius: 12px; margin-bottom: 20px;">
                <i class="fas fa-exclamation-circle" style="margin-right: 10px;"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <div class="table-container" style="background: #fff; border-radius: 20px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); overflow: hidden;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead style="background: #f8fafc;">
                    <tr>
                        <th style="padding: 20px; text-align: left; color: #64748b; font-weight: 700; text-transform: uppercase; font-size: 0.8rem; border-bottom: 2px solid #f1f5f9;">User</th>
                        <th style="padding: 20px; text-align: left; color: #64748b; font-weight: 700; text-transform: uppercase; font-size: 0.8rem; border-bottom: 2px solid #f1f5f9;">Role</th>
                        <th style="padding: 20px; text-align: left; color: #64748b; font-weight: 700; text-transform: uppercase; font-size: 0.8rem; border-bottom: 2px solid #f1f5f9;">Details</th>
                        <th style="padding: 20px; text-align: left; color: #64748b; font-weight: 700; text-transform: uppercase; font-size: 0.8rem; border-bottom: 2px solid #f1f5f9;">Status</th>
                        <th style="padding: 20px; text-align: center; color: #64748b; font-weight: 700; text-transform: uppercase; font-size: 0.8rem; border-bottom: 2px solid #f1f5f9;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr><td colspan="5" style="padding: 40px; text-align: center; color: #94a3b8;">No records found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($users as $user): ?>
                        <tr style="border-bottom: 1px solid #f1f5f9; transition: all 0.2s ease;">
                            <td style="padding: 20px;">
                                <div style="display: flex; flex-direction: column;">
                                    <span style="font-weight: 800; color: #1e293b;"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></span>
                                    <span style="font-size: 0.85rem; color: #64748b;"><?php echo htmlspecialchars($user['email']); ?></span>
                                </div>
                            </td>
                            <td style="padding: 20px;">
                                <span style="background: <?php echo ($user['role'] == 'Teacher') ? '#e0f2fe' : '#fef3c7'; ?>; 
                                              color: <?php echo ($user['role'] == 'Teacher') ? '#0369a1' : '#b45309'; ?>; 
                                              padding: 6px 12px; border-radius: 8px; font-weight: 700; font-size: 0.8rem;">
                                    <?php echo $user['role']; ?>
                                </span>
                            </td>
                            <td style="padding: 20px;">
                                <span style="font-size: 0.9rem; color: #475569;">
                                    <?php 
                                    if ($user['role'] == 'Student') {
                                        echo "Class: " . ($user['class_name'] ? htmlspecialchars($user['class_name']) : "<span style='color: #94a3b8; font-style: italic;'>Unassigned</span>");
                                    } else if ($user['role'] == 'Teacher') {
                                        echo "Dept: " . ($user['department'] ? htmlspecialchars($user['department']) : "<span style='color: #94a3b8; font-style: italic;'>Unassigned</span>");
                                    }
                                    ?>
                                </span>
                            </td>
                            <td style="padding: 20px;">
                                <span style="display: flex; align-items: center; gap: 8px;">
                                    <div style="width: 8px; height: 8px; border-radius: 50%; background: <?php echo $user['is_active'] ? '#22c55e' : '#94a3b8'; ?>;"></div>
                                    <span style="font-size: 0.9rem; color: #475569;"><?php echo $user['is_active'] ? 'Active' : 'Pending'; ?></span>
                                </span>
                            </td>
                            <td style="padding: 20px; text-align: center;">
                                <div style="display: flex; justify-content: center; gap: 10px;">
                                    <a href="admin_edit_user.php?id=<?php echo $user['id']; ?>" class="action-btn" style="color: #6366f1; font-size: 1.1rem;" title="Edit User"><i class="fas fa-edit"></i></a>
                                    <a href="?delete_id=<?php echo $user['id']; ?>" class="action-btn" style="color: #f43f5e; font-size: 1.1rem;" onclick="return confirm('Are you sure you want to delete this user?')" title="Delete User"><i class="fas fa-trash-alt"></i></a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<?php include_once '../includes/footer.php'; ?>

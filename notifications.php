<?php
require_once '../includes/auth_middleware.php';
require_once '../includes/db.php';
require_once '../includes/notification_helper.php';

checkAuth(['Admin', 'Teacher', 'Student']);

$myId = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Handle AJAX read (for urgent popup)
if (isset($_GET['ajax_read'])) {
    $stmt = $pdo->prepare("UPDATE notification SET is_read = 1 WHERE id = ? AND user_id = ?");
    $stmt->execute([$_GET['ajax_read'], $myId]);
    exit();
}

// Handle Mark All Read
if (isset($_GET['read_all'])) {
    $stmt = $pdo->prepare("UPDATE notification SET is_read = 1 WHERE user_id = ?");
    $stmt->execute([$myId]);
    header("Location: notifications.php");
    exit();
}

// Handle Mark Single Read
if (isset($_GET['read'])) {
    $stmt = $pdo->prepare("UPDATE notification SET is_read = 1 WHERE id = ? AND user_id = ?");
    $stmt->execute([$_GET['read'], $myId]);
    header("Location: notifications.php");
    exit();
}

// Handle Admin Delete
if (isset($_GET['delete_id']) && $role === 'Admin') {
    $stmt = $pdo->prepare("DELETE FROM notification WHERE id = ?");
    $stmt->execute([$_GET['delete_id']]);
    header("Location: notifications.php?msg=Deleted");
    exit();
}

// Handle Admin Clear All
if (isset($_GET['clear_all']) && $role === 'Admin') {
    $pdo->exec("TRUNCATE TABLE notification");
    header("Location: notifications.php?msg=Cleared");
    exit();
}

$pageTitle = "My Notifications";
include_once '../includes/header.php';

// Fetch all notifications for this user
$stmt = $pdo->prepare("SELECT * FROM notification WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$myId]);
$allNotifs = $stmt->fetchAll();
?>

<div class="dashboard-container" style="flex-direction: column;">
    <header class="dashboard-header">
        <div class="header-brand">
            <h1>Academic Management System</h1>
            <p>Institutional Portal > Notifications Center</p>
        </div>
        <div class="header-icons" style="margin-left: 20px; display: flex; gap: 15px; align-items: center;">
            <a href="<?php 
                if($role == 'Admin') echo 'admin_dashboard.php';
                elseif($role == 'Teacher') echo 'teacher_dashboard.php';
                else echo 'student_dashboard.php';
            ?>" style="color: #fff; text-decoration: none; font-weight: 700; font-size: 0.9rem; padding: 8px 16px; border: 1px solid rgba(255,255,255,0.3); border-radius: 12px;"><i class="fas fa-th-large" style="margin-right: 8px;"></i>Dashboard</a>
        </div>
    </header>

    <main class="main-content" style="padding: 40px 60px; background: #f8fafc;">
        <div style="max-width: 800px; margin: 0 auto;">
            
            <section style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
                <h2 style="font-size: 1.8rem; color: #1e293b; font-weight: 800;">My Notifications</h2>
                <?php if (!empty($allNotifs)): ?>
                    <a href="?read_all=1" style="color: #8b5cf6; text-decoration: none; font-weight: 700; font-size: 0.9rem;"><i class="fas fa-check-double" style="margin-right: 8px;"></i>Mark all as read</a>
                <?php endif; ?>
            </section>

            <?php if (empty($allNotifs)): ?>
                <div style="background: #fff; padding: 100px; border-radius: 35px; text-align: center; border: 2px dashed #e2e8f0; box-shadow: 0 10px 30px rgba(0,0,0,0.02);">
                    <i class="fas fa-bell-slash" style="font-size: 4rem; color: #cbd5e1; margin-bottom: 25px;"></i>
                    <h3 style="color: #64748b; font-weight: 800; font-size: 1.4rem;">Inbox Empty</h3>
                    <p style="color: #94a3b8; font-weight: 600;">You don't have any notifications at the moment.</p>
                </div>
            <?php else: ?>
                <div style="display: flex; flex-direction: column; gap: 15px;">
                    <?php foreach ($allNotifs as $n): ?>
                        <div style="background: #fff; padding: 25px 30px; border-radius: 24px; box-shadow: 0 4px 15px rgba(0,0,0,0.02); border: 1px solid <?php echo $n['is_read'] ? '#f1f5f9' : '#e0f2fe'; ?>; position: relative; transition: transform 0.2s;">
                            <?php if (!$n['is_read']): ?>
                                <div style="position: absolute; top: 25px; right: 30px; width: 10px; height: 10px; background: #3b82f6; border-radius: 50%;"></div>
                            <?php endif; ?>

                            <div style="margin-bottom: 10px;">
                                <span style="font-size: 0.7rem; color: #94a3b8; font-weight: 800; text-transform: uppercase; letter-spacing: 1px;"><?php echo $n['type']; ?> Notification • <?php echo timeAgo($n['created_at']); ?></span>
                                <h3 style="font-size: 1.1rem; color: #1e293b; font-weight: 800; margin: 5px 0;"><?php echo htmlspecialchars($n['title']); ?></h3>
                            </div>
                            
                            <p style="color: #64748b; font-weight: 500; line-height: 1.6; margin-bottom: 20px; font-size: 0.95rem;"><?php echo htmlspecialchars($n['message']); ?></p>

                            <?php if (!$n['is_read']): ?>
                                <a href="?read=<?php echo $n['id']; ?>" style="color: #3b82f6; text-decoration: none; font-weight: 800; font-size: 0.85rem;">Mark as read</a>
                            <?php else: ?>
                                <span style="color: #cbd5e1; font-weight: 700; font-size: 0.85rem;"><i class="fas fa-check" style="margin-right: 5px;"></i>Seen</span>
                            <?php endif; ?>

                            <?php if ($role === 'Admin'): ?>
                                <a href="?delete_id=<?php echo $n['id']; ?>" style="margin-left: 20px; color: #f43f5e; text-decoration: none; font-weight: 800; font-size: 0.85rem;" onclick="return confirm('Permanently remove this notification?')"><i class="fas fa-trash-alt"></i> Delete</a>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($role === 'Admin' && !empty($allNotifs)): ?>
                <div style="margin-top: 40px; text-align: center;">
                    <a href="?clear_all=1" style="color: #f43f5e; font-weight: 700; text-decoration: none; font-size: 0.9rem; border: 2px solid #fecaca; padding: 10px 25px; border-radius: 12px; transition: 0.3s;" onclick="return confirm('Wipe ALL system notifications? This cannot be undone.')"><i class="fas fa-broom" style="margin-right: 8px;"></i>Master Clear System Log</a>
                </div>
            <?php endif; ?>

        </div>
    </main>
</div>

<?php include_once '../includes/footer.php'; ?>

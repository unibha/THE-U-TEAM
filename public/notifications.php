<?php
require_once '../includes/auth_middleware.php';
require_once '../includes/db.php';

// All logged in users can view notifications
checkAuth(['Admin', 'Teacher', 'Student']);

$pageTitle = "Notifications Hub - Academic Management System";
include_once '../includes/header.php';

$role = $_SESSION['role'];
$userId = $_SESSION['user_id'];
$message = '';

// Handle Actions
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    $nid = $_GET['id'] ?? null;

    try {
        if ($action === 'mark_read' && $nid) {
            $stmt = $pdo->prepare("UPDATE notification SET is_read = 1 WHERE id = ? AND (user_id = ? OR ? = 'Admin')");
            $stmt->execute([$nid, $userId, $role]);
        } elseif ($action === 'mark_unread' && $nid) {
            $stmt = $pdo->prepare("UPDATE notification SET is_read = 0 WHERE id = ? AND (user_id = ? OR ? = 'Admin')");
            $stmt->execute([$nid, $userId, $role]);
        } elseif ($action === 'delete' && $nid) {
            $stmt = $pdo->prepare("DELETE FROM notification WHERE id = ? AND (user_id = ? OR ? = 'Admin')");
            $stmt->execute([$nid, $userId, $role]);
            $message = "Notification removed.";
        } elseif ($action === 'clear_all' && $role === 'Admin') {
            $pdo->exec("DELETE FROM notification");
            $message = "All notifications cleared from system.";
        }
    } catch (PDOException $e) {
        $error = "Action failed: " . $e->getMessage();
    }
}

// Fetch Notifications
try {
    if ($role === 'Admin' && isset($_GET['view']) && $_GET['view'] === 'all') {
        // Admin Master View
        $stmt = $pdo->query("
            SELECT n.*, u.first_name, u.last_name, u.role as user_role 
            FROM notification n 
            JOIN users u ON n.user_id = u.id 
            ORDER BY n.created_at DESC
        ");
        $notifications = $stmt->fetchAll();
        $isMasterView = true;
    } else {
        // Personal View
        $stmt = $pdo->prepare("SELECT * FROM notification WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->execute([$userId]);
        $notifications = $stmt->fetchAll();
        $isMasterView = false;
    }
} catch (PDOException $e) {
    $notifications = [];
}

// Helper for type styling
function getTypeStyles($type) {
    switch ($type) {
        case 'Academic': return ['icon' => 'fas fa-book', 'color' => '#8b5cf6'];
        case 'Security': return ['icon' => 'fas fa-shield-alt', 'color' => '#f43f5e'];
        case 'Attendance': return ['icon' => 'fas fa-calendar-check', 'color' => '#10b981'];
        default: return ['icon' => 'fas fa-info-circle', 'color' => '#3b82f6'];
    }
}
?>

<div class="dashboard-container" style="flex-direction: column;">
    <header class="dashboard-header">
        <div class="header-brand">
            <h1>Academic Management System</h1>
            <p>User Portal > <?php echo $isMasterView ? "System Audit Log" : "My Notifications"; ?></p>
        </div>
        <div class="header-icons" style="margin-left: 20px; display: flex; gap: 15px; align-items: center;">
            <?php if ($role === 'Admin'): ?>
                <a href="?view=<?php echo $isMasterView ? 'personal' : 'all'; ?>" style="color: #fff; text-decoration: none; font-weight: 700; font-size: 0.85rem; padding: 8px 16px; border: 1px solid #8b5cf6; border-radius: 12px; background: #8b5cf6;">
                    <?php echo $isMasterView ? "Switch to My Notifications" : "View All Notifications (Admin)"; ?>
                </a>
            <?php endif; ?>
            <a href="<?php 
                if($role == 'Admin') echo 'admin_dashboard.php';
                elseif($role == 'Teacher') echo 'teacher_dashboard.php';
                else echo 'student_dashboard.php';
            ?>" style="color: #fff; text-decoration: none; font-weight: 700; font-size: 0.9rem; padding: 8px 16px; border: 1px solid rgba(255,255,255,0.3); border-radius: 12px;"><i class="fas fa-th-large" style="margin-right: 8px;"></i>Dashboard</a>
        </div>
    </header>

    <main class="main-content" style="padding: 40px 60px; background: #f8fafc;">
        <div style="max-width: 1000px; margin: 0 auto;">
            
            <section style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 40px;">
                <div>
                    <h2 style="font-size: 2.2rem; color: #1e293b; font-weight: 800;"><?php echo $isMasterView ? "Global Notification Audit" : "Recent Activity"; ?></h2>
                    <p style="color: #64748b; font-weight: 600;"><?php echo $isMasterView ? "Monitoring all communications sent across the platform." : "Stay updated with important system notifications and academic announcements."; ?></p>
                </div>
                <?php if ($isMasterView && $role === 'Admin'): ?>
                    <a href="?action=clear_all" onclick="return confirm('Erase ALL notifications from the system? This cannot be undone.')" style="background: #fee2e2; color: #991b1b; padding: 12px 25px; border-radius: 12px; text-decoration: none; font-weight: 800; font-size: 0.9rem; border: 1px solid #fecaca;">Clear System History</a>
                <?php endif; ?>
            </section>

            <?php if ($message): ?> <div style="background: #dcfce7; color: #166534; padding: 15px; border-radius: 12px; margin-bottom: 30px; font-weight: 700;"><i class="fas fa-check-circle" style="margin-right: 10px;"></i> <?php echo $message; ?></div> <?php endif; ?>

            <div class="notification-list" style="display: flex; flex-direction: column; gap: 20px;">
                <?php if (empty($notifications)): ?>
                    <div style="background: #fff; padding: 60px; border-radius: 30px; text-align: center; border: 2px dashed #e2e8f0;">
                        <i class="fas fa-bell-slash" style="font-size: 3rem; color: #cbd5e1; margin-bottom: 20px;"></i>
                        <p style="color: #64748b; font-weight: 700; font-size: 1.1rem;">No notifications found.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($notifications as $n): 
                        $styles = getTypeStyles($n['type']);
                        $isRead = $n['is_read'];
                    ?>
                        <div style="background: #fff; padding: 25px; border-radius: 24px; box-shadow: 0 4px 20px rgba(0,0,0,0.02); border: 1px solid <?php echo $isRead ? '#f1f5f9' : '#e0f2fe'; ?>; display: flex; gap: 25px; align-items: flex-start; transition: 0.3s ease; position: relative; <?php echo !$isRead ? 'border-left: 6px solid '.$styles['color'].';' : ''; ?>">
                            <div style="width: 55px; height: 55px; background: <?php echo $styles['color']; ?>10; color: <?php echo $styles['color']; ?>; border-radius: 18px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; flex-shrink: 0;">
                                <i class="<?php echo $styles['icon']; ?>"></i>
                            </div>
                            <div style="flex-grow: 1;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                                    <div>
                                        <h4 style="font-weight: 800; color: #1e293b; margin: 0; font-size: 1.1rem;">
                                            <?php echo htmlspecialchars($n['title']); ?>
                                            <?php if (!$isRead): ?> <span style="background: #8b5cf6; color: #fff; font-size: 0.65rem; padding: 2px 8px; border-radius: 10px; margin-left: 10px; vertical-align: middle;">NEW</span> <?php endif; ?>
                                        </h4>
                                        <?php if ($isMasterView): ?>
                                            <p style="margin: 5px 0 0; font-size: 0.8rem; color: #64748b;">To: <strong style="color: #1e293b;"><?php echo htmlspecialchars($n['first_name'] . ' ' . $n['last_name']); ?></strong> (<?php echo $n['user_role']; ?>)</p>
                                        <?php endif; ?>
                                    </div>
                                    <span style="font-size: 0.85rem; color: #94a3b8; font-weight: 700;"><?php echo date('M d, Y | h:i A', strtotime($n['created_at'])); ?></span>
                                </div>
                                <p style="color: #475569; line-height: 1.7; margin-bottom: 15px; font-size: 0.95rem;"><?php echo nl2br(htmlspecialchars($n['message'])); ?></p>
                                
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <span style="font-size: 0.7rem; color: <?php echo $styles['color']; ?>; font-weight: 800; text-transform: uppercase; background: <?php echo $styles['color']; ?>10; padding: 5px 12px; border-radius: 20px; letter-spacing: 0.5px;"><?php echo $n['type']; ?></span>
                                    
                                    <div style="display: flex; gap: 15px;">
                                        <?php if (!$isRead): ?>
                                            <a href="?action=mark_read&id=<?php echo $n['id']; ?>&view=<?php echo $isMasterView ? 'all' : 'personal'; ?>" title="Mark as Read" style="color: #64748b;"><i class="fas fa-check"></i></a>
                                        <?php else: ?>
                                            <a href="?action=mark_unread&id=<?php echo $n['id']; ?>&view=<?php echo $isMasterView ? 'all' : 'personal'; ?>" title="Mark as Unread" style="color: #64748b;"><i class="fas fa-envelope"></i></a>
                                        <?php endif; ?>
                                        <a href="?action=delete&id=<?php echo $n['id']; ?>&view=<?php echo $isMasterView ? 'all' : 'personal'; ?>" title="Delete" style="color: #f43f5e;" onclick="return confirm('Delete notification?')"><i class="fas fa-trash-alt"></i></a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <div style="text-align: center; margin-top: 50px;">
                <p style="color: #94a3b8; font-style: italic; font-size: 0.9rem;">The notifications audit log maintains system transparency.</p>
            </div>
        </div>
    </main>
</div>

<?php include_once '../includes/footer.php'; ?>

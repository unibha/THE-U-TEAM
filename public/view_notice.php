<?php
require_once '../includes/auth_middleware.php';
require_once '../includes/db.php';

// All logged in users can view notices
checkAuth(['Admin', 'Teacher', 'Student']);

$pageTitle = "Notice Board - Academic Management System";
include_once '../includes/header.php';

$role = $_SESSION['role'];

// Fetch notices based on audience
try {
    if ($role === 'Admin') {
        $stmt = $pdo->query("SELECT * FROM notice ORDER BY created_at DESC");
    } else {
        $stmt = $pdo->prepare("SELECT * FROM notice WHERE target_audience = 'All' OR target_audience = ? ORDER BY created_at DESC");
        $stmt->execute([$role]);
    }
    $notices = $stmt->fetchAll();
} catch (PDOException $e) {
    $notices = [];
}
?>

<div class="dashboard-container" style="flex-direction: column;">
    <header class="dashboard-header">
        <div class="header-brand">
            <h1>Academic Management System</h1>
            <p>User Portal > Digital Noticeboard</p>
        </div>
        <div class="header-icons" style="margin-left: 20px; display: flex; gap: 15px; align-items: center;">
            <?php if ($role === 'Admin'): ?>
                <a href="manage_notice.php" style="color: #fff; text-decoration: none; font-weight: 700; font-size: 0.9rem; padding: 8px 16px; border: 1px solid #8b5cf6; border-radius: 12px; background: #8b5cf6;">Manage Notices</a>
            <?php endif; ?>
            <a href="<?php 
                if($role == 'Admin') echo 'admin_dashboard.php';
                elseif($role == 'Teacher') echo 'teacher_dashboard.php';
                else echo 'student_dashboard.php';
            ?>" style="color: #fff; text-decoration: none; font-weight: 700; font-size: 0.9rem; padding: 8px 16px; border: 1px solid rgba(255,255,255,0.3); border-radius: 12px;"><i class="fas fa-th-large" style="margin-right: 8px;"></i>Dashboard</a>
        </div>
    </header>

    <main class="main-content" style="padding: 40px 60px; background: #f8fafc;">
        <div style="max-width: 900px; margin: 0 auto;">
            <section class="welcome-header" style="margin-bottom: 50px; text-align: center;">
                <h2 style="font-size: 2.2rem; color: #1e293b; font-weight: 800;">Official Announcements</h2>
                <p style="color: #64748b; font-weight: 600;">Stay informed with the latest updates from the academy.</p>
            </section>

            <div class="notice-list" style="display: flex; flex-direction: column; gap: 30px;">
                <?php if (empty($notices)): ?>
                    <div style="background: #fff; padding: 80px; border-radius: 30px; text-align: center; border: 2px dashed #e2e8f0; box-shadow: 0 10px 30px rgba(0,0,0,0.02);">
                        <i class="fas fa-bullhorn" style="font-size: 4rem; color: #cbd5e1; margin-bottom: 25px;"></i>
                        <p style="color: #64748b; font-weight: 700; font-size: 1.2rem;">No notices currently posted.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($notices as $n): 
                        $color = '#3b82f6'; // General
                        if ($n['category'] === 'Urgent') $color = '#f43f5e';
                        elseif ($n['category'] === 'Academic') $color = '#8b5cf6';
                    ?>
                        <div style="background: #fff; padding: 35px; border-radius: 28px; box-shadow: 0 10px 40px rgba(0,0,0,0.03); border: 1px solid #f1f5f9; position: relative; overflow: hidden; transition: transform 0.2s ease;">
                            <div style="position: absolute; top: 0; left: 0; width: 6px; height: 100%; background: <?php echo $color; ?>;"></div>
                            
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px;">
                                <div style="display: flex; flex-direction: column; gap: 8px;">
                                    <span style="font-size: 0.75rem; color: <?php echo $color; ?>; font-weight: 800; text-transform: uppercase; letter-spacing: 1px;"><?php echo $n['category']; ?> Notice</span>
                                    <h3 style="font-size: 1.6rem; color: #1e293b; font-weight: 800; margin: 0;"><?php echo htmlspecialchars($n['title']); ?></h3>
                                </div>
                                <div style="text-align: right;">
                                    <span style="display: block; font-size: 0.9rem; color: #94a3b8; font-weight: 700;"><?php echo date('M d, Y', strtotime($n['created_at'])); ?></span>
                                    <span style="font-size: 0.75rem; color: #64748b; font-weight: 600;">Posted <?php echo date('h:i A', strtotime($n['created_at'])); ?></span>
                                </div>
                            </div>
                            
                            <div style="color: #475569; line-height: 1.8; font-size: 1.05rem; white-space: pre-wrap;"><?php echo htmlspecialchars($n['content']); ?></div>
                            
                            <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center;">
                                <div style="display: flex; align-items: center; gap: 10px; color: #94a3b8;">
                                    <i class="fas fa-users" style="font-size: 0.8rem;"></i>
                                    <span style="font-size: 0.8rem; font-weight: 700;">Audience: <?php echo $n['target_audience']; ?></span>
                                </div>
                                <div style="font-size: 0.85rem; color: #1e293b; font-weight: 800; opacity: 0.7;">
                                    <i class="fas fa-check-circle" style="color: #10b981; margin-right: 5px;"></i> Official Announcement
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<?php include_once '../includes/footer.php'; ?>

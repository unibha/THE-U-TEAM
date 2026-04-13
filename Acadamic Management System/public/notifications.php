<?php
require_once '../includes/auth_middleware.php';
require_once '../includes/db.php';

// All logged in users can view notifications
checkAuth(['Admin', 'Teacher', 'Student']);

$pageTitle = "Notifications - Academic Management System";
include_once '../includes/header.php';

$role = $_SESSION['role'];
$firstName = $_SESSION['first_name'];

// Simulated notifications for now
$notifications = [
    [
        'title' => 'Welcome to Academic Portal!',
        'message' => "Hello $firstName, your account is now fully active. You can manage your courses and profile through the dashboard.",
        'date' => date('M d, Y'),
        'type' => 'System',
        'icon' => 'fas fa-info-circle',
        'color' => '#3b82f6'
    ],
    [
        'title' => 'Security Update',
        'message' => 'Please ensure you use a strong password and multi-character validation for added safety.',
        'date' => date('M d, Y', strtotime('-1 day')),
        'type' => 'Security',
        'icon' => 'fas fa-shield-alt',
        'color' => '#f43f5e'
    ]
];

if ($role === 'Teacher') {
    $notifications[] = [
        'title' => 'Course Management Enabled',
        'message' => 'You can now publish new assignments and mark attendance for your assigned modules.',
        'date' => date('M d, Y', strtotime('-2 days')),
        'type' => 'Academic',
        'icon' => 'fas fa-book',
        'color' => '#8b5cf6'
    ];
}
?>

<div class="dashboard-container" style="flex-direction: column;">
    <header class="dashboard-header">
        <div class="header-brand">
            <h1>Academic Management System</h1>
            <p>User Portal > Notifications Hub</p>
        </div>
        <div class="header-icons" style="margin-left: 20px; display: flex; gap: 15px; align-items: center;">
            <a href="<?php 
                if($role == 'Admin') echo 'admin_dashboard.php';
                elseif($role == 'Teacher') echo 'teacher_dashboard.php';
                else echo 'student_dashboard.php';
            ?>" style="color: #fff; text-decoration: none; font-weight: 700; font-size: 0.9rem; padding: 8px 16px; border: 1px solid rgba(255,255,255,0.3); border-radius: 12px; transition: 0.3s ease; background: rgba(255,255,255,0.1);"><i class="fas fa-th-large" style="margin-right: 8px;"></i>Dashboard</a>
            <a href="logout.php" style="color: #fff; text-decoration: none; font-weight: 700; font-size: 0.9rem; padding: 8px 16px; border: 1px solid rgba(255,255,255,0.3); border-radius: 12px;">Logout</a>
        </div>
    </header>

    <main class="main-content" style="padding: 40px 60px; background: #f8fafc;">
        <div style="max-width: 900px; margin: 0 auto;">
            <section class="welcome-header" style="margin-bottom: 40px; text-align: center;">
                <h2 style="font-size: 2rem; color: #1e293b; font-weight: 800;">Your Recent Activity</h2>
                <p style="color: #64748b; font-weight: 600;">Stay updated with important system notifications and academic announcements.</p>
            </section>

            <div class="notification-list" style="display: flex; flex-direction: column; gap: 20px;">
                <?php foreach ($notifications as $n): ?>
                    <div style="background: #fff; padding: 25px; border-radius: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); border: 1px solid #f1f5f9; display: flex; gap: 20px; align-items: flex-start; transition: transform 0.2s ease;">
                        <div style="width: 50px; height: 50px; background: <?php echo $n['color']; ?>15; color: <?php echo $n['color']; ?>; border-radius: 15px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; flex-shrink: 0;">
                            <i class="<?php echo $n['icon']; ?>"></i>
                        </div>
                        <div style="flex-grow: 1;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                <h4 style="font-weight: 800; color: #1e293b; margin: 0;"><?php echo htmlspecialchars($n['title']); ?></h4>
                                <span style="font-size: 0.8rem; color: #94a3b8; font-weight: 700;"><?php echo $n['date']; ?></span>
                            </div>
                            <p style="color: #475569; line-height: 1.6; margin-bottom: 12px; font-size: 0.95rem;"><?php echo htmlspecialchars($n['message']); ?></p>
                            <span style="font-size: 0.75rem; color: <?php echo $n['color']; ?>; font-weight: 800; text-transform: uppercase; background: <?php echo $n['color']; ?>10; padding: 4px 10px; border-radius: 20px;"><?php echo $n['type']; ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div style="text-align: center; margin-top: 40px;">
                <p style="color: #94a3b8; font-style: italic; font-size: 0.9rem;">You have caught up with all notifications.</p>
            </div>
        </div>
    </main>
</div>

<?php include_once '../includes/footer.php'; ?>

<?php
require_once __DIR__ . '/../../config.php';

require_once ROOT_DIR . '/includes/security/auth_middleware.php';
require_once ROOT_DIR . '/includes/db.php';

// Only Teacher allowed
checkAuth(['Teacher']);

$pageTitle = "Teacher Dashboard";
include_once ROOT_DIR . '/includes/header.php';
require_once ROOT_DIR . '/includes/helpers/notification_helper.php';
include_once ROOT_DIR . '/includes/helpers/notification_logic.php';

$teacherId = $_SESSION['user_id'];

// Fetch some teacher-specific stats
try {
    // Get teacher's ID from teachers table
    $stmt = $pdo->prepare("SELECT id FROM teachers WHERE user_id = ?");
    $stmt->execute([$teacherId]);
    $teacherData = $stmt->fetch();
    $internalTeacherId = $teacherData['id'] ?? 0;

    $myCourses = $pdo->prepare("SELECT COUNT(*) FROM courses WHERE teacher_id = ?");
    $myCourses->execute([$internalTeacherId]);
    $totalMyCourses = $myCourses->fetchColumn();

    $totalStudentsInMyCourses = $pdo->prepare("
        SELECT COUNT(DISTINCT student_id) 
        FROM attendance a
        JOIN courses c ON a.course_id = c.id
        WHERE c.teacher_id = ?
    ");
    $totalStudentsInMyCourses->execute([$internalTeacherId]);
    $totalStudentsCount = $totalStudentsInMyCourses->fetchColumn();

} catch (PDOException $e) {
    $totalMyCourses = $totalStudentsCount = 0;
}
?>

<div class="dashboard-container">
    <!-- Top Gradient Header -->
    <header class="dashboard-header">
        <div class="header-brand">
            <h1>Academic Management System</h1>
        </div>
        <div class="header-tools" style="position: relative;">
            <div class="search-wrapper">
                <i class="fas fa-search"></i>
                <input type="text" id="dashboardSearch" placeholder="Search students, courses or tiles..." autocomplete="off">
            </div>
            <!-- Global Search Results Dropdown -->
            <div id="searchResults" style="display: none; position: absolute; top: 100%; left: 0; width: 100%; background: #fff; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); border: 1px solid #f1f5f9; z-index: 100; margin-top: 10px; max-height: 400px; overflow-y: auto;">
                <div id="resultsContent" style="padding: 10px;"></div>
            </div>

            <div class="header-icons" style="margin-left: 20px; display: flex; gap: 20px; align-items: center;">
                <!-- Notification Bell -->
                <div class="notif-wrapper" onclick="toggleNotifDropdown(event)">
                    <a href="<?php echo ROOT_URL; ?>/public/shared/notifications.php" style="color: #fff; display: flex; align-items: center;"><i class="fas fa-bell" style="color: #fff; font-size: 1.2rem;"></i></a>
                    <?php if (isset($unreadCount) && $unreadCount > 0): ?>
                        <span class="notif-badge"><?php echo $unreadCount; ?></span>
                    <?php endif; ?>

                    <div class="notif-dropdown" id="notifDropdown" onclick="event.stopPropagation()">
                        <div class="notif-header">
                            <span style="font-weight: 800; color: #1e293b; font-size: 0.9rem;">Notifications</span>
                            <a href="<?php echo ROOT_URL; ?>/public/shared/notifications.php" style="font-size: 0.75rem; color: #8b5cf6; font-weight: 700; text-decoration: none;">View All</a>
                        </div>
                        <div class="notif-list">
                            <?php if (empty($recentNotifs)): ?>
                                <div style="padding: 30px; text-align: center; color: #94a3b8; font-weight: 600; font-size: 0.85rem;">No notifications yet</div>
                            <?php else: ?>
                                <?php foreach ($recentNotifs as $rn): ?>
                                    <a href="notifications.php?read=<?php echo $rn['id']; ?>" class="notif-item <?php echo $rn['is_read'] ? '' : 'unread'; ?>">
                                        <span class="title"><?php echo htmlspecialchars($rn['title']); ?></span>
                                        <span class="time"><?php echo timeAgo($rn['created_at']); ?></span>
                                    </a>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <div class="dashboard-body" style="display: flex; flex: 1;">
        <!-- Left Sidebar -->
        <aside class="sidebar" style="z-index: 5; background: #fff;">
            <nav class="sidebar-nav">
                <ul id="sidebarMenu">
                    <li><a href="<?php echo ROOT_URL; ?>/public/shared/profile.php"><i class="fas fa-user"></i> <span>Account</span></a></li>
                    <li class="active"><a href="<?php echo ROOT_URL; ?>/public/teacher/dashboard.php"><i class="fas fa-th-large"></i> <span>Dashboard</span></a></li>
                    <li><a href="<?php echo ROOT_URL; ?>/public/teacher/view_students.php"><i class="fas fa-user-graduate"></i> <span>Students</span></a></li>
                    <li><a href="<?php echo ROOT_URL; ?>/public/teacher/manage_assignments.php"><i class="fas fa-file-alt"></i> <span>Assignment</span></a></li>
                    <li><a href="<?php echo ROOT_URL; ?>/public/shared/manage_attendance.php"><i class="fas fa-calendar-check"></i> <span>Attendance</span></a></li>
                    <li><a href="<?php echo ROOT_URL; ?>/public/shared/view_exam.php"><i class="fas fa-file-signature"></i> <span>Exams</span></a></li>
                    <li><a href="<?php echo ROOT_URL; ?>/public/teacher/manage_marks.php"><i class="fas fa-graduation-cap"></i> <span>Student Marks</span></a></li>
                    <li><a href="<?php echo ROOT_URL; ?>/public/shared/view_timetable.php"><i class="fas fa-calendar-alt"></i> <span>Timetable</span></a></li>
                    <li><a href="<?php echo ROOT_URL; ?>/public/shared/view_notice.php"><i class="fas fa-bullhorn"></i> <span>Notices</span></a></li>
                    <li><a href="<?php echo ROOT_URL; ?>/public/teacher/manage_resources.php"><i class="fas fa-folder-open"></i> <span>Resources</span></a></li>
                    <li><a href="<?php echo ROOT_URL; ?>/public/shared/notifications.php"><i class="fas fa-bell"></i> <span>Notifications</span></a></li>
                    <li><a href="<?php echo ROOT_URL; ?>/public/auth/logout.php" class="logout-link" style="margin-top: 50px; color: #f43f5e;"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
                </ul>
            </nav>
        </aside>

        <!-- Main Content Area -->
        <main class="main-content">
            <section class="welcome-header" style="margin-bottom: 30px;">
                <p style="font-size: 1.1rem; color: #64748b; font-weight: 600; margin-bottom: 5px;">Welcome Back!</p>
                <h2 style="font-size: 1.8rem; color: #1e293b; font-weight: 800;">Hello Teacher <?php echo htmlspecialchars($_SESSION['first_name']); ?>!</h2>
            </section>

            <div class="attendance-report-container teacher-stats-grid">
                <div class="assignment-status-card" style="display: flex; flex-direction: column; justify-content: center; align-items: center; padding: 30px;">
                    <i class="fas fa-book" style="font-size: 2.2rem; color: #8b5cf6; margin-bottom: 12px;"></i>
                    <h3 style="margin-bottom: 5px; color: #64748b; font-size: 0.95rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">My Courses</h3>
                    <div style="font-size: 2.2rem; font-weight: 800; color: #1e293b;"><?php echo $totalMyCourses; ?></div>
                </div>

                <div class="assignment-status-card" style="display: flex; flex-direction: column; justify-content: center; align-items: center; padding: 30px;">
                    <i class="fas fa-user-graduate" style="font-size: 2.2rem; color: #8b5cf6; margin-bottom: 12px;"></i>
                    <h3 style="margin-bottom: 5px; color: #64748b; font-size: 0.95rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">Total Students</h3>
                    <div style="font-size: 2.2rem; font-weight: 800; color: #1e293b;"><?php echo $totalStudentsCount; ?></div>
                </div>

                <div class="assignment-status-card" style="background: #eff6ff; border-color: #dbeafe; display: flex; flex-direction: column; justify-content: center; align-items: center; padding: 30px;">
                    <i class="fas fa-bullhorn" style="font-size: 2.2rem; color: #3b82f6; margin-bottom: 12px;"></i>
                    <h3 style="margin-bottom: 5px; color: #1e293b; font-size: 0.95rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">Notice Board</h3>
                    <a href="<?php echo ROOT_URL; ?>/public/shared/view_notice.php" style="display: block; text-align: center; background: #3b82f6; color: #fff; padding: 8px 16px; border-radius: 10px; text-decoration: none; font-weight: 800; font-size: 0.8rem; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.2); width: 80%; margin-top: 10px;">View Notices</a>
                </div>
            </div>

        </main>
    </div>
</div>

<script>
document.getElementById('dashboardSearch').addEventListener('input', function(e) {
    const query = e.target.value.toLowerCase();
    const resultsDiv = document.getElementById('searchResults');
    const resultsContent = document.getElementById('resultsContent');
    
    if (query.length < 1) {
        resultsDiv.style.display = 'none';
        // Reset Sidebar
        document.querySelectorAll('#sidebarMenu li').forEach(li => li.style.display = 'block');
        return;
    }

    // 1. Sidebar Filtering
    const sidebarItems = document.querySelectorAll('#sidebarMenu li');
    let sidebarMatch = false;
    sidebarItems.forEach(item => {
        const text = item.innerText.toLowerCase();
        if (text.includes(query)) {
            item.style.display = 'block';
            sidebarMatch = true;
        } else {
            item.style.display = 'none';
        }
    });
    if (!sidebarMatch && query.length > 2) {
        sidebarItems.forEach(li => li.style.display = 'block');
    }

    // 3. Search Students/Courses via API
    if (query.length > 1) {
        fetch(`<?php echo ROOT_URL; ?>/public/api/search_teacher_dashboard.php?q=${encodeURIComponent(query)}`)
            .then(res => res.json())
            .then(data => {
                if ((data.students && data.students.length > 0) || (data.courses && data.courses.length > 0)) {
                    resultsDiv.style.display = 'block';
                    let html = '';
                    
                    if (data.students.length > 0) {
                        html += '<div style="font-weight: 800; font-size: 0.75rem; color: #94a3b8; text-transform: uppercase; margin: 10px 5px 5px;">Students</div>';
                        data.students.forEach(s => {
                            html += `<a href="<?php echo ROOT_URL; ?>/public/teacher/view_students.php?search=${encodeURIComponent(s.first_name)}" style="display: block; padding: 10px; text-decoration: none; color: #1e293b; border-radius: 8px; transition: 0.2s;">
                                <div style="font-weight: 700;">${s.first_name} ${s.last_name}</div>
                                <div style="font-size: 0.75rem; color: #64748b;">${s.email}</div>
                            </a>`;
                        });
                    }
                    
                    if (data.courses.length > 0) {
                        html += '<div style="font-weight: 800; font-size: 0.75rem; color: #94a3b8; text-transform: uppercase; margin: 10px 5px 5px;">Courses</div>';
                        data.courses.forEach(c => {
                            html += `<a href="<?php echo ROOT_URL; ?>/public/teacher/manage_assignments.php?course=${encodeURIComponent(c.course_code)}" style="display: block; padding: 10px; text-decoration: none; color: #1e293b; border-radius: 8px; transition: 0.2s;">
                                <div style="font-weight: 700;">${c.course_name}</div>
                                <div style="font-size: 0.75rem; color: #64748b;">${c.course_code}</div>
                            </a>`;
                        });
                    }
                    
                    resultsContent.innerHTML = html;
                } else {
                    resultsDiv.style.display = 'none';
                }
            });
    } else {
        resultsDiv.style.display = 'none';
    }
});

// Close dropdown on click outside
document.addEventListener('click', function(e) {
    if (!e.target.closest('.header-tools')) {
        document.getElementById('searchResults').style.display = 'none';
    }
});

function toggleNotifDropdown(e) {
    e.stopPropagation();
    document.getElementById('notifDropdown').classList.toggle('active');
}
</script>

<?php include_once ROOT_DIR . '/includes/footer.php'; ?>


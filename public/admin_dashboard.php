<?php
require_once '../includes/auth_middleware.php';
require_once '../includes/db.php';

// Only Admin allowed
checkAuth(['Admin']);

$pageTitle = "Admin Dashboard";
include_once '../includes/header.php';

require_once '../includes/notification_helper.php';

// Fetch stats
try {
    $totalStudents = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'Student'")->fetchColumn();
    $totalTeachers = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'Teacher'")->fetchColumn();
    $totalCourses = $pdo->query("SELECT COUNT(*) FROM courses")->fetchColumn();
} catch (PDOException $e) {
    $totalStudents = $totalTeachers = $totalCourses = 0;
}

// Include notification logic for header
include_once '../includes/notification_logic.php';
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
                <input type="text" id="dashboardSearch" placeholder="Search system users, teachers or courses..." autocomplete="off">
            </div>
            <!-- Global Search Results Dropdown -->
            <div id="searchResults" style="display: none; position: absolute; top: 100%; left: 0; width: 100%; background: #fff; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); border: 1px solid #f1f5f9; z-index: 100; margin-top: 10px; max-height: 400px; overflow-y: auto;">
                <div id="resultsContent" style="padding: 10px;"></div>
            </div>

            <div class="header-icons" style="margin-left: 20px; display: flex; gap: 20px; align-items: center;">
                <a href="logout.php" class="header-logout" style="color: #fff; text-decoration: none; font-weight: 700; font-size: 0.9rem; padding: 8px 16px; border: 1px solid rgba(255,255,255,0.3); border-radius: 12px; transition: all 0.3s ease;">Logout</a>
                
                <!-- Notification Bell -->
                <div class="notif-wrapper" onclick="toggleNotifDropdown(event)">
                    <a href="notifications.php" style="color: #fff; display: flex; align-items: center;"><i class="fas fa-bell" style="color: #fff; font-size: 1.2rem;"></i></a>
                    <?php if (isset($unreadCount) && $unreadCount > 0): ?>
                        <span class="notif-badge"><?php echo $unreadCount; ?></span>
                    <?php endif; ?>

                    <div class="notif-dropdown" id="notifDropdown" onclick="event.stopPropagation()">
                        <div class="notif-header">
                            <span style="font-weight: 800; color: #1e293b; font-size: 0.9rem;">Notifications</span>
                            <a href="notifications.php" style="font-size: 0.75rem; color: #8b5cf6; font-weight: 700; text-decoration: none;">View All</a>
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

    <!-- Main Content Area (8-Tile Grid) -->
    <main class="main-content">
        <section class="welcome-header" style="margin-bottom: 40px;">
            <p style="font-size: 1.1rem; color: #64748b; font-weight: 600; margin-bottom: 5px;">Welcome Back! Admin</p>
            <h2 style="font-size: 1.8rem; color: #1e293b; font-weight: 800;">System Administrator Portal</h2>
        </section>

        <!-- The Navigation Tiles Grid (8 Tiles for Admin) -->
        <div class="teacher-grid-wrapper" id="tilesWrapper">
            <a href="profile.php" class="teacher-tile-card">
                <i class="fas fa-users-cog"></i>
                <span>Account</span>
            </a>

            <a href="admin_manage_users.php?role=Student" class="teacher-tile-card">
                <i class="fas fa-user-graduate"></i>
                <span>Students</span>
            </a>
            <a href="admin_manage_users.php?role=Teacher" class="teacher-tile-card">
                <i class="fas fa-chalkboard-teacher"></i>
                <span>Teachers</span>
            </a>
            <a href="admin_manage_courses.php" class="teacher-tile-card">
                <i class="fas fa-book"></i>
                <span>Courses</span>
            </a>
            <a href="admin_assignment_audit.php" class="teacher-tile-card">
                <i class="fas fa-file-alt"></i>
                <span>Assignment</span>
            </a>
            <a href="admin_attendance_master.php" class="teacher-tile-card">
                <i class="fas fa-calendar-check"></i>
                <span>Attendance</span>
            </a>
            <a href="admin_manage_enrollments.php" class="teacher-tile-card">
                <i class="fas fa-link"></i>
                <span>Enroll Student</span>
            </a>
            <a href="manage_notice.php" class="teacher-tile-card">
                <i class="fas fa-bullhorn"></i>
                <span>Notices</span>
            </a>
            <a href="admin_manage_exam.php" class="teacher-tile-card">
                <i class="fas fa-file-signature"></i>
                <span>Exams</span>
            </a>
            <a href="admin_manage_marks.php" class="teacher-tile-card">
                <i class="fas fa-chart-line"></i>
                <span>Student Marks</span>
            </a>
            <a href="admin_manage_timetable.php" class="teacher-tile-card">
                <i class="fas fa-calendar-alt"></i>
                <span>Timetable</span>
            </a>
            <a href="admin_manage_resources.php" class="teacher-tile-card">
                <i class="fas fa-folder-open"></i>
                <span>Resource Audit</span>
            </a>
            <a href="notifications.php" class="teacher-tile-card">
                <i class="fas fa-bell"></i>
                <span>Notifications</span>
            </a>
        </div>

<script>
document.getElementById('dashboardSearch').addEventListener('input', function(e) {
    const query = e.target.value.toLowerCase();
    const tiles = document.querySelectorAll('.teacher-tile-card');
    const resultsDiv = document.getElementById('searchResults');
    const resultsContent = document.getElementById('resultsContent');
    
    // 1. Filter Tiles
    let visibleTiles = 0;
    tiles.forEach(tile => {
        const text = tile.querySelector('span').innerText.toLowerCase();
        if (text.includes(query)) {
            tile.style.display = 'flex';
            visibleTiles++;
        } else {
            tile.style.display = 'none';
        }
    });

    // 2. Global Search for Users/Courses
    if (query.length > 1) {
        fetch(`api_search_admin_dashboard.php?q=${encodeURIComponent(query)}`)
            .then(res => res.json())
            .then(data => {
                if ((data.users && data.users.length > 0) || (data.courses && data.courses.length > 0)) {
                    resultsDiv.style.display = 'block';
                    let html = '';
                    
                    if (data.users.length > 0) {
                        html += '<div style="font-weight: 800; font-size: 0.75rem; color: #94a3b8; text-transform: uppercase; margin: 10px 5px 5px;">Users</div>';
                        data.users.forEach(u => {
                            html += `<a href="admin_manage_users.php?search=${encodeURIComponent(u.first_name)}&role=${u.role}" style="display: block; padding: 10px; text-decoration: none; color: #1e293b; border-radius: 8px; transition: 0.2s;">
                                <div style="font-weight: 700;">${u.first_name} ${u.last_name} <span style="font-size: 0.65rem; background: #f1f5f9; padding: 2px 6px; border-radius: 4px; margin-left: 5px;">${u.role}</span></div>
                                <div style="font-size: 0.75rem; color: #64748b;">${u.email}</div>
                            </a>`;
                        });
                    }
                    
                    if (data.courses.length > 0) {
                        html += '<div style="font-weight: 800; font-size: 0.75rem; color: #94a3b8; text-transform: uppercase; margin: 10px 5px 5px;">Courses</div>';
                        data.courses.forEach(c => {
                            html += `<a href="admin_manage_courses.php?search=${encodeURIComponent(c.course_code)}" style="display: block; padding: 10px; text-decoration: none; color: #1e293b; border-radius: 8px; transition: 0.2s;">
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

<?php include_once '../includes/footer.php'; ?>

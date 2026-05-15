<?php
require_once '../includes/auth_middleware.php';
require_once '../includes/db.php';

// Only Admin allowed
checkAuth(['Admin']);

$pageTitle = "Admin Dashboard";
include_once '../includes/header.php';

// Fetch some basic stats
try {
    $totalStudents = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'Student'")->fetchColumn();
    $totalTeachers = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'Teacher'")->fetchColumn();
    $totalCourses = $pdo->query("SELECT COUNT(*) FROM courses")->fetchColumn();
    $totalNotices = $pdo->query("SELECT COUNT(*) FROM notices WHERE is_active = 1")->fetchColumn();
    $totalExams = $pdo->query("SELECT COUNT(*) FROM exams WHERE is_active = 1")->fetchColumn();
} catch (PDOException $e) {
    $totalStudents = $totalTeachers = $totalCourses = $totalNotices = $totalExams = 0;
}
?>

<div class="dashboard-container">
    <!-- Top Gradient Header -->
    <header class="dashboard-header">
        <div class="header-brand">
            <h1>Academic Management System</h1>
            <h2 style="font-size: 1rem; font-weight: 400; margin-top: 5px; opacity: 0.9;">Admin Control Panel</h2>
        </div>
        <div class="header-tools">
            <div class="search-wrapper">
                <i class="fas fa-search"></i>
                <input type="text" id="dashboardSearch" placeholder="Search by name, email or role...">
            </div>
            <div class="header-icons" style="margin-left: 20px; display: flex; gap: 20px; align-items: center;">
                <a href="admin_dashboard.php" class="header-link" style="color: #fff; text-decoration: none; font-weight: 600; font-size: 0.9rem; padding: 8px 16px; border: 1px solid rgba(255,255,255,0.2); border-radius: 12px; transition: all 0.3s ease;">Dashboard</a>
                <a href="logout.php" class="header-logout" style="color: #fff; text-decoration: none; font-weight: 700; font-size: 0.9rem; padding: 8px 16px; border: 1px solid rgba(255,255,255,0.3); border-radius: 12px; transition: all 0.3s ease;">Logout</a>
            </div>
        </div>
    </header>

    <!-- Main Content Area -->
    <main class="main-content">
        <section class="welcome-header" style="margin-bottom: 40px;">
            <p style="font-size: 1.1rem; color: #64748b; font-weight: 600; margin-bottom: 5px;">Welcome Back! Admin</p>
            <h2 style="font-size: 1.8rem; color: #1e293b; font-weight: 800;">System Administrator Portal</h2>
        </section>

        <!-- The Navigation Tiles Grid (Admin Dashboard Layout) -->
        <div class="teacher-grid-wrapper">
            <a href="profile.php" class="teacher-tile-card">
                <i class="fas fa-user"></i>
                <span>Account</span>
            </a>

            <a href="manage_notices.php" class="teacher-tile-card">
                <i class="fas fa-bullhorn"></i>
                <span>Notices</span>
            </a>

            <a href="manage_exams.php" class="teacher-tile-card">
                <i class="fas fa-calendar-alt"></i>
                <span>Exams</span>
            </a>

            <a href="view_marks.php" class="teacher-tile-card">
                <i class="fas fa-chart-line"></i>
                <span>Marks & GPA</span>
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
            
            <a href="admin_attendance_master.php" class="teacher-tile-card">
                <i class="fas fa-calendar-check"></i>
                <span>Attendance</span>
            </a>

            <a href="manage_assignments.php" class="teacher-tile-card">
                <i class="fas fa-file-alt"></i>
                <span>Assignments</span>
            </a>

            <a href="admin_manage_enrollments.php" class="teacher-tile-card">
                <i class="fas fa-link"></i>
                <span>Enroll Students</span>
            </a>
        </div>
    </main>
</div>

<?php include_once '../includes/footer.php'; ?>

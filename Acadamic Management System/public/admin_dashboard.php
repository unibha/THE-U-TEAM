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
} catch (PDOException $e) {
    $totalStudents = $totalTeachers = $totalCourses = 0;
}
?>

<div class="dashboard-container">
    <!-- Top Gradient Header -->
    <header class="dashboard-header">
        <div class="header-brand">
            <h1>Academic Management System</h1>
        </div>
        <div class="header-tools">
            <div class="search-wrapper">
                <i class="fas fa-search"></i>
                <input type="text" id="dashboardSearch" placeholder="Search system users, teachers or courses...">
            </div>
            <div class="header-icons" style="margin-left: 20px; display: flex; gap: 20px; align-items: center;">
                <a href="logout.php" class="header-logout" style="color: #fff; text-decoration: none; font-weight: 700; font-size: 0.9rem; padding: 8px 16px; border: 1px solid rgba(255,255,255,0.3); border-radius: 12px; transition: all 0.3s ease;">Logout</a>
                <button class="icon-btn" style="background:none; border:none; color:#fff; font-size: 1.2rem; cursor:pointer;"><i class="fas fa-bell"></i></button>

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
        <div class="teacher-grid-wrapper">
            <a href="profile.php" class="teacher-tile-card">
                <i class="fas fa-users-cog"></i>
                <span>Account</span>
            </a>

            <a href="admin_manage_assignments.php" class="teacher-tile-card">
                <i class="fas fa-file-invoice"></i>
                <span>Assignments</span>
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
            <a href="manage_assignments.php" class="teacher-tile-card">
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
        </div>
    </main>
</div>

<?php include_once '../includes/footer.php'; ?>

<?php
require_once '../includes/auth_middleware.php';
require_once '../includes/db.php';

// Only Teacher allowed
checkAuth(['Teacher']);

$pageTitle = "Teacher Dashboard";
include_once '../includes/header.php';

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
        <div class="header-tools">
            <div class="search-wrapper">
                <i class="fas fa-search"></i>
                <input type="text" id="dashboardSearch" placeholder="Search students, courses or tiles...">
            </div>
            <div class="header-icons" style="margin-left: 20px; display: flex; gap: 20px; align-items: center;">
                <a href="logout.php" class="header-logout" style="color: #fff; text-decoration: none; font-weight: 700; font-size: 0.9rem; padding: 8px 16px; border: 1px solid rgba(255,255,255,0.3); border-radius: 12px; transition: all 0.3s ease;">Logout</a>
                <a href="notifications.php" class="icon-btn" style="background:none; border:none; color:#fff; font-size: 1.2rem; cursor:pointer;"><i class="fas fa-bell"></i></a>

            </div>
        </div>
    </header>

    <!-- Main Content Area (Tiled Grid) -->
    <main class="main-content">
        <section class="welcome-header" style="margin-bottom: 40px;">
            <p style="font-size: 1.1rem; color: #64748b; font-weight: 600; margin-bottom: 5px;">Welcome Back!</p>
            <h2 style="font-size: 1.8rem; color: #1e293b; font-weight: 800;">Hello Teacher <?php echo htmlspecialchars($_SESSION['first_name']); ?>!</h2>
        </section>

        <!-- The Navigation Tiles Grid (Matched to Reference) -->
        <div class="teacher-grid-wrapper">
            <a href="profile.php" class="teacher-tile-card">
                <i class="fas fa-user"></i>
                <span>Account</span>
            </a>

            <a href="teacher_view_students.php" class="teacher-tile-card">
                <i class="fas fa-user-graduate"></i>
                <span>Students</span>
            </a>
            <a href="manage_assignments.php" class="teacher-tile-card">
                <i class="fas fa-file-alt"></i>
                <span>Assignment</span>
            </a>
            <a href="manage_attendance.php" class="teacher-tile-card">
                <i class="fas fa-calendar-check"></i>
                <span>Attendance</span>
            </a>
            <a href="notifications.php" class="teacher-tile-card">
                <i class="fas fa-bell"></i>
                <span>Notifications</span>
            </a>
        </div>
    </main>
</div>

<?php include_once '../includes/footer.php'; ?>

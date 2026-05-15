<?php
require_once '../includes/auth_middleware.php';
require_once '../includes/db.php';

// Only Student allowed
checkAuth(['Student']);

$pageTitle = "Student Dashboard";
include_once '../includes/header.php';

$studentId = $_SESSION['user_id'];

// Fetch student specific stats
try {
    // Get student's ID from students table
    $stmt = $pdo->prepare("SELECT id FROM students WHERE user_id = ?");
    $stmt->execute([$studentId]);
    $studentData = $stmt->fetch();
    $internalStudentId = $studentData['id'] ?? 0;

    // Enrolled courses count
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM enrollments WHERE student_id = ?");
    $stmt->execute([$internalStudentId]);
    $totalEnrolled = $stmt->fetchColumn();

    // Attendance stats
    $stmt = $pdo->prepare("SELECT status, COUNT(*) as count FROM attendance WHERE student_id = ? GROUP BY status");
    $stmt->execute([$internalStudentId]);
    $attendanceRows = $stmt->fetchAll();
    
    $presentClasses = 0; $absentClasses = 0; $lateClasses = 0;
    foreach ($attendanceRows as $row) {
        if ($row['status'] == 'Present') $presentClasses = $row['count'];
        elseif ($row['status'] == 'Absent') $absentClasses = $row['count'];
        elseif ($row['status'] == 'Late') $lateClasses = $row['count'];
    }
    $totalClasses = $presentClasses + $absentClasses + $lateClasses;
    $attendanceRate = ($totalClasses > 0) ? round((($presentClasses + $lateClasses*0.5) / $totalClasses) * 100, 1) : 0;

    // Fetch Upcoming Tasks (Assignments)
    $stmt = $pdo->prepare("
        SELECT a.*, c.course_name 
        FROM assignments a 
        JOIN courses c ON a.course_id = c.id 
        JOIN enrollments e ON c.id = e.course_id 
        WHERE e.student_id = ? AND a.due_date >= NOW() 
        ORDER BY a.due_date ASC
    ");
    $stmt->execute([$internalStudentId]);
    $upcomingTasks = $stmt->fetchAll();

} catch (PDOException $e) {
    $totalEnrolled = $attendanceRate = 0;
    $presentClasses = $absentClasses = $lateClasses = 0;
    $upcomingTasks = [];
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
                <input type="text" id="dashboardSearch" placeholder="Search courses or assignments...">
            </div>
            <div class="header-icons" style="margin-left: 20px; display: flex; gap: 20px; align-items: center;">
                <a href="logout.php" class="header-logout" style="color: #fff; text-decoration: none; font-weight: 700; font-size: 0.9rem; padding: 8px 16px; border: 1px solid rgba(255,255,255,0.3); border-radius: 12px; transition: all 0.3s ease;">Logout</a>
                <a href="notifications.php" class="icon-btn" style="background:none; border:none; color:#fff; font-size: 1.2rem; cursor:pointer;"><i class="fas fa-bell"></i></a>

            </div>
        </div>
    </header>

    <div style="display: flex; flex: 1;">
        <!-- Left Sidebar -->
        <aside class="sidebar" style="border-radius: 0 40px 0 0; margin-top: -20px; z-index: 5; background: #fff;">
            <nav class="sidebar-nav">
                <ul>
                    <li><a href="profile.php"><i class="fas fa-user"></i> <span>Account</span></a></li>
                    <li><a href="student_dashboard.php"><i class="fas fa-th-large"></i> <span>Dashboard</span></a></li>
                    <li><a href="view_assignments.php"><i class="fas fa-file-alt"></i> <span>Assignment</span></a></li>
                    <li><a href="view_attendance.php"><i class="fas fa-calendar-check"></i> <span>Attendance</span></a></li>
                    <li><a href="notifications.php"><i class="fas fa-bell"></i> <span>Notifications</span></a></li>
                    <li><a href="logout.php" class="logout-link" style="margin-top: 50px; color: #f43f5e;"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
                </ul>
            </nav>
        </aside>

        <!-- Main Dashboard Content -->
        <main class="main-content">
            <section class="welcome-header" style="margin-bottom: 30px;">
                <h2 style="font-size: 1.8rem; color: #1e293b; font-weight: 800;">Hello <?php echo htmlspecialchars($_SESSION['first_name']); ?>!</h2>
            </section>

            <div class="attendance-report-container">
                <!-- Main Attendance Visual -->
                <div class="attendance-main-card">
                    <div class="attendance-info">
                        <h3 style="margin-bottom: 20px; color: #64748b; font-size: 1.1rem; font-weight: 700;">Attendance Summary</h3>
                        <div class="attendance-visual">
                            <svg viewBox="0 0 36 36" class="circular-chart">
                                <path class="circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" stroke="#f1f5f9" fill="none" stroke-width="3" />
                                <path class="circle-progress" stroke-dasharray="<?php echo $attendanceRate; ?>, 100" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" stroke="#8b5cf6" fill="none" stroke-width="3" stroke-linecap="round" />
                            </svg>
                            <div style="position: absolute; top:50%; left:50%; transform:translate(-50%, -50%); font-size: 1.2rem; font-weight: 800; color: #8b5cf6;"><?php echo $attendanceRate; ?>%</div>
                        </div>
                    </div>
                    <ul class="attendance-legend">
                        <li style="display:flex; justify-content:space-between; gap:20px; margin-bottom:10px;"><span>Present</span> <span style="font-weight:800;"><?php echo $presentClasses; ?></span></li>
                        <li style="display:flex; justify-content:space-between; gap:20px; margin-bottom:10px;"><span>Absent</span> <span style="font-weight:800;"><?php echo $absentClasses; ?></span></li>
                        <li style="display:flex; justify-content:space-between; gap:20px;"><span>Late</span> <span style="font-weight:800;"><?php echo $lateClasses; ?></span></li>
                    </ul>
                </div>

                <!-- Numerical Status Summary Table -->
                <div class="status-summary-card">
                    <h3 style="margin-bottom: 20px; color: #64748b; font-size: 1.1rem; font-weight: 700;">Attendance Summary</h3>
                    <table style="width: 100%; border: none;">
                        <tr style="color: #94a3b8; font-size: 0.75rem; text-transform: uppercase; font-weight: 800;">
                            <th style="border: none; padding: 5px 0;">Status</th>
                            <th style="border: none; padding: 5px 0; text-align: right;">Days</th>
                        </tr>
                        <tr style="border-bottom: 1px solid #f1f5f9;"><td style="padding: 10px 0;">Present</td><td style="text-align: right; font-weight: 800;"><?php echo $presentClasses; ?></td></tr>
                        <tr style="border-bottom: 1px solid #f1f5f9;"><td style="padding: 10px 0;">Absent</td><td style="text-align: right; font-weight: 800;"><?php echo $absentClasses; ?></td></tr>
                        <tr><td style="padding: 10px 0;">Late</td><td style="text-align: right; font-weight: 800;"><?php echo $lateClasses; ?></td></tr>
                    </table>
                </div>

                <!-- Assignment Status Card -->
                <div class="assignment-status-card">
                    <div style="font-size: 2rem; margin-bottom: 10px;">📋</div>
                    <h3 style="margin-bottom: 15px; color: #1e293b; font-size: 1rem; font-weight: 700;">Assignment status</h3>
                    <ul style="list-style: none; text-align: left; padding-left: 10px;">
                        <li style="margin-bottom: 10px; font-weight: 600; color: #475569;">🔘 Pending</li>
                        <li style="margin-bottom: 10px; font-weight: 600; color: #475569;">🔘 Upcoming</li>
                        <li style="font-weight: 600; color: #475569;">🔘 Due</li>
                    </ul>
                </div>
            </div>

            <!-- Upcoming Tasks / Searchable Area -->
            <section class="dashboard-section" style="margin-top: 40px; background: transparent; border:none; box-shadow:none; padding:0;">
                <h3 style="font-size: 1.3rem; margin-bottom: 20px; color: #1e293b; font-weight: 800;">Upcoming Tasks</h3>
                <div class="table-container" style="background: #fff; border-radius: 20px; overflow: hidden; border: 1px solid #f1f5f9;">
                    <table id="tasksTable">
                        <tbody id="studentTasksBody">
                            <?php if (empty($upcomingTasks)): ?>
                                <tr id="noResults">
                                    <td colspan="2" style="text-align: center; color: #94a3b8; padding: 50px; font-weight: 600;">No pending tasks to display right now.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($upcomingTasks as $task): 
                                    $dueDate = new DateTime($task['due_date']);
                                    $now = new DateTime();
                                    $diff = $now->diff($dueDate);
                                    $daysLeft = $diff->days;
                                    $color = ($daysLeft <= 2) ? '#f43f5e' : '#64748b';
                                ?>
                                <tr class="searchable-item">
                                    <td style="padding: 25px; font-weight: 700; color: #475569;">
                                        <?php echo htmlspecialchars($task['course_name']); ?> - <?php echo htmlspecialchars($task['title']); ?>
                                    </td>
                                    <td style="padding: 25px; text-align: right; color: <?php echo $color; ?>; font-weight: 700;">
                                        Due in <?php echo $daysLeft; ?> days
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            <tr id="noResults" style="display: none;">
                                <td colspan="2" style="text-align: center; color: #94a3b8; padding: 50px; font-weight: 600;">No tasks or courses available matches your search.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>
</div>

<?php include_once '../includes/header.php'; ?>

<?php include_once '../includes/footer.php'; ?>

<?php
require_once '../includes/auth_middleware.php';
require_once '../includes/db.php';

// Only Student allowed
checkAuth(['Student']);

$pageTitle = "Student Dashboard";
include_once '../includes/header.php';
require_once '../includes/notification_helper.php';
include_once '../includes/notification_logic.php';

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

    // Fetch Upcoming Tasks (Assignments NOT yet submitted)
    $stmt = $pdo->prepare("
        SELECT a.*, c.course_name 
        FROM assignment a 
        JOIN courses c ON a.course_id = c.id 
        JOIN enrollments e ON c.id = e.course_id 
        LEFT JOIN submissions s ON a.assignment_id = s.assignment_id AND s.student_id = ?
        WHERE e.student_id = ? AND a.due_date >= NOW() AND s.submission_id IS NULL
        ORDER BY a.due_date ASC
    ");
    $stmt->execute([$studentId, $internalStudentId]);
    $upcomingTasks = $stmt->fetchAll();

    // Assignment stats for the "Assignment status" card
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(CASE WHEN s.submission_id IS NULL AND a.due_date >= NOW() THEN 1 END) as pending,
            COUNT(CASE WHEN s.submission_id IS NULL AND a.due_date > DATE_ADD(NOW(), INTERVAL 2 DAY) THEN 1 END) as upcoming,
            COUNT(CASE WHEN s.submission_id IS NULL AND a.due_date >= NOW() AND a.due_date <= DATE_ADD(NOW(), INTERVAL 2 DAY) THEN 1 END) as due_soon
        FROM assignment a
        JOIN enrollments e ON a.course_id = e.course_id
        LEFT JOIN submissions s ON a.assignment_id = s.assignment_id AND s.student_id = ?
        WHERE e.student_id = ?
    ");
    $stmt->execute([$studentId, $internalStudentId]);
    $assignmentStats = $stmt->fetch();
    $pendingCount = $assignmentStats['pending'] ?? 0;
    $upcomingCount = $assignmentStats['upcoming'] ?? 0;
    $dueSoonCount = $assignmentStats['due_soon'] ?? 0;

} catch (PDOException $e) {
    $totalEnrolled = $attendanceRate = 0;
    $presentClasses = $absentClasses = $lateClasses = 0;
    $upcomingTasks = [];
    $pendingCount = $upcomingCount = $dueSoonCount = 0;
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
                <input type="text" id="dashboardSearch" placeholder="Search menu, courses or assignments..." autocomplete="off">
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

    <div style="display: flex; flex: 1;">
        <!-- Left Sidebar -->
        <aside class="sidebar" style="border-radius: 0 40px 0 0; margin-top: -20px; z-index: 5; background: #fff;">
            <nav class="sidebar-nav">
                <ul id="sidebarMenu">
                    <li><a href="profile.php"><i class="fas fa-user"></i> <span>Account</span></a></li>
                    <li><a href="student_dashboard.php"><i class="fas fa-th-large"></i> <span>Dashboard</span></a></li>
                    <li><a href="student_assignments.php"><i class="fas fa-file-alt"></i> <span>Assignment</span></a></li>
                    <li><a href="view_attendance.php"><i class="fas fa-calendar-check"></i> <span>Attendance</span></a></li>
                    <li><a href="view_exam.php"><i class="fas fa-file-signature"></i> <span>Exams</span></a></li>
                    <li><a href="view_marks.php"><i class="fas fa-graduation-cap"></i> <span>My Results</span></a></li>
                    <li><a href="view_timetable.php"><i class="fas fa-calendar-alt"></i> <span>Timetable</span></a></li>
                    <li><a href="student_view_resources.php"><i class="fas fa-folder-open"></i> <span>Resources</span></a></li>
                    <li><a href="view_notice.php"><i class="fas fa-bullhorn"></i> <span>Notices</span></a></li>
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
                <!-- Attendance cards remain... -->
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

                <div class="assignment-status-card">
                    <div style="font-size: 2rem; margin-bottom: 10px;">📋</div>
                    <h3 style="margin-bottom: 15px; color: #1e293b; font-size: 1rem; font-weight: 700;">Assignment status</h3>
                    <ul style="list-style: none; text-align: left; padding-left: 0px;">
                        <li style="margin-bottom: 12px; font-weight: 700; color: #475569; display: flex; align-items: center;">
                            <span style="background: #fff7ed; color: #f59e0b; width: 28px; height: 28px; display: inline-flex; align-items: center; justify-content: center; border-radius: 10px; margin-right: 12px; font-size: 0.85rem; border: 1px solid #ffedd5;"><?php echo $pendingCount; ?></span> 
                            Pending
                        </li>
                        <li style="margin-bottom: 12px; font-weight: 700; color: #475569; display: flex; align-items: center;">
                            <span style="background: #f0fdf4; color: #10b981; width: 28px; height: 28px; display: inline-flex; align-items: center; justify-content: center; border-radius: 10px; margin-right: 12px; font-size: 0.85rem; border: 1px solid #dcfce7;"><?php echo $upcomingCount; ?></span> 
                            Upcoming
                        </li>
                        <li style="font-weight: 700; color: #475569; display: flex; align-items: center;">
                            <span style="background: #fef2f2; color: #f43f5e; width: 28px; height: 28px; display: inline-flex; align-items: center; justify-content: center; border-radius: 10px; margin-right: 12px; font-size: 0.85rem; border: 1px solid #fee2e2;"><?php echo $dueSoonCount; ?></span> 
                            Due
                        </li>
                    </ul>
                </div>

                <div class="assignment-status-card" style="background: #eff6ff; border-color: #dbeafe;">
                    <div style="font-size: 2rem; margin-bottom: 10px;">📢</div>
                    <h3 style="margin-bottom: 15px; color: #1e293b; font-size: 1rem; font-weight: 700;">Notice Board</h3>
                    <p style="font-size: 0.85rem; color: #475569; margin-bottom: 20px; font-weight: 600; line-height: 1.5;">Check latest institutional announcements and exam schedules.</p>
                    <a href="view_notice.php" style="display: block; text-align: center; background: #3b82f6; color: #fff; padding: 12px; border-radius: 12px; text-decoration: none; font-weight: 800; font-size: 0.85rem; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.2);">View All Notices</a>
                </div>
            </div>

            <!-- Upcoming Tasks / Searchable Area -->
            <section class="upcoming-tasks-card" style="margin-top: 40px; background: #fff; border-radius: 30px; padding: 40px; border: 1px solid #f1f5f9; box-shadow: 0 10px 40px rgba(0,0,0,0.02);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
                    <h3 style="font-size: 1.4rem; color: #1e293b; font-weight: 800; margin: 0;">Upcoming Tasks</h3>
                    <span style="background: #f8fafc; color: #8b5cf6; padding: 6px 16px; border-radius: 12px; font-size: 0.8rem; font-weight: 800;">Real-time Filter Active</span>
                </div>
                
                <div class="table-container" style="border-radius: 20px; overflow: hidden; border: 1px solid #f8fafc;">
                    <table id="tasksTable" style="width: 100%; border-collapse: collapse;">
                        <tbody id="studentTasksBody">
                            <?php if (empty($upcomingTasks)): ?>
                                <tr id="noResults">
                                    <td colspan="2" style="text-align: center; color: #94a3b8; padding: 60px; font-weight: 600;">
                                        <i class="far fa-calendar-check" style="font-size: 3rem; display: block; margin-bottom: 15px; color: #e2e8f0;"></i>
                                        All caught up! No pending tasks to display.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($upcomingTasks as $task): 
                                    $dueDate = new DateTime($task['due_date']);
                                    $now = new DateTime();
                                    $diff = $now->diff($dueDate);
                                    $daysLeft = $diff->days;
                                    $isOverdue = ($dueDate < $now);
                                    $color = ($daysLeft <= 2 || $isOverdue) ? '#f43f5e' : '#64748b';
                                ?>
                                <tr class="searchable-item" style="border-bottom: 1px solid #f8fafc; transition: 0.2s;">
                                    <td style="padding: 25px; font-weight: 700; color: #475569;">
                                        <div style="display: flex; flex-direction: column; gap: 4px;">
                                            <span style="font-size: 0.7rem; color: #94a3b8; text-transform: uppercase;"><?php echo htmlspecialchars($task['course_name']); ?></span>
                                            <span style="font-size: 1.1rem; color: #1e293b;"><?php echo htmlspecialchars($task['title']); ?></span>
                                        </div>
                                    </td>
                                    <td style="padding: 25px; text-align: right;">
                                        <span style="color: <?php echo $color; ?>; font-weight: 800; background: <?php echo $color; ?>15; padding: 8px 16px; border-radius: 10px; font-size: 0.9rem;">
                                            <?php if($isOverdue) echo 'Overdue'; else echo 'Due in ' . $daysLeft . ' days'; ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
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
        // Reset Sidebar & Table
        document.querySelectorAll('#sidebarMenu li').forEach(li => li.style.display = 'block');
        return;
    }

    // 1. Sidebar Filtering (Only if matching)
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
    // If no sidebar match, show all sidebar items (don't break navigation)
    if (!sidebarMatch && query.length > 2) {
        sidebarItems.forEach(li => li.style.display = 'block');
    }

    // 2. Fetch Tasks (Table Filtering)
    fetch(`api_search_student_tasks.php?q=${encodeURIComponent(query)}`)
        .then(response => response.json())
        .then(data => {
            const tbody = document.getElementById('studentTasksBody');
            tbody.innerHTML = '';
            
            if (data.length === 0) {
                tbody.innerHTML = `<tr><td colspan="2" style="text-align: center; color: #94a3b8; padding: 60px; font-weight: 600;">
                    <i class="fas fa-search" style="font-size: 3rem; display: block; margin-bottom: 15px; color: #e2e8f0;"></i>
                    No tasks found matching "${query}".</td></tr>`;
            } else {
                data.forEach(task => {
                    const dueDate = new Date(task.due_date);
                    const now = new Date();
                    const isOverdue = dueDate < now;
                    const diffTime = Math.abs(dueDate - now);
                    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)); 
                    const color = (diffDays <= 2 || isOverdue) ? '#f43f5e' : '#64748b';

                    const row = document.createElement('tr');
                    row.className = 'searchable-item';
                    row.style.borderBottom = '1px solid #f8fafc';
                    row.innerHTML = `
                        <td style="padding: 25px; font-weight: 700; color: #475569;">
                            <div style="display: flex; flex-direction: column; gap: 4px;">
                                <span style="font-size: 0.7rem; color: #94a3b8; text-transform: uppercase;">${task.course_name}</span>
                                <span style="font-size: 1.1rem; color: #1e293b;">${task.title}</span>
                            </div>
                        </td>
                        <td style="padding: 25px; text-align: right;">
                            <span style="color: ${color}; font-weight: 800; background: ${color}15; padding: 8px 16px; border-radius: 10px; font-size: 0.9rem;">
                                ${isOverdue ? 'Overdue' : 'Due in ' + diffDays + ' days'}
                            </span>
                        </td>
                    `;
                    tbody.appendChild(row);
                });
            }
        });

    // 3. Global Search (Courses/Tasks Dropdown)
    if (query.length > 1) {
        resultsDiv.style.display = 'block';
        resultsContent.innerHTML = '<div style="padding: 20px; text-align: center; color: #94a3b8; font-weight: 600;"><i class="fas fa-spinner fa-spin" style="margin-right: 10px;"></i>Searching...</div>';
        
        fetch(`api_search_student_dashboard.php?q=${encodeURIComponent(query)}`)
            .then(res => res.json())
            .then(data => {
                if ((data.courses && data.courses.length > 0) || (data.tasks && data.tasks.length > 0)) {
                    let html = '';
                    
                    if (data.courses.length > 0) {
                        html += '<div style="font-weight: 800; font-size: 0.75rem; color: #94a3b8; text-transform: uppercase; margin: 10px 5px 5px;">My Courses</div>';
                        data.courses.forEach(c => {
                            html += `<a href="student_assignments.php?course=${encodeURIComponent(c.course_code)}" style="display: block; padding: 10px; text-decoration: none; color: #1e293b; border-radius: 8px; transition: 0.2s;">
                                <div style="font-weight: 700;">${c.course_name}</div>
                                <div style="font-size: 0.75rem; color: #64748b;">${c.course_code}</div>
                            </a>`;
                        });
                    }
                    
                    if (data.tasks.length > 0) {
                        html += '<div style="font-weight: 800; font-size: 0.75rem; color: #94a3b8; text-transform: uppercase; margin: 10px 5px 5px;">Pending Assignments</div>';
                        data.tasks.forEach(t => {
                            html += `<a href="student_assignments.php?search=${encodeURIComponent(t.title)}" style="display: block; padding: 10px; text-decoration: none; color: #1e293b; border-radius: 8px; transition: 0.2s;">
                                <div style="font-weight: 700;">${t.title}</div>
                                <div style="font-size: 0.75rem; color: #64748b;">${t.course_name} • Due ${new Date(t.due_date).toLocaleDateString()}</div>
                            </a>`;
                        });
                    }
                    
                    resultsContent.innerHTML = html;
                } else {
                    resultsContent.innerHTML = `
                        <div style="padding: 30px; text-align: center;">
                            <i class="fas fa-search-minus" style="font-size: 2rem; color: #e2e8f0; margin-bottom: 10px;"></i>
                            <div style="color: #64748b; font-weight: 700; font-size: 0.9rem;">No results found</div>
                            <div style="color: #94a3b8; font-size: 0.75rem; margin-top: 4px;">Try searching for courses or assignments</div>
                        </div>
                    `;
                }
            })
            .catch(err => {
                resultsDiv.style.display = 'none';
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

window.onclick = function() {
    document.getElementById('notifDropdown').classList.remove('active');
}
</script>

<?php include_once '../includes/footer.php'; ?>


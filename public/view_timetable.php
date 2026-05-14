<?php
require_once '../includes/auth_middleware.php';
require_once '../includes/db.php';

// Teachers and Students can view
checkAuth(['Teacher', 'Student', 'Admin']);

$role = $_SESSION['role'];
$userId = $_SESSION['user_id'];

$pageTitle = "My Academic Schedule - Timetable Portal";
include_once '../includes/header.php';

$selected_day = $_GET['day'] ?? date('l'); // Default to current day
if (!in_array($selected_day, ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'])) {
    $selected_day = 'Sunday';
}

$schedule = [];

try {
    if ($role === 'Student') {
        // Get internal Student ID
        $stmt = $pdo->prepare("SELECT id FROM students WHERE user_id = ?");
        $stmt->execute([$userId]);
        $studentId = $stmt->fetchColumn();

        // Fetch timetable for courses student is enrolled in
        $stmt = $pdo->prepare("
            SELECT tt.*, c.course_name, c.course_code, u.first_name as t_fname, u.last_name as t_lname
            FROM timetable tt
            JOIN courses c ON tt.course_id = c.id
            JOIN enrollments en ON c.id = en.course_id
            JOIN teachers t ON tt.teacher_id = t.id
            JOIN users u ON t.user_id = u.id
            WHERE en.student_id = ? AND tt.day_of_week = ?
            ORDER BY tt.period_number ASC
        ");
        $stmt->execute([$studentId, $selected_day]);
        $schedule = $stmt->fetchAll();

    } elseif ($role === 'Teacher') {
        // Get internal Teacher ID
        $stmt = $pdo->prepare("SELECT id FROM teachers WHERE user_id = ?");
        $stmt->execute([$userId]);
        $teacherId = $stmt->fetchColumn();

        // Fetch timetable for courses teacher is assigned to
        $stmt = $pdo->prepare("
            SELECT tt.*, c.course_name, c.course_code
            FROM timetable tt
            JOIN courses c ON tt.course_id = c.id
            WHERE tt.teacher_id = ? AND tt.day_of_week = ?
            ORDER BY tt.period_number ASC
        ");
        $stmt->execute([$teacherId, $selected_day]);
        $schedule = $stmt->fetchAll();
    } else {
        // Admin View (all for the day)
        $stmt = $pdo->prepare("
            SELECT tt.*, c.course_name, c.course_code, u.first_name as t_fname, u.last_name as t_lname
            FROM timetable tt
            JOIN courses c ON tt.course_id = c.id
            JOIN teachers t ON tt.teacher_id = t.id
            JOIN users u ON t.user_id = u.id
            WHERE tt.day_of_week = ?
            ORDER BY tt.period_number ASC
        ");
        $stmt->execute([$selected_day]);
        $schedule = $stmt->fetchAll();
    }
} catch (PDOException $e) {
    $schedule = [];
}
?>

<div class="dashboard-container" style="flex-direction: column;">
    <header class="dashboard-header">
        <div class="header-brand">
            <h1>Academic Management System</h1>
            <p>Personal Portal > My Schedule</p>
        </div>
        <div class="header-tools">
            <div class="search-wrapper">
                <i class="fas fa-search"></i>
                <input type="text" id="timetableSearch" placeholder="Filter today's sessions...">
            </div>
            <div class="header-icons" style="margin-left: 20px; display: flex; gap: 15px; align-items: center;">
                <a href="<?php 
                    if($role == 'Admin') echo 'admin_dashboard.php';
                    elseif($role == 'Teacher') echo 'teacher_dashboard.php';
                    else echo 'student_dashboard.php';
                ?>" style="color: #fff; text-decoration: none; font-weight: 700; font-size: 0.9rem; padding: 8px 16px; border: 1px solid rgba(255,255,255,0.3); border-radius: 12px;"><i class="fas fa-th-large" style="margin-right: 8px;"></i>Dashboard</a>
                <a href="logout.php" style="color: #fff; text-decoration: none; font-weight: 700; font-size: 0.9rem; padding: 8px 16px; border: 1px solid rgba(255,255,255,0.3); border-radius: 12px;">Logout</a>
            </div>
        </div>
    </header>

    <main class="main-content" style="padding: 40px 60px; background: #f8fafc;">
        <div style="max-width: 1000px; margin: 0 auto;">
            
            <!-- Day Selector -->
            <section style="display: flex; gap: 15px; margin-bottom: 50px; overflow-x: auto; padding-bottom: 15px;">
                <?php foreach(['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'] as $day): ?>
                    <a href="?day=<?php echo $day; ?>" style="text-decoration: none; padding: 12px 30px; border-radius: 15px; font-weight: 800; font-size: 0.95rem; transition: 0.3s ease; white-space: nowrap; 
                        <?php echo $selected_day === $day ? 'background: #8b5cf6; color: #fff; box-shadow: 0 10px 15px rgba(139, 92, 246, 0.2);' : 'background: #fff; color: #64748b; border: 1px solid #f1f5f9;'; ?>">
                        <?php echo $day; ?>
                    </a>
                <?php endforeach; ?>
            </section>

            <h2 style="font-size: 1.8rem; color: #1e293b; font-weight: 800; margin-bottom: 30px;"><?php echo $selected_day; ?> Schedule</h2>

            <div id="timetableContainer" style="display: flex; flex-direction: column; gap: 20px;">
                <?php if (empty($schedule)): ?>
                    <div style="background: #fff; padding: 100px; border-radius: 35px; text-align: center; border: 2px dashed #e2e8f0; box-shadow: 0 10px 30px rgba(0,0,0,0.02);">
                        <i class="far fa-calendar-check" style="font-size: 4rem; color: #cbd5e1; margin-bottom: 25px;"></i>
                        <h3 style="color: #64748b; font-weight: 800; font-size: 1.4rem;">No Sessions Today</h3>
                        <p style="color: #94a3b8; font-weight: 600;">You have no scheduled academic modules for <?php echo $selected_day; ?>.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($schedule as $s): ?>
                        <div class="timetable-card" style="background: #fff; padding: 30px; border-radius: 28px; box-shadow: 0 4px 20px rgba(0,0,0,0.02); border: 1px solid #f1f5f9; display: grid; grid-template-columns: 180px 1fr 150px; gap: 30px; align-items: center;">
                            <div>
                                <span style="display: block; font-size: 0.75rem; color: #8b5cf6; font-weight: 800; text-transform: uppercase; margin-bottom: 8px;">Period <?php echo $s['period_number']; ?></span>
                                <span style="font-size: 1.1rem; color: #1e293b; font-weight: 900;"><?php echo date('h:i A', strtotime($s['start_time'])); ?></span>
                                <span style="display: block; font-size: 0.8rem; color: #94a3b8; font-weight: 600; margin-top: 4px;">to <?php echo date('h:i A', strtotime($s['end_time'])); ?></span>
                            </div>

                            <div>
                                <span style="font-size: 0.75rem; color: #94a3b8; font-weight: 800; text-transform: uppercase; display: block; margin-bottom: 5px;"><?php echo htmlspecialchars($s['course_code']); ?></span>
                                <h4 style="font-size: 1.3rem; color: #1e293b; font-weight: 800; margin: 0;"><?php echo htmlspecialchars($s['course_name']); ?></h4>
                                <?php if ($role !== 'Teacher'): ?>
                                    <p style="color: #64748b; font-weight: 600; font-size: 0.9rem; margin-top: 6px;"><i class="fas fa-user-tie" style="margin-right: 8px; color: #cbd5e1;"></i><?php echo htmlspecialchars($s['t_fname'] . ' ' . $s['t_lname']); ?></p>
                                <?php endif; ?>
                            </div>

                            <div style="text-align: right;">
                                <div style="background: #f8fafc; padding: 12px; border-radius: 15px; border: 1px solid #f1f5f9; text-align: center;">
                                    <span style="display: block; font-size: 0.65rem; color: #94a3b8; font-weight: 800; text-transform: uppercase; margin-bottom: 4px;">Classroom</span>
                                    <span style="font-size: 0.95rem; color: #1e293b; font-weight: 800;"><?php echo htmlspecialchars($s['classroom']); ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div style="margin-top: 60px; text-align: center;">
                <p style="color: #94a3b8; font-size: 0.9rem; font-weight: 600;"><i class="fas fa-info-circle" style="margin-right: 8px;"></i> Timetable is subject to change. Please check notifications for any rescheduling.</p>
            </div>
        </div>
    </main>
</div>

<script>
document.getElementById('timetableSearch').addEventListener('input', function(e) {
    const query = e.target.value.toLowerCase();
    const cards = document.querySelectorAll('.timetable-card');
    
    cards.forEach(card => {
        const text = card.innerText.toLowerCase();
        card.style.display = text.includes(query) ? 'grid' : 'none';
    });
});
</script>

<?php include_once '../includes/footer.php'; ?>


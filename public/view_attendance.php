<?php
require_once '../includes/auth_middleware.php';
require_once '../includes/db.php';

// Only Student allowed
checkAuth(['Student']);

$pageTitle = "My Attendance - Academic Management System";
include_once '../includes/header.php';

$userId = $_SESSION['user_id'];

try {
    // 1. Get internal Student ID
    $stmt = $pdo->prepare("SELECT id FROM students WHERE user_id = ?");
    $stmt->execute([$userId]);
    $studentId = $stmt->fetchColumn();

    // 2. Fetch attendance per course with stats
    $stmt = $pdo->prepare("
        SELECT 
            c.course_name, 
            c.course_code,
            COUNT(a.id) as total_days,
            SUM(CASE WHEN a.status = 'Present' THEN 1 ELSE 0 END) as present_days,
            SUM(CASE WHEN a.status = 'Late' THEN 1 ELSE 0 END) as late_days
        FROM courses c
        JOIN enrollments e ON c.id = e.course_id
        LEFT JOIN attendance a ON (c.id = a.course_id AND a.student_id = ?)
        WHERE e.student_id = ?
        GROUP BY c.id
    ");
    $stmt->execute([$studentId, $studentId]);
    $courseStats = $stmt->fetchAll();

    // 3. Fetch detailed daily logs
    $stmt = $pdo->prepare("
        SELECT a.*, c.course_name, c.course_code
        FROM attendance a
        JOIN courses c ON a.course_id = c.id
        WHERE a.student_id = ?
        ORDER BY a.attendance_date DESC
        LIMIT 50
    ");
    $stmt->execute([$studentId]);
    $attendanceLogs = $stmt->fetchAll();

} catch (PDOException $e) {
    echo "Data error: " . $e->getMessage();
    exit();
}
?>

<div class="dashboard-container">
    <header class="dashboard-header">
        <div class="header-brand">
            <h1>Academic Management System</h1>
            <p>Portal > My Attendance</p>
        </div>
        <div class="header-icons" style="margin-left: 20px; display: flex; gap: 15px; align-items: center;">
            <a href="student_dashboard.php" style="color: #fff; text-decoration: none; font-weight: 700; font-size: 0.9rem; padding: 8px 16px; border: 1px solid rgba(255,255,255,0.3); border-radius: 12px;">Dashboard</a>
        </div>
    </header>

    <main class="main-content">
        <h2 style="font-size: 1.8rem; color: #1e293b; font-weight: 800; margin-bottom: 30px;">Attendance Performance Overview</h2>

        <!-- Course Percentage Cards -->
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 25px; margin-bottom: 50px;">
            <?php foreach ($courseStats as $course): ?>
                <?php 
                $percentage = ($course['total_days'] > 0) ? round(($course['present_days'] / $course['total_days']) * 100) : 0;
                $statusColor = ($percentage >= 75) ? '#10b981' : (($percentage >= 50) ? '#f59e0b' : '#f43f5e');
                ?>
                <div style="background: #fff; padding: 30px; border-radius: 24px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); border: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between;">
                    <div>
                        <h3 style="font-size: 1.1rem; color: #1e293b; font-weight: 800; margin-bottom: 5px;"><?php echo htmlspecialchars($course['course_name']); ?></h3>
                        <p style="font-size: 0.85rem; color: #64748b; font-weight: 700;"><?php echo htmlspecialchars($course['course_code']); ?></p>
                        <p style="margin-top: 15px; font-size: 0.9rem; color: #475569;">Present: <strong><?php echo $course['present_days']; ?>/<?php echo $course['total_days']; ?></strong></p>
                    </div>
                    <div style="width: 80px; height: 80px; border-radius: 50%; border: 8px solid #f1f5f9; border-top-color: <?php echo $statusColor; ?>; display: flex; align-items: center; justify-content: center;">
                        <span style="font-weight: 800; color: #1e293b; font-size: 1.1rem;"><?php echo $percentage; ?>%</span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Detailed Logs -->
        <section style="background: #fff; border-radius: 24px; padding: 40px; box-shadow: 0 10px 30px rgba(0,0,0,0.02);">
            <h3 style="font-size: 1.4rem; color: #1e293b; font-weight: 800; margin-bottom: 30px;">Recent Activity Logs</h3>
            <div class="table-container">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead style="background: #f8fafc;">
                        <tr>
                            <th style="padding: 20px; text-align: left; color: #64748b; font-weight: 700; text-transform: uppercase; font-size: 0.8rem; border-bottom: 2px solid #f1f5f9;">Academic Date</th>
                            <th style="padding: 20px; text-align: left; color: #64748b; font-weight: 700; text-transform: uppercase; font-size: 0.8rem; border-bottom: 2px solid #f1f5f9;">Course Catalog</th>
                            <th style="padding: 20px; text-align: center; color: #64748b; font-weight: 700; text-transform: uppercase; font-size: 0.8rem; border-bottom: 2px solid #f1f5f9;">Presence Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($attendanceLogs)): ?>
                            <tr><td colspan="3" style="padding: 40px; text-align: center; color: #94a3b8;">No attendance traces found in the system.</td></tr>
                        <?php else: ?>
                            <?php foreach ($attendanceLogs as $log): ?>
                                <tr style="border-bottom: 1px solid #f1f5f9;">
                                    <td style="padding: 20px; font-weight: 700; color: #1e293b;"><?php echo date('M d, Y', strtotime($log['attendance_date'])); ?></td>
                                    <td style="padding: 20px; color: #475569;"><?php echo htmlspecialchars($log['course_name']); ?></td>
                                    <td style="padding: 20px; text-align: center;">
                                        <?php 
                                        $chipClass = strtolower($log['status']);
                                        ?>
                                        <span class="status-chip <?php echo $chipClass; ?>" style="display: inline-block; padding: 6px 16px; border-radius: 8px; font-size: 0.85rem; font-weight: 800;">
                                            <?php echo $log['status']; ?>
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

<style>
.status-chip.present { background: #dcfce7; color: #166534; }
.status-chip.absent { background: #fee2e2; color: #991b1b; }
.status-chip.late { background: #fef3c7; color: #b45309; }
</style>

<?php include_once '../includes/footer.php'; ?>

<?php
require_once '../includes/auth_middleware.php';
require_once '../includes/db.php';

// Only Student allowed
checkAuth(['Student']);

$pageTitle = "My Assignments - Academic Management System";
include_once '../includes/header.php';

$userId = $_SESSION['user_id'];

try {
    // 1. Get internal Student ID
    $stmt = $pdo->prepare("SELECT id FROM students WHERE user_id = ?");
    $stmt->execute([$userId]);
    $studentId = $stmt->fetchColumn();

    // 2. Fetch all assignments for enrolled courses
    $stmt = $pdo->prepare("
        SELECT a.*, c.course_name, c.course_code
        FROM assignments a
        JOIN courses c ON a.course_id = c.id
        JOIN enrollments e ON c.id = e.course_id
        WHERE e.student_id = ?
        ORDER BY a.due_date ASC
    ");
    $stmt->execute([$studentId]);
    $assignments = $stmt->fetchAll();

} catch (PDOException $e) {
    echo "Data error: " . $e->getMessage();
    exit();
}
?>

<div class="dashboard-container">
    <header class="dashboard-header">
        <div class="header-brand">
            <h1>Academic Management System</h1>
            <p>Portal > My Assignments</p>
        </div>
        <div class="header-icons" style="margin-left: 20px; display: flex; gap: 15px; align-items: center;">
            <a href="student_dashboard.php" style="color: #fff; text-decoration: none; font-weight: 700; font-size: 0.9rem; padding: 8px 16px; border: 1px solid rgba(255,255,255,0.3); border-radius: 12px;">Dashboard</a>
        </div>
    </header>

    <main class="main-content" style="padding: 40px 60px; background: #f8fafc;">
        <h2 style="font-size: 1.8rem; color: #1e293b; font-weight: 800; margin-bottom: 30px;">Active Coursework & Deadlines</h2>

        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 30px;">
            <?php if (empty($assignments)): ?>
                <div style="background: #fff; padding: 50px; border-radius: 24px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); text-align: center; grid-column: 1 / -1;">
                    <i class="fas fa-tasks" style="font-size: 3rem; color: #e2e8f0; margin-bottom: 20px; display: block;"></i>
                    <p style="color: #64748b; font-size: 1.1rem; font-weight: 600;">No assignments found for your enrolled catalog.</p>
                </div>
            <?php else: ?>
                <?php foreach ($assignments as $a): ?>
                    <?php 
                    $dueDate = strtotime($a['due_date']);
                    $isPast = $dueDate < time();
                    ?>
                    <div style="background: #fff; padding: 35px; border-radius: 28px; box-shadow: 0 10px 40px rgba(0,0,0,0.02); border: 1px solid #f1f5f9; position: relative;">
                        <span style="font-size: 0.8rem; color: #8b5cf6; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; display: block; margin-bottom: 12px;">
                            <?php echo htmlspecialchars($a['course_name']); ?>
                        </span>
                        <h3 style="font-size: 1.3rem; color: #1e293b; font-weight: 800; margin-bottom: 15px;"><?php echo htmlspecialchars($a['title']); ?></h3>
                        <p style="color: #475569; font-size: 0.95rem; line-height: 1.7; margin-bottom: 25px; height: 100px; overflow-y: auto;">
                            <?php echo nl2br(htmlspecialchars($a['description'])); ?>
                        </p>
                        <div style="display: flex; justify-content: space-between; align-items: center; padding-top: 20px; border-top: 1px solid #f1f5f9;">
                            <span style="font-size: 0.85rem; color: <?php echo $isPast ? '#94a3b8' : '#10b981'; ?>; font-weight: 800;">
                                <i class="fas fa-clock" style="margin-right: 5px;"></i>
                                <?php echo date('M d, Y', $dueDate); ?>
                            </span>
                            <span style="background: <?php echo $isPast ? '#f1f5f9' : '#e0f2fe'; ?>; color: <?php echo $isPast ? '#64748b' : '#0369a1'; ?>; padding: 6px 12px; border-radius: 8px; font-size: 0.8rem; font-weight: 800;">
                                <?php echo $isPast ? 'Closed' : 'Active Task'; ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>
</div>

<?php include_once '../includes/footer.php'; ?>

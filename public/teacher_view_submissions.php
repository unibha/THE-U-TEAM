<?php
require_once '../includes/auth_middleware.php';
require_once '../includes/db.php';

// Only Teacher allowed
checkAuth(['Teacher']);

$teacherUserId = $_SESSION['user_id'];
$assignmentId = $_GET['assignment_id'] ?? '';

if (!$assignmentId) {
    header("Location: teacher_dashboard.php");
    exit();
}

// Fetch assignment details
$stmt = $pdo->prepare("SELECT a.*, c.course_name FROM assignment a JOIN courses c ON a.course_id = c.id WHERE a.assignment_id = ? AND a.created_by = ?");
$stmt->execute([$assignmentId, $teacherUserId]);
$assignment = $stmt->fetch();

if (!$assignment) {
    die("Assignment not found or access denied.");
}

// Fetch submissions
$stmt = $pdo->prepare("
    SELECT s.*, u.first_name, u.last_name, u.email as student_reg
    FROM submissions s
    JOIN users u ON s.student_id = u.id
    WHERE s.assignment_id = ?
    ORDER BY s.submitted_at DESC
");
$stmt->execute([$assignmentId]);
$submissions = $stmt->fetchAll();

$pageTitle = "View Submissions - Teacher Portal";
include_once '../includes/header.php';
?>

<div class="dashboard-container">
    <header class="dashboard-header">
        <div class="header-brand">
            <h1>Academic Management System</h1>
            <p>Teacher Portal > Submissions Audit</p>
        </div>
        <div class="header-icons" style="margin-left: 20px; display: flex; gap: 15px; align-items: center;">
            <a href="teacher_dashboard.php" style="color: #fff; text-decoration: none; font-weight: 700; font-size: 0.9rem; padding: 8px 16px; border: 1px solid rgba(255,255,255,0.3); border-radius: 12px;"><i class="fas fa-arrow-left" style="margin-right: 8px;"></i>Back</a>
        </div>
    </header>

    <main class="main-content" style="padding: 40px 60px; background: #f8fafc;">
        <div style="background: #fff; padding: 30px; border-radius: 24px; margin-bottom: 30px; border: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h2 style="font-size: 1.5rem; color: #1e293b; font-weight: 900; margin-bottom: 5px;"><?php echo htmlspecialchars($assignment['title']); ?></h2>
                <p style="color: #64748b; font-weight: 600;"><?php echo htmlspecialchars($assignment['course_name']); ?> | Due: <?php echo date('M d, Y H:i', strtotime($assignment['due_date'])); ?></p>
            </div>
            <div style="text-align: right;">
                <span style="background: #fefce8; color: #ca8a04; padding: 8px 20px; border-radius: 12px; font-weight: 800; font-size: 0.9rem;">
                    <?php echo count($submissions); ?> Submissions
                </span>
            </div>
        </div>

        <div style="background: #fff; border-radius: 24px; box-shadow: 0 4px 20px rgba(0,0,0,0.02); border: 1px solid #f1f5f9; overflow: hidden;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead style="background: #f8fafc;">
                    <tr>
                        <th style="padding: 20px; text-align: left; color: #64748b; font-size: 0.75rem; text-transform: uppercase; font-weight: 700;">Student Info</th>
                        <th style="padding: 20px; text-align: center; color: #64748b; font-size: 0.75rem; text-transform: uppercase; font-weight: 700;">Submitted At</th>
                        <th style="padding: 20px; text-align: center; color: #64748b; font-size: 0.75rem; text-transform: uppercase; font-weight: 700;">Grade</th>
                        <th style="padding: 20px; text-align: center; color: #64748b; font-size: 0.75rem; text-transform: uppercase; font-weight: 700;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($submissions)): ?>
                        <tr><td colspan="4" style="padding: 60px; text-align: center; color: #94a3b8; font-weight: 600;">No submissions yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($submissions as $s): ?>
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <td style="padding: 20px;">
                                <div style="font-weight: 800; color: #1e293b;"><?php echo htmlspecialchars($s['first_name'] . ' ' . $s['last_name']); ?></div>
                                <div style="font-size: 0.75rem; color: #94a3b8; font-weight: 700;">ID: <?php echo htmlspecialchars($s['student_reg']); ?></div>
                            </td>
                            <td style="padding: 20px; text-align: center; color: #475569; font-weight: 600; font-size: 0.9rem;">
                                <?php echo date('M d, H:i', strtotime($s['submitted_at'])); ?>
                            </td>
                            <td style="padding: 20px; text-align: center;">
                                <?php if ($s['marks'] !== null): ?>
                                    <span style="background: #f0fdf4; color: #166534; padding: 6px 14px; border-radius: 10px; font-weight: 800; font-size: 0.85rem;">
                                        <?php echo $s['marks']; ?> / <?php echo $assignment['total_marks']; ?>
                                    </span>
                                <?php else: ?>
                                    <span style="background: #fff1f2; color: #991b1b; padding: 6px 14px; border-radius: 10px; font-weight: 800; font-size: 0.85rem;">Ungraded</span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 20px; text-align: center;">
                                <div style="display: flex; justify-content: center; gap: 10px;">
                                    <a href="download_resource.php?id=<?php echo $s['submission_id']; ?>&type=submission" target="_blank" style="padding: 8px 15px; background: #eff6ff; color: #3b82f6; border-radius: 10px; font-weight: 800; font-size: 0.75rem; text-decoration: none;">View Work</a>
                                    <a href="teacher_grade_submission.php?sid=<?php echo $s['submission_id']; ?>" style="padding: 8px 15px; background: #fdf4ff; color: #a855f7; border-radius: 10px; font-weight: 800; font-size: 0.75rem; text-decoration: none;">Grade</a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<?php include_once '../includes/footer.php'; ?>

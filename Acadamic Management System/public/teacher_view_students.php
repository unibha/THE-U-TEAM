<?php
require_once '../includes/auth_middleware.php';
require_once '../includes/db.php';

// Only Teacher allowed
checkAuth(['Teacher']);

$pageTitle = "My Students - Academic Management System";
include_once '../includes/header.php';

$userId = $_SESSION['user_id'];

try {
    // Get internal Teacher ID
    $stmt = $pdo->prepare("SELECT id FROM teachers WHERE user_id = ?");
    $stmt->execute([$userId]);
    $teacher = $stmt->fetch();
    $teacherId = $teacher['id'] ?? 0;

    // Fetch all students enrolled in this teacher's courses
    $stmt = $pdo->prepare("
        SELECT DISTINCT u.first_name, u.last_name, u.email, u.contact, c.course_name, c.course_code
        FROM enrollments e
        JOIN students s ON e.student_id = s.id
        JOIN users u ON s.user_id = u.id
        JOIN courses c ON e.course_id = c.id
        WHERE c.teacher_id = ?
        ORDER BY c.course_name, u.first_name
    ");
    $stmt->execute([$teacherId]);
    $roster = $stmt->fetchAll();

} catch (PDOException $e) {
    $roster = [];
    $error = "Data Fetch Error: " . $e->getMessage();
}
?>

<div class="dashboard-container">
    <header class="dashboard-header">
        <div class="header-brand">
            <h1>Academic Management System</h1>
            <p>Teacher Portal > Student Roster</p>
        </div>
        <div class="header-icons" style="margin-left: 20px; display: flex; gap: 15px; align-items: center;">
            <a href="teacher_dashboard.php" style="color: #fff; text-decoration: none; font-weight: 700; font-size: 0.9rem; padding: 8px 16px; border: 1px solid rgba(255,255,255,0.3); border-radius: 12px; transition: 0.3s ease; background: rgba(255,255,255,0.1);"><i class="fas fa-th-large" style="margin-right: 8px;"></i>Dashboard</a>
            <a href="logout.php" style="color: #fff; text-decoration: none; font-weight: 700; font-size: 0.9rem; padding: 8px 16px; border: 1px solid rgba(255,255,255,0.3); border-radius: 12px;">Logout</a>
        </div>
    </header>

    <main class="main-content">
        <section class="welcome-header" style="margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h2 style="font-size: 1.8rem; color: #1e293b; font-weight: 800;">My Student Community</h2>
                <p style="color: #64748b; font-weight: 600;">Viewing all students currently enrolled in your academic modules.</p>
            </div>
            <div style="background: var(--brand-gradient); color: #fff; padding: 10px 20px; border-radius: 12px; font-weight: 800; font-size: 0.9rem;">
                Total Students: <?php echo count($roster); ?>
            </div>
        </section>

        <?php if (isset($error)): ?>
            <div style="background: #fee2e2; color: #b91c1c; padding: 15px; border-radius: 12px; margin-bottom: 25px;">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <div class="table-container" style="border-radius: 20px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); overflow: hidden;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead style="background: #f8fafc;">
                    <tr>
                        <th style="padding: 20px; text-align: left; color: #64748b; font-weight: 700; text-transform: uppercase; font-size: 0.8rem; border-bottom: 2px solid #f1f5f9;">Student Name</th>
                        <th style="padding: 20px; text-align: left; color: #64748b; font-weight: 700; text-transform: uppercase; font-size: 0.8rem; border-bottom: 2px solid #f1f5f9;">Course / Module</th>
                        <th style="padding: 20px; text-align: left; color: #64748b; font-weight: 700; text-transform: uppercase; font-size: 0.8rem; border-bottom: 2px solid #f1f5f9;">Contact Info</th>
                        <th style="padding: 20px; text-align: center; color: #64748b; font-weight: 700; text-transform: uppercase; font-size: 0.8rem; border-bottom: 2px solid #f1f5f9;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($roster)): ?>
                        <tr>
                            <td colspan="4" style="padding: 40px; text-align: center; color: #94a3b8;">No students are currently enrolled in your modules.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($roster as $s): ?>
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <td style="padding: 20px;">
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <div style="width: 40px; height: 40px; background: #e0e7ff; color: #4338ca; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 0.9rem;">
                                        <?php echo substr($s['first_name'], 0, 1) . substr($s['last_name'], 0, 1); ?>
                                    </div>
                                    <div style="display: flex; flex-direction: column;">
                                        <span style="font-weight: 800; color: #1e293b;"><?php echo htmlspecialchars($s['first_name'] . ' ' . $s['last_name']); ?></span>
                                        <span style="font-size: 0.8rem; color: #64748b;"><?php echo htmlspecialchars($s['email']); ?></span>
                                    </div>
                                </div>
                            </td>
                            <td style="padding: 20px;">
                                <div style="display: flex; flex-direction: column;">
                                    <span style="font-weight: 700; color: #475569;"><?php echo htmlspecialchars($s['course_name']); ?></span>
                                    <span style="font-size: 0.75rem; color: #94a3b8; font-weight: 600;"><?php echo htmlspecialchars($s['course_code']); ?></span>
                                </div>
                            </td>
                            <td style="padding: 20px;">
                                <span style="font-size: 0.85rem; color: #64748b; font-weight: 600;"><?php echo htmlspecialchars($s['contact']); ?></span>
                            </td>
                            <td style="padding: 20px; text-align: center;">
                                <span style="background: #dcfce7; color: #166534; padding: 6px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 800; text-transform: uppercase;">Active</span>
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

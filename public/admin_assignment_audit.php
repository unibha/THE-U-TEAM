<?php
require_once '../includes/auth_middleware.php';
require_once '../includes/db.php';

// Only Admin allowed
checkAuth(['Admin']);

$pageTitle = "Assignment Audit - Admin Portal";
include_once '../includes/header.php';

// Fetch all assignments with teacher info and submission counts
$stmt = $pdo->prepare("
    SELECT a.*, c.course_name, u.first_name as teacher_name,
    (SELECT COUNT(*) FROM submissions WHERE assignment_id = a.assignment_id) as submission_count
    FROM assignment a
    JOIN courses c ON a.course_id = c.id
    JOIN users u ON a.created_by = u.id
    ORDER BY a.created_at DESC
");
$stmt->execute();
$assignments = $stmt->fetchAll();
?>

<div class="dashboard-container">
    <header class="dashboard-header">
        <div class="header-brand">
            <h1>Academic Management System</h1>
            <p>Admin Portal > Global Assignment Audit</p>
        </div>
        <div class="header-icons" style="margin-left: 20px; display: flex; gap: 15px; align-items: center;">
            <a href="admin_dashboard.php" style="color: #fff; text-decoration: none; font-weight: 700; font-size: 0.9rem; padding: 8px 16px; border: 1px solid rgba(255,255,255,0.3); border-radius: 12px;"><i class="fas fa-th-large" style="margin-right: 8px;"></i>Dashboard</a>
        </div>
    </header>

    <main class="main-content" style="padding: 40px 60px; background: #f8fafc;">
        <h2 style="font-size: 1.8rem; color: #1e293b; font-weight: 800; margin-bottom: 25px;">Institutional Assignment Overview</h2>

        <div style="background: #fff; border-radius: 24px; box-shadow: 0 4px 20px rgba(0,0,0,0.02); border: 1px solid #f1f5f9; overflow: hidden;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead style="background: #f8fafc;">
                    <tr>
                        <th style="padding: 20px; text-align: left; color: #64748b; font-size: 0.75rem; text-transform: uppercase; font-weight: 700;">Assignment Details</th>
                        <th style="padding: 20px; text-align: left; color: #64748b; font-size: 0.75rem; text-transform: uppercase; font-weight: 700;">Teacher</th>
                        <th style="padding: 20px; text-align: center; color: #64748b; font-size: 0.75rem; text-transform: uppercase; font-weight: 700;">Submissions</th>
                        <th style="padding: 20px; text-align: center; color: #64748b; font-size: 0.75rem; text-transform: uppercase; font-weight: 700;">Due Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($assignments)): ?>
                        <tr><td colspan="4" style="padding: 60px; text-align: center; color: #94a3b8; font-weight: 600;">No assignments created in the system.</td></tr>
                    <?php else: ?>
                        <?php foreach ($assignments as $a): ?>
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <td style="padding: 20px;">
                                <div style="font-weight: 800; color: #1e293b;"><?php echo htmlspecialchars($a['title']); ?></div>
                                <div style="font-size: 0.75rem; color: #94a3b8; font-weight: 700;"><?php echo htmlspecialchars($a['course_name']); ?></div>
                            </td>
                            <td style="padding: 20px; color: #475569; font-weight: 600; font-size: 0.9rem;">
                                Prof. <?php echo htmlspecialchars($a['teacher_name']); ?>
                            </td>
                            <td style="padding: 20px; text-align: center;">
                                <span style="background: #eff6ff; color: #3b82f6; padding: 6px 14px; border-radius: 10px; font-weight: 800; font-size: 0.85rem;">
                                    <?php echo $a['submission_count']; ?> Files
                                </span>
                            </td>
                            <td style="padding: 20px; text-align: center; color: #ef4444; font-weight: 800; font-size: 0.85rem;">
                                <?php echo date('M d, Y', strtotime($a['due_date'])); ?>
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

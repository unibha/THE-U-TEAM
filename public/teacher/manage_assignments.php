<?php
require_once __DIR__ . '/../../config.php';

require_once ROOT_DIR . '/includes/security/auth_middleware.php';
require_once ROOT_DIR . '/includes/db.php';

// Only Teacher allowed
checkAuth(['Teacher']);

$teacherUserId = $_SESSION['user_id'];
$pageTitle = "Manage Assignments - Teacher Portal";
include_once ROOT_DIR . '/includes/header.php';

$message = $_GET['msg'] ?? '';

// Fetch teacher's assignments
$stmt = $pdo->prepare("
    SELECT a.*, c.course_name, 
    (SELECT COUNT(*) FROM submissions WHERE assignment_id = a.assignment_id) as submission_count
    FROM assignment a
    JOIN courses c ON a.course_id = c.id
    WHERE a.created_by = ?
    ORDER BY a.created_at DESC
");
$stmt->execute([$teacherUserId]);
$assignments = $stmt->fetchAll();
?>

<div class="dashboard-container">
    <header class="dashboard-header">
        <div class="header-brand">
            <h1>Academic Management System</h1>
            <p>Teacher Portal > Assignments</p>
        </div>
        <div class="header-tools">
            <div class="search-wrapper">
                <i class="fas fa-search"></i>
                <input type="text" id="assignmentSearch" placeholder="Filter assignments...">
            </div>
            <div class="header-icons" style="margin-left: 20px; display: flex; gap: 15px; align-items: center;">
                <a href="<?php echo ROOT_URL; ?>/public/teacher/dashboard.php" style="color: #fff; text-decoration: none; font-weight: 700; font-size: 0.9rem; padding: 8px 16px; border: 1px solid rgba(255,255,255,0.3); border-radius: 12px;"><i class="fas fa-th-large" style="margin-right: 8px;"></i>Dashboard</a>
                <a href="<?php echo ROOT_URL; ?>/public/teacher/create_assignment.php" style="color: #fff; text-decoration: none; font-weight: 700; font-size: 0.9rem; padding: 8px 16px; border: 1px solid rgba(255,255,255,0.3); border-radius: 12px; background: #f97316;"><i class="fas fa-plus" style="margin-right: 8px;"></i>Create New</a>
                
            </div>
        </div>
    </header>

    <main class="main-content" style="padding: 40px 60px; background: #f8fafc;">
        <h2 style="font-size: 1.8rem; color: #1e293b; font-weight: 800; margin-bottom: 25px;">My Assignments</h2>

        <?php if ($message): ?> <div style="background: #dcfce7; color: #166534; padding: 15px; border-radius: 12px; margin-bottom: 20px; font-weight: 600; border: 1px solid #bbf7d0;"><i class="fas fa-check-circle" style="margin-right: 10px;"></i><?php echo $message; ?></div> <?php endif; ?>

        <div style="background: #fff; border-radius: 24px; box-shadow: 0 4px 20px rgba(0,0,0,0.02); border: 1px solid #f1f5f9; overflow: hidden;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead style="background: #f8fafc;">
                    <tr>
                        <th style="padding: 20px; text-align: left; color: #64748b; font-size: 0.75rem; text-transform: uppercase; font-weight: 700;">Assignment Title</th>
                        <th style="padding: 20px; text-align: left; color: #64748b; font-size: 0.75rem; text-transform: uppercase; font-weight: 700;">Course</th>
                        <th style="padding: 20px; text-align: center; color: #64748b; font-size: 0.75rem; text-transform: uppercase; font-weight: 700;">Submissions</th>
                        <th style="padding: 20px; text-align: center; color: #64748b; font-size: 0.75rem; text-transform: uppercase; font-weight: 700;">Due Date</th>
                        <th style="padding: 20px; text-align: center; color: #64748b; font-size: 0.75rem; text-transform: uppercase; font-weight: 700;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($assignments)): ?>
                        <tr><td colspan="5" style="padding: 60px; text-align: center; color: #94a3b8; font-weight: 600;">You haven't created any assignments yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($assignments as $a): ?>
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <td style="padding: 20px; font-weight: 800; color: #1e293b;"><?php echo htmlspecialchars($a['title']); ?></td>
                            <td style="padding: 20px; color: #8b5cf6; font-weight: 700;"><?php echo htmlspecialchars($a['course_name']); ?></td>
                            <td style="padding: 20px; text-align: center;">
                                <span style="background: #fdf4ff; color: #a855f7; padding: 6px 14px; border-radius: 10px; font-weight: 800; font-size: 0.85rem;">
                                    <?php echo $a['submission_count']; ?> Submissions
                                </span>
                            </td>
                            <td style="padding: 20px; text-align: center; color: #f97316; font-weight: 800; font-size: 0.85rem;">
                                <?php echo date('M d, H:i', strtotime($a['due_date'])); ?>
                            </td>
                            <td style="padding: 20px; text-align: center;">
                                <a href="view_submissions.php?assignment_id=<?php echo $a['assignment_id']; ?>" style="padding: 8px 15px; background: #eff6ff; color: #3b82f6; border-radius: 10px; font-weight: 800; font-size: 0.75rem; text-decoration: none;">View Submissions</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<script>
document.getElementById('assignmentSearch').addEventListener('input', function(e) {
    const query = e.target.value;
    
    fetch(`<?php echo ROOT_URL; ?>/public/api/search_assignments.php?q=${encodeURIComponent(query)}`)
        .then(response => response.json())
        .then(data => {
            const tbody = document.querySelector('table tbody');
            tbody.innerHTML = '';
            
            if (data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" style="padding: 60px; text-align: center; color: #94a3b8; font-weight: 600;">No assignments found matching your search.</td></tr>';
                return;
            }
            
            data.forEach(a => {
                const row = document.createElement('tr');
                row.style.borderBottom = '1px solid #f1f5f9';
                
                // Helper to format date in JS
                const dueDate = new Date(a.due_date).toLocaleDateString('en-US', { month: 'short', day: '2-digit' }) + ', ' + 
                                new Date(a.due_date).toLocaleTimeString('en-US', { hour12: false, hour: '2-digit', minute: '2-digit' });

                row.innerHTML = `
                    <td style="padding: 20px; font-weight: 800; color: #1e293b;">${a.title}</td>
                    <td style="padding: 20px; color: #8b5cf6; font-weight: 700;">${a.course_name}</td>
                    <td style="padding: 20px; text-align: center;">
                        <span style="background: #fdf4ff; color: #a855f7; padding: 6px 14px; border-radius: 10px; font-weight: 800; font-size: 0.85rem;">
                            ${a.submission_count} Submissions
                        </span>
                    </td>
                    <td style="padding: 20px; text-align: center; color: #f97316; font-weight: 800; font-size: 0.85rem;">
                        ${dueDate}
                    </td>
                    <td style="padding: 20px; text-align: center;">
                        <a href="<?php echo ROOT_URL; ?>/public/teacher/view_submissions.php?assignment_id=${a.assignment_id}" style="padding: 8px 15px; background: #eff6ff; color: #3b82f6; border-radius: 10px; font-weight: 800; font-size: 0.75rem; text-decoration: none;">View Submissions</a>
                    </td>
                `;
                tbody.appendChild(row);
            });
        })
        .catch(error => console.error('Error fetching assignments:', error));
});
</script>

<?php include_once ROOT_DIR . '/includes/footer.php'; ?>


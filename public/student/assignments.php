<?php
require_once __DIR__ . '/../../config.php';

require_once ROOT_DIR . '/includes/security/auth_middleware.php';
require_once ROOT_DIR . '/includes/db.php';
require_once ROOT_DIR . '/includes/helpers/assignment_helper.php';
require_once ROOT_DIR . '/includes/security/csrf_helper.php';

// Only Student allowed
checkAuth(['Student']);

$studentId = $_SESSION['user_id'];
$pageTitle = "My Assignments - Student Portal";
include_once ROOT_DIR . '/includes/header.php';

$message = $_GET['msg'] ?? '';
$error = '';

// Handle Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assignment_id'])) {
    validate_csrf();
    $assignmentId = $_POST['assignment_id'];
    
    // Check if assignment exists and student is enrolled
    $stmt = $pdo->prepare("
        SELECT a.* 
        FROM assignment a 
        JOIN enrollments e ON a.course_id = e.course_id 
        WHERE a.assignment_id = ? AND e.student_id = (SELECT id FROM students WHERE user_id = ?)
    ");
    $stmt->execute([$assignmentId, $studentId]);
    $assignment = $stmt->fetch();
    
    if (!$assignment) {
        $error = "Assignment not found or not enrolled in course.";
    } elseif (strtotime($assignment['due_date']) < time()) {
        $error = "DEADLINE PASSED: You cannot submit after the due date.";
    } else {
        // Check if already submitted
        $check = $pdo->prepare("SELECT submission_id FROM submissions WHERE assignment_id = ? AND student_id = ?");
        $check->execute([$assignmentId, $studentId]);
        
        if ($check->fetch()) {
            $error = "You have already submitted this assignment.";
        } else {
            $uploadResult = uploadSubmissionFile($_FILES['submission_file']);
            if ($uploadResult['success']) {
                try {
                    $stmt = $pdo->prepare("INSERT INTO submissions (assignment_id, student_id, file_name, file_path) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$assignmentId, $studentId, $uploadResult['file_name'], $uploadResult['file_path']]);
                    $message = "Assignment submitted successfully!";
                } catch (Exception $e) {
                    deleteSubmissionFile($uploadResult['file_path']);
                    $error = "Database Error: " . $e->getMessage();
                }
            } else {
                $error = $uploadResult['message'];
            }
        }
    }
}

// Fetch available assignments and submissions
$stmt = $pdo->prepare("
    SELECT a.*, c.course_name, s.submission_id, s.submitted_at, s.marks, s.feedback, s.file_name
    FROM assignment a
    JOIN courses c ON a.course_id = c.id
    JOIN enrollments e ON c.id = e.course_id
    JOIN students st ON e.student_id = st.id
    LEFT JOIN submissions s ON a.assignment_id = s.assignment_id AND s.student_id = ?
    WHERE st.user_id = ?
    ORDER BY a.due_date ASC
");
$stmt->execute([$studentId, $studentId]);
$assignments = $stmt->fetchAll();
?>

<div class="dashboard-container">
    <header class="dashboard-header">
        <div class="header-brand">
            <h1>Academic Management System</h1>
            <p>Student Portal > Assignment Hub</p>
        </div>
        <div class="header-tools">
            <div class="search-wrapper">
                <i class="fas fa-search"></i>
                <input type="text" id="assignmentSearch" placeholder="Filter tasks...">
            </div>
            <div class="header-icons" style="margin-left: 20px; display: flex; gap: 15px; align-items: center;">
                <a href="<?php echo ROOT_URL; ?>/public/student/dashboard.php" style="color: #fff; text-decoration: none; font-weight: 700; font-size: 0.9rem; padding: 8px 16px; border: 1px solid rgba(255,255,255,0.3); border-radius: 12px;"><i class="fas fa-th-large" style="margin-right: 8px;"></i>Dashboard</a>
                
            </div>
        </div>
    </header>

    <main class="main-content" style="padding: 40px 60px; background: #f8fafc;">
        <h2 style="font-size: 1.8rem; color: #1e293b; font-weight: 800; margin-bottom: 25px;">Pending & Completed Tasks</h2>

        <?php if ($message): ?> <div style="background: #dcfce7; color: #166534; padding: 15px; border-radius: 12px; margin-bottom: 20px; font-weight: 600; border: 1px solid #bbf7d0;"><i class="fas fa-check-circle" style="margin-right: 10px;"></i><?php echo $message; ?></div> <?php endif; ?>
        <?php if ($error): ?> <div style="background: #fee2e2; color: #991b1b; padding: 15px; border-radius: 12px; margin-bottom: 20px; font-weight: 600; border: 1px solid #fecaca;"><i class="fas fa-exclamation-circle" style="margin-right: 10px;"></i><?php echo $error; ?></div> <?php endif; ?>

        <div id="assignmentGrid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 30px;">
            <?php foreach ($assignments as $a): ?>
                <?php 
                    $isSubmitted = !empty($a['submission_id']);
                    $isExpired = strtotime($a['due_date']) < time();
                ?>
                <div class="assignment-card" style="background: #fff; padding: 30px; border-radius: 28px; border: 1px solid #f1f5f9; box-shadow: 0 4px 20px rgba(0,0,0,0.02); display: flex; flex-direction: column;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 20px;">
                        <span style="background: #f1f5f9; color: #475569; padding: 5px 12px; border-radius: 8px; font-size: 0.7rem; font-weight: 800; text-transform: uppercase;"><?php echo htmlspecialchars($a['course_name']); ?></span>
                        <?php if ($isSubmitted): ?>
                            <span style="color: #10b981; font-weight: 800; font-size: 0.75rem;"><i class="fas fa-check-double" style="margin-right: 5px;"></i>SUBMITTED</span>
                        <?php elseif ($isExpired): ?>
                            <span style="color: #ef4444; font-weight: 800; font-size: 0.75rem;"><i class="fas fa-clock" style="margin-right: 5px;"></i>DEADLINE PASSED</span>
                        <?php else: ?>
                            <span style="color: #f59e0b; font-weight: 800; font-size: 0.75rem;"><i class="fas fa-hourglass-half" style="margin-right: 5px;"></i>PENDING</span>
                        <?php endif; ?>
                    </div>

                    <h3 style="font-size: 1.25rem; color: #1e293b; font-weight: 800; margin-bottom: 10px;"><?php echo htmlspecialchars($a['title']); ?></h3>
                    <p style="font-size: 0.85rem; color: #64748b; line-height: 1.6; margin-bottom: 20px; flex-grow: 1;"><?php echo htmlspecialchars($a['description']); ?></p>

                    <div style="background: #f8fafc; padding: 15px; border-radius: 16px; margin-bottom: 20px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                            <span style="font-size: 0.75rem; color: #94a3b8; font-weight: 700;">DUE DATE</span>
                            <span style="font-size: 0.75rem; color: #475569; font-weight: 800;"><?php echo date('M d, H:i', strtotime($a['due_date'])); ?></span>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span style="font-size: 0.75rem; color: #94a3b8; font-weight: 700;">TOTAL MARKS</span>
                            <span style="font-size: 0.75rem; color: #475569; font-weight: 800;"><?php echo $a['total_marks']; ?></span>
                        </div>
                    </div>

                    <?php if ($isSubmitted): ?>
                        <div style="padding-top: 15px; border-top: 1px solid #f1f5f9;">
                            <p style="font-size: 0.75rem; color: #94a3b8; font-weight: 700; margin-bottom: 10px;">MY RESULT</p>
                            <?php if ($a['marks'] !== null): ?>
                                <div style="display: flex; align-items: center; gap: 15px;">
                                    <div style="font-size: 1.8rem; font-weight: 900; color: #10b981;"><?php echo $a['marks']; ?></div>
                                    <div style="font-size: 0.8rem; color: #64748b; font-weight: 600;"><?php echo htmlspecialchars($a['feedback']); ?></div>
                                </div>
                            <?php else: ?>
                                <p style="font-size: 0.85rem; color: #ca8a04; font-weight: 700;">Awaiting Grading...</p>
                            <?php endif; ?>
                        </div>
                    <?php elseif (!$isExpired): ?>
                        <form action="" method="POST" enctype="multipart/form-data">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="assignment_id" value="<?php echo $a['assignment_id']; ?>">
                            <div style="position: relative; margin-bottom: 15px;">
                                <input type="file" name="submission_file" required style="width: 100%; font-size: 0.8rem; padding: 10px; border: 1px solid #e2e8f0; border-radius: 12px; background: #fff;">
                            </div>
                            <button type="submit" style="width: 100%; background: var(--brand-gradient); color: #fff; padding: 12px; border: none; border-radius: 12px; font-weight: 800; cursor: pointer;">Upload Submission</button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </main>
</div>

<script>
document.getElementById('assignmentSearch').addEventListener('input', function(e) {
    const query = e.target.value;
    
    fetch(`<?php echo ROOT_URL; ?>/public/api/search_student_assignments.php?q=${encodeURIComponent(query)}`)
        .then(response => response.json())
        .then(data => {
            const grid = document.getElementById('assignmentGrid');
            grid.innerHTML = '';
            
            if (data.length === 0) {
                grid.innerHTML = '<div style="grid-column: 1/-1; padding: 60px; text-align: center; color: #94a3b8; font-weight: 600;">No assignments matching your search.</div>';
                return;
            }
            
            data.forEach(a => {
                const card = document.createElement('div');
                card.className = 'assignment-card';
                card.style.cssText = 'background: #fff; padding: 30px; border-radius: 28px; border: 1px solid #f1f5f9; box-shadow: 0 4px 20px rgba(0,0,0,0.02); display: flex; flex-direction: column;';
                
                const isSubmitted = !!a.submission_id;
                const isExpired = new Date(a.due_date) < new Date();
                const dueDate = new Date(a.due_date).toLocaleDateString('en-US', { month: 'short', day: '2-digit' }) + ', ' + 
                                new Date(a.due_date).toLocaleTimeString('en-US', { hour12: false, hour: '2-digit', minute: '2-digit' });

                let statusBadge = '';
                if (isSubmitted) {
                    statusBadge = '<span style="color: #10b981; font-weight: 800; font-size: 0.75rem;"><i class="fas fa-check-double" style="margin-right: 5px;"></i>SUBMITTED</span>';
                } else if (isExpired) {
                    statusBadge = '<span style="color: #ef4444; font-weight: 800; font-size: 0.75rem;"><i class="fas fa-clock" style="margin-right: 5px;"></i>DEADLINE PASSED</span>';
                } else {
                    statusBadge = '<span style="color: #f59e0b; font-weight: 800; font-size: 0.75rem;"><i class="fas fa-hourglass-half" style="margin-right: 5px;"></i>PENDING</span>';
                }

                let footerContent = '';
                if (isSubmitted) {
                    footerContent = `
                        <div style="padding-top: 15px; border-top: 1px solid #f1f5f9;">
                            <p style="font-size: 0.75rem; color: #94a3b8; font-weight: 700; margin-bottom: 10px;">MY RESULT</p>
                            ${a.marks !== null ? `
                                <div style="display: flex; align-items: center; gap: 15px;">
                                    <div style="font-size: 1.8rem; font-weight: 900; color: #10b981;">${a.marks}</div>
                                    <div style="font-size: 0.8rem; color: #64748b; font-weight: 600;">${a.feedback || ''}</div>
                                </div>
                            ` : '<p style="font-size: 0.85rem; color: #ca8a04; font-weight: 700;">Awaiting Grading...</p>'}
                        </div>
                    `;
                } else if (!isExpired) {
                    footerContent = `
                        <form action="" method="POST" enctype="multipart/form-data">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="assignment_id" value="${a.assignment_id}">
                            <div style="position: relative; margin-bottom: 15px;">
                                <input type="file" name="submission_file" required style="width: 100%; font-size: 0.8rem; padding: 10px; border: 1px solid #e2e8f0; border-radius: 12px; background: #fff;">
                            </div>
                            <button type="submit" style="width: 100%; background: var(--brand-gradient); color: #fff; padding: 12px; border: none; border-radius: 12px; font-weight: 800; cursor: pointer;">Upload Submission</button>
                        </form>
                    `;
                }

                card.innerHTML = `
                    <div style="display: flex; justify-content: space-between; margin-bottom: 20px;">
                        <span style="background: #f1f5f9; color: #475569; padding: 5px 12px; border-radius: 8px; font-size: 0.7rem; font-weight: 800; text-transform: uppercase;">${a.course_name}</span>
                        ${statusBadge}
                    </div>
                    <h3 style="font-size: 1.25rem; color: #1e293b; font-weight: 800; margin-bottom: 10px;">${a.title}</h3>
                    <p style="font-size: 0.85rem; color: #64748b; line-height: 1.6; margin-bottom: 20px; flex-grow: 1;">${a.description}</p>
                    <div style="background: #f8fafc; padding: 15px; border-radius: 16px; margin-bottom: 20px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                            <span style="font-size: 0.75rem; color: #94a3b8; font-weight: 700;">DUE DATE</span>
                            <span style="font-size: 0.75rem; color: #475569; font-weight: 800;">${dueDate}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span style="font-size: 0.75rem; color: #94a3b8; font-weight: 700;">TOTAL MARKS</span>
                            <span style="font-size: 0.75rem; color: #475569; font-weight: 800;">${a.total_marks}</span>
                        </div>
                    </div>
                    ${footerContent}
                `;
                grid.appendChild(card);
            });
        })
        .catch(error => console.error('Error fetching assignments:', error));
});
</script>

<?php include_once ROOT_DIR . '/includes/footer.php'; ?>


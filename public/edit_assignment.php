<?php
require_once '../includes/auth_middleware.php';
require_once '../includes/db.php';

// Teacher and Admin allowed
checkAuth(['Admin', 'Teacher']);

$pageTitle = "Edit Assignment - Academic Management System";
include_once '../includes/header.php';

$message = '';
$error = '';
$role = $_SESSION['role'];
$userId = $_SESSION['user_id'];
$assignmentId = $_GET['id'] ?? 0;

// Fetch current assignment data
try {
    $stmt = $pdo->prepare("
        SELECT a.*, c.course_name, c.teacher_id
        FROM assignments a
        JOIN courses c ON a.course_id = c.id
        WHERE a.id = ?
    ");
    $stmt->execute([$assignmentId]);
    $assignment = $stmt->fetch();

    if (!$assignment) {
        header("Location: manage_assignments.php");
        exit();
    }

    // Security check: If teacher, ensure it's their course
    if ($role == 'Teacher') {
        $stmt2 = $pdo->prepare("SELECT id FROM teachers WHERE user_id = ?");
        $stmt2->execute([$userId]);
        $teacherInternalId = $stmt2->fetchColumn();
        
        if ($assignment['teacher_id'] != $teacherInternalId) {
            header("Location: manage_assignments.php?error=Access Denied");
            exit();
        }
    }
} catch (PDOException $e) {
    $error = "Error fetching assignment: " . $e->getMessage();
}

// Handle Update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $due_date = $_POST['due_date'] ?? '';

    if (empty($title)) {
        $error = "Title is mandatory.";
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE assignments SET title = ?, description = ?, due_date = ? WHERE id = ?");
            $stmt->execute([$title, $description, $due_date, $assignmentId]);
            $message = "Assignment updated successfully! <a href='manage_assignments.php?course_id=" . $assignment['course_id'] . "' style='color: #166534; font-weight: 700;'>Return to List</a>";
            
            // Refresh local data
            $assignment['title'] = $title;
            $assignment['description'] = $description;
            $assignment['due_date'] = $due_date;
        } catch (PDOException $e) {
            $error = "Update error: " . $e->getMessage();
        }
    }
}
?>

<div class="dashboard-container" style="flex-direction: column;">
    <header class="dashboard-header">
        <div class="header-brand">
            <h1>Academic Management System</h1>
            <p>Portal > Edit Assignment</p>
        </div>
        <div class="header-icons" style="margin-left: 20px; display: flex; gap: 15px; align-items: center;">
            <a href="manage_assignments.php?course_id=<?php echo $assignment['course_id']; ?>" style="color: #fff; text-decoration: none; font-weight: 700; font-size: 0.9rem; padding: 8px 16px; border: 1px solid rgba(255,255,255,0.3); border-radius: 12px;">Back to Tasks</a>
        </div>
    </header>

    <main class="main-content" style="padding: 40px 60px; background: #f8fafc;">
        <section class="form-container" style="max-width: 800px; margin: 0 auto; background: #fff; padding: 50px; border-radius: 24px; box-shadow: 0 10px 30px rgba(0,0,0,0.03);">
            <h2 style="font-size: 1.8rem; color: #1e293b; font-weight: 800; margin-bottom: 10px; text-align: center;">Refine Assignment Requirements</h2>
            <p style="text-align: center; color: #64748b; margin-bottom: 40px;">Editing for: <strong><?php echo htmlspecialchars($assignment['course_name']); ?></strong></p>

            <?php if ($message): ?>
                <div style="background: #dcfce7; color: #166534; padding: 15px; border-radius: 12px; margin-bottom: 30px; text-align: center;">
                    <i class="fas fa-check-circle" style="margin-right: 10px;"></i> <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div style="background: #fee2e2; color: #991b1b; padding: 15px; border-radius: 12px; margin-bottom: 30px; text-align: center;">
                    <i class="fas fa-exclamation-circle" style="margin-right: 10px;"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form action="" method="POST">
                <div class="input-group" style="margin-bottom: 25px;">
                    <label style="font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 8px; display: block;">Task Identity Title*</label>
                    <input type="text" name="title" value="<?php echo htmlspecialchars($assignment['title']); ?>" required style="width: 100%; padding: 14px; border: 2px solid #f1f5f9; border-radius: 12px; outline: none;">
                </div>

                <div class="input-group" style="margin-bottom: 25px;">
                    <label style="font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 8px; display: block;">Deadline (Date & Time)*</label>
                    <input type="datetime-local" name="due_date" value="<?php echo date('Y-m-d\TH:i', strtotime($assignment['due_date'])); ?>" required style="width: 100%; padding: 14px; border: 2px solid #f1f5f9; border-radius: 12px; outline: none;">
                </div>

                <div class="input-group" style="margin-bottom: 35px;">
                    <label style="font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 8px; display: block;">Detailed Briefing</label>
                    <textarea name="description" style="width: 100%; padding: 14px; border: 2px solid #f1f5f9; border-radius: 12px; outline: none; height: 200px; resize: none;"><?php echo htmlspecialchars($assignment['description']); ?></textarea>
                </div>

                <div style="text-align: center;">
                    <button type="submit" style="background: var(--brand-gradient); color: #fff; padding: 18px 60px; border: none; border-radius: 15px; font-size: 1.1rem; font-weight: 800; cursor: pointer; transition: all 0.3s ease; box-shadow: 0 10px 20px rgba(139, 92, 246, 0.2);">
                        Update Requirement Set
                    </button>
                </div>
            </form>
        </section>
    </main>
</div>

<?php include_once '../includes/footer.php'; ?>

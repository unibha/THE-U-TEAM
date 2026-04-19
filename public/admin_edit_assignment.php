<?php
require_once '../includes/auth_middleware.php';
require_once '../includes/db.php';

// Only Admin allowed
checkAuth(['Admin']);

$pageTitle = "Modify Assignment - Admin Portal";
include_once '../includes/header.php';

$message = '';
$error = '';
$assignmentId = $_GET['id'] ?? 0;

// Fetch current assignment data
try {
    $stmt = $pdo->prepare("
        SELECT a.*, c.course_name, c.course_code 
        FROM assignments a 
        JOIN courses c ON a.course_id = c.id 
        WHERE a.id = ?
    ");
    $stmt->execute([$assignmentId]);
    $assignment = $stmt->fetch();

    if (!$assignment) {
        header("Location: admin_manage_assignments.php");
        exit();
    }
} catch (PDOException $e) {
    $error = "Data fetch failure: " . $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title'] ?? '';
    $due_date = $_POST['due_date'] ?? '';
    $description = $_POST['description'] ?? '';

    if (empty($title) || empty($due_date)) {
        $error = "Title and Deadline are required fields.";
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE assignments SET title = ?, due_date = ?, description = ? WHERE id = ?");
            $stmt->execute([$title, $due_date, $description, $assignmentId]);
            $message = "Assignment updated successfully! <a href='admin_manage_assignments.php' style='color: #166534; font-weight: 700;'>Return to list</a>";
            $assignment['title'] = $title;
            $assignment['due_date'] = $due_date;
            $assignment['description'] = $description;
        } catch (PDOException $e) {
            $error = "Update failure: " . $e->getMessage();
        }
    }
}
?>

<div class="dashboard-container">
    <header class="dashboard-header">
        <div class="header-brand">
            <h1>Academic Management System</h1>
            <p>Admin Portal > Edit Assignment</p>
        </div>
        <div class="header-icons" style="margin-left: 20px; display: flex; gap: 15px; align-items: center;">
            <a href="admin_dashboard.php" style="color: #fff; text-decoration: none; font-weight: 700; font-size: 0.9rem; padding: 8px 16px; border: 1px solid rgba(255,255,255,0.3); border-radius: 12px; transition: 0.3s ease; background: rgba(255,255,255,0.1);"><i class="fas fa-th-large" style="margin-right: 8px;"></i>Dashboard</a>
            <a href="logout.php" style="color: #fff; text-decoration: none; font-weight: 700; font-size: 0.9rem; padding: 8px 16px; border: 1px solid rgba(255,255,255,0.3); border-radius: 12px;">Logout</a>
        </div>
    </header>

    <main class="main-content">
        <section class="form-container" style="max-width: 700px; margin: 0 auto; background: #fff; padding: 50px; border-radius: 24px; box-shadow: 0 10px 30px rgba(0,0,0,0.03);">
            <h2 style="font-size: 1.8rem; color: #1e293b; font-weight: 800; margin-bottom: 30px; text-align: center;">Assignment Update Hub</h2>

            <div style="background: #f1f5f9; padding: 15px; border-radius: 12px; margin-bottom: 30px; border-left: 4px solid #8b5cf6;">
                <p style="margin: 0; font-size: 0.85rem; color: #64748b; font-weight: 600;">MODULE CONTEXT</p>
                <p style="margin: 5px 0 0; font-weight: 800; color: #1e293b;"><?php echo htmlspecialchars($assignment['course_name'] . ' (' . $assignment['course_code'] . ')'); ?></p>
            </div>

            <?php if ($message): ?> <div style="background: #dcfce7; color: #166534; padding: 15px; border-radius: 12px; margin-bottom: 30px; text-align: center;"><?php echo $message; ?></div> <?php endif; ?>
            <?php if ($error): ?> <div style="background: #fee2e2; color: #991b1b; padding: 15px; border-radius: 12px; margin-bottom: 30px; text-align: center;"><?php echo $error; ?></div> <?php endif; ?>

            <form action="" method="POST">
                <div style="margin-bottom: 25px;">
                    <label style="font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 8px; display: block;">Assignment Title*</label>
                    <input type="text" name="title" value="<?php echo htmlspecialchars($assignment['title']); ?>" required style="width: 100%; padding: 14px; border: 2px solid #f1f5f9; border-radius: 12px; outline: none; font-weight: 600;">
                </div>
                <div style="margin-bottom: 25px;">
                    <label style="font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 8px; display: block;">Submission Deadline*</label>
                    <input type="date" name="due_date" value="<?php echo date('Y-m-d', strtotime($assignment['due_date'])); ?>" required style="width: 100%; padding: 14px; border: 2px solid #f1f5f9; border-radius: 12px; outline: none; font-weight: 600;">
                </div>
                <div style="margin-bottom: 40px;">
                    <label style="font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 8px; display: block;">Refined Instructions</label>
                    <textarea name="description" style="width: 100%; padding: 14px; border: 2px solid #f1f5f9; border-radius: 12px; outline: none; height: 150px; resize: none; font-weight: 600;"><?php echo htmlspecialchars($assignment['description']); ?></textarea>
                </div>
                <button type="submit" style="width: 100%; background: var(--brand-gradient); color: #fff; padding: 18px; border: none; border-radius: 15px; font-size: 1.1rem; font-weight: 800; cursor: pointer; transition: all 0.3s ease; box-shadow: 0 10px 20px rgba(139, 92, 246, 0.2);">
                    Authorize Changes
                </button>
            </form>
        </section>
    </main>
</div>

<?php include_once '../includes/footer.php'; ?>

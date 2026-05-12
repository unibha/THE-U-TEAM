<?php
require_once '../includes/auth_middleware.php';
require_once '../includes/db.php';

// Only Admin allowed to manage notices
checkAuth(['Admin']);

$pageTitle = "Manage Notices - Academic Management System";
include_once '../includes/header.php';

$message = '';
$error = '';
$adminId = $_SESSION['user_id'];

// Handle Create / Update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_notice'])) {
    $id = $_POST['notice_id'] ?? null;
    $title = $_POST['title'];
    $content = $_POST['content'];
    $category = $_POST['category'];
    $audience = $_POST['target_audience'];

    try {
        if ($id) {
            $stmt = $pdo->prepare("UPDATE notice SET title = ?, content = ?, category = ?, target_audience = ? WHERE id = ?");
            $stmt->execute([$title, $content, $category, $audience, $id]);
            $message = "Notice updated successfully!";
        } else {
            $stmt = $pdo->prepare("INSERT INTO notice (title, content, category, target_audience, created_by) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$title, $content, $category, $audience, $adminId]);
            $message = "Notice published successfully!";
        }
    } catch (PDOException $e) {
        $error = "Database Error: " . $e->getMessage();
    }
}

// Handle Delete
if (isset($_GET['delete_id'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM notice WHERE id = ?");
        $stmt->execute([$_GET['delete_id']]);
        $message = "Notice deleted.";
    } catch (PDOException $e) {
        $error = "Deletion failed.";
    }
}

// Fetch single notice for editing
$editNotice = null;
if (isset($_GET['edit_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM notice WHERE id = ?");
    $stmt->execute([$_GET['edit_id']]);
    $editNotice = $stmt->fetch();
}

// Fetch all notices
$notices = $pdo->query("SELECT * FROM notice ORDER BY created_at DESC")->fetchAll();
?>

<div class="dashboard-container">
    <header class="dashboard-header">
        <div class="header-brand">
            <h1>Academic Management System</h1>
            <p>Admin Portal > Bulletin Management</p>
        </div>
        <div class="header-icons" style="margin-left: 20px; display: flex; gap: 15px; align-items: center;">
            <a href="admin_dashboard.php" style="color: #fff; text-decoration: none; font-weight: 700; font-size: 0.9rem; padding: 8px 16px; border: 1px solid rgba(255,255,255,0.3); border-radius: 12px;"><i class="fas fa-th-large" style="margin-right: 8px;"></i>Dashboard</a>
            <a href="logout.php" style="color: #fff; text-decoration: none; font-weight: 700; font-size: 0.9rem; padding: 8px 16px; border: 1px solid rgba(255,255,255,0.3); border-radius: 12px;">Logout</a>
        </div>
    </header>

    <main class="main-content" style="padding: 40px 60px;">
        <section style="display: grid; grid-template-columns: 1fr 400px; gap: 40px;">
            <!-- Notices List -->
            <div>
                <h2 style="font-size: 1.8rem; color: #1e293b; font-weight: 800; margin-bottom: 25px;">Published Bulletins</h2>
                
                <?php if ($message): ?> <div style="background: #dcfce7; color: #166534; padding: 15px; border-radius: 12px; margin-bottom: 20px; font-weight: 700;"><?php echo $message; ?></div> <?php endif; ?>
                <?php if ($error): ?> <div style="background: #fee2e2; color: #991b1b; padding: 15px; border-radius: 12px; margin-bottom: 20px; font-weight: 700;"><?php echo $error; ?></div> <?php endif; ?>

                <div class="table-container" style="border-radius: 20px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); overflow: hidden;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead style="background: #f8fafc;">
                            <tr>
                                <th style="padding: 20px; text-align: left; color: #64748b; font-weight: 700; text-transform: uppercase; font-size: 0.8rem; border-bottom: 2px solid #f1f5f9;">Notice Details</th>
                                <th style="padding: 20px; text-align: left; color: #64748b; font-weight: 700; text-transform: uppercase; font-size: 0.8rem; border-bottom: 2px solid #f1f5f9;">Target</th>
                                <th style="padding: 20px; text-align: center; color: #64748b; font-weight: 700; text-transform: uppercase; font-size: 0.8rem; border-bottom: 2px solid #f1f5f9;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($notices)): ?>
                                <tr><td colspan="3" style="padding: 40px; text-align: center; color: #94a3b8;">No notices have been published yet.</td></tr>
                            <?php else: ?>
                                <?php foreach ($notices as $n): ?>
                                <tr style="border-bottom: 1px solid #f1f5f9;">
                                    <td style="padding: 20px;">
                                        <div style="display: flex; flex-direction: column; gap: 5px;">
                                            <span style="font-weight: 800; color: #1e293b;"><?php echo htmlspecialchars($n['title']); ?></span>
                                            <span style="font-size: 0.8rem; color: <?php echo ($n['category'] == 'Urgent') ? '#f43f5e' : '#64748b'; ?>; font-weight: 700;">
                                                <i class="fas fa-tag" style="margin-right: 5px;"></i> <?php echo $n['category']; ?> • <?php echo date('M d, Y', strtotime($n['created_at'])); ?>
                                            </span>
                                        </div>
                                    </td>
                                    <td style="padding: 20px;">
                                        <span style="font-size: 0.75rem; font-weight: 800; background: #f1f5f9; color: #475569; padding: 4px 10px; border-radius: 20px;"><?php echo $n['target_audience']; ?></span>
                                    </td>
                                    <td style="padding: 20px; text-align: center;">
                                        <div style="display: flex; gap: 15px; justify-content: center;">
                                            <a href="?edit_id=<?php echo $n['id']; ?>" style="color: #6366f1; font-size: 1.1rem;"><i class="fas fa-edit"></i></a>
                                            <a href="?delete_id=<?php echo $n['id']; ?>" style="color: #f43f5e; font-size: 1.1rem;" onclick="return confirm('Erase this notice?')"><i class="fas fa-trash-alt"></i></a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Management Sidebar -->
            <aside style="background: #f8fafc; padding: 30px; border-radius: 24px; border: 1px solid #f1f5f9; height: fit-content;">
                <h3 style="font-size: 1.3rem; color: #1e293b; font-weight: 800; margin-bottom: 25px;">
                    <?php echo $editNotice ? "Refine Bulletin" : "Compose New Bulletin"; ?>
                </h3>
                <form action="" method="POST">
                    <?php if ($editNotice): ?>
                        <input type="hidden" name="notice_id" value="<?php echo $editNotice['id']; ?>">
                    <?php endif; ?>

                    <div style="margin-bottom: 20px;">
                        <label style="font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 8px; display: block;">Headline Title</label>
                        <input type="text" name="title" value="<?php echo $editNotice['title'] ?? ''; ?>" required style="width: 100%; padding: 12px; border: 2px solid #fff; border-radius: 12px; outline: none; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
                    </div>

                    <div style="margin-bottom: 20px;">
                        <label style="font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 8px; display: block;">Notice Content</label>
                        <textarea name="content" required style="width: 100%; height: 150px; padding: 12px; border: 2px solid #fff; border-radius: 12px; outline: none; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); resize: vertical;"><?php echo $editNotice['content'] ?? ''; ?></textarea>
                    </div>

                    <div style="display: flex; gap: 15px; margin-bottom: 30px;">
                        <div style="flex: 1;">
                            <label style="font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 8px; display: block;">Priority Category</label>
                            <select name="category" required style="width: 100%; padding: 10px; border: 2px solid #fff; border-radius: 10px; background: #fff; outline: none; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
                                <option value="General" <?php echo ($editNotice['category'] ?? '') == 'General' ? 'selected' : ''; ?>>General</option>
                                <option value="Urgent" <?php echo ($editNotice['category'] ?? '') == 'Urgent' ? 'selected' : ''; ?>>Urgent</option>
                                <option value="Academic" <?php echo ($editNotice['category'] ?? '') == 'Academic' ? 'selected' : ''; ?>>Academic</option>
                            </select>
                        </div>
                        <div style="flex: 1;">
                            <label style="font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 8px; display: block;">Target Audience</label>
                            <select name="target_audience" required style="width: 100%; padding: 10px; border: 2px solid #fff; border-radius: 10px; background: #fff; outline: none; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
                                <option value="All" <?php echo ($editNotice['target_audience'] ?? '') == 'All' ? 'selected' : ''; ?>>All Members</option>
                                <option value="Student" <?php echo ($editNotice['target_audience'] ?? '') == 'Student' ? 'selected' : ''; ?>>Students Only</option>
                                <option value="Teacher" <?php echo ($editNotice['target_audience'] ?? '') == 'Teacher' ? 'selected' : ''; ?>>Teachers Only</option>
                            </select>
                        </div>
                    </div>

                    <button type="submit" name="save_notice" style="width: 100%; background: var(--brand-gradient); color: #fff; padding: 15px; border: none; border-radius: 12px; font-weight: 800; cursor: pointer; transition: 0.3s ease;">
                        <?php echo $editNotice ? "Update Bulletin" : "Broadcast Notice"; ?>
                    </button>
                    <?php if ($editNotice): ?>
                        <a href="manage_notice.php" style="display: block; text-align: center; margin-top: 15px; color: #64748b; font-weight: 700; text-decoration: none; font-size: 0.9rem;">Cancel Refinement</a>
                    <?php endif; ?>
                </form>
            </aside>
        </section>
    </main>
</div>

<?php include_once '../includes/footer.php'; ?>

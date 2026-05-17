require_once '../includes/auth_middleware.php';
require_once '../includes/db.php';
require_once '../includes/csrf_helper.php';
require_once '../includes/validation_helper.php';

// Only Admin allowed
checkAuth(['Admin']);

$pageTitle = "Manage Notices - Admin Portal";
include_once '../includes/header.php';

$message = '';
$error = '';

// Handle Delete
if (isset($_GET['delete_id'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM notice WHERE id = ?");
        $stmt->execute([$_GET['delete_id']]);
        $message = "Notice removed successfully.";
    } catch (PDOException $e) {
        $error = "Deletion error: " . $e->getMessage();
    }
}

// Handle Add/Edit Notice
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_notice'])) {
    // 1. Validate CSRF
    validate_csrf();

    $id = $_POST['id'] ?? '';
    $title = sanitize($_POST['title'] ?? '');
    $content = sanitize($_POST['content'] ?? '');
    $target = $_POST['target_audience'] ?? 'All';
    $priority = $_POST['priority'] ?? 'Normal';
    $publish_date = $_POST['publish_date'] ?? date('Y-m-d');
    $admin_id = $_SESSION['user_id'];

    $errors = [];
    if (empty($title)) $errors[] = "Notice title cannot be empty.";
    if (empty($content)) $errors[] = "Notice content cannot be empty.";
    if (!validate_date($publish_date)) $errors[] = "Invalid publish date format.";

    if (empty($errors)) {
        try {
            if ($id) {
                // Update
                $stmt = $pdo->prepare("UPDATE notice SET title = ?, content = ?, target_audience = ?, priority = ?, publish_date = ? WHERE id = ?");
                $stmt->execute([$title, $content, $target, $priority, $publish_date, $id]);
                $message = "Notice updated successfully.";
            } else {
                // Insert
                $stmt = $pdo->prepare("INSERT INTO notice (title, content, target_audience, priority, publish_date, created_by) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$title, $content, $target, $priority, $publish_date, $admin_id]);
                
                // Trigger Notifications
                require_once '../includes/notification_helper.php';
                $isUrgent = ($priority === 'Urgent') ? 1 : 0;
                notifyAudience($target, "New Notice: $title", $content, 'System', $isUrgent);
                
                $message = "New notice published and notifications sent.";
            }
        } catch (PDOException $e) {
            $error = "Error saving notice: " . $e->getMessage();
        }
    } else {
        $error = format_errors($errors);
    }
}

// Fetch notices for listing
$notices = $pdo->query("SELECT * FROM notice ORDER BY publish_date DESC, created_at DESC")->fetchAll();

// Handle Edit Fetch
$editNotice = null;
if (isset($_GET['edit_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM notice WHERE id = ?");
    $stmt->execute([$_GET['edit_id']]);
    $editNotice = $stmt->fetch();
}
?>

<div class="dashboard-container">
    <header class="dashboard-header">
        <div class="header-brand">
            <h1>Academic Management System</h1>
            <p>Admin Portal > Institutional Notice Board</p>
        </div>
        <div class="header-icons" style="margin-left: 20px; display: flex; gap: 15px; align-items: center;">
            <a href="admin_dashboard.php" style="color: #fff; text-decoration: none; font-weight: 700; font-size: 0.9rem; padding: 8px 16px; border: 1px solid rgba(255,255,255,0.3); border-radius: 12px;"><i class="fas fa-th-large" style="margin-right: 8px;"></i>Dashboard</a>
        </div>
    </header>

    <main class="main-content" style="padding: 40px 60px; background: #f8fafc;">
        <section style="display: grid; grid-template-columns: 1fr 400px; gap: 40px;">
            <!-- Notice List -->
            <div>
                <h2 style="font-size: 1.8rem; color: #1e293b; font-weight: 800; margin-bottom: 25px;">Published Announcements</h2>
                
                <?php if ($message): ?> <div style="background: #dcfce7; color: #166534; padding: 15px; border-radius: 12px; margin-bottom: 20px; font-weight: 600; border: 1px solid #bbf7d0;"><?php echo $message; ?></div> <?php endif; ?>
                <?php if ($error): ?> <div style="background: #fee2e2; color: #991b1b; padding: 15px; border-radius: 12px; margin-bottom: 20px; font-weight: 600; border: 1px solid #fecaca;"><?php echo $error; ?></div> <?php endif; ?>

                <div style="background: #fff; border-radius: 24px; box-shadow: 0 4px 20px rgba(0,0,0,0.02); border: 1px solid #f1f5f9; overflow: hidden;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead style="background: #f8fafc;">
                            <tr>
                                <th style="padding: 20px; text-align: left; color: #64748b; font-size: 0.75rem; text-transform: uppercase; font-weight: 700;">Details</th>
                                <th style="padding: 20px; text-align: center; color: #64748b; font-size: 0.75rem; text-transform: uppercase; font-weight: 700;">Audience</th>
                                <th style="padding: 20px; text-align: center; color: #64748b; font-size: 0.75rem; text-transform: uppercase; font-weight: 700;">Priority</th>
                                <th style="padding: 20px; text-align: center; color: #64748b; font-size: 0.75rem; text-transform: uppercase; font-weight: 700;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($notices as $n): ?>
                            <tr style="border-bottom: 1px solid #f1f5f9;">
                                <td style="padding: 20px;">
                                    <div style="font-weight: 700; color: #1e293b; margin-bottom: 4px;"><?php echo htmlspecialchars($n['title']); ?></div>
                                    <div style="font-size: 0.8rem; color: #94a3b8; font-weight: 600;">Published: <?php echo date('M d, Y', strtotime($n['publish_date'])); ?></div>
                                </td>
                                <td style="padding: 20px; text-align: center;">
                                    <span style="background: #f1f5f9; color: #475569; padding: 4px 12px; border-radius: 8px; font-size: 0.8rem; font-weight: 700;"><?php echo $n['target_audience']; ?></span>
                                </td>
                                <td style="padding: 20px; text-align: center;">
                                    <span style="background: <?php echo $n['priority'] == 'Urgent' ? '#fee2e2' : '#f0fdf4'; ?>; color: <?php echo $n['priority'] == 'Urgent' ? '#ef4444' : '#10b981'; ?>; padding: 4px 12px; border-radius: 8px; font-size: 0.8rem; font-weight: 700;">
                                        <?php echo $n['priority']; ?>
                                    </span>
                                </td>
                                <td style="padding: 20px; text-align: center;">
                                    <div style="display: flex; gap: 15px; justify-content: center;">
                                        <a href="?edit_id=<?php echo $n['id']; ?>" style="color: #3b82f6;"><i class="fas fa-edit"></i></a>
                                        <a href="?delete_id=<?php echo $n['id']; ?>" style="color: #f43f5e;" onclick="return confirm('Remove this announcement?')"><i class="fas fa-trash-alt"></i></a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Add/Edit Sidebar -->
            <aside style="background: #fff; padding: 35px; border-radius: 30px; border: 1px solid #f1f5f9; box-shadow: 0 10px 40px rgba(0,0,0,0.03); height: fit-content; position: sticky; top: 40px;">
                <h3 style="font-size: 1.4rem; color: #1e293b; font-weight: 800; margin-bottom: 25px;">
                    <?php echo $editNotice ? 'Edit Announcement' : 'New Announcement'; ?>
                </h3>
                <form action="" method="POST">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="id" value="<?php echo $editNotice['id'] ?? ''; ?>">
                    
                    <div style="margin-bottom: 20px;">
                        <label style="font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 8px; display: block;">Notice Title</label>
                        <input type="text" name="title" value="<?php echo htmlspecialchars($editNotice['title'] ?? ''); ?>" required placeholder="e.g. Mid-Term Schedule Update" style="width: 100%; padding: 12px; border: 2px solid #f8fafc; border-radius: 12px; background: #f8fafc; font-weight: 600; outline: none;">
                    </div>

                    <div style="margin-bottom: 20px;">
                        <label style="font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 8px; display: block;">Announcement Content</label>
                        <textarea name="content" required placeholder="Details about the notice..." style="width: 100%; height: 150px; padding: 12px; border: 2px solid #f8fafc; border-radius: 12px; background: #f8fafc; font-weight: 600; outline: none; resize: none;"><?php echo htmlspecialchars($editNotice['content'] ?? ''); ?></textarea>
                    </div>

                    <div style="margin-bottom: 20px; display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div>
                            <label style="font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 8px; display: block;">Target Audience</label>
                            <select name="target_audience" style="width: 100%; padding: 12px; border: 2px solid #f8fafc; border-radius: 12px; background: #f8fafc; font-weight: 600;">
                                <option value="All" <?php echo ($editNotice['target_audience'] ?? '') == 'All' ? 'selected' : ''; ?>>Everyone</option>
                                <option value="Student" <?php echo ($editNotice['target_audience'] ?? '') == 'Student' ? 'selected' : ''; ?>>Students Only</option>
                                <option value="Teacher" <?php echo ($editNotice['target_audience'] ?? '') == 'Teacher' ? 'selected' : ''; ?>>Teachers Only</option>
                            </select>
                        </div>
                        <div>
                            <label style="font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 8px; display: block;">Priority</label>
                            <select name="priority" style="width: 100%; padding: 12px; border: 2px solid #f8fafc; border-radius: 12px; background: #f8fafc; font-weight: 600;">
                                <option value="Normal" <?php echo ($editNotice['priority'] ?? '') == 'Normal' ? 'selected' : ''; ?>>Normal</option>
                                <option value="Urgent" <?php echo ($editNotice['priority'] ?? '') == 'Urgent' ? 'selected' : ''; ?>>Urgent</option>
                            </select>
                        </div>
                    </div>

                    <div style="margin-bottom: 30px;">
                        <label style="font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 8px; display: block;">Publish Date</label>
                        <input type="date" name="publish_date" value="<?php echo $editNotice['publish_date'] ?? date('Y-m-d'); ?>" style="width: 100%; padding: 12px; border: 2px solid #f8fafc; border-radius: 12px; background: #f8fafc; font-weight: 600;">
                    </div>

                    <button type="submit" name="save_notice" style="width: 100%; background: var(--brand-gradient); color: #fff; padding: 16px; border: none; border-radius: 14px; font-weight: 800; cursor: pointer; transition: 0.3s ease; box-shadow: 0 10px 15px -3px rgba(139, 92, 246, 0.3);">
                        <?php echo $editNotice ? 'Update Announcement' : 'Publish Notice'; ?>
                    </button>
                    <?php if ($editNotice): ?>
                        <a href="manage_notice.php" style="display: block; text-align: center; margin-top: 15px; color: #64748b; font-weight: 700; text-decoration: none; font-size: 0.9rem;">Cancel Editing</a>
                    <?php endif; ?>
                </form>
            </aside>
        </section>
    </main>
</div>

<?php include_once '../includes/footer.php'; ?>

<?php
require_once __DIR__ . '/../../config.php';

require_once ROOT_DIR . '/includes/security/auth_middleware.php';
require_once ROOT_DIR . '/includes/db.php';
require_once ROOT_DIR . '/includes/helpers/resource_helper.php';
require_once ROOT_DIR . '/includes/security/csrf_helper.php';

// Only Teacher allowed
checkAuth(['Teacher']);

$teacherUserId = $_SESSION['user_id'];
$pageTitle = "Manage Resources - Teacher Portal";
include_once ROOT_DIR . '/includes/header.php';

$message = $_GET['msg'] ?? '';
$error = '';

// Handle Delete
if (isset($_GET['delete_id'])) {
    try {
        // Fetch file path first
        $stmt = $pdo->prepare("SELECT file_path FROM resources WHERE resource_id = ? AND uploaded_by = ?");
        $stmt->execute([$_GET['delete_id'], $teacherUserId]);
        $res = $stmt->fetch();
        
        if ($res) {
            deleteResourceFile($res['file_path']);
            $pdo->prepare("DELETE FROM resources WHERE resource_id = ?")->execute([$_GET['delete_id']]);
            $message = "Resource successfully removed.";
        }
    } catch (Exception $e) {
        $error = "System error: " . $e->getMessage();
    }
}

// Fetch teacher's resources
$stmt = $pdo->prepare("
    SELECT r.*, c.course_name, c.course_code 
    FROM resources r 
    JOIN courses c ON r.course_id = c.id 
    WHERE r.uploaded_by = ? 
    ORDER BY r.created_at DESC
");
$stmt->execute([$teacherUserId]);
$myResources = $stmt->fetchAll();
?>

<div class="dashboard-container">
    <header class="dashboard-header">
        <div class="header-brand">
            <h1>Academic Management System</h1>
            <p>Teacher Portal > My Resource Library</p>
        </div>
        <div class="header-icons" style="margin-left: 20px; display: flex; gap: 15px; align-items: center;">
            <a href="<?php echo ROOT_URL; ?>/public/teacher/dashboard.php" style="color: #fff; text-decoration: none; font-weight: 700; font-size: 0.9rem; padding: 8px 16px; border: 1px solid rgba(255,255,255,0.3); border-radius: 12px;"><i class="fas fa-th-large" style="margin-right: 8px;"></i>Dashboard</a>
            <a href="<?php echo ROOT_URL; ?>/public/teacher/upload_resource.php" style="color: #fff; text-decoration: none; font-weight: 700; font-size: 0.9rem; padding: 8px 16px; border: 1px solid rgba(255,255,255,0.3); border-radius: 12px; background: #8b5cf6;"><i class="fas fa-plus" style="margin-right: 8px;"></i>Upload New</a>
        </div>
    </header>

    <main class="main-content resource-main-content" style="padding: 40px 60px; background: #f8fafc;">
        <h2 style="font-size: 1.8rem; color: #1e293b; font-weight: 800; margin-bottom: 25px;">My Academic Contributions</h2>

        <?php if ($message): ?> <div style="background: #dcfce7; color: #166534; padding: 15px; border-radius: 12px; margin-bottom: 20px; font-weight: 600; border: 1px solid #bbf7d0;"><i class="fas fa-check-circle" style="margin-right: 10px;"></i><?php echo $message; ?></div> <?php endif; ?>
        <?php if ($error): ?> <div style="background: #fee2e2; color: #991b1b; padding: 15px; border-radius: 12px; margin-bottom: 20px; font-weight: 600; border: 1px solid #fecaca;"><i class="fas fa-exclamation-circle" style="margin-right: 10px;"></i><?php echo $error; ?></div> <?php endif; ?>

        <div class="table-container" style="background: #fff; border-radius: 24px; box-shadow: 0 4px 20px rgba(0,0,0,0.02); border: 1px solid #f1f5f9; overflow: hidden;">
            <table style="width: 100%; border-collapse: collapse; min-width: 600px;">
                <thead style="background: #f8fafc;">
                    <tr>
                        <th style="padding: 20px; text-align: left; color: #64748b; font-size: 0.75rem; text-transform: uppercase; font-weight: 700;">Resource Details</th>
                        <th style="padding: 20px; text-align: left; color: #64748b; font-size: 0.75rem; text-transform: uppercase; font-weight: 700;">Module</th>
                        <th style="padding: 20px; text-align: center; color: #64748b; font-size: 0.75rem; text-transform: uppercase; font-weight: 700;">File Type</th>
                        <th style="padding: 20px; text-align: center; color: #64748b; font-size: 0.75rem; text-transform: uppercase; font-weight: 700;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($myResources)): ?>
                        <tr>
                            <td colspan="4" style="padding: 60px; text-align: center; color: #94a3b8; font-weight: 600;">You haven't uploaded any resources yet.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($myResources as $r): ?>
                        <tr style="border-bottom: 1px solid #f1f5f9; transition: background 0.2s ease;">
                            <td style="padding: 20px;">
                                <div style="font-weight: 800; color: #1e293b; margin-bottom: 4px;"><?php echo htmlspecialchars($r['title']); ?></div>
                                <div style="font-size: 0.8rem; color: #94a3b8; font-weight: 600;">Uploaded: <?php echo date('M d, Y', strtotime($r['created_at'])); ?></div>
                            </td>
                            <td style="padding: 20px;">
                                <div style="font-weight: 700; color: #8b5cf6; font-size: 0.9rem;"><?php echo htmlspecialchars($r['course_name']); ?></div>
                                <div style="font-size: 0.75rem; color: #64748b; font-weight: 600;"><?php echo htmlspecialchars($r['course_code']); ?></div>
                            </td>
                            <td style="padding: 20px; text-align: center;">
                                <span style="background: #eff6ff; color: #3b82f6; padding: 6px 14px; border-radius: 10px; font-weight: 800; font-size: 0.75rem; text-transform: uppercase;">
                                    <?php 
                                        if(strpos($r['file_type'], 'pdf') !== false) echo 'PDF';
                                        elseif(strpos($r['file_type'], 'presentation') !== false || strpos($r['file_type'], 'powerpoint') !== false) echo 'PPT';
                                        elseif(strpos($r['file_type'], 'word') !== false) echo 'DOC';
                                        elseif(strpos($r['file_type'], 'image') !== false) echo 'IMG';
                                        else echo 'FILE';
                                    ?>
                                </span>
                            </td>
                            <td style="padding: 20px; text-align: center;">
                                <div style="display: flex; justify-content: center; gap: 12px;">
                                    <a href="<?php echo ROOT_URL; ?>/public/shared/download_resource.php?id=<?php echo $r['resource_id']; ?>" target="_blank" style="width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; background: #f0fdf4; color: #10b981; border-radius: 10px; transition: 0.3s ease;" title="View"><i class="fas fa-eye"></i></a>
                                    <a href="edit_resource.php?id=<?php echo $r['resource_id']; ?>" style="width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; background: #fef9c3; color: #ca8a04; border-radius: 10px; transition: 0.3s ease;" title="Edit"><i class="fas fa-edit"></i></a>
                                    <a href="?delete_id=<?php echo $r['resource_id']; ?>" style="width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; background: #fff1f2; color: #f43f5e; border-radius: 10px; transition: 0.3s ease;" onclick="return confirm('Permanently remove this study material?')" title="Delete"><i class="fas fa-trash-alt"></i></a>
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

<?php include_once ROOT_DIR . '/includes/footer.php'; ?>

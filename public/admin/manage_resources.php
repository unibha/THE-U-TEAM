<?php
require_once __DIR__ . '/../../config.php';

require_once ROOT_DIR . '/includes/security/auth_middleware.php';
require_once ROOT_DIR . '/includes/db.php';
require_once ROOT_DIR . '/includes/helpers/resource_helper.php';

// Only Admin allowed
checkAuth(['Admin']);

$pageTitle = "Global Resource Control - Admin Portal";
include_once ROOT_DIR . '/includes/header.php';

$message = '';
$error = '';

// Handle Admin Delete (Any Resource)
if (isset($_GET['delete_id'])) {
    try {
        $stmt = $pdo->prepare("SELECT file_path FROM resources WHERE resource_id = ?");
        $stmt->execute([$_GET['delete_id']]);
        $res = $stmt->fetch();
        
        if ($res) {
            deleteResourceFile($res['file_path']);
            $pdo->prepare("DELETE FROM resources WHERE resource_id = ?")->execute([$_GET['delete_id']]);
            $message = "Institutional resource permanently removed by Administrative action.";
        }
    } catch (Exception $e) {
        $error = "System error: " . $e->getMessage();
    }
}

// Fetch all resources with uploader and course info
$allResources = $pdo->query("
    SELECT r.*, c.course_name, c.course_code, u.first_name, u.last_name, u.role as uploader_role
    FROM resources r
    JOIN courses c ON r.course_id = c.id
    JOIN users u ON r.uploaded_by = u.id
    ORDER BY r.created_at DESC
")->fetchAll();
?>

<div class="dashboard-container">
    <header class="dashboard-header">
        <div class="header-brand">
            <h1>Academic Management System</h1>
            <p>Admin Portal > Resource Audit</p>
        </div>
        <div class="header-icons" style="margin-left: 20px; display: flex; gap: 15px; align-items: center;">
            <a href="<?php echo ROOT_URL; ?>/public/admin/dashboard.php" style="color: #fff; text-decoration: none; font-weight: 700; font-size: 0.9rem; padding: 8px 16px; border: 1px solid rgba(255,255,255,0.3); border-radius: 12px;"><i class="fas fa-th-large" style="margin-right: 8px;"></i>Dashboard</a>
        </div>
    </header>

    <main class="main-content resource-main-content" style="padding: 40px 60px; background: #f8fafc;">
        <div class="flex-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
            <h2 style="font-size: 1.8rem; color: #1e293b; font-weight: 800;">Resource Directory</h2>
            <div style="background: #fff; padding: 10px 25px; border-radius: 14px; border: 1px solid #f1f5f9; box-shadow: 0 4px 15px rgba(0,0,0,0.02);">
                <span style="font-weight: 800; color: #64748b; font-size: 0.9rem;">Total System Assets: <span style="color: #8b5cf6;"><?php echo count($allResources); ?></span></span>
            </div>
        </div>

        <?php if ($message): ?> <div style="background: #dcfce7; color: #166534; padding: 15px; border-radius: 12px; margin-bottom: 20px; font-weight: 600; border: 1px solid #bbf7d0;"><i class="fas fa-shield-alt" style="margin-right: 10px;"></i><?php echo $message; ?></div> <?php endif; ?>
        <?php if ($error): ?> <div style="background: #fee2e2; color: #991b1b; padding: 15px; border-radius: 12px; margin-bottom: 20px; font-weight: 600; border: 1px solid #fecaca;"><i class="fas fa-exclamation-triangle" style="margin-right: 10px;"></i><?php echo $error; ?></div> <?php endif; ?>

        <div class="table-container" style="background: #fff; border-radius: 24px; box-shadow: 0 4px 20px rgba(0,0,0,0.02); border: 1px solid #f1f5f9; overflow: hidden;">
            <table style="width: 100%; border-collapse: collapse; min-width: 600px;">
                <thead style="background: #f8fafc;">
                    <tr>
                        <th style="padding: 20px; text-align: left; color: #64748b; font-size: 0.75rem; text-transform: uppercase; font-weight: 700;">Resource & Instructor</th>
                        <th style="padding: 20px; text-align: left; color: #64748b; font-size: 0.75rem; text-transform: uppercase; font-weight: 700;">Academic Module</th>
                        <th style="padding: 20px; text-align: center; color: #64748b; font-size: 0.75rem; text-transform: uppercase; font-weight: 700;">File Data</th>
                        <th style="padding: 20px; text-align: center; color: #64748b; font-size: 0.75rem; text-transform: uppercase; font-weight: 700;">Admin Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($allResources)): ?>
                        <tr>
                            <td colspan="4" style="padding: 80px; text-align: center; color: #94a3b8; font-weight: 600;">No resources have been uploaded to the system yet.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($allResources as $r): ?>
                        <tr style="border-bottom: 1px solid #f1f5f9; transition: background 0.2s ease;">
                            <td style="padding: 20px;">
                                <div style="font-weight: 800; color: #1e293b; margin-bottom: 4px;"><?php echo htmlspecialchars($r['title']); ?></div>
                                <div style="font-size: 0.8rem; color: #64748b; font-weight: 700;">
                                    <i class="fas fa-user-tie" style="margin-right: 6px; color: #8b5cf6;"></i>
                                    <?php echo htmlspecialchars($r['first_name'] . ' ' . $r['last_name']); ?> 
                                    <span style="font-size: 0.7rem; color: #94a3b8; margin-left: 5px;">(<?php echo $r['uploader_role']; ?>)</span>
                                </div>
                            </td>
                            <td style="padding: 20px;">
                                <div style="font-weight: 700; color: #3b82f6; font-size: 0.9rem;"><?php echo htmlspecialchars($r['course_name']); ?></div>
                                <div style="font-size: 0.75rem; color: #64748b; font-weight: 600;"><?php echo htmlspecialchars($r['course_code']); ?></div>
                            </td>
                            <td style="padding: 20px; text-align: center;">
                                <div style="font-size: 0.8rem; font-weight: 800; color: #475569; margin-bottom: 5px;"><?php echo strtoupper(explode('/', $r['file_type'])[1]); ?></div>
                                <div style="font-size: 0.75rem; color: #94a3b8; font-weight: 600;"><?php echo date('d M, Y', strtotime($r['created_at'])); ?></div>
                            </td>
                            <td style="padding: 20px; text-align: center;">
                                <div style="display: flex; justify-content: center; gap: 12px;">
                                    <a href="<?php echo ROOT_URL; ?>/public/shared/download_resource.php?id=<?php echo $r['resource_id']; ?>" target="_blank" style="padding: 8px 15px; background: #eff6ff; color: #3b82f6; border-radius: 10px; font-weight: 800; font-size: 0.75rem; text-decoration: none; border: 1px solid #dbeafe;">Review</a>
                                    <a href="?delete_id=<?php echo $r['resource_id']; ?>" style="padding: 8px 15px; background: #fff1f2; color: #f43f5e; border-radius: 10px; font-weight: 800; font-size: 0.75rem; text-decoration: none; border: 1px solid #ffe4e6;" onclick="return confirm('MASTER OVERRIDE: Permanently delete this file from the system?')">Delete</a>
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

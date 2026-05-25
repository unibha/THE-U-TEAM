<?php
require_once __DIR__ . '/../../config.php';

require_once ROOT_DIR . '/includes/security/auth_middleware.php';
require_once ROOT_DIR . '/includes/db.php';
require_once ROOT_DIR . '/includes/helpers/resource_helper.php';
require_once ROOT_DIR . '/includes/security/csrf_helper.php';
require_once ROOT_DIR . '/includes/helpers/validation_helper.php';

// Only Teacher allowed
checkAuth(['Teacher']);

$teacherUserId = $_SESSION['user_id'];
$resourceId = $_GET['id'] ?? '';

if (!$resourceId) {
    header("Location: " . ROOT_URL . "/public/teacher/manage_resources.php");
    exit();
}

// Fetch existing resource
$stmt = $pdo->prepare("SELECT * FROM resources WHERE resource_id = ? AND uploaded_by = ?");
$stmt->execute([$resourceId, $teacherUserId]);
$resource = $stmt->fetch();

if (!$resource) {
    die("Access denied or resource not found.");
}

$pageTitle = "Edit Resource - Teacher Portal";
include_once ROOT_DIR . '/includes/header.php';

$message = '';
$error = '';

// Fetch teacher's courses for the dropdown
$stmt = $pdo->prepare("
    SELECT c.id, c.course_name, c.course_code 
    FROM courses c 
    JOIN teachers t ON c.teacher_id = t.id 
    WHERE t.user_id = ?
");
$stmt->execute([$teacherUserId]);
$courses = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validate_csrf();
    
    $title = sanitize($_POST['title'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $courseId = $_POST['course_id'] ?? '';
    
    if (empty($title) || empty($courseId)) {
        $error = "Title and Course are mandatory.";
    } else {
        try {
            $updateFields = "title = ?, description = ?, course_id = ?";
            $params = [$title, $description, $courseId];
            
            // Check if new file is uploaded
            if (!empty($_FILES['resource_file']['name'])) {
                $uploadResult = uploadResourceFile($_FILES['resource_file']);
                if ($uploadResult['success']) {
                    // Delete old file
                    deleteResourceFile($resource['file_path']);
                    
                    $updateFields .= ", file_name = ?, file_path = ?, file_type = ?";
                    $params[] = $uploadResult['file_name'];
                    $params[] = $uploadResult['file_path'];
                    $params[] = $uploadResult['file_type'];
                } else {
                    throw new Exception($uploadResult['message']);
                }
            }
            
            $params[] = $resourceId;
            $params[] = $teacherUserId;
            
            $stmt = $pdo->prepare("UPDATE resources SET $updateFields WHERE resource_id = ? AND uploaded_by = ?");
            $stmt->execute($params);
            
            header("Location: " . ROOT_URL . "/public/teacher/manage_resources.php?msg=Resource updated successfully!");
            exit();
        } catch (Exception $e) {
            $error = "Update failed: " . $e->getMessage();
        }
    }
}
?>

<div class="dashboard-container">
    <header class="dashboard-header">
        <div class="header-brand">
            <h1>Academic Management System</h1>
            <p>Teacher Portal > Edit Resource</p>
        </div>
        <div class="header-icons" style="margin-left: 20px; display: flex; gap: 15px; align-items: center;">
            <a href="<?php echo ROOT_URL; ?>/public/teacher/manage_resources.php" style="color: #fff; text-decoration: none; font-weight: 700; font-size: 0.9rem; padding: 8px 16px; border: 1px solid rgba(255,255,255,0.3); border-radius: 12px;"><i class="fas fa-arrow-left" style="margin-right: 8px;"></i>Back to Library</a>
        </div>
    </header>

    <main class="main-content" style="padding: 60px; background: #f8fafc;">
        <div style="max-width: 600px; margin: 0 auto; background: #fff; padding: 50px; border-radius: 35px; box-shadow: 0 20px 50px rgba(0,0,0,0.03); border: 1px solid #f1f5f9;">
            <div style="text-align: center; margin-bottom: 40px;">
                <div style="width: 70px; height: 70px; background: #fefce8; color: #ca8a04; border-radius: 20px; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; font-size: 2rem;">
                    <i class="fas fa-edit"></i>
                </div>
                <h2 style="font-size: 1.8rem; color: #1e293b; font-weight: 900;">Update Material Info</h2>
                <p style="color: #64748b; font-weight: 600;">Modifying: <?php echo htmlspecialchars($resource['file_name']); ?></p>
            </div>

            <?php if ($error): ?> <div style="background: #fee2e2; color: #991b1b; padding: 15px; border-radius: 12px; margin-bottom: 30px; font-weight: 600; text-align: center; border: 1px solid #fecaca;"><i class="fas fa-exclamation-circle" style="margin-right: 10px;"></i><?php echo $error; ?></div> <?php endif; ?>

            <form action="" method="POST" enctype="multipart/form-data">
                <?php echo csrf_field(); ?>
                
                <div style="margin-bottom: 25px;">
                    <label style="font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 8px; display: block;">Resource Title</label>
                    <input type="text" name="title" value="<?php echo htmlspecialchars($resource['title']); ?>" required style="width: 100%; padding: 14px; border: 2px solid #f1f5f9; border-radius: 14px; font-weight: 600; outline: none;">
                </div>

                <div style="margin-bottom: 25px;">
                    <label style="font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 8px; display: block;">Target Module</label>
                    <select name="course_id" required style="width: 100%; padding: 14px; border: 2px solid #f1f5f9; border-radius: 14px; font-weight: 600; outline: none; background: #fff;">
                        <?php foreach($courses as $c): ?>
                            <option value="<?php echo $c['id']; ?>" <?php echo ($c['id'] == $resource['course_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($c['course_name'] . ' (' . $c['course_code'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="margin-bottom: 25px;">
                    <label style="font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 8px; display: block;">Description (Optional)</label>
                    <textarea name="description" style="width: 100%; height: 80px; padding: 14px; border: 2px solid #f1f5f9; border-radius: 14px; font-weight: 600; outline: none; resize: none;"><?php echo htmlspecialchars($resource['description']); ?></textarea>
                </div>

                <div style="margin-bottom: 35px;">
                    <label style="font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 8px; display: block;">Replace File (Optional - Max 50MB)</label>
                    <div style="border: 2px dashed #f1f5f9; padding: 20px; border-radius: 14px; text-align: center; position: relative;" id="dropZone">
                        <i class="fas fa-file-upload" style="font-size: 1.5rem; color: #cbd5e1; margin-bottom: 5px;"></i>
                        <p id="fileName" style="font-size: 0.8rem; color: #94a3b8; font-weight: 600;">Leave blank to keep current file</p>
                        <input type="file" name="resource_file" id="fileInput" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; opacity: 0; cursor: pointer;">
                    </div>
                </div>

                <script>
                    document.getElementById('fileInput').addEventListener('change', function(e) {
                        const fileName = e.target.files[0] ? e.target.files[0].name : 'Leave blank to keep current file';
                        document.getElementById('fileName').textContent = fileName;
                        document.getElementById('fileName').style.color = '#ca8a04';
                        document.getElementById('dropZone').style.borderColor = '#ca8a04';
                    });
                </script>

                <button type="submit" style="width: 100%; background: var(--brand-gradient); color: #fff; padding: 18px; border: none; border-radius: 16px; font-size: 1.1rem; font-weight: 800; cursor: pointer;">
                    Save Changes
                </button>
            </form>
        </div>
    </main>
</div>

<?php include_once ROOT_DIR . '/includes/footer.php'; ?>

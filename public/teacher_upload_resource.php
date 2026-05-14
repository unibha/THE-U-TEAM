<?php
require_once '../includes/auth_middleware.php';
require_once '../includes/db.php';
require_once '../includes/resource_helper.php';
require_once '../includes/csrf_helper.php';
require_once '../includes/validation_helper.php';
require_once '../includes/notification_helper.php';

// Only Teacher allowed
checkAuth(['Teacher']);

$teacherUserId = $_SESSION['user_id'];
$pageTitle = "Upload Resources - Teacher Portal";
include_once '../includes/header.php';

// Fetch teacher's courses
$stmt = $pdo->prepare("
    SELECT c.id, c.course_name, c.course_code 
    FROM courses c 
    JOIN teachers t ON c.teacher_id = t.id 
    WHERE t.user_id = ?
");
$stmt->execute([$teacherUserId]);
$courses = $stmt->fetchAll();

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validate_csrf();
    
    $title = sanitize($_POST['title'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $courseId = $_POST['course_id'] ?? '';
    
    if (empty($title) || empty($courseId) || empty($_FILES['resource_file']['name'])) {
        $error = "Title, Course, and File are mandatory.";
    } else {
        $uploadResult = uploadResourceFile($_FILES['resource_file']);
        
        if ($uploadResult['success']) {
            try {
                $stmt = $pdo->prepare("INSERT INTO resources (title, description, course_id, file_name, file_path, file_type, uploaded_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $title, 
                    $description, 
                    $courseId, 
                    $uploadResult['file_name'], 
                    $uploadResult['file_path'], 
                    $uploadResult['file_type'], 
                    $teacherUserId
                ]);
                
                // Trigger Notification for students
                $courseName = '';
                foreach($courses as $c) if($c['id'] == $courseId) $courseName = $c['course_name'];
                
                notifyEnrolledStudents($courseId, "New Resource Available", "A new study material '$title' has been uploaded for $courseName.", 'Academic');
                
                header("Location: teacher_manage_resources.php?msg=Resource uploaded successfully!");
                exit();
            } catch (Exception $e) {
                deleteResourceFile($uploadResult['file_path']); // Cleanup on DB fail
                $error = "Database Error: " . $e->getMessage();
            }
        } else {
            $error = $uploadResult['message'];
        }
    }
}
?>

<div class="dashboard-container">
    <header class="dashboard-header">
        <div class="header-brand">
            <h1>Academic Management System</h1>
            <p>Teacher Portal > Resource Uploader</p>
        </div>
        <div class="header-icons" style="margin-left: 20px; display: flex; gap: 15px; align-items: center;">
            <a href="teacher_dashboard.php" style="color: #fff; text-decoration: none; font-weight: 700; font-size: 0.9rem; padding: 8px 16px; border: 1px solid rgba(255,255,255,0.3); border-radius: 12px;"><i class="fas fa-th-large" style="margin-right: 8px;"></i>Dashboard</a>
            <a href="teacher_manage_resources.php" style="color: #fff; text-decoration: none; font-weight: 700; font-size: 0.9rem; padding: 8px 16px; border: 1px solid rgba(255,255,255,0.3); border-radius: 12px; background: rgba(255,255,255,0.1);">Manage Uploads</a>
        </div>
    </header>

    <main class="main-content" style="padding: 60px; background: #f8fafc;">
        <div style="max-width: 600px; margin: 0 auto; background: #fff; padding: 50px; border-radius: 35px; box-shadow: 0 20px 50px rgba(0,0,0,0.03); border: 1px solid #f1f5f9;">
            <div style="text-align: center; margin-bottom: 40px;">
                <div style="width: 70px; height: 70px; background: #eef2ff; color: #8b5cf6; border-radius: 20px; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; font-size: 2rem;">
                    <i class="fas fa-cloud-upload-alt"></i>
                </div>
                <h2 style="font-size: 1.8rem; color: #1e293b; font-weight: 900;">Upload Study Material</h2>
                <p style="color: #64748b; font-weight: 600;">Share notes, slides, and documents with your students.</p>
            </div>

            <?php if ($message): ?> <div style="background: #dcfce7; color: #166534; padding: 15px; border-radius: 12px; margin-bottom: 30px; font-weight: 600; text-align: center; border: 1px solid #bbf7d0;"><i class="fas fa-check-circle" style="margin-right: 10px;"></i><?php echo $message; ?></div> <?php endif; ?>
            <?php if ($error): ?> <div style="background: #fee2e2; color: #991b1b; padding: 15px; border-radius: 12px; margin-bottom: 30px; font-weight: 600; text-align: center; border: 1px solid #fecaca;"><i class="fas fa-exclamation-circle" style="margin-right: 10px;"></i><?php echo $error; ?></div> <?php endif; ?>

            <form action="" method="POST" enctype="multipart/form-data">
                <?php echo csrf_field(); ?>
                
                <div style="margin-bottom: 25px;">
                    <label style="font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 8px; display: block;">Resource Title</label>
                    <input type="text" name="title" required placeholder="e.g. Week 1: Introduction to PHP" style="width: 100%; padding: 14px; border: 2px solid #f1f5f9; border-radius: 14px; font-weight: 600; outline: none; transition: 0.3s focus;">
                </div>

                <div style="margin-bottom: 25px;">
                    <label style="font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 8px; display: block;">Target Module</label>
                    <select name="course_id" required style="width: 100%; padding: 14px; border: 2px solid #f1f5f9; border-radius: 14px; font-weight: 600; outline: none; background: #fff;">
                        <option value="">-- Select Course --</option>
                        <?php foreach($courses as $c): ?>
                            <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['course_name'] . ' (' . $c['course_code'] . ')'); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="margin-bottom: 25px;">
                    <label style="font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 8px; display: block;">Description (Optional)</label>
                    <textarea name="description" placeholder="Short summary of the resource..." style="width: 100%; height: 100px; padding: 14px; border: 2px solid #f1f5f9; border-radius: 14px; font-weight: 600; outline: none; resize: none;"></textarea>
                </div>

                <div style="margin-bottom: 40px;">
                    <label style="font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 8px; display: block;">File Attachment (Max 50MB)</label>
                    <div style="border: 2px dashed #e2e8f0; padding: 30px; border-radius: 20px; text-align: center; position: relative;" id="dropZone">
                        <i class="fas fa-file-pdf" style="font-size: 2rem; color: #cbd5e1; margin-bottom: 10px;"></i>
                        <p id="fileName" style="font-size: 0.85rem; color: #94a3b8; font-weight: 600;">Click to browse or drag your file here</p>
                        <input type="file" name="resource_file" id="fileInput" required style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; opacity: 0; cursor: pointer;">
                    </div>
                </div>

                <script>
                    document.getElementById('fileInput').addEventListener('change', function(e) {
                        const fileName = e.target.files[0] ? e.target.files[0].name : 'Click to browse or drag your file here';
                        document.getElementById('fileName').textContent = fileName;
                        document.getElementById('fileName').style.color = '#8b5cf6';
                        document.getElementById('dropZone').style.borderColor = '#8b5cf6';
                        document.getElementById('dropZone').style.background = '#f5f3ff';
                    });
                </script>

                <button type="submit" style="width: 100%; background: var(--brand-gradient); color: #fff; padding: 18px; border: none; border-radius: 16px; font-size: 1.1rem; font-weight: 800; cursor: pointer; transition: 0.3s ease; box-shadow: 0 10px 25px rgba(139, 92, 246, 0.2);">
                    Publish Resource
                </button>
            </form>
        </div>
    </main>
</div>

<?php include_once '../includes/footer.php'; ?>

<?php
require_once '../includes/auth_middleware.php';
require_once '../includes/db.php';

// Only Admin allowed
checkAuth(['Admin']);

$pageTitle = "Add New User - Admin Portal";
include_once '../includes/header.php';

$message = '';
$error = '';
$preselectedRole = $_GET['role'] ?? 'Student';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Collect all fields
    $first_name = $_POST['first_name'] ?? '';
    $last_name = $_POST['last_name'] ?? '';
    $dob = $_POST['dob'] ?? '';
    $address = $_POST['address'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $contact = $_POST['contact'] ?? '';
    $role = $_POST['role'] ?? 'Student';
    $mother_name = $_POST['mother_name'] ?? '';
    $father_name = $_POST['father_name'] ?? '';
    $guardian_contact = $_POST['guardian_contact'] ?? '';
    $class_name = $_POST['class_name'] ?? ''; // For Students
    $department = $_POST['department'] ?? ''; // For Teachers

    if (empty($first_name) || empty($last_name) || empty($email) || empty($password)) {
        $error = "Please fill in all required fields.";
    } else {
        try {
            $pdo->beginTransaction();

            // 1. Create User
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("
                INSERT INTO users (first_name, last_name, dob, address, email, password_hash, role, contact, mother_name, father_name, guardian_contact, is_active) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
            ");
            $stmt->execute([$first_name, $last_name, $dob, $address, $email, $password_hash, $role, $contact, $mother_name, $father_name, $guardian_contact]);
            $userId = $pdo->lastInsertId();

            // 2. Create Role-specific record
            if ($role == 'Student') {
                $stmt = $pdo->prepare("INSERT INTO students (user_id, class_name) VALUES (?, ?)");
                $stmt->execute([$userId, $class_name]);
            } else if ($role == 'Teacher') {
                $stmt = $pdo->prepare("INSERT INTO teachers (user_id, department) VALUES (?, ?)");
                $stmt->execute([$userId, $department]);
            }

            $pdo->commit();
            $message = "New $role added successfully! <a href='admin_manage_users.php' style='color: #166534; font-weight: 700;'>Return to list</a>";
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = ($e->getCode() == 23000) ? "The email address is already in use." : "Error adding user: " . $e->getMessage();
        }
    }
}
?>

<div class="dashboard-container">
    <header class="dashboard-header">
        <div class="header-brand">
            <h1>Academic Management System</h1>
            <p>Admin Portal > Add User</p>
        </div>
        <div class="header-tools">
            <div class="header-icons" style="margin-left: 20px; display: flex; gap: 15px; align-items: center;">
                <a href="admin_dashboard.php" style="color: #fff; text-decoration: none; font-weight: 700; font-size: 0.9rem; padding: 8px 16px; border: 1px solid rgba(255,255,255,0.3); border-radius: 12px; transition: 0.3s ease; background: rgba(255,255,255,0.1);"><i class="fas fa-th-large" style="margin-right: 8px;"></i>Dashboard</a>
                <a href="logout.php" style="color: #fff; text-decoration: none; font-weight: 700; font-size: 0.9rem; padding: 8px 16px; border: 1px solid rgba(255,255,255,0.3); border-radius: 12px;">Logout</a>
            </div>
        </div>
    </header>

    <main class="main-content">
        <section class="form-container" style="max-width: 900px; margin: 0 auto; background: #fff; padding: 50px; border-radius: 24px; box-shadow: 0 10px 30px rgba(0,0,0,0.03);">
            <h2 style="font-size: 1.8rem; color: #1e293b; font-weight: 800; margin-bottom: 30px; text-align: center;">Registration Command Center</h2>

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

            <form action="" method="POST" id="addUserForm">
                <!-- Personal Info -->
                <p style="color: #8b5cf6; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; font-size: 0.9rem; margin-bottom: 25px; border-bottom: 2px dashed #f1f5f9; padding-bottom: 10px;">
                    <i class="fas fa-user-tag" style="margin-right: 8px;"></i> Primary Identity
                </p>
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 25px; margin-bottom: 35px;">
                    <div class="input-group">
                        <label style="font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 8px; display: block;">First Name*</label>
                        <input type="text" name="first_name" required style="width: 100%; padding: 14px; border: 2px solid #f1f5f9; border-radius: 12px; outline: none;">
                    </div>
                    <div class="input-group">
                        <label style="font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 8px; display: block;">Last Name*</label>
                        <input type="text" name="last_name" required style="width: 100%; padding: 14px; border: 2px solid #f1f5f9; border-radius: 12px; outline: none;">
                    </div>
                    <div class="input-group">
                        <label style="font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 8px; display: block;">Email Address*</label>
                        <input type="email" name="email" required style="width: 100%; padding: 14px; border: 2px solid #f1f5f9; border-radius: 12px; outline: none;">
                    </div>
                    <div class="input-group">
                        <label style="font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 8px; display: block;">System Password*</label>
                        <input type="password" name="password" required style="width: 100%; padding: 14px; border: 2px solid #f1f5f9; border-radius: 12px; outline: none;">
                    </div>
                </div>

                <!-- Assignment Specs -->
                <p style="color: #8b5cf6; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; font-size: 0.9rem; margin-bottom: 25px; border-bottom: 2px dashed #f1f5f9; padding-bottom: 10px;">
                    <i class="fas fa-id-badge" style="margin-right: 8px;"></i> Professional Role & Assignment
                </p>
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 25px; margin-bottom: 35px;">
                    <div class="input-group">
                        <label style="font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 8px; display: block;">System Role*</label>
                        <select name="role" id="roleSelector" onchange="toggleRoleFields()" style="width: 100%; padding: 14px; border: 2px solid #f1f5f9; border-radius: 12px; outline: none; background: #fff;">
                            <option value="Student" <?php echo $preselectedRole == 'Student' ? 'selected' : ''; ?>>Student (Academy Member)</option>
                            <option value="Teacher" <?php echo $preselectedRole == 'Teacher' ? 'selected' : ''; ?>>Teacher (Faculty Member)</option>
                        </select>
                    </div>
                    <div class="input-group" id="classGroup">
                        <label style="font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 8px; display: block;">Assigned Class</label>
                        <input type="text" name="class_name" placeholder="e.g. 10th Grade, CS-A" style="width: 100%; padding: 14px; border: 2px solid #f1f5f9; border-radius: 12px; outline: none;">
                    </div>
                    <div class="input-group" id="deptGroup" style="display: none;">
                        <label style="font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 8px; display: block;">Assigned Department</label>
                        <input type="text" name="department" placeholder="e.g. Science, Mathematics" style="width: 100%; padding: 14px; border: 2px solid #f1f5f9; border-radius: 12px; outline: none;">
                    </div>
                </div>

                <!-- Extended Data -->
                <p style="color: #8b5cf6; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; font-size: 0.9rem; margin-bottom: 25px; border-bottom: 2px dashed #f1f5f9; padding-bottom: 10px;">
                    <i class="fas fa-address-book" style="margin-right: 8px;"></i> Personal & Contact Records
                </p>
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 25px; margin-bottom: 35px;">
                    <div class="input-group">
                        <label style="font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 8px; display: block;">Date of Birth</label>
                        <input type="date" name="dob" style="width: 100%; padding: 14px; border: 2px solid #f1f5f9; border-radius: 12px; outline: none;">
                    </div>
                    <div class="input-group">
                        <label style="font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 8px; display: block;">Primary Contact</label>
                        <input type="text" name="contact" style="width: 100%; padding: 14px; border: 2px solid #f1f5f9; border-radius: 12px; outline: none;">
                    </div>
                    <div class="input-group" style="grid-column: span 2;">
                        <label style="font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 8px; display: block;">Residential Address</label>
                        <textarea name="address" style="width: 100%; padding: 14px; border: 2px solid #f1f5f9; border-radius: 12px; outline: none; height: 100px; resize: none;"></textarea>
                    </div>
                </div>

                <div style="text-align: center; margin-top: 50px;">
                    <button type="submit" style="background: var(--brand-gradient); color: #fff; padding: 18px 60px; border: none; border-radius: 15px; font-size: 1.1rem; font-weight: 800; cursor: pointer; transition: all 0.3s ease; box-shadow: 0 10px 20px rgba(139, 92, 246, 0.2);">
                        Finalize & Add User
                    </button>
                </div>
            </form>
        </section>
    </main>
</div>

<script>
function toggleRoleFields() {
    const role = document.getElementById('roleSelector').value;
    const classGroup = document.getElementById('classGroup');
    const deptGroup = document.getElementById('deptGroup');
    
    if (role === 'Student') {
        classGroup.style.display = 'block';
        deptGroup.style.display = 'none';
    } else {
        classGroup.style.display = 'none';
        deptGroup.style.display = 'block';
    }
}

// Initial check for pre-selected role
window.addEventListener('DOMContentLoaded', toggleRoleFields);
</script>

<?php include_once '../includes/footer.php'; ?>

<?php
require_once '../includes/auth_middleware.php';
require_once '../includes/db.php';

// Only Admin allowed
checkAuth(['Admin']);

$pageTitle = "Edit User - Admin Portal";
include_once '../includes/header.php';

$message = '';
$error = '';
$userId = $_GET['id'] ?? 0;

// Fetch current user data
try {
    $stmt = $pdo->prepare("
        SELECT u.*, s.class_name, t.department 
        FROM users u 
        LEFT JOIN students s ON u.id = s.user_id 
        LEFT JOIN teachers t ON u.id = t.user_id 
        WHERE u.id = ?
    ");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!$user) {
        header("Location: admin_manage_users.php");
        exit();
    }
} catch (PDOException $e) {
    $error = "Error fetching user data: " . $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Collect all fields
    $first_name = $_POST['first_name'] ?? '';
    $last_name = $_POST['last_name'] ?? '';
    $dob = $_POST['dob'] ?? '';
    $address = $_POST['address'] ?? '';
    $email = $_POST['email'] ?? '';
    $contact = $_POST['contact'] ?? '';
    $mother_name = $_POST['mother_name'] ?? '';
    $father_name = $_POST['father_name'] ?? '';
    $guardian_contact = $_POST['guardian_contact'] ?? '';
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $class_name = $_POST['class_name'] ?? ''; 
    $department = $_POST['department'] ?? ''; 

    if (empty($first_name) || empty($last_name) || empty($email)) {
        $error = "Please fill in all required fields.";
    } else {
        try {
            $pdo->beginTransaction();

            // 1. Update User
            $stmt = $pdo->prepare("
                UPDATE users SET 
                    first_name = ?, last_name = ?, dob = ?, address = ?, 
                    email = ?, contact = ?, mother_name = ?, father_name = ?, 
                    guardian_contact = ?, is_active = ? 
                WHERE id = ?
            ");
            $stmt->execute([$first_name, $last_name, $dob, $address, $email, $contact, $mother_name, $father_name, $guardian_contact, $is_active, $userId]);

            // 2. Update Role-specific record
            if ($user['role'] == 'Student') {
                $stmt = $pdo->prepare("UPDATE students SET class_name = ? WHERE user_id = ?");
                $stmt->execute([$class_name, $userId]);
            } else if ($user['role'] == 'Teacher') {
                $stmt = $pdo->prepare("UPDATE teachers SET department = ? WHERE user_id = ?");
                $stmt->execute([$department, $userId]);
            }

            $pdo->commit();
            $message = "User updated successfully! <a href='admin_manage_users.php' style='color: #166534; font-weight: 700;'>Return to list</a>";
            
            // Refresh local data
            $user['first_name'] = $first_name; $user['last_name'] = $last_name; $user['email'] = $email;
            $user['class_name'] = $class_name; $user['department'] = $department;
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = ($e->getCode() == 23000) ? "The email address is already in use." : "Error updating user: " . $e->getMessage();
        }
    }
}
?>

<div class="dashboard-container">
    <header class="dashboard-header">
        <div class="header-brand">
            <h1>Academic Management System</h1>
            <p>Admin Portal > Edit User</p>
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
            <h2 style="font-size: 1.8rem; color: #1e293b; font-weight: 800; margin-bottom: 30px; text-align: center;">Profile Update Center</h2>

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
                <p style="color: #8b5cf6; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; font-size: 0.9rem; margin-bottom: 25px; border-bottom: 2px dashed #f1f5f9; padding-bottom: 10px;">
                    <i class="fas fa-user-tag" style="margin-right: 8px;"></i> User Identity
                </p>
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 25px; margin-bottom: 35px;">
                    <div class="input-group">
                        <label style="font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 8px; display: block;">First Name*</label>
                        <input type="text" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>" required style="width: 100%; padding: 14px; border: 2px solid #f1f5f9; border-radius: 12px;">
                    </div>
                    <div class="input-group">
                        <label style="font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 8px; display: block;">Last Name*</label>
                        <input type="text" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>" required style="width: 100%; padding: 14px; border: 2px solid #f1f5f9; border-radius: 12px;">
                    </div>
                    <div class="input-group">
                        <label style="font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 8px; display: block;">Email Address*</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required style="width: 100%; padding: 14px; border: 2px solid #f1f5f9; border-radius: 12px;">
                    </div>
                    <div class="input-group">
                        <label style="font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 8px; display: block;">Account Status</label>
                        <div style="display: flex; align-items: center; gap: 10px; padding: 14px;">
                            <input type="checkbox" name="is_active" <?php echo $user['is_active'] ? 'checked' : ''; ?> style="width: 20px; height: 20px; cursor: pointer;">
                            <span style="font-size: 0.9rem; color: #475569;">Active & Visible in System</span>
                        </div>
                    </div>
                </div>

                <p style="color: #8b5cf6; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; font-size: 0.9rem; margin-bottom: 25px; border-bottom: 2px dashed #f1f5f9; padding-bottom: 10px;">
                    <i class="fas fa-id-badge" style="margin-right: 8px;"></i> Professional Assignment (<?php echo $user['role']; ?>)
                </p>
                <div style="margin-bottom: 35px;">
                    <?php if ($user['role'] == 'Student'): ?>
                    <div class="input-group">
                        <label style="font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 8px; display: block;">Assigned Class</label>
                        <input type="text" name="class_name" value="<?php echo htmlspecialchars($user['class_name'] ?? ''); ?>" style="width: 100%; padding: 14px; border: 2px solid #f1f5f9; border-radius: 12px;">
                    </div>
                    <?php else: ?>
                    <div class="input-group">
                        <label style="font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 8px; display: block;">Assigned Department</label>
                        <input type="text" name="department" value="<?php echo htmlspecialchars($user['department'] ?? ''); ?>" style="width: 100%; padding: 14px; border: 2px solid #f1f5f9; border-radius: 12px;">
                    </div>
                    <?php endif; ?>
                </div>

                <p style="color: #8b5cf6; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; font-size: 0.9rem; margin-bottom: 25px; border-bottom: 2px dashed #f1f5f9; padding-bottom: 10px;">
                    <i class="fas fa-address-book" style="margin-right: 8px;"></i> Personal Records
                </p>
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 25px;">
                    <div class="input-group">
                        <label style="font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 8px; display: block;">Date of Birth</label>
                        <input type="date" name="dob" value="<?php echo $user['dob']; ?>" style="width: 100%; padding: 14px; border: 2px solid #f1f5f9; border-radius: 12px;">
                    </div>
                    <div class="input-group">
                        <label style="font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 8px; display: block;">Primary Contact</label>
                        <input type="text" name="contact" value="<?php echo htmlspecialchars($user['contact']); ?>" style="width: 100%; padding: 14px; border: 2px solid #f1f5f9; border-radius: 12px;">
                    </div>
                </div>

                <div style="text-align: center; margin-top: 50px;">
                    <button type="submit" style="background: var(--brand-gradient); color: #fff; padding: 18px 60px; border: none; border-radius: 15px; font-size: 1.1rem; font-weight: 800; cursor: pointer; transition: all 0.3s ease; box-shadow: 0 10px 20px rgba(139, 92, 246, 0.2);">
                        Update Membership Profile
                    </button>
                </div>
            </form>
        </section>
    </main>
</div>

<?php include_once '../includes/footer.php'; ?>

<?php
require_once '../includes/auth_middleware.php';
require_once '../includes/db.php';

// All logged-in users can view their profile
checkAuth(['Admin', 'Teacher', 'Student']);

$pageTitle = "My Profile - Academic Management System";
include_once '../includes/header.php';

$userId = $_SESSION['user_id'];
$role = $_SESSION['role'];
$message = '';
$error = '';

// Handle Profile Updates
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $contact = $_POST['contact'] ?? '';
    $address = $_POST['address'] ?? '';
    
    try {
        $stmt = $pdo->prepare("UPDATE users SET contact = ?, address = ? WHERE id = ?");
        $stmt->execute([$contact, $address, $userId]);
        $message = "Your profile has been updated successfully!";
        // Update session
    } catch (PDOException $e) {
        $error = "Update Error: " . $e->getMessage();
    }
}

// Fetch Comprehensive User Data
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    $extraData = null;
    if ($role == 'Teacher') {
        $stmt = $pdo->prepare("SELECT department FROM teachers WHERE user_id = ?");
        $stmt->execute([$userId]);
        $extraData = $stmt->fetch();
    } elseif ($role == 'Student') {
        $stmt = $pdo->prepare("SELECT class_name FROM students WHERE user_id = ?");
        $stmt->execute([$userId]);
        $extraData = $stmt->fetch();
    }
} catch (PDOException $e) {
    $error = "Fetch Error: " . $e->getMessage();
}

$displayName = $user['first_name'] . ' ' . $user['last_name'];
$initials = substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1);
?>

<div class="dashboard-container" style="flex-direction: column;">
    <header class="dashboard-header">
        <div class="header-brand">
            <h1>Academic Management System</h1>
            <p>User Portal > My Account Profile</p>
        </div>
        <div class="header-icons" style="margin-left: 20px; display: flex; gap: 15px; align-items: center;">
            <a href="<?php 
                if($role == 'Admin') echo 'admin_dashboard.php';
                elseif($role == 'Teacher') echo 'teacher_dashboard.php';
                else echo 'student_dashboard.php';
            ?>" style="color: #fff; text-decoration: none; font-weight: 700; font-size: 0.9rem; padding: 8px 16px; border: 1px solid rgba(255,255,255,0.3); border-radius: 12px; transition: 0.3s ease; background: rgba(255,255,255,0.1);"><i class="fas fa-th-large" style="margin-right: 8px;"></i>Dashboard</a>
            <a href="logout.php" style="color: #fff; text-decoration: none; font-weight: 700; font-size: 0.9rem; padding: 8px 16px; border: 1px solid rgba(255,255,255,0.3); border-radius: 12px;">Logout</a>
        </div>
    </header>

    <main class="main-content" style="padding: 40px 60px; background: #f8fafc; display: flex; align-items: center; justify-content: center;">
        <div style="background: #fff; width: 100%; max-width: 900px; border-radius: 32px; box-shadow: 0 15px 40px rgba(0,0,0,0.04); overflow: hidden; display: flex;">
            
            <!-- Left Side: Visual Profile Card -->
            <div style="background: var(--brand-gradient); width: 350px; padding: 60px 40px; color: #fff; text-align: center; display: flex; flex-direction: column; align-items: center;">
                <div style="width: 120px; height: 120px; background: rgba(255,255,255,0.2); border-radius: 40px; display: flex; align-items: center; justify-content: center; font-size: 2.5rem; font-weight: 800; margin-bottom: 30px; border: 2px solid rgba(255,255,255,0.3);">
                    <?php echo $initials; ?>
                </div>
                <h2 style="font-size: 1.8rem; font-weight: 800; margin-bottom: 10px;"><?php echo htmlspecialchars($displayName); ?></h2>
                <div style="background: rgba(255,255,255,0.15); padding: 6px 20px; border-radius: 20px; font-weight: 800; font-size: 0.9rem; text-transform: uppercase; margin-bottom: 30px;">
                    <?php echo $role; ?>
                </div>
                
                <div style="width: 100%; text-align: left; padding-top: 30px; border-top: 1px solid rgba(255,255,255,0.2);">
                    <p style="font-size: 0.85rem; opacity: 0.7; font-weight: 700; margin-bottom: 15px;">MEMBER IDENTITIES</p>
                    <div style="margin-bottom: 15px;">
                        <span style="font-size: 0.8rem; opacity: 0.6; display: block;">Email</span>
                        <span style="font-weight: 700;"><?php echo htmlspecialchars($user['email']); ?></span>
                    </div>
                    <?php if ($extraData): ?>
                        <div>
                            <span style="font-size: 0.8rem; opacity: 0.6; display: block;"><?php echo $role == 'Teacher' ? 'Department' : 'Class Group'; ?></span>
                            <span style="font-weight: 700;"><?php echo htmlspecialchars($extraData['department'] ?? $extraData['class_name'] ?? 'Not Assigned'); ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Right Side: Edit Form -->
            <div style="flex: 1; padding: 60px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px;">
                    <h3 style="font-size: 1.6rem; color: #1e293b; font-weight: 800; margin-bottom: 0;">Member Information</h3>
                    <button id="editBtn" type="button" style="background: #faf5ff; color: #8b5cf6; padding: 8px 16px; border: 1px solid #ddd6fe; border-radius: 10px; font-weight: 700; font-size: 0.85rem; cursor: pointer; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-edit"></i> Edit Profile
                    </button>
                </div>

                <?php if ($message): ?> <div style="background: #dcfce7; color: #166534; padding: 15px; border-radius: 12px; margin-bottom: 25px;"><?php echo $message; ?></div> <?php endif; ?>
                <?php if ($error): ?> <div style="background: #fee2e2; color: #991b1b; padding: 15px; border-radius: 12px; margin-bottom: 25px;"><?php echo $error; ?></div> <?php endif; ?>

                <form action="" method="POST">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 30px;">
                        <div>
                            <label style="font-size: 0.85rem; font-weight: 700; color: #64748b; margin-bottom: 8px; display: block;">First Name</label>
                            <input type="text" value="<?php echo htmlspecialchars($user['first_name']); ?>" disabled style="width: 100%; padding: 14px; border: 2px solid #f1f5f9; border-radius: 12px; background: #f8fafc; color: #94a3b8; font-weight: 600;">
                        </div>
                        <div>
                            <label style="font-size: 0.85rem; font-weight: 700; color: #64748b; margin-bottom: 8px; display: block;">Last Name</label>
                            <input type="text" value="<?php echo htmlspecialchars($user['last_name']); ?>" disabled style="width: 100%; padding: 14px; border: 2px solid #f1f5f9; border-radius: 12px; background: #f8fafc; color: #94a3b8; font-weight: 600;">
                        </div>
                    </div>

                    <div style="margin-bottom: 30px;">
                        <label style="font-size: 0.85rem; font-weight: 700; color: #64748b; margin-bottom: 8px; display: block;">Contact Number</label>
                        <input type="text" name="contact" id="contact" value="<?php echo htmlspecialchars($user['contact']); ?>" required readonly style="width: 100%; padding: 14px; border: 2px solid #f1f5f9; border-radius: 12px; outline: none; transition: 0.3s ease; background: #f8fafc; color: #475569; font-weight: 600;">
                    </div>

                    <div style="margin-bottom: 40px;">
                        <label style="font-size: 0.85rem; font-weight: 700; color: #64748b; margin-bottom: 8px; display: block;">Residential Address</label>
                        <input type="text" name="address" id="address" value="<?php echo htmlspecialchars($user['address']); ?>" required readonly style="width: 100%; padding: 14px; border: 2px solid #f1f5f9; border-radius: 12px; outline: none; transition: 0.3s ease; background: #f8fafc; color: #475569; font-weight: 600;">
                    </div>

                    <div style="display: flex; gap: 20px; align-items: center;">
                        <button type="submit" id="saveButton" name="update_profile" style="background: var(--brand-gradient); color: #fff; padding: 15px 40px; border: none; border-radius: 15px; font-weight: 800; cursor: pointer; transition: 0.3s ease; box-shadow: 0 10px 20px rgba(0,0,0,0.05); display: none;">Update Account</button>
                        <a href="update_password.php" style="color: #6366f1; text-decoration: none; font-weight: 700; font-size: 0.9rem;">Change Password?</a>
                    </div>
                </form>
            </div>
        </div>
    </main>
</div>

<script>
document.getElementById('editBtn').addEventListener('click', function() {
    // Unlock fields
    const contact = document.getElementById('contact');
    const address = document.getElementById('address');
    
    contact.readOnly = false;
    address.readOnly = false;
    
    // Visual indicator of editability
    contact.style.background = "#fff";
    contact.style.borderColor = "#8b5cf6";
    address.style.background = "#fff";
    address.style.borderColor = "#8b5cf6";
    
    // Swap buttons
    this.style.display = "none";
    document.getElementById('saveButton').style.display = "block";
    
    // Focus first field
    contact.focus();
});
</script>

<?php include_once '../includes/footer.php'; ?>

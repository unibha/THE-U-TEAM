<?php
session_start();
require_once '../includes/db.php';
$pageTitle = "Update Password";
$update_error = "";
$update_success = "";
$email = $_SESSION['reset_verified_email'] ?? '';

if (empty($email)) {
    header("Location: reset_password.php");
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND reset_token IS NOT NULL");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user || strtotime($user['reset_expires_at']) <= time()) {
    $update_error = "Session expired or reset process not initialized. Please start again.";
    unset($_SESSION['reset_verified_email']);
} else {
    // Logic remains the same, but we update based on email and reset_token
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirmPassword'] ?? '';
        $strongPasswordRegex = "/^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[@$!%*?&]).{6,}$/";

        if (empty($password) || empty($confirmPassword)) {
            $update_error = "Both fields are required.";
        } elseif ($password !== $confirmPassword) {
            $update_error = "Passwords do not match!";
        } elseif (!preg_match($strongPasswordRegex, $password)) {
            $update_error = "Password must be at least 6 characters long, include an uppercase letter, a lowercase letter, a number, and a special character.";
        } elseif (password_verify($password, $user['password_hash'])) {
            $update_error = "New password cannot be the same as your current password.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            $stmt = $pdo->prepare("UPDATE users SET password_hash = ?, reset_token = NULL, reset_expires_at = NULL WHERE email = ?");
            $stmt->execute([$hashed_password, $email]);
            
            unset($_SESSION['reset_email']);
            unset($_SESSION['reset_verified_email']);
            
            $update_success = "Password successfully reset! Redirecting to login...";
            header("Refresh: 2; url=login.php");
        }
    }
}
include_once '../includes/header.php';
?>

<div class="auth-container">
    <div class="page">
        <div class="registration-card">
            
            <div class="card-header">
                <div class="logo">🎓 AMS</div>
                <h2>Create New Password</h2>
                <p>Ensure your account is secure with a strong password.</p>
            </div>

            <?php if ($user && strtotime($user['reset_expires_at']) > time()): ?>
                <form id="updatePasswordForm" class="form-body" action="update_password.php" method="POST">
                    
                    <?php if (!empty($update_error)): ?>
                        <div class="alert error">
                            <?php echo htmlspecialchars($update_error); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($update_success)): ?>
                        <div class="alert success">
                            <?php echo htmlspecialchars($update_success); ?>
                        </div>
                    <?php else: ?>
                        <!-- Section: New Password -->
                        <div class="form-section">
                            <div class="input-row">
                                <div class="input-group">
                                    <label for="password">New Password</label>
                                    <input type="password" id="password" name="password" placeholder="Min. 6 characters" required>
                                </div>
                            </div>
                            <div class="input-row">
                                <div class="input-group">
                                    <label for="confirmPassword">Confirm Password</label>
                                    <input type="password" id="confirmPassword" name="confirmPassword" placeholder="Repeat new password" required>
                                </div>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn-submit" id="submitBtn">Update My Password</button>
                            <p class="login-link"><a href="login.php">Back to Login</a></p>
                        </div>
                    <?php endif; ?>
                </form>
            <?php else: ?>
                 <div class="form-body">
                    <div class="alert error">
                        <?php echo htmlspecialchars($update_error); ?>
                    </div>
                    <div class="form-actions">
                        <p class="login-link">The link may have expired. <a href="reset_password.php">Request a new link</a></p>
                    </div>
                 </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<?php 
// Global footer handles any needed scripts
include_once '../includes/footer.php'; 
?>

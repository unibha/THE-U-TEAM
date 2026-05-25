<?php
require_once __DIR__ . '/../../config.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once ROOT_DIR . '/includes/db.php';
$pageTitle = "Reset Password Request";
$hideChatbot = true;
$reset_error = "";
$reset_success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'] ?? '';
    if (empty($email)) {
        $reset_error = "Please enter your email.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if ($user) {
            $otp = sprintf("%06d", mt_rand(1, 999999));
            $expiry = date("Y-m-d H:i:s", time() + 15 * 60);
            $stmt = $pdo->prepare("UPDATE users SET reset_token = ?, reset_expires_at = ? WHERE id = ?");
            $stmt->execute([$otp, $expiry, $user['id']]);
            $_SESSION['reset_email'] = $email;

            require_once ROOT_DIR . '/includes/mail_sender.php';
            if (sendOTP($email, $otp)) {
                $_SESSION['otp_message'] = "A reset code has been sent to your email address.";
            } else {
                $_SESSION['otp_message'] = "Failed to send reset email. Please ensure your Mail Configuration is correct.";
            }
            header("Location: " . ROOT_URL . "/public/auth/verify_reset.php");
            exit();
        } else {
            $reset_success = "If this email exists, a reset code will be sent.";
        }
    }
}
include_once ROOT_DIR . '/includes/header.php';
?>
<div class="split-login-container">
    <div class="login-left">
        <div class="brand-header">
            <span class="brand-logo"><i class="fas fa-graduation-cap"></i></span>
            <span class="brand-text">AMS</span>
        </div>
        <div class="brand-content">
            <h1 class="brand-title">Academic Management System</h1>
            <p class="brand-subtitle">A unified platform for students, teachers, and administrators.</p>
        </div>
        <ul class="feature-list">
            <li>Manage grades &amp; results</li>
            <li>Track attendance</li>
            <li>View timetables</li>
            <li>Notice board &amp; updates</li>
        </ul>
    </div>
    <div class="login-right">
        <div class="login-form-wrapper">
            <h2 class="form-title">Recover Your Account</h2>
            <p class="form-subtitle">Enter your registered email to receive a reset link.</p>
            <form id="resetPasswordForm" class="form-body-new" action="<?php echo ROOT_URL; ?>/public/auth/reset_password.php" method="POST">
                <?php if (!empty($reset_error)): ?>
                    <div class="alert error"><?php echo htmlspecialchars($reset_error); ?></div>
                <?php endif; ?>
                <?php if (!empty($reset_success)): ?>
                    <div class="alert success"><?php echo $reset_success; ?></div>
                <?php endif; ?>
                <div class="form-section">
                    <div class="input-row">
                        <div class="input-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" placeholder="Enter your registered email" required>
                        </div>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn-submit" id="submitBtn">Send Reset Link</button>
                    <p class="login-link">Remembered your password? <a href="<?php echo ROOT_URL; ?>/public/auth/login.php">Back to Login</a></p>
                </div>
            </form>
        </div>
    </div>
</div>
<?php
include_once ROOT_DIR . '/includes/footer.php';
?>

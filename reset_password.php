<?php
session_start();
require_once '../includes/db.php';
$pageTitle = "Reset Password Request";
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
              $expiry = date("Y-m-d H:i:s", time() + 15 * 60); // 15 minutes
              
              $stmt = $pdo->prepare("UPDATE users SET reset_token = ?, reset_expires_at = ? WHERE id = ?");
              $stmt->execute([$otp, $expiry, $user['id']]);
              
              $_SESSION['reset_email'] = $email;

              // Send actual Reset OTP email
              require_once '../includes/mail_sender.php';
              if (sendOTP($email, $otp)) {
                  $_SESSION['otp_message'] = "A reset code has been sent to your email address.";
              } else {
                  $_SESSION['otp_message'] = "Failed to send reset email. Please ensure your Mail Configuration is correct.";
              }

              header("Location: verify_reset.php");
              exit();
          } else {
              $reset_success = "If this email exists, a reset code will be sent."; // Don't leak existence
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
                <h2>Recover Your Account</h2>
                <p>We'll send a secure reset link to your registered email address.</p>
            </div>

            <form id="resetPasswordForm" class="form-body" action="reset_password.php" method="POST">
                
                <?php if (!empty($reset_error)): ?>
                    <div class="alert error">
                        <?php echo htmlspecialchars($reset_error); ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($reset_success)): ?>
                    <div class="alert success">
                        <?php echo $reset_success; // Contains simulated HTML link ?>
                    </div>
                <?php endif; ?>

                <!-- Section: Email Search -->
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
                    <p class="login-link">Remembered your password? <a href="login.php">Back to Login</a></p>
                </div>

            </form>
        </div>
    </div>
</div>

<?php 
// Global footer handles any needed scripts
include_once '../includes/footer.php'; 
?>

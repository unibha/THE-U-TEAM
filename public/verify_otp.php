<?php
session_start();
$pageTitle = "Verify OTP";
require_once '../includes/db.php';

$verify_error = "";
$verify_success = "";

if (!isset($_SESSION['verifying_email'])) {
    header("Location: signup.php");
    exit();
}

$email = $_SESSION['verifying_email'];

if (isset($_GET['resend'])) {
    $otp = sprintf("%06d", mt_rand(1, 999999));
    $otp_expiry = date("Y-m-d H:i:s", time() + 15 * 60);

    try {
        $stmt = $pdo->prepare("UPDATE users SET otp_code = ?, otp_expires_at = ? WHERE email = ?");
        $stmt->execute([$otp, $otp_expiry, $email]);

        // Send actual OTP email
        require_once '../includes/mail_sender.php';
        if (sendOTP($email, $otp)) {
            $_SESSION['otp_message'] = "A new OTP has been sent to your email address.";
            $verify_success = "A new OTP has been generated and sent!";
        } else {
            $verify_error = "Failed to send new OTP email. Please ensure your Mail Configuration is correct.";
        }
    } catch (PDOException $e) {
        $verify_error = "Database error: " . $e->getMessage();
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $otp = $_POST['otp'] ?? '';
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user) {
        if ($user['otp_code'] === $otp) {
            if (strtotime($user['otp_expires_at']) > time()) {
                $stmt = $pdo->prepare("UPDATE users SET is_active = 1, otp_code = NULL, otp_expires_at = NULL WHERE id = ?");
                $stmt->execute([$user['id']]);
                
                $verify_success = "Your account has been activated! Redirecting to login...";
                unset($_SESSION['verifying_email']);
                unset($_SESSION['otp_message']);
                header("Refresh: 2; url=login.php");
            } else {
                $verify_error = "OTP has expired. Please register again.";
            }
        } else {
            $verify_error = "Invalid OTP. Please try again.";
        }
    } else {
        $verify_error = "User not found.";
    }
}

include_once '../includes/header.php';
?>

<div class="auth-container">
    <div class="page">
        <div class="registration-card">
            <div class="card-header">
                <div class="logo">🎓 AMS</div>
                <h2>Verify Your Email</h2>
                <p>Enter the OTP sent to your email to activate your account.</p>
            </div>
            <form class="form-body" action="verify_otp.php" method="POST">
                <?php if (isset($_SESSION['otp_message'])): ?>
                    <div style='color: #856404; background-color: #fff3cd; padding: 10px; border-radius: 5px; margin-bottom: 15px; font-weight: bold; text-align: center;'>
                        <?php echo htmlspecialchars($_SESSION['otp_message']); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($verify_error)): ?>
                    <div style='color: #d9534f; background-color: #fdf7f7; padding: 10px; border-radius: 5px; margin-bottom: 15px; font-weight: bold; text-align: center;'>
                        <?php echo htmlspecialchars($verify_error); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($verify_success)): ?>
                    <div style='color: #4CAF50; background-color: #f6fdf6; padding: 15px; border-radius: 12px; margin-bottom: 25px; font-weight: 800; text-align: center; border: 1px solid #dcfce7;'>
                        <i class="fas fa-check-circle" style="margin-right: 8px;"></i>
                        <?php echo htmlspecialchars($verify_success); ?>
                    </div>
                <?php endif; ?>
                
                <?php 
                // Only hide the form if the account is activated and redirecting to login
                $isActivated = (strpos($verify_success, 'activated') !== false);
                if (!$isActivated): 
                ?>
                    <div class="otp-wrapper" style="display: flex; gap: 10px; justify-content: center; margin-bottom: 30px;">
                        <input type="text" class="otp-digit" maxlength="1" pattern="\d" required style="width: 50px; height: 60px; text-align: center; font-size: 1.5rem; font-weight: 800; border: 2px solid #f1f5f9; border-radius: 12px; transition: all 0.3s ease; outline: none; background: #fff;">
                        <input type="text" class="otp-digit" maxlength="1" pattern="\d" required style="width: 50px; height: 60px; text-align: center; font-size: 1.5rem; font-weight: 800; border: 2px solid #f1f5f9; border-radius: 12px; transition: all 0.3s ease; outline: none; background: #fff;">
                        <input type="text" class="otp-digit" maxlength="1" pattern="\d" required style="width: 50px; height: 60px; text-align: center; font-size: 1.5rem; font-weight: 800; border: 2px solid #f1f5f9; border-radius: 12px; transition: all 0.3s ease; outline: none; background: #fff;">
                        <input type="text" class="otp-digit" maxlength="1" pattern="\d" required style="width: 50px; height: 60px; text-align: center; font-size: 1.5rem; font-weight: 800; border: 2px solid #f1f5f9; border-radius: 12px; transition: all 0.3s ease; outline: none; background: #fff;">
                        <input type="text" class="otp-digit" maxlength="1" pattern="\d" required style="width: 50px; height: 60px; text-align: center; font-size: 1.5rem; font-weight: 800; border: 2px solid #f1f5f9; border-radius: 12px; transition: all 0.3s ease; outline: none; background: #fff;">
                        <input type="text" class="otp-digit" maxlength="1" pattern="\d" required style="width: 50px; height: 60px; text-align: center; font-size: 1.5rem; font-weight: 800; border: 2px solid #f1f5f9; border-radius: 12px; transition: all 0.3s ease; outline: none; background: #fff;">
                    </div>
                    <!-- Hidden input to store the full OTP for the PHP form submission -->
                    <input type="hidden" name="otp" id="full_otp">

                    <div class="form-actions">
                        <button type="submit" class="btn-submit" id="verifyBtn" style="width: 100%; background: var(--brand-gradient); color: #fff; padding: 18px; border: none; border-radius: 15px; font-weight: 800; font-size: 1.1rem; cursor: pointer; transition: all 0.3s ease; box-shadow: 0 10px 20px rgba(0,0,0,0.05); margin-bottom: 25px;">Verify Account</button>
                        <p class="login-link" style="text-align: center;">Didn't receive the email? <a href="verify_otp.php?resend" style="color: #6366f1; text-decoration: none; font-weight: 700;">Resend OTP</a></p>
                        <div style="text-align: center; margin-top: 15px;">
                            <a href="signup.php" style="color: #94a3b8; text-decoration: none; font-size: 0.9rem; font-weight: 600;">Back to Registration</a>
                        </div>
                    </div>
                <?php endif; ?>
            </form>
        </div>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>

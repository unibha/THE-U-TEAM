<?php
session_start();
$pageTitle = "Verify Reset Code";
require_once '../includes/db.php';

$verify_error = "";
$verify_success = "";

if (!isset($_SESSION['reset_email'])) {
    header("Location: reset_password.php");
    exit();
}

$email = $_SESSION['reset_email'];

// Handle Resend
if (isset($_GET['resend'])) {
    $otp = sprintf("%06d", mt_rand(1, 999999));
    $expiry = date("Y-m-d H:i:s", time() + 15 * 60);

    try {
        $stmt = $pdo->prepare("UPDATE users SET reset_token = ?, reset_expires_at = ? WHERE email = ?");
        $stmt->execute([$otp, $expiry, $email]);

        // Send actual OTP email
        require_once '../includes/mail_sender.php';
        if (sendOTP($email, $otp)) {
            $_SESSION['otp_message'] = "A new reset code has been sent to your email address.";
            $verify_success = "A new reset code has been generated and sent!";
        } else {
            $verify_error = "Failed to send new reset email. Please ensure your Mail Configuration is correct.";
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
        if ($user['reset_token'] === $otp) {
            if (strtotime($user['reset_expires_at']) > time()) {
                // Grant access to update page
                $_SESSION['reset_verified_email'] = $email;
                $verify_success = "Code verified! Redirecting to update password...";
                header("Refresh: 2; url=update_password.php");
            } else {
                $verify_error = "Reset code has expired. Please request a new one.";
            }
        } else {
            $verify_error = "Invalid code. Please try again.";
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
                <h2>Verify Reset Code</h2>
                <p>Enter the 6-digit code sent to <?php echo htmlspecialchars($email); ?> to reset your password.</p>
            </div>
            
            <div class="form-body">
                <?php if (isset($_SESSION['otp_message'])): ?>
                    <div class="alert simulation" style="background: #eff6ff; color: #1e40af; padding: 15px; border-radius: 12px; margin-bottom: 25px; border: 1px dashed #3b82f6; text-align: center;">
                        <i class="fas fa-info-circle" style="margin-right: 8px;"></i> <?php echo $_SESSION['otp_message']; ?>
                    </div>
                <?php endif; ?>

                <form action="verify_reset.php" method="POST">
                <?php if (!empty($verify_error)): ?>
                    <div class="alert error" style="background-color: #fce7e7; color: #d9534f; padding: 12px; border-radius: 10px; margin-bottom: 20px; font-weight: 600; text-align: center;">
                        <?php echo htmlspecialchars($verify_error); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($verify_success)): ?>
                    <div class="alert success" style="background-color: #e7fcf0; color: #4CAF50; padding: 12px; border-radius: 10px; margin-bottom: 20px; font-weight: 600; text-align: center;">
                        <?php echo htmlspecialchars($verify_success); ?>
                    </div>
                <?php endif; ?>

                <?php if (empty($verify_success) || strpos($verify_success, 'Redirecting') === false): ?>
                    <div class="otp-wrapper">
                        <input type="text" class="otp-digit" maxlength="1" pattern="\d" required>
                        <input type="text" class="otp-digit" maxlength="1" pattern="\d" required>
                        <input type="text" class="otp-digit" maxlength="1" pattern="\d" required>
                        <input type="text" class="otp-digit" maxlength="1" pattern="\d" required>
                        <input type="text" class="otp-digit" maxlength="1" pattern="\d" required>
                        <input type="text" class="otp-digit" maxlength="1" pattern="\d" required>
                    </div>
                    <input type="hidden" name="otp" id="full_otp">

                    <div class="form-actions">
                        <button type="submit" class="btn-submit" id="verifyBtn">Verify & Continue</button>
                        <p class="login-link">Didn't receive it? <a href="verify_reset.php?resend">Resend Code</a></p>
                        <p class="login-link"><a href="reset_password.php">Back to Email Input</a></p>
                    </div>
                <?php endif; ?>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const digits = document.querySelectorAll('.otp-digit');
    const fullOtp = document.getElementById('full_otp');
    const form = document.querySelector('form');

    digits.forEach((digit, idx) => {
        // Auto-focus next box
        digit.addEventListener('input', (e) => {
            if (e.target.value.length === 1 && idx < digits.length - 1) {
                digits[idx + 1].focus();
            }
        });

        // Backspace handling
        digit.addEventListener('keydown', (e) => {
            if (e.key === 'Backspace' && !e.target.value && idx > 0) {
                digits[idx - 1].focus();
            }
        });

        // Paste handling
        digit.addEventListener('paste', (e) => {
            e.preventDefault();
            const data = e.clipboardData.getData('text').slice(0, digits.length);
            if (/^\d+$/.test(data)) {
                data.split('').forEach((char, i) => {
                    if (digits[idx + i]) {
                        digits[idx + i].value = char;
                    }
                });
                const nextIdx = idx + data.length;
                if (digits[nextIdx]) {
                    digits[nextIdx].focus();
                } else {
                    digits[digits.length - 1].focus();
                }
            }
        });
    });

    // Combine digits before submission
    form.addEventListener('submit', (e) => {
        let combined = '';
        digits.forEach(d => combined += d.value);
        fullOtp.value = combined;
        
        if (combined.length !== digits.length) {
            e.preventDefault();
            alert("Please enter the full 6-digit code.");
        }
    });
});
</script>

<?php include_once '../includes/footer.php'; ?>

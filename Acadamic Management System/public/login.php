<?php
session_start();
require_once '../includes/jwttoken.php';
require_once '../includes/db.php';

$pageTitle = "Academic Login";
$login_error = "";
$login_success = "";
$myJwtToken = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $role = $_POST['role'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = ?");
    $stmt->execute([$email, $role]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        if ($user['is_active'] == 1) {
            createSession($user['email'], $user['role']);
            $myJwtToken = createJWT($user['email'], $user['role']);
            
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['first_name'] = $user['first_name'];
            
            if ($role === 'Admin') {
                header("Location: admin_dashboard.php");
            } else if ($role === 'Teacher') {
                header("Location: teacher_dashboard.php");
            } else {
                header("Location: student_dashboard.php");
            }
            exit();
        } else {
            $login_error = "Account is not activated. Redirecting to OTP verification.";
            $_SESSION['verifying_email'] = $user['email'];
            header("Refresh: 2; url=verify_otp.php");
        }
    } else {
        $login_error = "Login Failed: Invalid credentials or role.";
    }
}


include_once '../includes/header.php';
?>

<div class="auth-container">
    <div class="page">
        <div class="registration-card">
            
            <div class="card-header">
                <div class="logo">🎓 AMS</div>
                <h2>Welcome Back</h2>
                <p>Login to the Academic Management System</p>
            </div>

            <form id="loginForm" class="form-body" action="login.php" method="POST">

                <?php if (!empty($login_error)): ?>
                    <div class="alert error">
                        <?php echo htmlspecialchars($login_error); ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($login_success)): ?>
                    <div class="alert success">
                        <?php echo htmlspecialchars($login_success); ?>
                    </div>
                <?php endif; ?>

                <!-- Section: Role Selection -->
                <div class="form-section">
                    <h3 class="section-title">Select Role</h3>
                    <div class="role-selection">
                        <label class="role-card">
                            <input type="radio" name="role" value="Student" checked>
                            <div class="role-content">
                                <h4>📚 Student</h4>
                            </div>
                        </label>
                        <label class="role-card">
                            <input type="radio" name="role" value="Teacher">
                            <div class="role-content">
                                <h4>👨‍🏫 Teacher</h4>
                            </div>
                        </label>
                        <label class="role-card">
                            <input type="radio" name="role" value="Admin">
                            <div class="role-content">
                                <h4>⚙️ Admin</h4>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Section: Credentials -->
                <div class="form-section">
                    <h3 class="section-title">Credentials</h3>
                    <div class="input-row">
                        <div class="input-group" style="width: 100%;">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" placeholder="Enter your email" required>
                        </div>
                    </div>

                    <div class="input-row">
                        <div class="input-group" style="width: 100%;">
                            <label for="password">Password</label>
                            <input type="password" id="password" name="password" placeholder="Enter your password" required>
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-submit" id="submitBtn">Log In</button>
                    <div class="auth-links-row">
                        <p class="login-link compact">Forgot your password? <a href="reset_password.php">Reset here</a></p>
                        <p class="login-link compact divider">Don't have an account? <a href="signup.php">Register here</a></p>
                    </div>
                </div>

            </form>
        </div>
    </div>
</div>

<?php 
include_once '../includes/footer.php'; 
?>
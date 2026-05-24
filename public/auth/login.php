<?php
require_once __DIR__ . '/../../config.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once ROOT_DIR . '/includes/security/jwttoken.php';
require_once ROOT_DIR . '/includes/db.php';
require_once ROOT_DIR . '/includes/security/csrf_helper.php';
require_once ROOT_DIR . '/includes/helpers/validation_helper.php';

$pageTitle = "Academic Login";
$hideChatbot = true;
$login_error = "";
$login_success = "";
$myJwtToken = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. Validate CSRF token to prevent cross-site request forgery attacks
    validate_csrf();

    $role = $_POST['role'] ?? '';
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // 2. Validate Email Format before querying the database
    if (!validate_email($email)) {
        $login_error = "Please enter a valid email address.";
    } else {
        // 3. Securely query the user based on email and selected role
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = ?");
        $stmt->execute([$email, $role]);
        $user = $stmt->fetch();

        // 4. Check if user exists and verify their hashed password
        if ($user && password_verify($password, $user['password_hash'])) {
            // 5. Ensure the user account is fully activated via OTP
            if ($user['is_active'] == 1) {
                // Generate secure JWT token for API requests
                $_SESSION['token'] = createJWT($user['id'], $user['email'], $user['role']);
                
                // Store essential user info in the secure session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['first_name'] = $user['first_name'];
                $_SESSION['role'] = $user['role'];
                
                // 6. Route the user to their respective dashboard based on role
                $dashboards = ['Admin' => ROOT_URL . '/public/admin/dashboard.php', 'Teacher' => ROOT_URL . '/public/teacher/dashboard.php', 'Student' => ROOT_URL . '/public/student/dashboard.php'];
                header("Location: " . ($dashboards[$role] ?? ROOT_URL . '/public/student/dashboard.php'));
                exit();
            } else {
                // Handle unactivated accounts by redirecting to OTP verification
                $login_error = "Account is not activated. Redirecting to OTP verification.";
                $_SESSION['verifying_email'] = $user['email'];
                header("Refresh: 2; url=" . ROOT_URL . "/public/auth/verify_otp.php");
            }
        } else {
            // Generic error message to prevent account enumeration
            $login_error = "Login Failed: Invalid credentials or role.";
        }
    }
}


include_once ROOT_DIR . '/includes/header.php';
?>

<div class="split-login-container">
    <!-- Left Column: Branding and Features -->
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
            <li>Manage grades & results</li>
            <li>Track attendance</li>
            <li>View timetables</li>
            <li>Notice board & updates</li>
        </ul>
    </div>

    <!-- Right Column: Login Form -->
    <div class="login-right">
        <div class="login-form-wrapper">
            <h2 class="form-title">Welcome back</h2>
            <p class="form-subtitle">Login to your account</p>

            <form id="loginForm" class="form-body-new" action="<?php echo ROOT_URL; ?>/public/auth/login.php" method="POST">
                <?php echo csrf_field(); ?>

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
                <div class="form-section-new">
                    <span class="section-label">SELECT ROLE</span>
                    <div class="role-selection-new">
                        <label class="role-card-new">
                            <input type="radio" name="role" value="Student" checked>
                            <div class="role-content-new">
                                <i class="fas fa-user-graduate"></i>
                                <span>Student</span>
                            </div>
                        </label>
                        <label class="role-card-new">
                            <input type="radio" name="role" value="Teacher">
                            <div class="role-content-new">
                                <i class="fas fa-chalkboard-teacher"></i>
                                <span>Teacher</span>
                            </div>
                        </label>
                        <label class="role-card-new">
                            <input type="radio" name="role" value="Admin">
                            <div class="role-content-new">
                                <i class="fas fa-user-shield"></i>
                                <span>Admin</span>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Section: Credentials -->
                <div class="form-section-new">
                    <span class="section-label">CREDENTIALS</span>
                    <div class="input-group-new">
                        <label for="email">Email address</label>
                        <input type="email" id="email" name="email" placeholder="Enter your email address" value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" required>
                    </div>

                    <div class="input-group-new">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" placeholder="Enter your password" required>
                    </div>

                    <div class="forgot-row">
                        <a href="<?php echo ROOT_URL; ?>/public/auth/reset_password.php" class="forgot-link">Forgot password?</a>
                    </div>
                </div>

                <div class="form-actions-new">
                    <button type="submit" class="btn-submit-new" id="submitBtn">Log in</button>
                    <p class="signup-prompt">Don't have an account? <a href="<?php echo ROOT_URL; ?>/public/auth/signup.php">Register here</a></p>
                </div>
            </form>
        </div>
    </div>
</div>

<?php 
include_once ROOT_DIR . '/includes/footer.php'; 
?>
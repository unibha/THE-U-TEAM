<?php
session_start();
require_once '../includes/jwttoken.php';

$pageTitle = "Academic Login";
$login_error = "";
$login_success = "";
$myJwtToken = "";

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $role = $_POST['role'] ?? '';
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if ($username == "admin" && $password == "1234") {
        createSession($username, $role);
        $myJwtToken = createJWT($username, $role);
        $login_success = "Login Successful! Welcome " . htmlspecialchars($username) . ".";
    } else {
        $login_error = "Login Failed: Invalid credentials or role.";
    }
}

// Bring in the global header
include_once '../includes/header.php';
?>

<div class="page">
    <div class="registration-card">
        
        <div class="card-header">
            <div class="logo">🎓 AMS</div>
            <h2>Welcome Back</h2>
            <p>Login to the Academic Management System</p>
        </div>

        <form id="loginForm" class="form-body" action="login.php" method="POST">

            <?php if (!empty($login_error)): ?>
                <div style='color: #d9534f; background-color: #fdf7f7; padding: 10px; border-radius: 5px; margin-bottom: 15px; font-weight: bold; text-align: center;'>
                    <?php echo htmlspecialchars($login_error); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($login_success)): ?>
                <div style='color: #4CAF50; background-color: #f6fdf6; padding: 10px; border-radius: 5px; margin-bottom: 15px; font-weight: bold; text-align: center;'>
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
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" placeholder="Enter your username" required>
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
                <p class="login-link">Don't have an account? <a href="signup.php">Register here</a></p>
            </div>

        </form>
    </div>
</div>

<?php 
// Bring in the global footer
include_once '../includes/footer.php'; 
?>
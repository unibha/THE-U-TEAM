<?php
$pageTitle = "Academic Registration";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Collect POST data
    $firstName = $_POST['firstName'] ?? '';
    // Process backend save logic
    echo "<script>alert('Test: Form POST submitted to PHP successfully, $firstName!');</script>";
}

// Bring in the global header which holds the CSS
include_once '../includes/header.php';
?>

<div class="page">
    <div class="registration-card">
        
        <div class="card-header">
            <div class="logo">🎓 AMS</div>
            <h2>Create Your Account</h2>
            <p>Register for the Academic Management System</p>
        </div>

        <form id="registrationForm" class="form-body" action="signup.php" method="POST">
            
            <!-- Section 1: Account & Contact Info -->
            <div class="form-section">
                <h3 class="section-title">Personal Details</h3>
                
                <div class="input-row">
                    <div class="input-group">
                        <label for="firstName">First Name</label>
                        <input type="text" id="firstName" name="firstName">
                    </div>
                    <div class="input-group">
                        <label for="lastName">Last Name</label>
                        <input type="text" id="lastName" name="lastName">
                    </div>
                </div>

                <div class="input-row">
                    <div class="input-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email">
                    </div>
                    <div class="input-group">
                        <label for="contact">Contact Number</label>
                        <input type="tel" id="contact" name="contact">
                    </div>
                </div>

                <div class="input-row">
                    <div class="input-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" placeholder="Create a strong password" required>
                    </div>
                    <div class="input-group">
                        <label for="confirmPassword">Confirm Password</label>
                        <input type="password" id="confirmPassword" name="confirmPassword" placeholder="Confirm your password" required>
                    </div>
                </div>
            </div>

            <!-- Section 2: Role Selection -->
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
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-submit" id="submitBtn">Register Account</button>
                <p class="login-link">Already have an account? <a href="login.php">Log in here</a></p>
            </div>

        </form>
    </div>
</div>

<?php 
// Bring in the global footer which holds the JS 
include_once '../includes/footer.php'; 
?>
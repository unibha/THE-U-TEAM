<?php
session_start();
$pageTitle = "Academic Registration";

$signup_error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Collect POST data
    $firstName = $_POST['firstName'] ?? '';
    $lastName = $_POST['lastName'] ?? '';
    $dobDay = $_POST['dobDay'] ?? '';
    $dobMonth = $_POST['dobMonth'] ?? '';
    $dobYear = $_POST['dobYear'] ?? '';
    $dob = ($dobDay && $dobMonth && $dobYear) ? "$dobYear-" . str_pad($dobMonth, 2, "0", STR_PAD_LEFT) . "-" . str_pad($dobDay, 2, "0", STR_PAD_LEFT) : '';
    $address = $_POST['address'] ?? '';
    $email = $_POST['email'] ?? '';
    $contact = $_POST['contact'] ?? '';
    $motherName = $_POST['motherName'] ?? '';
    $fatherName = $_POST['fatherName'] ?? '';
    $guardianContact = $_POST['guardianContact'] ?? '';
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'Student';
    
    $strongPasswordRegex = "/^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[@$!%*?&]).{6,}$/";
    $contactRegex = "/^\+?\d{7,15}$/";
    if (!preg_match($strongPasswordRegex, $password)) {
        $signup_error = "Password must be at least 6 characters long, include an uppercase letter, a lowercase letter, a number, and a special character.";
    } elseif (!preg_match($contactRegex, $contact)) {
        $signup_error = "Validation Error: Please enter a valid contact number (7 to 15 digits).";
    } elseif (!preg_match($contactRegex, $guardianContact)) {
        $signup_error = "Validation Error: Please enter a valid guardian contact number (7 to 15 digits).";
    } else {
        require_once '../includes/db.php';
        $hashed_password = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        
        $otp = sprintf("%06d", mt_rand(1, 999999));
        $otp_expiry = date("Y-m-d H:i:s", time() + 15 * 60);
        
        try {
            $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, dob, address, email, password_hash, role, contact, mother_name, father_name, guardian_contact, is_active, otp_code, otp_expires_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?, ?)");
            $stmt->execute([$firstName, $lastName, $dob, $address, $email, $hashed_password, $role, $contact, $motherName, $fatherName, $guardianContact, $otp, $otp_expiry]);
            
            $userId = $pdo->lastInsertId();
            if ($role === 'Student') {
                $pdo->prepare("INSERT INTO students (user_id) VALUES (?)")->execute([$userId]);
            } elseif ($role === 'Teacher') {
                $pdo->prepare("INSERT INTO teachers (user_id) VALUES (?)")->execute([$userId]);
            }
            
            $_SESSION['verifying_email'] = $email;
            
            // Send actual OTP email
            require_once '../includes/mail_sender.php';
            if (sendOTP($email, $otp)) {
                $_SESSION['otp_message'] = "An OTP has been sent to your email address.";
            } else {
                $_SESSION['otp_message'] = "Account created, but failed to send OTP email (Please configure your Gmail App Password). Click resend when you are ready.";
            }
            
            header("Location: verify_otp.php");
            exit();
            
        } catch (\PDOException $e) {
            if ($e->getCode() == 23000) {
                $signup_error = "Email address is already registered.";
            } else {
                $signup_error = "Database Error: " . $e->getMessage();
            }
        }
    }
}

// Bring in the global header which holds the CSS
include_once '../includes/header.php';
?>

<div class="auth-container">
    <div class="page">
        <div class="registration-card">
            
            <div class="card-header">
                <div class="logo">🎓 AMS</div>
                <h2>Create Your Account</h2>
                <p>Register for the Academic Management System</p>
            </div>

            <form id="registrationForm" class="form-body" action="signup.php" method="POST">
                
                <?php if (!empty($signup_error)): ?>
                    <div class="alert error">
                        <?php echo htmlspecialchars($signup_error); ?>
                    </div>
                <?php endif; ?>
                <!-- Section 1: Account & Contact Info -->
                <div class="form-section">
                    <h3 class="section-title">Personal Details</h3>
                    
                    <div class="input-row">
                        <div class="input-group">
                            <label for="firstName">First Name</label>
                            <input type="text" id="firstName" name="firstName" required>
                        </div>
                        <div class="input-group">
                            <label for="lastName">Last Name</label>
                            <input type="text" id="lastName" name="lastName" required>
                        </div>
                    </div>

                    <div class="input-row">
                        <div class="input-group">
                            <label>Date of Birth</label>
                            <div class="date-group">
                                <select id="dobDay" name="dobDay" required>
                                    <option value="" disabled selected>Day</option>
                                    <?php for ($i = 1; $i <= 31; $i++) echo "<option value='$i'>$i</option>"; ?>
                                </select>
                                <select id="dobMonth" name="dobMonth" required>
                                    <option value="" disabled selected>Month</option>
                                    <?php 
                                    $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                                    foreach ($months as $index => $month) {
                                        $m = $index + 1;
                                        echo "<option value='$m'>$month</option>"; 
                                    }
                                    ?>
                                </select>
                                <select id="dobYear" name="dobYear" required>
                                    <option value="" disabled selected>Year</option>
                                    <?php 
                                    $currentYear = date('Y');
                                    for ($i = $currentYear; $i >= 1900; $i--) {
                                        echo "<option value='$i'>$i</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="input-group">
                            <label for="address">Address</label>
                            <input type="text" id="address" name="address" required>
                        </div>
                    </div>

                    <div class="input-row">
                        <div class="input-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" required>
                        </div>
                        <div class="input-group">
                            <label for="contact">Contact Number</label>
                            <input type="tel" id="contact" name="contact" required>
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

                <!-- Section 2: Guardian Information -->
                <div class="form-section">
                    <h3 class="section-title">Guardian Information</h3>
                    
                    <div class="input-row">
                        <div class="input-group">
                            <label for="motherName">Mother's Name</label>
                            <input type="text" id="motherName" name="motherName" required>
                        </div>
                        <div class="input-group">
                            <label for="fatherName">Father's Name</label>
                            <input type="text" id="fatherName" name="fatherName" required>
                        </div>
                    </div>

                    <div class="input-row">
                        <div class="input-group">
                            <label for="guardianContact">Guardian Contact Number</label>
                            <input type="tel" id="guardianContact" name="guardianContact" required>
                        </div>
                    </div>
                </div>

                <!-- Section 3: Role Selection -->
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
</div>

<?php 
// Bring in the global footer which holds the JS 
include_once '../includes/footer.php'; 
?>
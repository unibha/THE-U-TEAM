<?php
require_once __DIR__ . '/../../config.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once ROOT_DIR . '/includes/security/csrf_helper.php';
require_once ROOT_DIR . '/includes/helpers/validation_helper.php';
require_once ROOT_DIR . '/includes/db.php';

$pageTitle = "Academic Registration";
$hideChatbot = true;
$signup_error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate CSRF
    validate_csrf();

    // Collect & sanitize data
    $firstName = sanitize($_POST['firstName'] ?? '');
    $lastName = sanitize($_POST['lastName'] ?? '');
    $dob = (!empty($_POST['dobYear']) && !empty($_POST['dobMonth']) && !empty($_POST['dobDay']))
        ? sprintf("%04d-%02d-%02d", $_POST['dobYear'], $_POST['dobMonth'], $_POST['dobDay'])
        : '';
    $address = sanitize($_POST['address'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $contact = sanitize($_POST['contact'] ?? '');
    $motherName = sanitize($_POST['motherName'] ?? '');
    $fatherName = sanitize($_POST['fatherName'] ?? '');
    $guardianContact = sanitize($_POST['guardianContact'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirmPassword'] ?? '';
    $role = $_POST['role'] ?? 'Student';

    // Validation
    $errors = [];
    if (empty($firstName) || empty($lastName)) $errors[] = "Full name is required.";
    if (!validate_email($email)) $errors[] = "A valid email address is required.";
    if (!validate_password($password)) $errors[] = "Password must be at least 8 characters, containing both letters and numbers.";
    if ($password !== $confirmPassword) $errors[] = "Passwords do not match.";
    if (!validate_phone($contact)) $errors[] = "Primary contact must be numeric.";
    if (!validate_phone($guardianContact)) $errors[] = "Guardian contact must be numeric.";
    if (!validate_date($dob)) $errors[] = "Please provide a valid date of birth.";

    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        $otp = sprintf("%06d", mt_rand(1, 999999));
        $otp_expiry = date("Y-m-d H:i:s", time() + 15 * 60);
        try {
            $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, dob, address, email, password_hash, role, contact, mother_name, father_name, guardian_contact, is_active, otp_code, otp_expires_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?, ?)");
            $stmt->execute([$firstName, $lastName, $dob, $address, $email, $hashed_password, $role, $contact, $motherName, $fatherName, $guardianContact, $otp, $otp_expiry]);
            $userId = $pdo->lastInsertId();
            $table = ($role === 'Teacher') ? 'teachers' : 'students';
            $pdo->prepare("INSERT INTO $table (user_id) VALUES (?)")->execute([$userId]);
            $_SESSION['verifying_email'] = $email;
            require_once ROOT_DIR . '/includes/mail_sender.php';
            if (sendOTP($email, $otp)) {
                $_SESSION['otp_message'] = "An OTP has been sent to your email address.";
            } else {
                $_SESSION['otp_message'] = "Account created, but failed to send OTP email (Please configure your Gmail App Password). Click resend when you are ready.";
            }
            header("Location: " . ROOT_URL . "/public/auth/verify_otp.php");
            exit();
        } catch (\PDOException $e) {
            if ($e->getCode() == 23000) {
                $signup_error = "Email address is already registered.";
            } else {
                $signup_error = "Database Error: " . $e->getMessage();
            }
        }
    } else {
        $signup_error = format_errors($errors);
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
            <h2 class="form-title">Create Your Account</h2>
            <p class="form-subtitle">Register for the Academic Management System</p>
            <form id="registrationForm" class="form-body-new" action="<?php echo ROOT_URL; ?>/public/auth/signup.php" method="POST">
                <?php echo csrf_field(); ?>
                <?php if (!empty($signup_error)): ?>
                    <div class="alert error"><?php echo htmlspecialchars($signup_error); ?></div>
                <?php endif; ?>
                <!-- Personal Details -->
                <div class="form-section">
                    <h3 class="section-title">Personal Details</h3>
                    <div class="input-row">
                        <div class="input-group"><label for="firstName">First Name</label><input type="text" id="firstName" name="firstName" required></div>
                        <div class="input-group"><label for="lastName">Last Name</label><input type="text" id="lastName" name="lastName" required></div>
                    </div>
                    <div class="input-row">
                        <div class="input-group"><label>Date of Birth</label><div class="date-group">
                            <select id="dobDay" name="dobDay" required><option value="" disabled selected>Day</option>
                                <?php for ($i = 1; $i <= 31; $i++) echo "<option value='$i'>$i</option>"; ?>
                            </select>
                            <select id="dobMonth" name="dobMonth" required><option value="" disabled selected>Month</option>
                                <?php $months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
                                      foreach ($months as $idx=>$month) { $m=$idx+1; echo "<option value='$m'>$month</option>"; } ?>
                            </select>
                            <select id="dobYear" name="dobYear" required><option value="" disabled selected>Year</option>
                                <?php $currentYear = date('Y'); for ($i = $currentYear; $i >= 1900; $i--) echo "<option value='$i'>$i</option>"; ?>
                            </select>
                        </div></div>
                        <div class="input-group"><label for="address">Address</label><input type="text" id="address" name="address" required></div>
                    </div>
                    <div class="input-row">
                        <div class="input-group"><label for="email">Email Address</label><input type="email" id="email" name="email" required></div>
                        <div class="input-group"><label for="contact">Contact Number</label><input type="tel" id="contact" name="contact" required></div>
                    </div>
                    <div class="input-row">
                        <div class="input-group"><label for="password">Password</label><input type="password" id="password" name="password" placeholder="Create a strong password" required></div>
                        <div class="input-group"><label for="confirmPassword">Confirm Password</label><input type="password" id="confirmPassword" name="confirmPassword" placeholder="Confirm your password" required></div>
                    </div>
                </div>
                <!-- Guardian Information -->
                <div class="form-section">
                    <h3 class="section-title">Guardian Information</h3>
                    <div class="input-row">
                        <div class="input-group"><label for="motherName">Mother's Name</label><input type="text" id="motherName" name="motherName" required></div>
                        <div class="input-group"><label for="fatherName">Father's Name</label><input type="text" id="fatherName" name="fatherName" required></div>
                    </div>
                    <div class="input-row">
                        <div class="input-group"><label for="guardianContact">Guardian Contact Number</label><input type="tel" id="guardianContact" name="guardianContact" required></div>
                    </div>
                </div>
                <!-- Role Selection -->
                <div class="form-section">
                    <h3 class="section-title">Select Role</h3>
                    <div class="role-selection">
                        <label class="role-card">
                            <input type="radio" name="role" value="Student" checked>
                            <div class="role-content"><i class="fas fa-user-graduate"></i> Student</div>
                        </label>
                        <label class="role-card">
                            <input type="radio" name="role" value="Teacher">
                            <div class="role-content"><i class="fas fa-chalkboard-teacher"></i> Teacher</div>
                        </label>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn-submit" id="submitBtn">Register Account</button>
                    <p class="login-link">Already have an account? <a href="<?php echo ROOT_URL; ?>/public/auth/login.php">Log in here</a></p>
                </div>
            </form>
        </div>
    </div>
</div>
<?php
include_once ROOT_DIR . '/includes/footer.php';
?>
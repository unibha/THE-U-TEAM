<?php
session_start();

// get form data
$role = $_POST['role'];
$username = $_POST['username'];
$password = $_POST['password'];

// simple validation (demo purpose)
if ($username == "admin" && $password == "1234") {
    $_SESSION['user'] = $username;
    $_SESSION['role'] = $role;

    echo "<h2>Login Successful</h2>";
    echo "Welcome $username <br>";
    echo "Role: $role";
} else {
    echo "<h2>Login Failed ❌</h2>";
    echo "<a href='index.php'>Try Again</a>";
}
?>
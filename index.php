<?php
// start session
session_start();
?>

<!DOCTYPE html>
<html>
<head>
  <title>Academic Management System</title>
  <link rel="stylesheet" href="style.css">
</head>

<body>

<div class="main">

  <div class="left">
    <div class="logo">🎓</div>
    <h2>Academic Management System</h2>
    <p>The U-Team</p>
  </div>

  <div class="right">

    <form action="login.php" method="POST">

      <div class="card">
        <h3>Select your role:</h3>

        <select name="role" required>
          <option value="">--Select Role--</option>
          <option value="Student">Student</option>
          <option value="Teacher">Teacher</option>
          <option value="Admin">Admin</option>
        </select>
      </div>

      <div class="card">
        <h3>Login</h3>

        <input type="text" name="username" placeholder="Username" required>
        <input type="password" name="password" placeholder="Password" required>

        <button type="submit">Login</button>
      </div>

    </form>

  </div>

</div>

</body>
</html>
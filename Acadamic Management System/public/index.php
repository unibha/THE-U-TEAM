<?php
// Since login.php is now our beautiful standalone form page, 
// we simply redirect any traffic hitting the public root straight to the login screen!
header("Location: login.php");
exit();
?>
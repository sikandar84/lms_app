<?php
session_start();

// Destroy all session data
$_SESSION = [];
session_unset();
session_destroy();

// Redirect user to login page
header("Location: ../auth/login.php");
exit();
?>

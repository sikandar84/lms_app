<?php
// TEMP: Just to hash a password manually and show the value
$password = 'sikandarr'; // Change this to whatever password you want
$hashed = password_hash($password, PASSWORD_DEFAULT);
echo "Hashed password: " . $hashed;
?>

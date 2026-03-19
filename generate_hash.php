<?php
// Replace 'admin123' with the password you want to use
$password = 'admin123';

// Generate a secure hash
$hash = password_hash($password, PASSWORD_DEFAULT);

// Output the hash
echo $hash;
<?php
// This is a one-time script to create a test admin user with hashed password
require 'config.php';

$username = 'admin';
$password = 'admin123';
$hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $pdo->prepare("INSERT INTO admins (username, password_hash) VALUES (:username, :password_hash)");
$stmt->execute(['username' => $username, 'password_hash' => $hash]);

echo "Admin created.";
?>

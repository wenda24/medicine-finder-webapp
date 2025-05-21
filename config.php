<?php
$host = 'localhost';
$dbname = 'pharmacy_db';
$username = 'root';
$password = '';
$charset = 'utf8mb4';

// Create connection
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>

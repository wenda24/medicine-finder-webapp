<?php
// includes/auth.php
session_start();

function authenticateAdmin() {
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['ip_address'] !== $_SERVER['REMOTE_ADDR']) {
        header('Location: adminlogin.php');
        exit();
    }
}

function connectDB() {
    $host = "localhost";
    $dbname = "pharmacy_db";
    $user = "root";
    $pass = "";
    
    try {
        return new PDO(
            "mysql:host=$host;dbname=$dbname",
            $user,
            $pass,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]
        );
    } catch (PDOException $e) {
        die("Database connection failed: " . $e->getMessage());
    }
}
?>
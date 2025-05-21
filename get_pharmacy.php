<?php
session_start();
require 'config.php';

if (!isset($_SESSION['admin_logged_in'])) {
    http_response_code(403);
    exit("Unauthorized access.");
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    exit("Invalid request.");
}

$pharmacyId = (int)$_GET['id'];

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );

    $stmt = $pdo->prepare("SELECT * FROM pharmacies WHERE id = ?");
    $stmt->execute([$pharmacyId]);
    $pharmacy = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($pharmacy) {
        header('Content-Type: application/json');
        echo json_encode($pharmacy);
    } else {
        http_response_code(404);
        exit("Pharmacy not found.");
    }
} catch (PDOException $e) {
    http_response_code(500);
    exit("Database error: " . $e->getMessage());
}
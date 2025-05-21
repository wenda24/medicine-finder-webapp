<?php
$pdo = new PDO("mysql:host=localhost;dbname=pharmacy_db", "root", "", [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);
echo "Connection successful!";
?>

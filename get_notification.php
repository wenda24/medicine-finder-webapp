<?php
session_start();
require_once 'config.php';

$pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=$charset",
               $username,$password,
               [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);

$sql = "
  SELECT name, quantity, expiration_date
  FROM inventory
  WHERE pharmacy_id = ?
    AND (
      quantity <= 2
      OR expiration_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
    )
  ORDER BY expiration_date ASC, quantity ASC
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$_SESSION['pharmacy_id']]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
  'count' => count($items),
  'items' => $items
]);

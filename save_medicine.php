<?php
session_start();
require_once 'config.php';

// CSRF check
if (!isset($_POST['csrf_token'])
    || $_POST['csrf_token'] !== $_SESSION['csrf_token']
) {
    http_response_code(403);
    echo json_encode(['success'=>false,'error'=>'Security validation failed!']);
    exit;
}

// Validate and insert just like before
try {
    $required = ['name','quantity','expiry','price'];
    foreach($required as $f){
      if(empty($_POST[$f])) throw new Exception('All fields are required');
    }
    $quantity = filter_var($_POST['quantity'], FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
    $price    = filter_var($_POST['price'], FILTER_VALIDATE_FLOAT);
    if ($quantity===false||$price===false) throw new Exception('Invalid input values');
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=$charset",
                   $username,$password,
                   [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
    
    $stmt = $pdo->prepare("
      INSERT INTO inventory
         (pharmacy_id, name, quantity, expiration_date, price)
      VALUES
         (:pharmacy_id, :name, :quantity, :expiration_date, :price)
    ");
    $stmt->execute([
      ':pharmacy_id'=>$_SESSION['pharmacy_id'],
      ':name'        =>htmlspecialchars(trim($_POST['name'])),
      ':quantity'    =>$quantity,
      ':expiration_date'=>date('Y-m-d',strtotime($_POST['expiry'])),
      ':price'       =>$price
    ]);
    
    echo json_encode(['success'=>true]);
} catch(Exception $e) {
    http_response_code(400);
    echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
}

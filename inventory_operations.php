<?php
function fetch_notifications($pdo, $manager_id) {
    $stmt = $pdo->prepare("SELECT * FROM notifications WHERE manager_id = ?");
    $stmt->execute([$manager_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function handle_operations($pdo) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

    $pharmacy_id = $_SESSION['pharmacy_id'];

    if (isset($_POST['save'])) {
        // Add/update medicine
        $medicine_id = handle_medicine($pdo, $_POST['name']);

        $data = [
            'pharmacy_id' => $pharmacy_id,
            'medicine_id' => $medicine_id,
            'quantity' => $_POST['quantity'],
            'price' => $_POST['price'],
            'expiry' => $_POST['expiry']
        ];

        $pdo->prepare("
            INSERT INTO medicine_stock 
            (pharmacy_id, medicine_id, quantity, price, expiration_date)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
            quantity = VALUES(quantity),
            price = VALUES(price),
            expiration_date = VALUES(expiration_date)
        ")->execute(array_values($data));
    } elseif (isset($_POST['delete'])) {
        $pdo->prepare("DELETE FROM medicine_stock WHERE id = ?")
           ->execute([$_POST['stock_id']]);
    }
}

function handle_medicine($pdo, $name) {
    $stmt = $pdo->prepare("SELECT id FROM medicines WHERE name = ?");
    $stmt->execute([$name]);

    if ($stmt->rowCount() > 0) {
        return $stmt->fetch()['id'];
    }

    $pdo->prepare("INSERT INTO medicines (name) VALUES (?)")
       ->execute([$name]);
    return $pdo->lastInsertId();
}

function fetch_inventory($pdo, $pharmacy_id) {
    $stmt = $pdo->prepare("
        SELECT ps.id, ps.quantity, ps.price, ps.expiration_date, m.name
        FROM medicine_stock ps
        JOIN medicines m ON ps.medicine_id = m.id
        WHERE ps.pharmacy_id = ?
        ORDER BY m.name
    ");
    $stmt->execute([$pharmacy_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

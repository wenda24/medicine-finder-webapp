<?php
// search.php

require 'config.php'; // adjust this to your actual DB connection file

header('Content-Type: application/json');

$q = isset($_GET['q']) ? trim($_GET['q']) : '';
if ($q === '') {
    echo json_encode([]);
    exit;
}

$sql = "
SELECT 
    p.name AS pharmacy_name,
    p.address AS location,
    p.phone,
    m.name AS medicine_name,
    ms.quantity,
    ms.price,
    p.status = 'active' AS verified
FROM 
    medicine_stock ms
JOIN 
    pharmacies p ON ms.pharmacy_id = p.id
JOIN
    medicines m ON ms.medicine_id = m.id
WHERE 
    m.name LIKE CONCAT('%', ?, '%')
    AND p.status = 'active'
ORDER BY 
    ms.price ASC
";



$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $q);
$stmt->execute();
$result = $stmt->get_result();

$data = [];

while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode($data);
?>

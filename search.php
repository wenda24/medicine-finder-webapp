<?php
// search.php

require 'config.php'; // adjust this to your actual DB connection file

header('Content-Type: application/json');

// Get query and optional user location
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$userLat = isset($_GET['lat']) ? floatval($_GET['lat']) : null;
$userLng = isset($_GET['lng']) ? floatval($_GET['lng']) : null;

if ($q === '') {
    echo json_encode([]);
    exit;
}

$includeDistance = $userLat !== null && $userLng !== null;

// Build SQL with optional distance calculation
$sql = "
SELECT 
    p.name AS pharmacy_name,
    p.address AS location,
    p.phone,
    m.name AS medicine_name,
    ms.quantity,
    ms.price,
    p.latitude,
    p.longitude,
    p.status = 'active' AS verified
";

if ($includeDistance) {
    $sql .= ",
    (
        6371 * ACOS(
            COS(RADIANS(?)) * COS(RADIANS(p.latitude)) *
            COS(RADIANS(p.longitude) - RADIANS(?)) +
            SIN(RADIANS(?)) * SIN(RADIANS(p.latitude))
        )
    ) AS distance_km
    ";
}

$sql .= "
FROM 
    medicine_stock ms
JOIN 
    pharmacies p ON ms.pharmacy_id = p.id
JOIN
    medicines m ON ms.medicine_id = m.id
WHERE 
    m.name LIKE CONCAT('%', ?, '%')
    AND p.status = 'active'
";

if ($includeDistance) {
    $sql .= " ORDER BY distance_km ASC";
} else {
    $sql .= " ORDER BY ms.price ASC";
}

// Prepare and bind parameters
if ($includeDistance) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ddds", $userLat, $userLng, $userLat, $q);
} else {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $q);
}

$stmt->execute();
$result = $stmt->get_result();

$data = [];

while ($row = $result->fetch_assoc()) {
    if ($includeDistance) {
        $row['distance_km'] = round($row['distance_km'], 2);
    }
    $data[] = $row;
}

echo json_encode($data);
?>

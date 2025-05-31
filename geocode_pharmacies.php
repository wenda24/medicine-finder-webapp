<?php
require 'config.php';

// Set user agent as required by OpenStreetMap policy
ini_set('user_agent', 'MedFinder/1.0 (contact@example.com)');

function geocodeAddress($address) {
    $url = 'https://nominatim.openstreetmap.org/search?' . http_build_query([
        'q' => $address . ', Ethiopia',
        'format' => 'json',
        'limit' => 1,
        'addressdetails' => 1
    ]);

    $context = stream_context_create([
        'http' => ['header' => "User-Agent: MedFinder/1.0 (contact@example.com)\r\n"]
    ]);
    
    $response = file_get_contents($url, false, $context);
    
    if ($response === false) {
        return null;
    }
    
    $data = json_decode($response, true);
    
    if (!empty($data) && isset($data[0]['lat'], $data[0]['lon'])) {
        return [
            'lat' => floatval($data[0]['lat']),
            'lon' => floatval($data[0]['lon'])
        ];
    }
    return null;
}

// Select only records with missing coordinates
$sql = "SELECT id, address FROM pharmacies 
        WHERE (latitude IS NULL OR longitude IS NULL)
        AND status = 'active'";
$result = $conn->query($sql);

if (!$result) {
    die("Database error: " . $conn->error);
}

$updated = 0;
$failed = 0;

while ($row = $result->fetch_assoc()) {
    // Add delay to comply with OSM's 1 request/second policy
    sleep(1);
    
    $coords = geocodeAddress($row['address']);
    
    if ($coords) {
        $stmt = $conn->prepare("UPDATE pharmacies 
                               SET latitude = ?, longitude = ? 
                               WHERE id = ?");
        $stmt->bind_param("ddi", $coords['lat'], $coords['lon'], $row['id']);
        
        if ($stmt->execute()) {
            $updated++;
            echo "Updated ID {$row['id']} with coordinates: " .
                 "{$coords['lat']}, {$coords['lon']}<br>";
        } else {
            $failed++;
            echo "Database update failed for ID {$row['id']}: " .
                 $stmt->error . "<br>";
        }
        $stmt->close();
    } else {
        $failed++;
        echo "Geocoding failed for ID {$row['id']}: {$row['address']}<br>";
    }
}

echo "<br>Process complete. Updated: $updated, Failed: $failed";
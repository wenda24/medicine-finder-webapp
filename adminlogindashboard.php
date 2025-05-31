<?php
session_start();
require 'config.php';

$error = '';
$success = '';

// CSRF token generation
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Invalid CSRF token";
    } else {
        $name = trim($_POST['name'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $latitude = $_POST['latitude'] ?? null;
        $longitude = $_POST['longitude'] ?? null;

        if (isset($_POST['add_pharmacy'])) {
            if ($name && $address && $phone && $latitude && $longitude) {
                $stmt = $conn->prepare("INSERT INTO pharmacies (name, address, phone, latitude, longitude, status, created_at, is_approved) VALUES (?, ?, ?, ?, ?, 'active', NOW(), 0)");
                $stmt->bind_param("sssdd", $name, $address, $phone, $latitude, $longitude);
                $stmt->execute() ? $success = "Pharmacy added successfully." : $error = "Error adding pharmacy: " . $stmt->error;
                $stmt->close();
            } else {
                $error = "Please fill in all fields and select a location on the map.";
            }
        } elseif (isset($_POST['update_pharmacy'])) {
            $id = intval($_POST['pharmacy_id']);
            $status = trim($_POST['status'] ?? '');
            $latitude = $_POST['latitude'] ?? null;
            $longitude = $_POST['longitude'] ?? null;
            
            if ($id && $name && $address && $phone && $status && $latitude && $longitude) {
                $stmt = $conn->prepare("UPDATE pharmacies SET name = ?, address = ?, phone = ?, latitude = ?, longitude = ?, status = ? WHERE id = ?");
                $stmt->bind_param("sssddsi", $name, $address, $phone, $latitude, $longitude, $status, $id);
                $stmt->execute() ? $success = "Pharmacy updated successfully." : $error = "Error updating pharmacy: " . $stmt->error;
                $stmt->close();
            } else {
                $error = "All fields are required and location must be selected on map.";
            }
        } elseif (isset($_POST['delete_pharmacy'])) {
            $id = intval($_POST['pharmacy_id']);
            if ($id > 0) {
                $stmt = $conn->prepare("DELETE FROM pharmacies WHERE id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute() ? $success = "Pharmacy deleted successfully." : $error = "Error deleting pharmacy: " . $stmt->error;
                $stmt->close();
            } else {
                $error = "Invalid pharmacy ID.";
            }
        }
    }
}

// Fetch all pharmacies
$result = $conn->query("SELECT * FROM pharmacies ORDER BY created_at DESC");
$pharmacies = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $pharmacies[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pharmacy Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        .healthcare-pattern {
            background-image: url("images/manage.jpg");
        }
        #map, #editMap { height: 300px; }
        .leaflet-container { z-index: 0; }
    </style>
</head>
<body class="bg-gray-50 healthcare-pattern">
    <nav class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4 py-3">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-3">
                    <span class="text-xl font-bold text-blue-600">MedFinder መድሃኒት አፋላጊ Admin</span>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-gray-600">Welcome, <?= $_SESSION['admin_username'] ?></span>
                    <a href="ui.php" class="text-red-600 hover:text-red-700">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 py-8">
        <?php if ($error): ?>
        <div class="mb-4 p-4 bg-red-100 text-red-700 rounded-lg"><?= $error ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
        <div class="mb-4 p-4 bg-green-100 text-green-700 rounded-lg"><?= $success ?></div>
        <?php endif; ?>

        <div class="bg-white rounded-xl shadow-lg border border-gray-100">
            <!-- Add Pharmacy Form -->
            <div class="p-6 bg-gray-50 border-b border-gray-200">
                <form method="POST" class="grid grid-cols-1 gap-4">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" id="latitude" name="latitude">
                    <input type="hidden" id="longitude" name="longitude">
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <input type="text" name="name" placeholder="Pharmacy Name" required
                               class="p-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                        <input type="tel" name="phone" placeholder="Contact Number" required
                               class="p-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                        <button type="submit" name="add_pharmacy"
                               class="bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 transition-colors">
                            <i class="fas fa-plus-circle mr-2"></i>Add Pharmacy
                        </button>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Address (Select on Map)</label>
                        <input type="text" id="address" name="address" placeholder="Search address or click on map" 
                               class="w-full p-2 border rounded-lg focus:ring-2 focus:ring-blue-500 mb-2">
                        <div id="map"></div>
                        <p class="text-sm text-gray-500 mt-1">Click on the map to select location</p>
                    </div>
                </form>
            </div>

            <!-- Pharmacy List Table -->
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pharmacy</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Address</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Contact</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($pharmacies as $pharmacy): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($pharmacy['name']) ?></td>
                            <td class="px-6 py-4"><?= htmlspecialchars($pharmacy['address']) ?></td>
                            <td class="px-6 py-4"><?= htmlspecialchars($pharmacy['phone']) ?></td>
                            <td class="px-6 py-4">
                                <span class="px-2.5 py-1 rounded-full 
                                    <?= match($pharmacy['status']) {
                                        'approved' => 'bg-green-100 text-green-800',
                                        'pending' => 'bg-yellow-100 text-yellow-800',
                                        'suspended' => 'bg-red-100 text-red-800',
                                        default => 'bg-gray-100 text-gray-800'
                                    } ?>">
                                    <?= ucfirst($pharmacy['status']) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 space-x-2">
                                <button onclick="openEditModal(<?= $pharmacy['id'] ?>, <?= $pharmacy['latitude'] ?>, <?= $pharmacy['longitude'] ?>)"
                                        class="text-blue-600 hover:text-blue-800">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                    <input type="hidden" name="pharmacy_id" value="<?= $pharmacy['id'] ?>">
                                    <button type="submit" name="delete_pharmacy" 
                                            class="text-red-600 hover:text-red-800"
                                            onclick="return confirm('Are you sure?')">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Edit Pharmacy Modal -->
    <div id="editModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg">
            <div class="flex justify-between items-center p-4 border-b">
                <h3 class="text-xl font-semibold text-gray-800">
                    <i class="fas fa-edit text-blue-600 mr-2"></i>
                    Update Pharmacy Details
                </h3>
                <button onclick="closeEditModal()" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form method="POST" class="p-6 space-y-4">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <input type="hidden" name="pharmacy_id" id="editPharmacyId">
                <input type="hidden" name="latitude" id="editLatitude">
                <input type="hidden" name="longitude" id="editLongitude">
                <input type="hidden" name="update_pharmacy" value="1">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Pharmacy Name</label>
                        <input type="text" name="name" id="editName" required
                               class="w-full p-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Contact Number</label>
                        <input type="tel" name="phone" id="editPhone" required
                               class="w-full p-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                        <select name="status" id="editStatus" 
                                class="w-full p-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                            <option value="active">Active</option>
                            <option value="pending">Pending</option>
                            <option value="approved">Approved</option>
                            <option value="suspended">Suspended</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Address (Select on Map)</label>
                    <input type="text" id="editAddress" name="address" placeholder="Search address or click on map" 
                           class="w-full p-2 border rounded-lg focus:ring-2 focus:ring-blue-500 mb-2">
                    <div id="editMap"></div>
                    <p class="text-sm text-gray-500 mt-1">Click on the map to change location</p>
                </div>

                <div class="pt-4 flex justify-end space-x-3">
                    <button type="button" onclick="closeEditModal()"
                            class="px-4 py-2 text-gray-600 hover:text-gray-800">
                        Cancel
                    </button>
                    <button type="submit"
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        // Initialize main map
        const map = L.map('map').setView([9.005401, 38.763611], 13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);

        let marker = L.marker(map.getCenter(), {draggable: true}).addTo(map);
        document.getElementById('latitude').value = map.getCenter().lat;
        document.getElementById('longitude').value = map.getCenter().lng;

        // Update marker position on map click
        map.on('click', function(e) {
            marker.setLatLng(e.latlng);
            updateAddressFromCoords(e.latlng.lat, e.latlng.lng, 'address');
            document.getElementById('latitude').value = e.latlng.lat;
            document.getElementById('longitude').value = e.latlng.lng;
        });

        // Initialize edit modal map
        let editMap, editMarker;

        // Open edit modal with pharmacy data
        async function openEditModal(pharmacyId, latitude, longitude) {
            try {
                const response = await fetch(`get_pharmacy.php?id=${pharmacyId}`);
                const pharmacy = await response.json();

                document.getElementById('editPharmacyId').value = pharmacy.id;
                document.getElementById('editName').value = pharmacy.name;
                document.getElementById('editAddress').value = pharmacy.address;
                document.getElementById('editPhone').value = pharmacy.phone;
                document.getElementById('editStatus').value = pharmacy.status;
                document.getElementById('editLatitude').value = pharmacy.latitude;
                document.getElementById('editLongitude').value = pharmacy.longitude;

                // Initialize or update map in modal
                if (!editMap) {
                    initEditMap(pharmacy.latitude, pharmacy.longitude);
                } else {
                    editMap.setView([pharmacy.latitude, pharmacy.longitude], 13);
                    editMarker.setLatLng([pharmacy.latitude, pharmacy.longitude]);
                }

                document.getElementById('editModal').classList.remove('hidden');
            } catch (error) {
                console.error('Error fetching pharmacy data:', error);
            }
        }

        function initEditMap(lat, lng) {
            editMap = L.map('editMap').setView([lat, lng], 13);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(editMap);

            editMarker = L.marker([lat, lng], {draggable: true}).addTo(editMap);
            
            editMap.on('click', function(e) {
                editMarker.setLatLng(e.latlng);
                updateAddressFromCoords(e.latlng.lat, e.latlng.lng, 'editAddress');
                document.getElementById('editLatitude').value = e.latlng.lat;
                document.getElementById('editLongitude').value = e.latlng.lng;
            });
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.add('hidden');
        }

        // Address search functionality
        document.getElementById('address').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                searchAddress('address', map, marker);
            }
        });

        document.getElementById('editAddress').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                if (editMap) searchAddress('editAddress', editMap, editMarker);
            }
        });

        function searchAddress(fieldId, mapObj, markerObj) {
            const address = document.getElementById(fieldId).value;
            if (!address) return;

            fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(address)}`)
                .then(response => response.json())
                .then(data => {
                    if (data && data.length > 0) {
                        const lat = parseFloat(data[0].lat);
                        const lon = parseFloat(data[0].lon);
                        mapObj.setView([lat, lon], 15);
                        markerObj.setLatLng([lat, lon]);
                        document.getElementById(fieldId === 'address' ? 'latitude' : 'editLatitude').value = lat;
                        document.getElementById(fieldId === 'address' ? 'longitude' : 'editLongitude').value = lon;
                    } else {
                        alert('Address not found');
                    }
                })
                .catch(error => console.error('Geocoding error:', error));
        }

        function updateAddressFromCoords(lat, lng, fieldId) {
            fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`)
                .then(response => response.json())
                .then(data => {
                    if (data && data.display_name) {
                        document.getElementById(fieldId).value = data.display_name;
                    }
                })
                .catch(error => console.error('Reverse geocoding error:', error));
        }
    </script>
</body>
</html>
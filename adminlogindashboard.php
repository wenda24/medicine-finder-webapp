<?php
session_start();
require 'config.php';

// Check admin authentication
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: admin_login.php");
    exit();
}

$error = '';
$success = '';
$pharmacies = [];

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

    // Handle form submissions
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // CSRF protection
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            throw new Exception("Security validation failed");
        }

        if (isset($_POST['add_pharmacy'])) {
            // Add new pharmacy
            $name = htmlspecialchars($_POST['name']);
            $address = htmlspecialchars($_POST['address']);
            $phone = htmlspecialchars($_POST['phone']);
            
            $stmt = $pdo->prepare("INSERT INTO pharmacies 
                (name, address, phone, status, created_at)
                VALUES (?, ?, ?, 'active', NOW())");
            $stmt->execute([$name, $address, $phone]);
            $success = "Pharmacy added successfully!";
        }
        elseif (isset($_POST['update_pharmacy'])) {
            // Update pharmacy
            $id = (int)$_POST['pharmacy_id'];
            $name = htmlspecialchars($_POST['name']);
            $address = htmlspecialchars($_POST['address']);
            $phone = htmlspecialchars($_POST['phone']);
            $status = htmlspecialchars($_POST['status']);
            
            $stmt = $pdo->prepare("UPDATE pharmacies SET
                name = ?, address = ?, phone = ?, status = ?
                WHERE id = ?");
            $stmt->execute([$name, $address, $phone, $status, $id]);
            $success = "Pharmacy updated successfully!";
        }
        elseif (isset($_POST['delete_pharmacy'])) {
            // Delete pharmacy
            $id = (int)$_POST['pharmacy_id'];
            $stmt = $pdo->prepare("DELETE FROM pharmacies WHERE id = ?");
            $stmt->execute([$id]);
            $success = "Pharmacy deleted successfully!";
        }
    }

    // Fetch all pharmacies
    $stmt = $pdo->query("SELECT * FROM pharmacies ORDER BY created_at DESC");
    $pharmacies = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Generate CSRF token
  if ($_SERVER['REQUEST_METHOD'] === 'GET' || !isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
} catch (Exception $e) {
    $error = $e->getMessage();
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
    <style>
        .healthcare-pattern {
           background-image: url("images/manage.jpg");//www.w3.org/2000/svg'%3E%3Cpath d='M50 0L0 50l50 50 50-50L50 0zM15 50l35 35 35-35-35-35L15 50zm35 15L35 50l15-15v-5h5v5l15 15-15 15v5h-5v-5z' fill='%23078735' fill-opacity='0.05'/%3E%3C/svg%3E");
        }
    </style>
</head>
<body class="bg-gray-50 healthcare-pattern">
    <!-- Navigation -->
    <nav class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4 py-3">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-3">
                    <span class="text-xl font-bold text-blue-600">MedFinder Admin</span>
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

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 py-8">
        <?php if ($error): ?>
        <div class="mb-4 p-4 bg-red-100 text-red-700 rounded-lg"><?= $error ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
        <div class="mb-4 p-4 bg-green-100 text-green-700 rounded-lg"><?= $success ?></div>
        <?php endif; ?>

        <!-- Pharmacy Management Card -->
        <div class="bg-white rounded-xl shadow-lg border border-gray-100">
            <!-- Add Pharmacy Form -->
            <div class="p-6 bg-gray-50 border-b border-gray-200">
                <form method="POST" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="text" name="name" placeholder="Pharmacy Name" required
                           class="p-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                    <input type="text" name="address" placeholder="Address" required
                           class="p-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                    <input type="tel" name="phone" placeholder="Contact Number" required
                           class="p-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                    <button type="submit" name="add_pharmacy"
                           class="bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 transition-colors">
                        <i class="fas fa-plus-circle mr-2"></i>Add Pharmacy
                    </button>
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
                                <button onclick="openEditModal(<?= $pharmacy['id'] ?>)" 
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
    <div id="editModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4">
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
                <input type="hidden" name="update_pharmacy" value="1">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Pharmacy Name</label>
                        <input type="text" name="name" id="editName" required
                               class="w-full p-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Address</label>
                        <input type="text" name="address" id="editAddress" required
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
                            <option value="pending">Active</option>
                            <option value="approved">Approved</option>
                            <option value="suspended">Suspended</option>
                        </select>
                    </div>
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

    <script>
        async function openEditModal(pharmacyId) {
            try {
                const response = await fetch(`get_pharmacy.php?id=${pharmacyId}`);
                const pharmacy = await response.json();

                document.getElementById('editPharmacyId').value = pharmacy.id;
                document.getElementById('editName').value = pharmacy.name;
                document.getElementById('editAddress').value = pharmacy.address;
                document.getElementById('editPhone').value = pharmacy.phone;
                document.getElementById('editStatus').value = pharmacy.status;
                document.getElementById('editModal').classList.remove('hidden');
            } catch (error) {
                console.error('Error fetching pharmacy data:', error);
            }
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.add('hidden');
        }
    </script>
</body>
</html>
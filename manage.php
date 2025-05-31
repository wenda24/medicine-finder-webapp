<?php
// File: manager_dashboard.php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';

if (!isset($_SESSION['manager_id'], $_SESSION['pharmacy_id'])) {
    header("Location: login.php");
    exit();
}

// CSRF Token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Database connection
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=$charset", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
} catch (PDOException $e) {
    die("DB connection failed: " . $e->getMessage());
}

$error = '';
$success = '';
$editItem = null;

// Get medicine options
$medicinesStmt = $pdo->query("SELECT id, name, generic_name, dosage FROM medicines ORDER BY name");
$medicineOptions = $medicinesStmt->fetchAll(PDO::FETCH_ASSOC);

// Handle CRUD operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            throw new Exception("Invalid CSRF token");
        }

        if (isset($_POST['save'])) {
            // Add new medicine
            $medicine_id = filter_var($_POST['medicine_id'], FILTER_VALIDATE_INT);
            $quantity    = filter_var($_POST['quantity'], FILTER_VALIDATE_INT);
            $expiry      = $_POST['expiry'];
            $price       = filter_var($_POST['price'], FILTER_VALIDATE_FLOAT);

            if (!$medicine_id || $quantity === false || !$expiry || $price === false) {
                throw new Exception("All fields must be valid");
            }

            $stmt = $pdo->prepare(
                "INSERT INTO medicine_stock (pharmacy_id, medicine_id, quantity, expiration_date, price)
                 VALUES (:pharmacy_id, :medicine_id, :quantity, :expiry, :price)"
            );
            $stmt->execute([
                ':pharmacy_id' => $_SESSION['pharmacy_id'],
                ':medicine_id' => $medicine_id,
                ':quantity'    => $quantity,
                ':expiry'      => $expiry,
                ':price'       => $price
            ]);
            $success = "Medicine added successfully!";
        }

        if (isset($_POST['update'])) {
            // Update existing medicine
            $id         = filter_var($_POST['update_id'], FILTER_VALIDATE_INT);
            $medicine_id = filter_var($_POST['medicine_id'], FILTER_VALIDATE_INT);
            $quantity   = filter_var($_POST['quantity'], FILTER_VALIDATE_INT);
            $expiry     = $_POST['expiry'];
            $price      = filter_var($_POST['price'], FILTER_VALIDATE_FLOAT);

            if (!$id || !$medicine_id || $quantity === false || !$expiry || $price === false) {
                throw new Exception("Invalid update data.");
            }

            $stmt = $pdo->prepare(
                "UPDATE medicine_stock 
                 SET medicine_id = :medicine_id, quantity = :quantity, 
                     expiration_date = :expiry, price = :price
                 WHERE id = :id AND pharmacy_id = :pharmacy_id"
            );
            $stmt->execute([
                ':medicine_id' => $medicine_id,
                ':quantity'    => $quantity,
                ':expiry'      => $expiry,
                ':price'       => $price,
                ':id'          => $id,
                ':pharmacy_id' => $_SESSION['pharmacy_id']
            ]);
            $success = "Medicine updated!";
        }

        if (isset($_POST['delete'])) {
            // Delete medicine
            $id = filter_var($_POST['stock_id'], FILTER_VALIDATE_INT);
            if (!$id) throw new Exception("Invalid ID");

            $stmt = $pdo->prepare(
                "DELETE FROM medicine_stock 
                 WHERE id = :id AND pharmacy_id = :pharmacy_id"
            );
            $stmt->execute([':id' => $id, ':pharmacy_id' => $_SESSION['pharmacy_id']]);
            $success = "Item deleted!";
        }

        $editItem = null;
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Handle edit request
if (isset($_GET['edit']) && !$success) {
    $id = filter_var($_GET['edit'], FILTER_VALIDATE_INT);
    if ($id) {
        $stmt = $pdo->prepare(
            "SELECT * FROM medicine_stock 
             WHERE id = :id AND pharmacy_id = :pharmacy_id"
        );
        $stmt->execute([':id' => $id, ':pharmacy_id' => $_SESSION['pharmacy_id']]);
        $editItem = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

// Load inventory
$stmt = $pdo->prepare(
    "SELECT ms.*, m.name, m.generic_name, m.dosage 
     FROM medicine_stock ms
     JOIN medicines m ON ms.medicine_id = m.id
     WHERE ms.pharmacy_id = :pharmacy_id
     ORDER BY ms.created_at DESC"
);
$stmt->execute([':pharmacy_id' => $_SESSION['pharmacy_id']]);
$inventory = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get notifications
$notificationStmt = $pdo->prepare(
    "SELECT m.name, ms.quantity, ms.expiration_date,
            ms.quantity < 10 AS low_stock,
            ms.expiration_date <= DATE_ADD(CURDATE(), INTERVAL 1 MONTH) AS expiring_soon
     FROM medicine_stock ms
     JOIN medicines m ON ms.medicine_id = m.id
     WHERE ms.pharmacy_id = :pharmacy_id
       AND (ms.quantity < 10 OR ms.expiration_date <= DATE_ADD(CURDATE(), INTERVAL 1 MONTH))"
);
$notificationStmt->execute([':pharmacy_id' => $_SESSION['pharmacy_id']]);
$notifications = $notificationStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Pharmacy Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
<div class="flex min-h-screen">
    <!-- Sidebar -->
    <aside class="w-64 bg-white p-4 shadow">
        <h2 class="text-xl font-bold mb-4">Manager Panel</h2>
        <nav>
            <a href="#" class="block py-2 px-3 text-gray-700 hover:bg-blue-100 rounded">Inventory</a>
            <a href="logout.php" class="block py-2 px-3 text-red-600 hover:bg-red-100 rounded">Logout</a>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 p-8">
        <!-- Header -->
        <header class="flex justify-between mb-6">
            <h1 class="text-2xl font-bold">Inventory Management</h1>
            <div class="flex gap-4">
                <!-- Notifications -->
                <div class="relative">
                    <button onclick="toggleNotifications()" 
                            class="p-2 hover:bg-gray-200 rounded-full relative">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                  d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                        </svg>
                        <?php if (!empty($notifications)): ?>
                            <span class="absolute top-0 right-0 bg-red-500 text-white 
                                   text-xs px-2 py-1 rounded-full transform translate-x-1/2 -translate-y-1/2">
                                <?= count($notifications) ?>
                            </span>
                        <?php endif; ?>
                    </button>
                    <!-- Notification Panel -->
                    <div id="notificationPanel" 
                         class="hidden absolute right-0 mt-2 w-64 bg-white rounded-lg 
                                shadow-lg border p-4">
                        <h3 class="font-semibold mb-2">Notifications</h3>
                        <?php if (empty($notifications)): ?>
                            <p class="text-gray-500 text-sm">No notifications</p>
                        <?php else: ?>
                            <ul class="space-y-2">
                                <?php foreach ($notifications as $alert): ?>
                                    <li class="text-sm <?= $alert['low_stock'] ? 'text-red-600' : 'text-orange-600' ?>">
                                        <?= htmlspecialchars($alert['name']) ?> - 
                                        <?= $alert['low_stock'] 
                                            ? "Low stock ({$alert['quantity']} left)" 
                                            : "Expires " . date('M d, Y', strtotime($alert['expiration_date'])) ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
                <!-- Add Button -->
                <button onclick="toggleForm('addForm')" 
                        class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                    + Add Medicine
                </button>
            </div>
        </header>

        <!-- Status Messages -->
        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-2 rounded mb-4">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-2 rounded mb-4">
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <!-- Add/Edit Form -->
        <div id="addForm" class="mb-8 bg-white p-6 rounded shadow <?= !$editItem ? 'hidden' : '' ?>">
            <h3 class="text-lg font-semibold mb-4">
                <?= $editItem ? 'Edit Medicine' : 'New Medicine Entry' ?>
            </h3>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <?php if ($editItem): ?>
                    <input type="hidden" name="update_id" value="<?= $editItem['id'] ?>">
                <?php endif; ?>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-1">Medicine</label>
                        <select name="medicine_id" required 
                                class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Select Medicine</option>
                            <?php foreach ($medicineOptions as $medicine): ?>
                                <option value="<?= $medicine['id'] ?>" 
                                    <?= ($editItem && $editItem['medicine_id'] == $medicine['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($medicine['name']) ?> 
                                    (<?= $medicine['generic_name'] ?>, <?= $medicine['dosage'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Quantity</label>
                        <input type="number" name="quantity" required min="1"
                               value="<?= $editItem ? $editItem['quantity'] : '' ?>" 
                               class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Expiry Date</label>
                        <input type="date" name="expiry" required 
                               value="<?= $editItem ? $editItem['expiration_date'] : '' ?>" 
                               class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Price (ETB)</label>
                        <input type="number" step="0.01" name="price" required 
                               value="<?= $editItem ? $editItem['price'] : '' ?>" 
                               class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <div class="mt-4">
                    <?php if ($editItem): ?>
                        <button type="submit" name="update" 
                                class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                            Update
                        </button>
                        <a href="manager_dashboard.php" 
                           class="ml-2 bg-gray-300 px-4 py-2 rounded hover:bg-gray-400">
                            Cancel
                        </a>
                    <?php else: ?>
                        <button type="submit" name="save" 
                                class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                            Save
                        </button>
                        <button type="button" onclick="toggleForm('addForm')" 
                                class="ml-2 bg-gray-300 px-4 py-2 rounded hover:bg-gray-400">
                            Cancel
                        </button>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Inventory Table -->
        <div class="bg-white rounded shadow overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-4 py-2 text-left">Medicine</th>
                        <th class="px-4 py-2 text-left">Quantity</th>
                        <th class="px-4 py-2 text-left">Expiry Date</th>
                        <th class="px-4 py-2 text-left">Price</th>
                        <th class="px-4 py-2 text-left">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($inventory as $item): ?>
                        <tr class="border-t">
                            <td class="px-4 py-3">
                                <?= htmlspecialchars($item['name']) ?> 
                                <span class="text-sm text-gray-600">
                                    (<?= $item['dosage'] ?>)
                                </span>
                            </td>
                            <td class="px-4 py-3"><?= $item['quantity'] ?></td>
                            <td class="px-4 py-3"><?= date('M d, Y', strtotime($item['expiration_date'])) ?></td>
                            <td class="px-4 py-3">ETB <?= number_format($item['price'], 2) ?></td>
                            <td class="px-4 py-3">
                                <a href="?edit=<?= $item['id'] ?>" 
                                   class="text-blue-600 hover:text-blue-800">Edit</a>
                                <form method="POST" class="inline ml-2">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                    <input type="hidden" name="stock_id" value="<?= $item['id'] ?>">
                                    <button type="submit" name="delete" 
                                            onclick="return confirm('Are you sure you want to delete this item?')" 
                                            class="text-red-600 hover:text-red-800">
                                        Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<script>
function toggleForm(id) {
    const form = document.getElementById(id);
    form.classList.toggle('hidden');
}

function toggleNotifications() {
    const panel = document.getElementById('notificationPanel');
    panel.classList.toggle('hidden');
}
</script>
</body>
</html>
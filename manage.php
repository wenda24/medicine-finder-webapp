<?php
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

// Get medicine options for dropdown
$medicinesStmt = $pdo->query("SELECT id, name, generic_name, dosage FROM medicines ORDER BY name");
$medicineOptions = $medicinesStmt->fetchAll(PDO::FETCH_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            throw new Exception("Invalid CSRF token");
        }

        if (isset($_POST['save'])) {
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
                 SET medicine_id = :medicine_id, quantity = :quantity, expiration_date = :expiry, price = :price
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

            $success = "Medicine updated.";
        }

        if (isset($_POST['delete'])) {
            $id = filter_var($_POST['stock_id'], FILTER_VALIDATE_INT);
            if (!$id) {
                throw new Exception("Invalid ID");
            }

            $stmt = $pdo->prepare("DELETE FROM medicine_stock WHERE id = :id AND pharmacy_id = :pharmacy_id");
            $stmt->execute([
                ':id' => $id,
                ':pharmacy_id' => $_SESSION['pharmacy_id']
            ]);

            $success = "Deleted.";
        }

        $editItem = null;
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Edit fetch
if (isset($_GET['edit']) && !$success) {
    $id = filter_var($_GET['edit'], FILTER_VALIDATE_INT);
    if ($id) {
        $stmt = $pdo->prepare("SELECT * FROM medicine_stock WHERE id = :id AND pharmacy_id = :pharmacy_id");
        $stmt->execute([
            ':id' => $id,
            ':pharmacy_id' => $_SESSION['pharmacy_id']
        ]);
        $editItem = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

// Load full inventory with medicine names
$stmt = $pdo->prepare("
    SELECT ms.*, m.name, m.generic_name, m.dosage 
    FROM medicine_stock ms
    JOIN medicines m ON ms.medicine_id = m.id
    WHERE ms.pharmacy_id = :pharmacy_id
    ORDER BY ms.created_at DESC
");
$stmt->execute([':pharmacy_id' => $_SESSION['pharmacy_id']]);
$inventory = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Pharmacy Manager</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
<div class="flex min-h-screen">
    <aside class="w-64 bg-white p-4 shadow">
        <h2 class="text-xl font-bold mb-4">Manager Panel</h2>
        <a href="#" class="block py-2 px-3 text-gray-700 hover:bg-blue-100 rounded">Inventory</a>
        <a href="logout.php" class="block py-2 px-3 text-red-600">Logout</a>
    </aside>
    <main class="flex-1 p-8">
        <header class="flex justify-between mb-6">
            <h1 class="text-2xl font-bold">Inventory</h1>
            <button onclick="toggleForm('addForm')" class="bg-blue-600 text-white px-4 py-2 rounded">+ Add</button>
        </header>

        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-2 rounded mb-4"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-2 rounded mb-4"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <!-- Add/Edit Form -->
        <div id="addForm" class="<?= $editItem ? 'hidden' : 'mb-8' ?> bg-white p-6 rounded shadow">
            <h3 class="text-lg font-semibold mb-4">Add Medicine</h3>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label>Medicine</label>
                        <select name="medicine_id" required class="w-full border px-3 py-2 rounded">
                            <option value="">Select medicine</option>
                            <?php foreach ($medicineOptions as $m): ?>
                                <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['name']) ?> (<?= $m['generic_name'] ?>, <?= $m['dosage'] ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div><label>Quantity</label><input type="number" name="quantity" required class="w-full border px-3 py-2 rounded"></div>
                    <div><label>Expiry Date</label><input type="date" name="expiry" required class="w-full border px-3 py-2 rounded"></div>
                    <div><label>Price (ETB)</label><input type="number" step="0.01" name="price" required class="w-full border px-3 py-2 rounded"></div>
                </div>
                <div class="mt-4">
                    <button name="save" class="bg-blue-600 text-white px-4 py-2 rounded">Save</button>
                    <button type="button" onclick="toggleForm('addForm')" class="ml-2 bg-gray-300 px-4 py-2 rounded">Cancel</button>
                </div>
            </form>
        </div>

        <!-- Inventory Table -->
        <div class="bg-white rounded shadow overflow-auto">
            <table class="min-w-full table-auto">
                <thead class="bg-gray-100"><tr>
                    <th>Name</th><th>Qty</th><th>Expiry</th><th>Price</th><th>Actions</th>
                </tr></thead>
                <tbody>
                <?php foreach ($inventory as $item): ?>
                    <tr>
                        <td><?= htmlspecialchars($item['name']) ?> (<?= $item['dosage'] ?>)</td>
                        <td><?= $item['quantity'] ?></td>
                        <td><?= htmlspecialchars($item['expiration_date']) ?></td>
                        <td>ETB <?= number_format($item['price'], 2) ?></td>
                        <td>
                            <a href="?edit=<?= $item['id'] ?>" class="text-blue-600">Edit</a>
                            <form method="POST" class="inline">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                <input type="hidden" name="stock_id" value="<?= $item['id'] ?>">
                                <button name="delete" onclick="return confirm('Delete this item?')" class="text-red-600 ml-2">Delete</button>
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
    document.getElementById(id).classList.toggle('hidden');
}
</script>
</body>
</html>

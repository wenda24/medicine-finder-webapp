<?php
session_start();
require 'config.php';
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

$error = '';
$success = '';

// // Redirect logged-in admins
// if (isset($_SESSION['admin_logged_in'])) {
//     header("Location: adminlogindashboard.php");
//     exit();
// }



    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Validate CSRF token
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            throw new Exception("Security validation failed");
        }

        $username = trim($_POST['username']);
        $password = $_POST['password'];

        // Validate inputs
        if (empty($username) || empty($password)) {
            throw new Exception("Please fill in all fields");
        }

        // Get admin from database
        $stmt = $pdo->prepare("SELECT id, username, password_hash FROM admins WHERE username = ?");
        $stmt->execute([$username]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password_hash'])) {
            // Successful login
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            
            // Regenerate session ID
            session_regenerate_id(true);
            
            header("Location: adminlogindashboard.php");
            exit();
        } else {
            throw new Exception("Invalid credentials");
        }
    }

    // Generate CSRF token
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

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
    <title>MedFinder Admin Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .medical-pattern {
            background-image: url("admin.jpg");
        }
        .card-glow {
            box-shadow: 0 0 40px rgba(6, 135, 53, 0.1);
        }
    </style>
</head>
<body class="bg-gradient-to-br from-[#078735] to-[#0066cc] medical-pattern min-h-screen">
    <!-- Navigation -->
    <nav class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4 py-3">
            <div class="flex justify-between items-center">
                <a href="ui.php" class="text-blue-600 hover:text-blue-800 flex items-center">
                    <i class="fas fa-arrow-left mr-2"></i>
                    <span>Back to Main Site</span>
                </a>
            </div>
        </div>
    </nav>

    <div class="container mx-auto px-4 py-16 flex items-center justify-center">
        <!-- Login Card -->
        <div class="bg-white/95 backdrop-blur-sm rounded-xl card-glow border-2 border-[#078735]/20 w-full max-w-md">
            <!-- Card Header -->
            <div class="bg-[#f8fafb] px-8 py-6 rounded-t-xl border-b border-[#078735]/10">
                <div class="flex items-center justify-center space-x-4">
                    <div class="p-3 bg-[#078735] rounded-lg">
                        <i class="fas fa-shield-alt text-2xl text-white"></i>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800">
                            <span class="text-[#078735]">MedFinder</span> Admin
                        </h1>
                        <p class="text-sm text-gray-600">Secure MedFinder Portal</p>
                    </div>
                </div>
            </div>

            <!-- Login Form -->
            <form method="POST" class="px-8 py-8 space-y-6" id="loginForm">
                <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg">
                    <?= htmlspecialchars($error) ?>
                </div>
                <?php endif; ?>

                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">

                <!-- Username Field -->
                <div class="space-y-2">
                    <label for="username" class="block text-sm font-medium text-gray-700">Institutional ID</label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">
                            <i class="fas fa-hospital-user"></i>
                        </span>
                        <input type="text" 
                               id="username" 
                               name="username" 
                               required
                               autocomplete="username"
                               class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#078735] focus:border-transparent placeholder-gray-400"
                               placeholder="HMS-ADMIN-XXXX"
                               value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                    </div>
                </div>

                <!-- Password Field -->
                <div class="space-y-2">
                    <label for="password" class="block text-sm font-medium text-gray-700">Secure Passkey</label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">
                            <i class="fas fa-lock"></i>
                        </span>
                        <input type="password" 
                               id="password" 
                               name="password" 
                               required
                               autocomplete="current-password"
                               class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#078735] focus:border-transparent placeholder-gray-400"
                               placeholder="••••••••">
                        <button type="button" 
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-[#078735]"
                                aria-label="Toggle password visibility">
                            <i class="fas fa-eye-slash"></i>
                        </button>
                    </div>
                </div>

                <!-- Security Assurance -->
                <div class="flex items-center space-x-3 bg-blue-50 p-3 rounded-lg">
                    <i class="fas fa-shield-check text-blue-600"></i>
                    <p class="text-sm text-blue-800">
                        All sessions encrypted with AES-256
                    </p>
                </div>

                <!-- Submit Button -->
                <button type="submit" 
                        class="w-full bg-[#078735] text-white py-3.5 rounded-lg hover:bg-[#0066cc] transition-all duration-300
                               flex items-center justify-center space-x-2 disabled:opacity-50"
                        id="submitBtn">
                    <i class="fas fa-sign-in-alt"></i>
                    <span>Authenticate</span>
                    <div class="spinner hidden">
                        <i class="fas fa-circle-notch fa-spin"></i>
                    </div>
                </button>
            </form>
        </div>
    </div>

    <script>
        // Password visibility toggle
        document.querySelector('.fa-eye-slash').parentNode.addEventListener('click', function(e) {
            const passwordField = document.getElementById('password');
            const icon = this.querySelector('i');
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            } else {
                passwordField.type = 'password';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            }
        });

        // Form submission handler
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.disabled = true;
            submitBtn.querySelector('.spinner').classList.remove('hidden');
            submitBtn.querySelector('span').textContent = 'Authenticating...';
        });
    </script>
</body>
</html>
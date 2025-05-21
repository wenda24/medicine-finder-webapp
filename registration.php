<?php
session_start([
    'cookie_lifetime' => 86400,
    'cookie_secure' => true,
    'cookie_httponly' => true,
    'cookie_samesite' => 'Strict'
]);
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/errors.log');

require 'config.php';


$errors = [];
$pharmacies = [];
$input_values = [
    'name' => '',
    'email' => '',
    'pharmacy_id' => ''
];

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

   // Fetch active and unassigned pharmacies
$stmt = $pdo->query("
    SELECT p.id, p.name
    FROM pharmacies p
    LEFT JOIN managers m ON p.id = m.pharmacy_id
    WHERE p.status = 'active' AND m.pharmacy_id IS NULL
    ORDER BY p.name
");
$pharmacies = $stmt->fetchAll(PDO::FETCH_ASSOC);


    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Validate CSRF token
        if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
            throw new Exception("Security validation failed");
        }

        // Sanitize and store input values
        $input_values['name'] = trim(htmlspecialchars($_POST['name'] ?? ''));
        $input_values['email'] = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'] ?? '';
        $input_values['pharmacy_id'] = (int)($_POST['pharmacy_id'] ?? 0);

        // Validation
        if (empty($input_values['name']) || strlen($input_values['name']) > 100) {
            $errors[] = "Full name must be between 2-100 characters";
        }
        
        if (!filter_var($input_values['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email format";
        }

        if (!preg_match('/^(?=.*\d)(?=.*[A-Z])(?=.*[a-z]).{12,}$/', $password)) {
            $errors[] = "Password must be at least 12 characters with uppercase, lowercase, and number";
        }

        // Validate pharmacy exists and is active
        $valid_pharmacies = array_column($pharmacies, 'id');
        if (!in_array($input_values['pharmacy_id'], $valid_pharmacies)) {
            $errors[] = "Invalid pharmacy selection";
        }

        // Check email uniqueness
        $stmt = $pdo->prepare("SELECT id FROM managers WHERE email = ?");
        $stmt->execute([$input_values['email']]);
        if ($stmt->fetch()) {
            $errors[] = "Email already registered";
        }

        if (empty($errors)) {
            $hashed_password = password_hash($password, PASSWORD_ARGON2ID);
            $pdo->beginTransaction();
         $stmt = $pdo->prepare("INSERT INTO managers 
    (full_name, email, password_hash, pharmacy_id)
    VALUES (?, ?, ?, ?)");

            $stmt->execute([
                $input_values['name'],
                $input_values['email'],
                $hashed_password,
                $input_values['pharmacy_id']
            ]);
            $stmt = $pdo->prepare("INSERT INTO audit_log 
                (user_id, event_type, ip_address, user_agent)
                VALUES (?, 'registration', ?, ?)");
            $stmt->execute([
                $pdo->lastInsertId(),
                $_SERVER['REMOTE_ADDR'],
                $_SERVER['HTTP_USER_AGENT']
            ]);
            $pdo->commit();

            $_SESSION['registration_success'] = true;
            header("Location: login.php");
            exit();
        }}
    

    //  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    $errors[] = "Database Error: " . $e->getMessage(); // TEMPORARY for debugging only
} catch (Exception $e) {
    error_log("Application Error: " . $e->getMessage());
    $errors[] = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pharmacy Manager Registration</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #2563eb;
            --secondary: #1e40af;
            --background: #f8fafc;
            --text: #1e293b;
            --error: #dc2626;
            --success: #16a34a;
            --border: #cbd5e1;
        }
        body {
            font-family: 'Inter', system-ui, sans-serif;
            background: var(--background);
            margin: 0;
            padding: 2rem;
            color: var(--text);
        }
        .registration-container {
            max-width: 800px;
            margin: 2rem auto;
            background: white;
            border-radius: 1rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            padding: 2.5rem;
            border: 1px solid var(--border);
        }
        .form-header {
            text-align: center;
            margin-bottom: 2.5rem;
        }
        .form-header i {
            font-size: 2.5rem;
            color: var(--primary);
            background: #dbeafe;
            padding: 1.5rem;
            border-radius: 50%;
            margin-bottom: 1.5rem;
        }
        .form-section {
            margin-bottom: 2rem;
            padding: 1.5rem;
            background: #f1f5f9;
            border-radius: 0.75rem;
            border: 1px solid var(--border);
        }
        .input-group {
            position: relative;
            margin-bottom: 1.5rem;
        }
        .input-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary);
            z-index: 10;
        }
        input, select {
            width: 100%;
            padding: 0.875rem 1rem 0.875rem 3rem;
            border: 2px solid var(--border);
            border-radius: 0.75rem;
            font-size: 1rem;
            transition: all 0.2s ease;
            background: white;
        }
        input:focus, select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        select {
            appearance: none;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%232563eb'%3e%3cpath d='M12 15l-5-5h10l-5 5z'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            background-size: 1.5em;
        }
        .submit-btn {
            width: 100%;
            padding: 1rem;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 0.75rem;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        .submit-btn:hover {
            background: var(--secondary);
            transform: translateY(-1px);
        }
        .submit-btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }
        .error-container {
            background: #fee2e2;
            border: 2px solid var(--error);
            border-radius: 0.75rem;
            padding: 1rem;
            margin-bottom: 2rem;
        }
        .error-message {
            color: var(--error);
            font-size: 0.875rem;
            margin: 0.25rem 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .error-message::before {
            content: "⚠";
            font-size: 1em;
        }
        .login-link {
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.95rem;
        }
        .login-link a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
        }
        .login-link a:hover {
            text-decoration: underline;
        }
        @media (max-width: 640px) {
            .registration-container {
                padding: 1.5rem;
                margin: 1rem;
            }
            .form-section {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="registration-container">
        <?php if (!empty($errors)): ?>
            <div class="error-container">
                <?php foreach ($errors as $error): ?>
                    <p class="error-message"><?= htmlspecialchars($error) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="form-header">
            <i class="fas fa-clinic-medical"></i>
            <h1>Pharmacy Manager Registration</h1>
        </div>

        <form id="registrationForm" method="POST">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
            
            <div class="form-section">
                <h2><i class="fas fa-user-md"></i> Manager Details</h2>
                <div class="input-group">
                    <span class="input-icon"><i class="fas fa-user"></i></span>
                    <input type="text" id="name" name="name" 
                           value="<?= htmlspecialchars($input_values['name']) ?>" 
                           placeholder="Full Name" required>
                </div>
                <div class="input-group">
                    <span class="input-icon"><i class="fas fa-envelope"></i></span>
                    <input type="email" id="email" name="email" 
                           value="<?= htmlspecialchars($input_values['email']) ?>" 
                           placeholder="Email" required>
                </div>
                <div class="input-group">
                    <span class="input-icon"><i class="fas fa-lock"></i></span>
                    <input type="password" id="password" name="password" 
                           placeholder="Password (min 12 characters)" 
                           required
                           pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{12,}">
                </div>
            </div>

            <div class="form-section">
                <h2><i class="fas fa-hospital-symbol"></i> Pharmacy Assignment</h2>
                <div class="input-group">
                    <span class="input-icon"><i class="fas fa-map-marker-alt"></i></span>
                    <label for="pharmacy_id">Select Pharmacy</label>
<select name="pharmacy_id" id="pharmacy_id" required class="w-full p-2 border rounded-lg">
    <option value="">Select Pharmacy</option>
    <?php foreach ($pharmacies as $pharmacy): ?>
        <option value="<?= $pharmacy['id'] ?>">
            <?= htmlspecialchars($pharmacy['name']) ?>
        </option>
    <?php endforeach; ?>
</select>

                </div>
            </div>

            <button type="submit" class="submit-btn">
                <i class="fas fa-file-medical"></i> Complete Registration
            </button>
        </form>

        <div class="login-link">
            Already have an account? <a href="login.php">Login</a>
        </div>
    </div>

    <script>
        document.getElementById('registrationForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const form = e.target;
            const submitBtn = form.querySelector('.submit-btn');
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }
            submitBtn.disabled = true;
            submitBtn.innerHTML = `<i class="fas fa-spinner fa-spin"></i> Registering...`;

            try {
                  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));}
                const formData = new FormData(form);
                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });

                if (response.redirected) {
                    window.location.href = response.url;
                } else {
                    const result = await response.text();
                    const tempDiv = document.createElement('div');
                    tempDiv.innerHTML = result;
                    const newErrors = tempDiv.querySelector('.error-container');
                    const existingErrors = document.querySelector('.error-container');
                    if (existingErrors) existingErrors.remove();
                    if (newErrors) {
                        form.prepend(newErrors);
                    }
                }
            } catch (error) {
                const errorContainer = document.createElement('div');
                errorContainer.className = 'error-container';
                errorContainer.innerHTML = `<p class="error-message">Network error occurred. Please try again.</p>`;
                form.prepend(errorContainer);
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = `<i class="fas fa-file-medical"></i> Complete Registration`;
            }
        });
    </script>
</body>
</html>

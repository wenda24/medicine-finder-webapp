<?php
session_start();
require 'config.php';

$error = '';

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $email    = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'] ?? '';

        if ($email === '' || $password === '') {
            throw new Exception("Please fill in all fields");
        }

        $stmt = $pdo->prepare("
            SELECT 
                id,
                full_name,
                email,
                password_hash,
                pharmacy_id
            FROM managers
            WHERE email = ?
        ");
        $stmt->execute([$email]);
        $manager = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($manager && password_verify($password, $manager['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['manager_logged_in'] = true;
            $_SESSION['manager_id']        = $manager['id'];
            $_SESSION['pharmacy_id']       = $manager['pharmacy_id'];
            $_SESSION['manager_name']      = $manager['full_name'];

            header("Location: manage.php");
            exit;
        } else {
            throw new Exception("Invalid email or password");
        }
    }
} catch (PDOException $e) {
    $error = "Database error: " . htmlspecialchars($e->getMessage());
} catch (Exception $e) {
    $error = htmlspecialchars($e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Pharmacy Manager Login</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
  <style>
    * {
      box-sizing: border-box;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    body {
      margin: 0;
      min-height: 100vh;
      background: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6)),
        url('login.jpg') no-repeat center center;
      background-size: cover;
      display: flex;
      justify-content: center;
      align-items: center;
    }
    .login-container {
      background-color: rgba(255, 255, 255, 0.95);
      padding: 2.5rem;
      border-radius: 20px;
      max-width: 420px;
      width: 90%;
      box-shadow: 0 8px 30px rgba(0, 0, 0, 0.2);
      animation: fadeIn 1s ease;
    }
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }
    .login-container h2 {
      text-align: center;
      margin-bottom: 1.5rem;
      color: #2e7d32;
    }
    .form-group {
      margin-bottom: 1rem;
      position: relative;
    }
    .form-group label {
      display: block;
      margin-bottom: 0.3rem;
      color: #333;
      font-weight: 500;
    }
    .input-icon {
      position: absolute;
      top: 50%;
      left: 10px;
      transform: translateY(-50%);
      color: #888;
    }
    input {
      width: 100%;
      padding: 0.75rem 0.75rem 0.75rem 2.5rem;
      border: 1px solid #ccc;
      border-radius: 6px;
      font-size: 1rem;
      transition: border-color 0.3s;
    }
    input:focus {
      border-color: #2e7d32;
      outline: none;
    }
    button {
      width: 100%;
      padding: 0.75rem;
      background-color: #2e7d32;
      border: none;
      color: white;
      font-size: 1rem;
      border-radius: 6px;
      cursor: pointer;
      transition: background-color 0.3s;
    }
    button:hover {
      background-color: #1b5e20;
    }
    .error-message {
      color: #d32f2f;
      text-align: center;
      margin-bottom: 1rem;
      font-weight: bold;
    }
    @media (max-width: 480px) {
      .login-container {
        padding: 1.5rem;
      }
    }
  </style>
</head>
<body>
  <div class="login-container">
    <h2><i class="fas fa-leaf"></i> MedFinder Manager Login</h2>

    <?php if ($error): ?>
      <div class="error-message"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST" novalidate>
      <div class="form-group">
        <label for="email">Email</label>
        <i class="fas fa-envelope input-icon"></i>
        <input
          type="email"
          id="email"
          name="email"
          required
          value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
        />
      </div>
      <div class="form-group">
        <label for="password">Password</label>
        <i class="fas fa-lock input-icon"></i>
        <input
          type="password"
          id="password"
          name="password"
          required
        />
      </div>
      <button type="submit"><i class="fas fa-sign-in-alt"></i> Login</button>
    </form>
  </div>

  <script>
    document.querySelector('form').addEventListener('submit', function(e) {
      let email = document.getElementById('email').value.trim();
      let pwd   = document.getElementById('password').value;
      const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (!re.test(email)) {
        alert('Please enter a valid email address');
        e.preventDefault();
      }
      if (pwd.length < 8) {
        alert('Password must be at least 8 characters');
        e.preventDefault();
      }
    });
  </script>
</body>
</html>

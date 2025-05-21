<?php
// 1. Include database configuration
require 'config.php';

// 2. Check if form is submitted via POST
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // 3. Get form data with null coalescing operator to avoid undefined warnings
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $pharmacy_id = $_POST['pharmacy_id'] ?? '';

    // 4. Basic validation (you can add more)
    if (empty($name) || empty($email) || empty($password) || empty($pharmacy_id)) {
        die("All fields are required.");
    }

    // 5. Hash the password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    try {
        // 6. Prepare and execute the insert query using prepared statements
        $stmt = $pdo->prepare("INSERT INTO managers (full_name, email, password, pharmacy_id)
                               VALUES (:name, :email, :password, :pharmacy_id)");
        $stmt->execute([
            ':name' => $name,
            ':email' => $email,
            ':password' => $hashed_password,
            ':pharmacy_id' => $pharmacy_id
        ]);

        // 7. Redirect to login page
        header("Location: login.php?registration=success");
        exit();
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
} else {
    echo "Invalid request method.";
}
?>

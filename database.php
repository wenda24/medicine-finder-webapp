<?php
class Database {
    private $host = 'localhost';
    private $db   = 'pharmacy_db';
    private $user = 'root';
    private $pass = '';
    private $charset = 'utf8mb4';
    public $pdo;
    session_start();
require_once 'inventory_operations.php';

// Redirect if not logged in
if (!isset($_SESSION['manager_id'])) {
    header("Location: login.php");
    exit();
}

// Create DB connection
$db = new Database();
$pdo = $db->pdo;

handle_operations($pdo); // <-- this line processes form submissions

// Fetch data for display
$inventory = fetch_inventory($pdo, $_SESSION['pharmacy_id']);
$notifications = fetch_notifications($pdo, $_SESSION['manager_id']);


    public function __construct() {
        $dsn = "mysql:host=$this->host;dbname=$this->db;charset=$this->charset";
        try {
            $this->pdo = new PDO($dsn, $this->user, $this->pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (PDOException $e) {
            throw new RuntimeException("Database connection failed: " . $e->getMessage());
        }
    }
}

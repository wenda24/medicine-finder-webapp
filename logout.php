<?php
// File: logout.php
session_start();

// Clear session data
$_SESSION = array();

// Destroy session
session_destroy();

// Redirect to UI
header("Location: ui.php");
exit();
?>
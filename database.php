<?php
// Database configuration
$servername = "cob-server.mysql.database.azure.com";
$username = "bhuwqcnbtc";
$password = 'YKqAjBhw$yUkjF$I';
$database = "tc_monitoring";

// Create connection
$conn = new mysqli($servername, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to utf8
$conn->set_charset("utf8");

// Return connection
$connection = $conn;
?>

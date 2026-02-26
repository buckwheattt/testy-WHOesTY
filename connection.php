<?php
/**
 * @file connection.php
 * @brief Database connection initialization.
 *
 * Creates a MySQLi connection used across the application.
 */
$servername = 'localhost';
$username = 'grechsof';
$password = 'webove aplikace';
$dbname = 'grechsof';
/**
 * Active MySQLi database connection.
 *
 * @var mysqli $conn
 */
$conn = new mysqli($servername, $username, $password, $dbname);
// Stop execution if connection fails
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
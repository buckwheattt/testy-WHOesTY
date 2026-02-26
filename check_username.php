<?php
/**
 * @file check_username.php
 * @brief AJAX endpoint to check username availability.
 *
 * Receives a username via POST and checks whether it already exists
 * in the `users` table. Outputs a plain text response.
 */
include("connection.php");

/**
 * Ensure required input exists.
 */
if (!isset($_POST['user_name'])) {
    exit;
}

/**
 * Username to check.
 *
 * @var string $user_name
 */
$user_name = trim($_POST['user_name']);

if ($user_name === "") {
    exit;
}

/**
 * Check username existence using prepared statement.
 */
$stmt = mysqli_prepare($conn, "SELECT user_id FROM users WHERE user_name = ?");
mysqli_stmt_bind_param($stmt, "s", $user_name);
mysqli_stmt_execute($stmt);
mysqli_stmt_store_result($stmt);

if (mysqli_stmt_num_rows($stmt) > 0) {
    echo "Username already exists";
} else {
    echo "Username is available";
}

mysqli_stmt_close($stmt);

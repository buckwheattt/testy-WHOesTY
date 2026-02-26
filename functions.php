<?php
/**
 * @file functions.php
 * @brief Shared helper functions for authentication, authorization and utilities.
 *
 * Contains:
 * - login/session helpers (check_login)
 * - admin helpers (require_admin, is_admin)
 * - random ID generator (random_num, generateUniqueUserId)
 * - input validation (validateInput)
 * - avatar upload helpers (avatarSecurity, loadAvatar)
 */
include("connection.php");
/**
 * Ensures the user is logged in.
 *
 * If a user_id exists in session, loads the user record from DB and returns it.
 * Otherwise redirects to login.php and terminates the request.
 *
 * @param mysqli $conn Active MySQLi connection.
 * @return array User record from `users` table.
 */
function check_login($conn)
{
    if (isset($_SESSION['user_id'])) {
        $id = $_SESSION['user_id'];
        $query = "SELECT * FROM users WHERE user_id = '$id' LIMIT 1";

        $result = mysqli_query($conn, $query);
        if ($result && mysqli_num_rows($result) > 0) {
            $user_data = mysqli_fetch_assoc($result);
            return $user_data;
        }
    }

    // Redirect to login if user is not logged in
    header("Location: login.php");
    die;
}
/**
 * Enforces admin-only access.
 *
 * Redirects to home.php if user is not admin.
 *
 * @param array $user_data User record (typically from check_login()).
 * @return void
 */
function require_admin($user_data) {
    if (!isset($user_data['role']) || $user_data['role'] !== 'admin') {
        header("Location: home.php");
        exit;
    }
}
/**
 * Checks whether the current user is admin.
 *
 * @param array $user_data User record (typically from check_login()).
 * @return bool True if role=admin.
 */
function is_admin($user_data) {
    return isset($user_data['role']) && $user_data['role'] === 'admin';
}


/**
 * Generates a random numeric string with a minimum length of 5.
 *
 * Used as a building block for generating user IDs.
 *
 * @param int $length Desired maximum length.
 * @return string Random numeric string.
 */
function random_num($length)
{
    $text = "";
    if ($length < 5) {
        $length = 5;
    }

    $len = rand(4, $length);

    for ($i = 0; $i < $len; $i++) {
        $text .= rand(0, 9);
    }

    return $text;
}

/**
 * Performs basic security checks for avatar uploads.
 *
 * Checks:
 * - file extension against a blacklist,
 * - MIME type must be png/jpg/jpeg,
 * - size limit.
 *
 * @param array $avatar File array (e.g. $_FILES['avatar']).
 * @return bool True if file passes checks, otherwise false.
 */
function avatarSecurity($avatar)
{
    $name = $avatar['name'];
    $type = $avatar['type'];
    $size = $avatar['size'];
    $blacklist = array(".php", ".js", ".html");
    foreach ($blacklist as $row) {
    }
    if (preg_match("/$row\$/i", $name)) return false;
    if (($type != "image/png") && ($type != "image/jpg") && ($type != "image/jpeg")) return false;
    if ($size > 5 * 1024 * 1824) return false;
    else return true;
}

/**
 * Uploads avatar file and updates user's profilePicture in DB.
 *
 * - Saves file to `uploads/` with a unique filename.
 * - On success updates `users.profilePicture` for current session user.
 * - Redirects to profile.php on success.
 *
 * NOTE: Uses global $conn and builds UPDATE query via interpolation.
 *
 * @param array $avatar File array (e.g. $_FILES['avatar']).
 * @return void
 */
function loadAvatar($avatar)
{
    global $conn;
    // Validate file type
    if (isset($_FILES['avatar']) && isset($_SESSION['user_id'])) {
        // Get user ID from session
        $userId = $_SESSION['user_id'];

        // Directory for uploading avatars
        $uploadDirectory = 'uploads/';

        // Generate a unique file name
        $fileName = uniqid('avatar_') . '.' . pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
        $uploadFilePath = $uploadDirectory . $fileName;

        // Move the uploaded file to the specified directory
        if (move_uploaded_file($_FILES['avatar']['tmp_name'], $uploadFilePath)) {
            // Update the file name in the database
            $updateQuery = "UPDATE users SET profilePicture = '$uploadFilePath' WHERE user_id = $userId";

            // Attempt to execute the update query
            if ($conn->query($updateQuery)) {
                $rezult_nahravani = "Avatar uploaded successfully.";
                header("Location: profile.php");
                exit();
            } else {
                // Handle database update error
                unlink($uploadFilePath); // Delete the uploaded file in case of an error
                $rezult_nahravani_error = "Error updating avatar in the database.";
            }
        } else {
            // Handle file upload error
            $rezult_nahravani_error = "Error during uploading avatar.";
        }
    }

    // Close the database connection
}

/**
 * Validates a string by rejecting forbidden characters.
 *
 * Returns false if any forbidden char is present.
 * If no forbidden char is found, function returns null (current implementation).
 *
 * @param string $inputValue User-provided value.
 * @return bool|null False if invalid; null if no forbidden chars found.
 */
function validateInput($inputValue)
{
    $forbiddenChars = ['$', '#', '%', ',', ';', '-', '>', '<', '&']; // Specify forbidden characters

    foreach ($forbiddenChars as $char) {
        if (strpos($inputValue, $char) !== false) {
            return false; // Break the loop at the first forbidden character found
            break;
        }
    }
}
/**
 * Generates a unique user_id that does not exist in the database.
 *
 * Uses random_num() and checks for collisions using a prepared statement.
 *
 * @param mysqli $conn Active MySQLi connection.
 * @return string Unique generated user_id.
 */
function generateUniqueUserId($conn) {
    $user_id = random_num(20);

    // Check if the generated user_id already exists in the database
    $check_query = "SELECT * FROM users WHERE user_id = ?";
    $stmt = mysqli_prepare($conn, $check_query);
    mysqli_stmt_bind_param($stmt, "s", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    // If user_id already exists, generate a new one
    while (mysqli_num_rows($result) > 0) {
        $user_id = random_num(20);
        mysqli_stmt_bind_param($stmt, "s", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
    }

    // Close the prepared statement
    mysqli_stmt_close($stmt);

    return $user_id;
}



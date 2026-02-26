<?php
/**
 * @file profile.php
 * @brief User profile page.
 *
 * Displays user profile information and avatar.
 * Allows logged-in users to upload a new avatar.
 *
 * Features:
 * - authentication required (check_login)
 * - avatar upload with basic security checks
 * - CSRF protection
 * - admin shortcut for admin users
 */
session_start();

/**
 * Include the connection.php file.
 * This file likely contains the database connection logic.
 */
include("connection.php");

/**
 * Include the functions.php file.
 * This file likely contains various functions used in the code.
 */
include("functions.php");

/**
 * Check if the user is logged in.
 * The check_login function likely verifies the user's session and retrieves user data.
 */
$user_data = check_login($conn);



/**
 * Check if the "set_avatar" form was submitted.
 */
if (isset($_POST['set_avatar'])) {
    // Check if the form was submitted using POST and if the "avatar" file exists.
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES['avatar'])) {
        // Check CSRF token to ensure the form submission is legitimate.
        if (!isset($_SESSION['csrf_token']) || !isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            die("CSRF token validation failed.");
        }

        // Handle image upload.
        $avatar = $_FILES['avatar'];
        if (avatarSecurity($avatar)) {
            loadAvatar($avatar);
            $rezult_nahravani = "Image uploaded successfully.";
        } else {
            $rezult_nahravani_error = "Invalid image format.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" type="text/css" href="mainstyle.css">
    <link rel="stylesheet" type="text/css" href="print-styles.css" media="print">
    <title>Your profile</title>
</head>
<body id="form_body">

<!-- Page header -->
<header>
    <div class="menushka">
        <a href="home.php">Main</a>
        <a href="login.php">Login</a>
        <a href="forum.php">Forum</a>
        <a href="profile.php">Profile</a>
    </div>
    <a href="#biba" class="biba"></a>
    <div class="welcome">
        <h2><a href="logout.php">Logout</a></h2>
        <h1>Hello, <?php echo htmlspecialchars($user_data['user_name']); ?></h1>
    </div>
    <a class="boba" href="#boba"></a>
</header>

<!-- Profile section -->
<section id="form_section">
    <!-- Display user's avatar -->
    <div>
        <img src="<?php echo htmlspecialchars($user_data['profilePicture']); ?>" class="avatar">
    </div>

    <!-- Avatar upload form -->
    <form action="profile.php" method="post" enctype="multipart/form-data">
        <!-- Add a hidden field for CSRF token -->
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
        <input type="file" name="avatar">
        <button type="submit" name="set_avatar">Upload Pic</button>
    </form>

    <!-- Display upload result messages -->
    <?php if (isset($rezult_nahravani)): ?>
        <div style="color: green;"><?php echo $rezult_nahravani; ?></div>
    <?php endif; ?>
    <?php if (isset($rezult_nahravani_error)): ?>
        <div style="color: red;"><?php echo $rezult_nahravani_error; ?></div>
    <?php endif; ?>
    <?php if (isset($user_data['role']) && $user_data['role'] === 'admin'): ?>
    <button><a href="admin_users.php">Admin</a></button>
    <?php endif; ?>

    <!-- Link to password change page -->
    <button><a href="password_change.php">Update Profile</a></button>

</section>

</body>
</html>
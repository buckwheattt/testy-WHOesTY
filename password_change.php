<?php
/**
 * @file password_change.php
 * @brief Account settings page (change username and/or password).
 *
 * Requires the user to be logged in (check_login). Uses CSRF protection.
 * The current password is always required to authorize any change.
 *
 * Possible changes:
 * - Username (optional): validated + must be unique.
 * - Password (optional): validated + confirmed + hashed before storing.
 */

session_start();

include("connection.php");
include("functions.php");

/**
 * Logged-in user data (redirects/blocks access if not logged in).
 *
 * @var array $user_data
 */
$user_data = check_login($conn);

/**
 * CSRF token for the settings form.
 *
 * @var string $_SESSION['csrf_token']
 */
if (!isset($_SESSION['csrf_token'])) {
    session_regenerate_id(true);
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/**
 * Feedback messages displayed above the form.
 *
 * @var string|null $error
 * @var string|null $successMessage
 */
$error = null;
$successMessage = null;

/**
 * Handle form submission.
 * Validates CSRF token, verifies current password, then applies requested updates.
 */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // CSRF validation (hard stop on failure).
    if (!isset($_SESSION['csrf_token']) || !isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("CSRF token validation failed.");
    }
    /**
     * Current user id (taken from session; used in DB queries).
     *
     * @var int|string $userId
     */
    $userId = $_SESSION['user_id'];

     /**
     * Form inputs.
     *
     * @var string $current_password Required to authorize changes.
     * @var string $new_username     Optional username update.
     * @var string $new_password     Optional password update.
     * @var string $confirm_password Must match new_password.
     */
    $current_password = $_POST['current_password'] ?? '';
    $new_username     = trim($_POST['new_username'] ?? '');
    $new_password     = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    /**
     * Determine which changes the user requested.
     *
     * @var bool $wantsUsernameChange
     * @var bool $wantsPasswordChange
     */
    $wantsUsernameChange = ($new_username !== '');
    $wantsPasswordChange = ($new_password !== '' || $confirm_password !== '');

    if (!$wantsUsernameChange && !$wantsPasswordChange) {
        $error = "Nothing to change.";
    } else {

        // Current password is required for any update.
        if ($current_password === '') {
            $error = "Current password is required.";
        } elseif (validateInput($current_password) === false) {
            $error = "Please use letters or numbers. There is no need to use such symbols on this website :)";
        } else {
             /**
             * Load password hash from DB and verify current password.
             */
            $stmt = mysqli_prepare($conn, "SELECT password FROM users WHERE user_id = ? LIMIT 1");
            mysqli_stmt_bind_param($stmt, "i", $userId);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            $user = mysqli_fetch_assoc($res);
            mysqli_stmt_close($stmt);

            if (!$user || !password_verify($current_password, $user['password'])) {
                $error = "Incorrect current password.";
            } else {
                 /**
                 * Username update:
                 * - must pass validateInput,
                 * - cannot be only numbers,
                 * - must be unique (excluding current user).
                 */
                if ($wantsUsernameChange) {
                    if (validateInput($new_username) === false || is_numeric($new_username)) {
                        $error = "Username must contain only letters/numbers and not be only numbers.";
                    } else {
                        $check = mysqli_prepare($conn, "SELECT 1 FROM users WHERE user_name = ? AND user_id <> ? LIMIT 1");
                        mysqli_stmt_bind_param($check, "si", $new_username, $userId);
                        mysqli_stmt_execute($check);
                        mysqli_stmt_store_result($check);

                        if (mysqli_stmt_num_rows($check) > 0) {
                            $error = "Username already exists.";
                        }
                        mysqli_stmt_close($check);

                        if (!$error) {
                            $upd = mysqli_prepare($conn, "UPDATE users SET user_name = ? WHERE user_id = ?");
                            mysqli_stmt_bind_param($upd, "si", $new_username, $userId);
                            mysqli_stmt_execute($upd);
                            mysqli_stmt_close($upd);

                            $successMessage = "Username changed successfully.";
                            $user_data['user_name'] = $new_username;
                        }
                    }
                }

                /**
                 * Password update:
                 * - both fields required if changing,
                 * - validateInput must pass,
                 * - must match confirmation,
                 * - minimum length enforced,
                 * - stored as password_hash (PASSWORD_DEFAULT).
                 */
                if (!$error && $wantsPasswordChange) {
                    if ($new_password === '' || $confirm_password === '') {
                        $error = "New password and confirmation are required.";
                    } elseif (validateInput($new_password) === false || validateInput($confirm_password) === false) {
                        $error = "Please use letters or numbers. There is no need to use such symbols on this website :)";
                    } elseif ($new_password !== $confirm_password) {
                        $error = "New password and its confirmation are not identical.";
                    } elseif (strlen($new_password) < 8) {
                        $error = "Password has to contain at least 8 characters!";
                    } else {
                        $hashedNewPassword = password_hash($new_password, PASSWORD_DEFAULT);

                        $upd = mysqli_prepare($conn, "UPDATE users SET password = ? WHERE user_id = ?");
                        mysqli_stmt_bind_param($upd, "si", $hashedNewPassword, $userId);
                        mysqli_stmt_execute($upd);
                        mysqli_stmt_close($upd);

                        $successMessage = $successMessage
                            ? ($successMessage . " Password changed successfully.")
                            : "Password changed successfully.";
                    }
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <script src="passwordValidation.js"></script>
    <link rel="stylesheet" href="mainstyle.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account settings</title>
</head>

<body id="form_body">

    <div class="menushka">
        <a href="home.php">Main</a>
        <a href="forum.php">Forum</a>
        <a href="profile.php">Profile</a>
        <?php if (isset($user_data['role']) && $user_data['role'] === 'admin'): ?>
            <a href="admin_users.php">Admin</a>
        <?php endif; ?>
    </div>

    <section id="form_section">
        <div class="form-box">
            <div class="form-value">
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <h2>Account settings</h2>

                    <?php if (!empty($error)): ?>
                        <div style="color:red; text-align:center; margin-bottom:10px;">
                            <?= htmlspecialchars($error) ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($successMessage)): ?>
                        <div style="color:green; text-align:center; margin-bottom:10px;">
                            <?= htmlspecialchars($successMessage) ?>
                        </div>
                    <?php endif; ?>

                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                    <div class="inputbox">
                        <input type="password" id="current_password" name="current_password" required>
                        <label for="current_password">Current password (required)</label>
                    </div>

                    <div class="inputbox">
                        <input type="text" id="new_username" name="new_username">
                        <label for="new_username">New username (optional)</label>
                    </div>

                    <div class="inputbox">
                        <input type="password" id="new_password" name="new_password">
                        <label for="new_password">New password (optional)</label>
                    </div>

                    <div class="inputbox">
                        <input type="password" id="confirm_password" name="confirm_password">
                        <label for="confirm_password">Confirm new password (optional)</label>
                    </div>

                    <button type="submit">Save changes</button>
                </form>
            </div>
        </div>
    </section>

</body>
</html>

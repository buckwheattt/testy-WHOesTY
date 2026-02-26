<?php

/**
 * @file signup.php
 * @brief User registration script.
 *
 * Handles user signup:
 * - generates and validates CSRF token,
 * - validates user input,
 * - checks username and email uniqueness,
 * - stores new user with hashed password,
 * - redirects to login page on success.
 */

session_start();

include("connection.php");
include("functions.php");

/**
 * CSRF token stored in session.
 * Regenerates session ID on first creation.
 *
 * @var string $_SESSION['csrf_token']
 */

if (!isset($_SESSION['csrf_token'])) {
    session_regenerate_id(true);
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/**
 * Error messages used for form validation feedback.
 *
 * @var string|null $user_error
 * @var string|null $email_error
 * @var string|null $error_char
 * @var string|null $passwordError
 * @var string|null $error
 */
$user_error = null;
$email_error = null;
$error_char = null;
$passwordError = null;
$error = null;

/**
 * Process form submission.
 * Validates CSRF token, user input and inserts a new user into database.
 */
if ($_SERVER['REQUEST_METHOD'] === "POST") {

    // CSRF check
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Invalid CSRF token.";
    } else {

        /**
         * User input values from signup form.
         *
         * @var string $user_name
         * @var string $email
         * @var string $password
         */
        $user_name = trim($_POST['user_name'] ?? '');
        $email     = trim($_POST['email'] ?? '');
        $password  = $_POST['password'] ?? '';

        // Basic validation
        if (validateInput($user_name) === false || validateInput($email) === false || validateInput($password) === false) {
            $error_char = "Please use letters or numbers. There is no need to use such symbols on this website :)";
        }

        // Additional rules
        if (!$error_char) {
            if ($user_name === '' || $email === '' || $password === '') {
                $error = "Please enter some valid information!";
            }elseif (strlen($password) < 8) {
                $passwordError = "Password has to contain at least 8 characters!";
            }
        }

        /**
         * Check uniqueness of username and email.
         * Executed only if previous validations passed.
         */
        if (!$error && !$error_char && !$passwordError) {

            // username exists?
            $check_query = "SELECT 1 FROM users WHERE user_name = ? LIMIT 1";
            $check_stmt = mysqli_prepare($conn, $check_query);
            mysqli_stmt_bind_param($check_stmt, "s", $user_name);
            mysqli_stmt_execute($check_stmt);
            mysqli_stmt_store_result($check_stmt);

            if (mysqli_stmt_num_rows($check_stmt) > 0) {
                $user_error = "Error: Username already exists.";
            }
            mysqli_stmt_close($check_stmt);

            // email exists?
            $check_email_query = "SELECT 1 FROM users WHERE email = ? LIMIT 1";
            $check_email_stmt = mysqli_prepare($conn, $check_email_query);
            mysqli_stmt_bind_param($check_email_stmt, "s", $email);
            mysqli_stmt_execute($check_email_stmt);
            mysqli_stmt_store_result($check_email_stmt);

            if (mysqli_stmt_num_rows($check_email_stmt) > 0) {
                $email_error = "Error: Email already exists.";
            }
            mysqli_stmt_close($check_email_stmt);
        }

        /**
         * Insert new user into database.
         */
        if (!$error && !$error_char && !$passwordError && !$user_error && !$email_error) {

            $user_id = generateUniqueUserId($conn);
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $profilePicture = 'uploads/noavatar.png';

            try {
                $query = "INSERT INTO users (user_id, user_name, email, password, profilePicture) VALUES (?, ?, ?, ?, ?)";
                $insert_stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($insert_stmt, "sssss", $user_id, $user_name, $email, $hashedPassword, $profilePicture);
                mysqli_stmt_execute($insert_stmt);
                mysqli_stmt_close($insert_stmt);

                header("Location: login.php");
                exit;

            } catch (mysqli_sql_exception $e) {
                if ($e->getCode() == 1062) {
                    $error = "User with this username or email already exists.";
                } else {
                    $error = "Database error.";
                    // error_log($e->getMessage());
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <script src="ajax-script.js"></script>
  <link rel="stylesheet" href="mainstyle.css">
  <link rel="stylesheet" type="text/css" href="print-styles.css" media="print">
  <meta charset="UTF-8">
  <title>Signup Form</title>
</head>

<body id="form_body">
    <div class="menushka">
        <a href="home.php">Main</a>
        <a href="forum.php">Forum</a>
        <a href="login.php">Login</a>
    </div>

    <section id="form_section">
        <div class="form-box">
            <div class="form-value">
                <form action="signup.php" method="post">
                    <h2>Signup</h2>

                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                    <?php if ($error): ?>
                        <div style="color:red; text-align:center; margin-bottom:10px;">
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>

                    <div class="inputbox">
                        <ion-icon name="person-outline"></ion-icon>

                        <input
                            type="text"
                            required
                            name="user_name"
                            id="username"
                            value="<?php echo isset($_POST['user_name']) ? htmlspecialchars($_POST['user_name']) : ''; ?>"
                        >
                        <label>Username</label>

                        <div id="usernameResult"></div>

                        <?php if ($user_error): ?>
                            <div style="color:red;"><?php echo htmlspecialchars($user_error); ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="inputbox">
                        <ion-icon name="mail-outline"></ion-icon>

                        <input
                            type="email"
                            required
                            name="email"
                            value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                        >
                        <label>Email</label>

                        <?php if ($email_error): ?>
                            <div style="color:red;"><?php echo htmlspecialchars($email_error); ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="inputbox">
                        <ion-icon name="lock-closed-outline"></ion-icon>

                        <input
                            type="password"
                            required
                            name="password"
                        >
                        <label>Password</label>

                        <?php if ($passwordError): ?>
                            <div style="color:red;"><?php echo htmlspecialchars($passwordError); ?></div>
                        <?php endif; ?>

                        <?php if ($error_char): ?>
                            <div style="color:red;"><?php echo htmlspecialchars($error_char); ?></div>
                        <?php endif; ?>
                    </div>

                    <button type="submit">Signup</button>
                </form>
            </div>
        </div>
    </section>

    <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>
</body>
</html>

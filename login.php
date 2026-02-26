<?php
/**
 * @file login.php
 * @brief User login page (authentication) + HTML form.
 *
 * Features:
 * - CSRF protection via session token
 * - input validation (validateInput)
 * - secure DB query via prepared statements
 * - password verification via password_verify()
 * - on success stores user_id in session and redirects to profile.php
 */
session_start();

include("connection.php");
include("functions.php");

/**
 * CSRF token for the login form.
 *
 * @var string $_SESSION['csrf_token']
 */
if (!isset($_SESSION['csrf_token'])) {
    session_regenerate_id(true);
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/**
 * Handle login form submission (POST).
 */
if ($_SERVER['REQUEST_METHOD'] == "POST") {
     /**
     * Raw user input from the form.
     *
     * @var string $user_name
     * @var string $password
     */
    $user_name = $_POST['user_name'];
    $password = $_POST['password'];

    // Validate inputs
    if ((validateInput($user_name)) === false || (validateInput($password)) === false) {
        $error_char = "Please use letters or numbers. There is no need to use such symbols on this website :)";
    }

    /**
     * Proceed only if:
     * - both fields are provided
     * - username is not purely numeric
     * - validation did not fail
     */
    if (!empty($user_name) && !empty($password) && !is_numeric($user_name) && empty($error_char)) {
        // Check CSRF token
        if (isset($_SESSION['csrf_token']) && isset($_POST['csrf_token']) && $_POST['csrf_token'] === $_SESSION['csrf_token']) {
             /**
             * Fetch user record by username using prepared statement.
             * Prevents SQL injection.
             */
            $query = "SELECT * FROM users WHERE user_name = ?";
            $stmt = mysqli_prepare($conn, $query);

            // Bind parameters
            mysqli_stmt_bind_param($stmt, "s", $user_name);

            // Execute query
            mysqli_stmt_execute($stmt);

            // Get results
            $result = mysqli_stmt_get_result($stmt);

            if ($result && mysqli_num_rows($result) > 0) {
                $user_data = mysqli_fetch_assoc($result);
                $password_entered = $user_data['password'];
                if (password_verify($password, $password_entered)) {
                    $_SESSION['user_id'] = $user_data['user_id'];
                    header("Location: profile.php");
                    die;
                }
            }

            // Close the prepared statement
            mysqli_stmt_close($stmt);
        } else {
            $error = "Invalid CSRF token.";
        }
    } else {
        $error = "Wrong username or password!";
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <link rel="stylesheet" href="mainstyle.css">
  <link rel="stylesheet" type="text/css" href="print-styles.css" media="print">
  <title>Registration Form</title>
</head>

<body id="form_body">
    <div class="menushka">
        <a href="home.php">Main</a>
        <a href="login.php" >Login</a>
        <a href="forum.php">Forum</a>
		  <?php if ($conn): ?>
          <a href="profile.php">Profile</a>
          <?php endif; ?>
    </div>
    
	<section id="form_section">
        <div class="form-box">
            <div class="form-value">
                <form action = "login.php" method = "post">
                    <h2>Login</h2>
					<input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <div class="inputbox">
                        <ion-icon name="person-outline"></ion-icon>
                        <input type="text" required name="user_name" id="username" value="<?php echo isset($_POST['user_name']) ? htmlspecialchars($_POST['user_name']) : ''; ?>">
                        <div id="usernameResult"></div><br><br>
                        <label>Username</label>
                    </div>
                    <div class="inputbox">
                        <ion-icon name="lock-closed-outline"></ion-icon>
                        <input type="password" required name="password"><br><br>
                        <label>Password</label>
                    </div>

					<?php if (isset($error)): ?>
                        <div style="color: red;"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <?php if (isset($error_char)): ?>
                        <div style="color: red;"><?php echo $error_char; ?></div>
                    <?php endif; ?>

                    <button type="submit">Log in</button>
                    <div class="register">
                        <p>Don't have an account? <a href="signup.php">Register</a></p>
                    </div>
                </form>
            </div>
        </div>
    </section>
    <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>
</body>
</html>
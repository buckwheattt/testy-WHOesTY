<?php
/**
 * @file admin_users.php
 * @brief User management page for administrators.
 *
 * Allows admins to view all users and promote them to admin role.
 * Access is restricted to authenticated administrators only.
 */
session_start();
include("connection.php");
include("functions.php");

/**
 * Logged-in user data.
 * Redirects if user is not logged in.
 */
$user_data = check_login($conn);
require_admin($user_data);
/**
 * CSRF token initialization.
 */

if (!isset($_SESSION['csrf_token'])) {
    session_regenerate_id(true);
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/** @var string|null $message Status message after promotion */
$message = null;

/**
 * Handle user promotion to admin.
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['promote_user_id'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("CSRF token validation failed.");
    }

        /** @var int $promoteId ID of user to promote */
    $promoteId = (int)$_POST['promote_user_id'];
    // Update user role
    $stmt = mysqli_prepare($conn, "UPDATE users SET role='admin' WHERE user_id=?");
    mysqli_stmt_bind_param($stmt, "i", $promoteId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    $message = "User promoted to admin.";
}

/**
 * Load all users for display.
 */
$stmt = mysqli_prepare($conn, "SELECT user_id, user_name, email, role FROM users ORDER BY user_name ASC");
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$users = mysqli_fetch_all($res, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <link rel="stylesheet" href="mainstyle.css">
  <title>Users management</title>
</head>

<body id="forum_body">

<header>
  <div class="menushka">
    <a href="home.php">Main</a>
    <a href="forum.php">Forum</a>
    <a href="profile.php">Profile</a>
  </div>

  <a href="#biba" class="biba"></a>
  <div class="welcome">
    <h1>Users management</h1>
  </div>
  <a class="boba" href="#boba"></a>
</header>

<section id="forum_section">
  <main id="mainn" class="admin-list">

    <?php if ($message): ?>
      <div class="admin-card">
        <span id="test_choice1"><?= htmlspecialchars($message) ?></span>
      </div>
    <?php endif; ?>

    <?php foreach ($users as $u): ?>
      <section class="admin-card">
        <div class="admin-meta">
          <span id="test_choice"><?= htmlspecialchars($u['user_name']) ?></span>
          <span id="test_choice1"><?= htmlspecialchars($u['email']) ?></span>
          <span id="test_choice1">Role: <?= htmlspecialchars($u['role']) ?></span>
        </div>

        <div>
          <?php if ($u['role'] !== 'admin'): ?>
            <form method="post">
              <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
              <input type="hidden" name="promote_user_id" value="<?= (int)$u['user_id'] ?>">
              <button type="submit">Promote to admin</button>
            </form>
          <?php else: ?>
            <span id="test_choice1">very admin</span>
          <?php endif; ?>
        </div>
      </section>
    <?php endforeach; ?>

  </main>
</section>

</body>
</html>

<?php
/**
 * @file forum.php
 * @brief Simple forum page with posts and admin comments.
 *
 * - Guests can read posts
 * - Logged-in users can create posts
 * - Only admins can add comments
 * - CSRF protected forms
 */
session_start();
include("connection.php");
include("functions.php");

/**
 * Logged-in user data (null for guests).
 *
 * @var array|null $user_data
 */
$user_data = null;
if (isset($_SESSION['user_id'])) {
    $user_data = check_login($conn);
}
/**
 * Local admin check helper.
 *
 * @param array|null $user_data
 * @return bool
 */
function is_admin_local($user_data) {
    return isset($user_data['role']) && $user_data['role'] === 'admin';
}

/**
 * CSRF token initialization.
 */
if (!isset($_SESSION['csrf_token'])) {
    session_regenerate_id(true);
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/** @var string|null $post_error Error while creating post */
$post_error = null;

/** @var string|null $comment_error Error while adding comment */
$comment_error = null;

/* ---------- CREATE POST (logged-in users) ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_post'])) {

    if (!$user_data) {
        header("Location: login.php");
        exit;
    }

    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("CSRF token validation failed.");
    }

    $title = trim($_POST['title'] ?? '');
    $body  = trim($_POST['body'] ?? '');

    if ($title === '' || $body === '') {
        $post_error = "Title and text are required.";
    } elseif (mb_strlen($title) > 255) {
        $post_error = "Title is too long.";
    } else {
        // В БД кладём как есть (no htmlspecialchars!)
        $stmt = mysqli_prepare($conn, "INSERT INTO forum_posts (user_id, title, body) VALUES (?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "sss", $user_data['user_id'], $title, $body);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        header("Location: forum.php");
        exit;
    }
}

/* ---------- ADD COMMENT (admin only) ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_comment'])) {

    if (!$user_data || !is_admin_local($user_data)) {
        header("Location: login.php");
        exit;
    }

    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("CSRF token validation failed.");
    }

    $post_id = (int)($_POST['post_id'] ?? 0);
    $body = trim($_POST['comment_body'] ?? '');

    if ($post_id <= 0) {
        $comment_error = "Invalid post.";
    } elseif ($body === '') {
        $comment_error = "Comment cannot be empty.";
    } else {
        // as it is (no htmlspecialchars!)
        $stmt = mysqli_prepare($conn, "INSERT INTO forum_comments (post_id, admin_id, body) VALUES (?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "iss", $post_id, $user_data['user_id'], $body);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        header("Location: forum.php");
        exit;
    }
}

/* ---------- LOAD POSTS ---------- */
$stmt = mysqli_prepare($conn, "
    SELECT p.id, p.title, p.body, p.created_at, u.user_name
    FROM forum_posts p
    JOIN users u ON u.user_id = p.user_id
    ORDER BY p.created_at DESC
");
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$posts = mysqli_fetch_all($res, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

/* ---------- LOAD COMMENTS (all) and group by post_id ---------- */
$commentsByPost = [];

$stmt = mysqli_prepare($conn, "
    SELECT c.post_id, c.body, c.created_at, u.user_name
    FROM forum_comments c
    JOIN users u ON u.user_id = c.admin_id
    ORDER BY c.created_at ASC
");
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($res)) {
    $pid = (int)$row['post_id'];
    if (!isset($commentsByPost[$pid])) $commentsByPost[$pid] = [];
    $commentsByPost[$pid][] = $row;
}
mysqli_stmt_close($stmt);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <link rel="stylesheet" href="mainstyle.css">
  <title>Forum</title>
</head>

<body id="forum_body">

<header>
  <div class="menushka">
    <a href="home.php">Main</a>
    <a href="forum.php">Forum</a>
    <?php if ($user_data): ?>
      <a href="profile.php">Profile</a>
    <?php else: ?>
      <a href="login.php">Login</a>
    <?php endif; ?>
  </div>

  <a href="#biba" class="biba"></a>
  <div class="welcome"><h1>Forum</h1></div>
  <a class="boba" href="#boba"></a>
</header>

<section id="forum_section">
  <div class="forum-wrap">

    <!-- Create post -->
    <?php if ($user_data): ?>
      <div class="card">
        <h2>New post</h2>

        <?php if ($post_error): ?>
          <div class="msg-error"><?= htmlspecialchars($post_error) ?></div>
        <?php endif; ?>

        <form class="post-form" method="post">
          <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
          <input type="hidden" name="create_post" value="1">

          <input type="text" name="title" placeholder="Title" required>
          <textarea name="body" rows="4" placeholder="Write something..." required></textarea>

          <button type="submit" style="margin-top:10px;">Publish</button>
        </form>
      </div>
    <?php else: ?>
      <div class="card">
        <div class="hint">
          You can read posts. To publish, please <a href="login.php" id="test_choice">login</a>.
        </div>
      </div>
    <?php endif; ?>

    <!-- Posts -->
    <?php foreach ($posts as $p): $pid = (int)$p['id']; ?>
      <div class="card">
        <h2><?= htmlspecialchars($p['title']) ?></h2>
        <div class="meta">
          by <?= htmlspecialchars($p['user_name']) ?> / <?= htmlspecialchars($p['created_at']) ?>
        </div>
        <div class="body"><?= htmlspecialchars($p['body']) ?></div>

        <div class="small-title">Admin comments</div>

        <?php if (!empty($commentsByPost[$pid])): ?>
          <?php foreach ($commentsByPost[$pid] as $c): ?>
            <div class="comment">
              <b><?= htmlspecialchars($c['user_name']) ?></b>
              <span style="opacity:.7;">(<?= htmlspecialchars($c['created_at']) ?>)</span><br>
              <?= htmlspecialchars($c['body']) ?>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="comment" style="border-top:none; padding-top:0; opacity:.8;">
            No comments yet.
          </div>
        <?php endif; ?>

        <?php if ($user_data && is_admin_local($user_data)): ?>
          <?php if ($comment_error && isset($_POST['post_id']) && (int)$_POST['post_id'] === $pid): ?>
            <div class="msg-error"><?= htmlspecialchars($comment_error) ?></div>
          <?php endif; ?>

          <form class="comment-form" method="post" style="margin-top:10px;">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="add_comment" value="1">
            <input type="hidden" name="post_id" value="<?= $pid ?>">

            <textarea name="comment_body" rows="2" placeholder="Write admin comment..."></textarea>
            <button type="submit" style="margin-top:8px;">Comment</button>
          </form>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>

  </div>
</section>

</body>
</html>

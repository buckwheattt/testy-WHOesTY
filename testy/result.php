<?php
/**
 * @file result.php
 * @brief Displays test result based on calculated result key.
 *
 * Loads and displays the result title, description and optional image
 * for a completed test. Access requires logged-in user.
 */
session_start();
include("../connection.php");
include("../functions.php");

/**
 * Logged-in user data (authentication required).
 */
$user_data = check_login($conn);

/**
 * Test ID and result key from URL parameters.
 *
 * @var int    $testId
 * @var string $key
 */

$testId = isset($_GET['test']) ? (int)$_GET['test'] : 0;
$key    = isset($_GET['key']) ? $_GET['key'] : '';

if ($testId <= 0 || $key === '') { die("Bad params"); }

/**
 * Load result data for given test and result key.
 */
$stmt = mysqli_prepare($conn, "SELECT result_title, result_text, image_path FROM results WHERE test_id=? AND result_key=?");
mysqli_stmt_bind_param($stmt, "is", $testId, $key);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

if (!$row) { die("Result not found"); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <link rel="stylesheet" href="../mainstyle.css">
  <title>result</title>
</head>
<body id="option_bodyy">

  <h1><?= htmlspecialchars($row['result_title']) ?></h1>
  <h3><?= htmlspecialchars($row['result_text']) ?></h3>

  <?php if (!empty($row['image_path'])): ?>
    <img src="../<?= htmlspecialchars($row['image_path']) ?>" alt="result">
  <?php endif; ?>

</body>
</html>

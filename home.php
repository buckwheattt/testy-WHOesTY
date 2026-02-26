<?php
/**
 * @file home.php
 * @brief Displays a list of available tests from the database.
 *
 * - Requires authenticated user (check_login).
 * - Loads test IDs and titles from table `tests`.
 * - Sorts tests alphabetically by title, ignoring the first 2 characters (project-specific prefix).
 * - Renders links to `testy/test.php?id=...`.
 */
session_start();

include("connection.php");
include("functions.php");

/**
 * Currently logged-in user data (redirects/blocks if not logged in).
 *
 * @var array $user_data
 */
$user_data = check_login($conn);

/**
 * List of tests to display.
 * Each item contains: ['id' => int, 'title' => string].
 *
 * @var array<int, array{id:mixed, title:string}> $tests
 */
$tests = [];

/**
 * Load tests from DB.
 */
$stmt = mysqli_prepare($conn, "SELECT id, title FROM tests");
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);

while ($row = mysqli_fetch_assoc($res)) {
    $tests[] = $row;
}
mysqli_stmt_close($stmt);

/**
 * Sort tests by title (case-insensitive),
 * comparing titles without the first 2 characters (emoji:).
 *
 * @param array $a
 * @param array $b
 * @return int
 */
usort($tests, function ($a, $b) {
    return strcasecmp(
        mb_substr($a['title'], 2),
        mb_substr($b['title'], 2)
    );
});
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="mainstyle.css">
    <link rel="stylesheet" type="text/css" href="print-styles.css" media="print">
    <title>TESTY~WHOesTY</title>
</head>
<body id="forum_body">

<header>
    <div class="menushka">
        <a href="home.php">Main</a>
        <a href="forum.php">Forum</a>
        <a href="login.php">Login</a>

        <?php if ($conn): ?>
            <a href="profile.php">Profile</a>
        <?php else: ?>
            <a href="login.php">Profile</a>
        <?php endif; ?>
    </div>

    <a href="#biba" class="biba"></a>
    <div class="welcome">
        <h1>TESTY~WHOesTY</h1>
    </div>
    <a class="boba" href="#boba"></a>
</header>

<section id="forum_section">
    <main id="mainn">

        <?php foreach ($tests as $test): ?>
            <div>
                <a
                    href="testy/test.php?id=<?= (int)$test['id'] ?>"
                    id="test_choice"
                >
                    <?= htmlspecialchars($test['title']) ?>
                </a>
            </div>
        <?php endforeach; ?>

        <div>
            <a id="test_choice1">Coming soon!</a>
        </div>

    </main>
</section>

</body>
</html>
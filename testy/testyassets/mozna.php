<?php
/**
 * @file mozna.php
 * @brief Paginated loading / transition screen before final result.
 *
 * Displays a sequence of messages step by step using pagination.
 * Requires authenticated user.
 */
session_start();

include("../../connection.php");
include("../../functions.php");
/**
 * Logged-in user data (authentication required).
 */
$user_data = check_login($conn);

/**
 * Number of items displayed per page.
 *
 * @var int $perPage
 */
$perPage = 1;

/**
 * Current page number from URL.
 *
 * @var int $page
 */
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;

/**
 * Messages displayed during loading steps.
 *
 * @var array<int,string> $items
 */
$items = array(
  "<h1>conducting opinion polls...</h1>",
  "<h1>verifying answers...</h1>",
  "<h1>just a second, lets ask a toad itself and see what it says!</h1>",
  "<h1><a href='toads_answer.php'>ask a toad itself</a></h1>"
);
/**
 * Pagination calculations.
 */
$totalPages = (int)ceil(count($items) / $perPage);
if ($totalPages < 1) $totalPages = 1;
if ($page > $totalPages) $page = $totalPages;
/**
 * Select items for current page.
 */
$startIndex = ($page - 1) * $perPage;
$itemsOnPage = array_slice($items, $startIndex, $perPage);

/**
 * Render current page items.
 */
foreach ($itemsOnPage as $item) {
    echo "<p>$item</p>";
}

/**
 * Render pagination navigation.
 */
echo "<br>Page : ";

if ($page > 1) {
    echo "<a href='?page=" . ($page - 1) . "'>Previous</a> ";
} else {
    echo "<span>Previous</span> ";
}


for ($i = 1; $i <= $totalPages; $i++) {
    echo "<a href='?page=$i'>$i</a> ";
}

if ($page < $totalPages) {
    echo "<a href='?page=" . ($page + 1) . "'>Next</a>";
} else {
    echo "<span>Next</span>";
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <link rel="stylesheet" href="../../mainstyle.css">
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>result</title>
</head>
<body id="option_bodyy">
</body>
</html>

<?php
/**
 * @file test.php
 * @brief Test execution and result evaluation page.
 *
 * Displays questions for a selected test, processes submitted answers,
 * calculates the result key and redirects user to the corresponding result.
 */
session_start();
include("../connection.php");
include("../functions.php");

/**
 * Logged-in user data (authentication required).
 */
$user_data = check_login($conn);

/**
 * Test ID from URL.
 *
 * @var int $testId
 */

$testId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($testId <= 0) { die("Bad test id"); }
/**
 * Load test title.
 */
$stmt = mysqli_prepare($conn, "SELECT title FROM tests WHERE id=?");
mysqli_stmt_bind_param($stmt, "i", $testId);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$test = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);
if (!$test) { die("Test not found"); }


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /**
     * Load all questions for the test.
     */
    $qstmt = mysqli_prepare($conn, "SELECT id FROM questions WHERE test_id=? ORDER BY sort_order");
    mysqli_stmt_bind_param($qstmt, "i", $testId);
    mysqli_stmt_execute($qstmt);
    $qres = mysqli_stmt_get_result($qstmt);

     /**
     * Collected scores by result_key.
     *
     * @var array<string,int> $scores
     */
    $scores = []; // result_key => count

    while ($q = mysqli_fetch_assoc($qres)) {
        $qid = (int)$q['id'];
        $field = "q" . $qid; 

        if (!isset($_POST[$field])) continue;
        $answerId = (int)$_POST[$field];

         /**
         * Load result key for selected answer.
         */
        $astmt = mysqli_prepare($conn, "SELECT result_key FROM answers WHERE id=? AND question_id=?");
        mysqli_stmt_bind_param($astmt, "ii", $answerId, $qid);
        mysqli_stmt_execute($astmt);
        $ares = mysqli_stmt_get_result($astmt);
        $a = mysqli_fetch_assoc($ares);
        mysqli_stmt_close($astmt);

        if ($a) {
            $key = $a['result_key'];
            $scores[$key] = ($scores[$key] ?? 0) + 1;
        }
    }
    mysqli_stmt_close($qstmt);

     /**
     * Determine best matching result.
     */
    arsort($scores);
    $bestKey = $scores ? array_key_first($scores) : null;

    if ($bestKey) {
         /**
         * Load redirect URL or fallback result page.
         */
        $rstmt = mysqli_prepare($conn, "SELECT redirect_url FROM results WHERE test_id=? AND result_key=?");
        mysqli_stmt_bind_param($rstmt, "is", $testId, $bestKey);
        mysqli_stmt_execute($rstmt);
        $rres = mysqli_stmt_get_result($rstmt);
        $r = mysqli_fetch_assoc($rres);
        mysqli_stmt_close($rstmt);

        if ($r) {
    if (!empty($r['redirect_url'])) {
        header("Location: " . $r['redirect_url']);
        exit;
    } else {
        header("Location: result.php?test=" . $testId . "&key=" . urlencode($bestKey));
        exit;
    }
}


    }

    die("Result not configured");
}

$qstmt = mysqli_prepare($conn, "SELECT id, question_text FROM questions WHERE test_id=? ORDER BY sort_order");
mysqli_stmt_bind_param($qstmt, "i", $testId);
mysqli_stmt_execute($qstmt);
$qres = mysqli_stmt_get_result($qstmt);

/**
 * Prepared questions with answers for rendering.
 *
 * @var array<int,array>
 */
$questions = [];
while ($q = mysqli_fetch_assoc($qres)) {
    $qid = (int)$q['id'];

    $astmt = mysqli_prepare($conn, "SELECT id, answer_text FROM answers WHERE question_id=? ORDER BY sort_order");
    mysqli_stmt_bind_param($astmt, "i", $qid);
    mysqli_stmt_execute($astmt);
    $ares = mysqli_stmt_get_result($astmt);

    $answers = [];
    while ($a = mysqli_fetch_assoc($ares)) $answers[] = $a;
    mysqli_stmt_close($astmt);

    $questions[] = [
        'id' => $qid,
        'text' => $q['question_text'],
        'answers' => $answers
    ];
}
mysqli_stmt_close($qstmt);
?>
<!doctype html>
<html lang="en">
<head>
  <link rel="stylesheet" href="../mainstyle.css">
  <meta charset="UTF-8" />
  <title><?= htmlspecialchars($test['title']) ?></title>
</head>

<body id="test_body">
  <div class="menushka">
    <a href="../home.php">Main</a>
    <a href="../login.php">Login</a>
  </div>

  <section class="testik_section">
    <div class="testobox">

      <h2><?= htmlspecialchars($test['title']) ?></h2>

      <form method="post" class="quizform">
        <?php foreach ($questions as $q): ?>
          <div class="quizsection">
            <h2><?= htmlspecialchars($q['text']) ?></h2>

            <?php foreach ($q['answers'] as $a): ?>
              <div class="answer">
                <label>
                  <input type="radio" name="q<?= (int)$q['id'] ?>" value="<?= (int)$a['id'] ?>" required>
                  <?= htmlspecialchars($a['answer_text']) ?>
                </label>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endforeach; ?>

        <input value="Submit" type="submit" />
        <input value="Reset" type="reset" />
      </form>

    </div>
  </section>
</body>
</html>

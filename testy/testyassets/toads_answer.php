<?php
/**
 * @file toads_answer.php
 * @brief Redirects to a random test result.
 *
 * Selects a random result key and redirects the user
 * to the result page of the toad test.
 */
session_start();

include("../../connection.php");
include("../../functions.php");
/**
 * Logged-in user data (authentication required).
 */
$user_data = check_login($conn);

/**
 * Randomly choose between two possible result keys.
 *
 * @var string $randomKey
 */
$randomKey = (rand(0, 1) === 0) ? 'value1' : 'value3';

/**
 * Redirect to test result (test_id = 5).
 */
header("Location: ../result.php?test=5&key=" . $randomKey);
exit;




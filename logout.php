<?php
/**
 * @file logout.php
 * @brief Logs the user out and redirects to login page.
 *
 * Removes the user authentication data from the session
 * and terminates the current login state.
 */

session_start();
/**
 * Unset user identifier to end authenticated session.
 *
 * @var int|string $_SESSION['user_id']
 */
if(isset($_SESSION['user_id']))
{
	unset($_SESSION['user_id']);

}
// Redirect user to login page after logout
header("Location: login.php");
die;
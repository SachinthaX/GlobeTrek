<?php

/*
 * GlobeTrek Adventures
 * Logout
 *
 * This page destroys the user's PHP session and sends them
 * back to the login page.
 */

session_start();

/*
 * Remove all session variables.
 */
$_SESSION = [];

/*
 * If PHP is using a session cookie, remove the cookie as well.
 */
if (ini_get('session.use_cookies')) {

    $sessionCookieParameters = session_get_cookie_params();

    setcookie(
        session_name(),
        '',
        time() - 42000,
        $sessionCookieParameters['path'],
        $sessionCookieParameters['domain'],
        $sessionCookieParameters['secure'],
        $sessionCookieParameters['httponly']
    );
}

/*
 * Destroy the session completely.
 */
session_destroy();

/*
 * Return the user to the login page.
 */
header('Location: /globetrek/customer/login.php');
exit;
<?php

/*
 * GlobeTrek Adventures
 * Authentication and Role-Based Access Control
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/**
 * Check whether a user is logged in.
 *
 * @return bool
 */
function isLoggedIn()
{
    return isset($_SESSION['user_id'])
        && isset($_SESSION['role']);
}


/**
 * Require an authenticated user.
 *
 * Guests are redirected to a 401 error page.
 */
function requireLogin()
{
    if (!isLoggedIn()) {
        header('Location: /globetrek/error.php?code=401');
        exit;
    }
}


/**
 * Require a specific role.
 *
 * @param string ...$allowedRoles
 */
function requireRole(...$allowedRoles)
{
    requireLogin();

    $currentRole = $_SESSION['role'] ?? '';

    if (!in_array($currentRole, $allowedRoles, true)) {

        /*
         * The account is valid, but the user does not have
         * permission to access the requested page.
         */
        header('Location: /globetrek/error.php?code=403');
        exit;
    }
}
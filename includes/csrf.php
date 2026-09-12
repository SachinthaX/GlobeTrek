<?php

/*
 * GlobeTrek Adventures
 * CSRF Protection
 *
 * This file provides reusable functions for generating
 * and validating CSRF tokens for state-changing forms.
 */


/*
 * Start the session if one is not already active.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/**
 * Get the application's CSRF token.
 *
 * A cryptographically secure token is generated the first
 * time the function is called during the session.
 *
 * @return string
 */
function getCsrfToken()
{
    if (
        empty($_SESSION['csrf_token'])
        || !is_string($_SESSION['csrf_token'])
    ) {
        $_SESSION['csrf_token'] =
            bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}


/**
 * Generate a hidden HTML input containing the CSRF token.
 *
 * @return string
 */
function csrfField()
{
    return '<input type="hidden" name="csrf_token" value="'
        . htmlspecialchars(
            getCsrfToken(),
            ENT_QUOTES,
            'UTF-8'
        )
        . '">';
}


/**
 * Validate a submitted CSRF token.
 *
 * @param string|null $submittedToken
 * @return bool
 */
function isValidCsrfToken($submittedToken)
{
    if (
        !is_string($submittedToken)
        || $submittedToken === ''
        || empty($_SESSION['csrf_token'])
    ) {
        return false;
    }

    return hash_equals(
        $_SESSION['csrf_token'],
        $submittedToken
    );
}


/**
 * Require a valid CSRF token for a POST request.
 *
 * Invalid requests are sent to the central 403 error page.
 */
function requireCsrfToken()
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }

    $submittedToken =
        $_POST['csrf_token'] ?? null;

    if (!isValidCsrfToken($submittedToken)) {

        header(
            'Location: /globetrek/error.php?code=403'
        );

        exit;
    }
}
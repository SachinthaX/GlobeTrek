<?php

/*
 * GlobeTrek Adventures
 * Shared page header.
 *
 * Every page can define $pageTitle before including this file.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pageTitle = $pageTitle ?? 'GlobeTrek Adventures';

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <meta
        name="description"
        content="GlobeTrek Adventures - Travel and tourism packages in Sri Lanka."
    >

    <meta
        name="theme-color"
        content="#0f4c5c"
    >

    <title>
        <?= htmlspecialchars($pageTitle) ?>
    </title>

    <link
        rel="stylesheet"
        href="/globetrek/assets/css/style.css"
    >

</head>

<body>

<?php require_once __DIR__ . '/navbar.php'; ?>

<main class="site-main">

    <div class="container">
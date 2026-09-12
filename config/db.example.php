<?php

/*
 * GlobeTrek Adventures
 * Example PDO Database Configuration
 *
 * Copy this file to:
 *
 * config/db.php
 *
 * Then update the credentials for your local environment.
 */

$host = 'localhost';
$db_name = 'globetrek';
$db_user = 'root';
$db_password = '';

$dsn = "mysql:host=$host;dbname=$db_name;charset=utf8mb4";

try {

    $pdo = new PDO(
        $dsn,
        $db_user,
        $db_password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );

} catch (PDOException $exception) {

    error_log(
        'GlobeTrek database connection error: '
        . $exception->getMessage()
    );

    die(
        'Database connection failed. '
        . 'Please check your local database configuration.'
    );
}
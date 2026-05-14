<?php

/**
 * config.example.php
 *
 * Copy this file to config.php and fill in your database credentials.
 * Never commit config.php to version control.
 */

declare(strict_types=1);

define('DB_HOST', 'localhost');
define('DB_USER', 'your_db_user');
define('DB_PASS', 'your_db_password');
define('DB_NAME', 'your_db_name');

$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($mysqli->connect_errno) {
    error_log('MySQL connection failed: ' . $mysqli->connect_error);
    http_response_code(500);
    exit('Database connection error.');
}

$mysqli->set_charset('utf8mb4');

<?php
// Simple database connection file using mysqli.
// Adjust credentials if your MySQL setup is different.

$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'covid_db';

$connect = mysqli_connect($host, $user, $pass, $db);

if (!$connect) {
    die('Database connection failed: ' . mysqli_connect_error());
}

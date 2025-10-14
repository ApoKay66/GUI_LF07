<?php
date_default_timezone_set('Europe/Berlin');
error_reporting(E_ALL);
ini_set('display_errors', 1);

// db.php
define('DB_HOST', '127.0.0.1');
define('DB_USER', 'PIOS');
define('DB_PASS', '@M0Gu$27');
define('DB_NAME', 'Sentinel');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die("❌ Datenbankverbindung fehlgeschlagen: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");
?>

<?php
// AyoFoods - Database Configuration
// Update these values with your hosting credentials

define('DB_HOST', 'localhost');
define('DB_USER', 'root');        // Change to your DB username
define('DB_PASS', '');            // Change to your DB password
define('DB_NAME', 'ayofoods');

define('SITE_URL', 'http://localhost/AyoFoods'); // Change to your domain

function getDB() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        http_response_code(500);
        die(json_encode(['error' => 'Database connection failed']));
    }
    $conn->set_charset('utf8mb4');
    return $conn;
}

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

<?php

define('TWILIO_SID', 'AC39ea69eed009c51d0b46cefe184585a2');
define('TWILIO_AUTH_TOKEN', 'fb71f5b9e91db4e47d49317790d78186');
define('TWILIO_PHONE', '+13186595863'); // Twilio phone number

$host = 'localhost';
$dbname = 'aquatrack_db';
$username = 'root';
$password = ''; // Laragon default

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
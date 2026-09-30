<?php
/**
 * db.php — shared database connection + small helpers.
 * Every file in /api includes this first.
 *
 * Edit $DB_USER / $DB_PASS below to match your MySQL setup
 * (XAMPP default is user "root" with an empty password).
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Browsers send a pre-flight OPTIONS request — just acknowledge it.
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$DB_HOST = 'localhost';
$DB_NAME = 'centinel_wfm';
$DB_USER = 'root';
$DB_PASS = 'root@123';

try {
    $pdo = new PDO(
        "mysql:host={$DB_HOST};dbname={$DB_NAME};charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed. Did you import database/schema.sql and set the credentials in api/db.php?']);
    exit;
}

/** Reads the JSON body of the current request into an associative array. */
function input(): array
{
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

/** Sends a JSON error response and stops execution. */
function fail(int $code, string $message): void
{
    http_response_code($code);
    echo json_encode(['error' => $message]);
    exit;
}

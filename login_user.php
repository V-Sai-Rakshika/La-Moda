<?php
ob_start();
session_start();
include "db.php";
include "auth.php";

function send(array $data): void {
    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') send(['error' => 'Invalid method']);

csrf_verify();

$username = clean($_POST['username'] ?? '', 50);
$password = $_POST['password'] ?? '';

if (!$username || !$password) send(['error' => 'Username and password are required']);

// Lookup user in MongoDB
$user = $users->findOne(['username' => $username]);

if (!$user) send(['error' => 'Invalid username or password']);

// Verify hashed password
if (!password_verify($password, $user['password'])) {
    send(['error' => 'Invalid username or password']);
}

// Regenerate session ID to prevent session fixation
session_regenerate_id(true);

$_SESSION['user'] = [
    'name'     => (string)$user['name'],
    'username' => (string)$user['username'],
];
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

send(['success' => true]);
 
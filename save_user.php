<?php
ob_start();
session_start();
include __DIR__ . "/db.php";
include __DIR__ . "/auth.php";

function send(array $data): void {
    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') send(['error' => 'Invalid method']);
csrf_verify();

$name     = clean($_POST['name']     ?? '', 100);
$username = clean($_POST['username'] ?? '', 50);
$phone    = clean($_POST['phone']    ?? '', 20);
$gender   = clean($_POST['gender']   ?? '', 20);
$password = $_POST['password'] ?? '';
$confirm  = $_POST['confirm']  ?? '';

if (!$name || !$username || !$password) send(['error' => 'Name, username and password are required']);
if ($password !== $confirm) send(['error' => 'Passwords do not match']);

// ── Strong password rules ──
if (strlen($password) < 8)                     send(['error' => 'Password must be at least 8 characters']);
if (!preg_match('/[a-z]/', $password))          send(['error' => 'Password must contain at least 1 lowercase letter']);
if (!preg_match('/[A-Z]/', $password))          send(['error' => 'Password must contain at least 1 uppercase letter']);
if (!preg_match('/[0-9]/', $password))          send(['error' => 'Password must contain at least 1 number']);
if (!preg_match('/[^a-zA-Z0-9]/', $password))  send(['error' => 'Password must contain at least 1 special character']);

if (!preg_match('/^[a-zA-Z0-9_]{3,50}$/', $username))
    send(['error' => 'Username must be 3–50 letters, numbers or underscores']);

$existing = $users->findOne(['username' => $username]);
if ($existing) send(['error' => 'Username already taken. Please choose another.']);

$users->insertOne([
    'name'       => $name,
    'username'   => $username,
    'phone'      => $phone,
    'gender'     => $gender,
    'password'   => password_hash($password, PASSWORD_BCRYPT),
    'created_at' => date('Y-m-d H:i:s'),
]);

$_SESSION['user'] = ['name' => $name, 'username' => $username];
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
send(['success' => true]);
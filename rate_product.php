<?php
ob_start();
session_start();
include __DIR__ . "/db.php";
include __DIR__ . "/auth.php";

function send(array $d): void {
    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode($d);
    exit();
}

if (!is_logged_in())                       send(['error' => 'Login required']);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') send(['error' => 'Invalid method']);

csrf_verify();

$productName = clean($_POST['product_name'] ?? '', 200);
$stars       = (int)($_POST['stars'] ?? 0);
$review      = clean($_POST['review'] ?? '', 500);
$username    = current_user()['username'];

if (!$productName)            send(['error' => 'Product name required']);
if ($stars < 1 || $stars > 5) send(['error' => 'Rating must be between 1 and 5']);

// Verify product exists
$product = $products->findOne(['name' => $productName]);
if (!$product) send(['error' => 'Product not found']);

// Upsert — one rating per user per product
$ratings->updateOne(
    ['username' => $username, 'product_name' => $productName],
    ['$set' => [
        'username'     => $username,
        'product_name' => $productName,
        'stars'        => $stars,
        'review'       => $review,
        'rated_at'     => date('Y-m-d H:i:s'),
    ]],
    ['upsert' => true]
);

send(['success' => true]);
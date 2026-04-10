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

if (!is_logged_in()) send(['error' => 'login required']);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') send(['error' => 'Invalid method']);
csrf_verify();

$name = clean($_POST['name'] ?? '', 200);
$size = clean($_POST['size'] ?? '', 10);
if (!$name) send(['error' => 'Product name is required']);

// Always fetch price from DB — never trust client
$product = $products->findOne(['name' => $name]);
if (!$product) send(['error' => 'Product not found']);

// Stock check
$stock = (int)($product['stock'] ?? 999);
if ($stock <= 0) send(['error' => 'Sorry, this product is out of stock']);

$price    = (int)($product['new_price'] ?? 0);
$image    = (string)($product['image'] ?? '');
$imgSrc   = (strpos($image, 'http') === 0) ? $image : "images/" . $image;
$username = current_user()['username'];

// Load cart from MongoDB
$userDoc = $users->findOne(['username' => $username]);
$cart    = is_array($userDoc['cart'] ?? null) ? $userDoc['cart'] : [];

$found = false;
foreach ($cart as &$item) {
    if (($item['name'] ?? '') === $name && ($item['size'] ?? '') === $size) {
        if ($item['qty'] >= $stock) send(['error' => "Only $stock left in stock"]);
        $item['qty']++;
        $found = true;
        break;
    }
}
unset($item);

if (!$found) {
    $cart[] = [
        'name'  => $name,
        'price' => $price,
        'image' => $imgSrc,
        'qty'   => 1,
        'size'  => $size,
        'stock' => $stock,
    ];
}

// Save back to MongoDB
$users->updateOne(
    ['username' => $username],
    ['$set' => ['cart' => array_values($cart)]]
);
$_SESSION['cart'] = array_values($cart);

send([
    'success'    => true,
    'cart_count' => count($cart),
    'price'      => $price,
    'image'      => $imgSrc,
]);
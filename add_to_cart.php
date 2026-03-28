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


if (!is_logged_in())                       send(['error' => 'login required']);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') send(['error' => 'Invalid method']);


csrf_verify();


$name = clean($_POST['name'] ?? '', 200);
if (!$name) send(['error' => 'Product name is required']);


// Always fetch price from DB — never trust client
$product = $products->findOne(['name' => $name]);
if (!$product) send(['error' => 'Product not found']);


$price  = (int)($product['new_price'] ?? 0);
$image  = (string)($product['image']  ?? '');
$imgSrc = (strpos($image, 'http') === 0) ? $image : "images/" . $image;


if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) $_SESSION['cart'] = [];


$found = false;
foreach ($_SESSION['cart'] as &$item) {
    if (isset($item['name']) && $item['name'] === $name) {
        $item['qty']++;
        $found = true;
        break;
    }
}
unset($item);


if (!$found) {
    $_SESSION['cart'][] = ['name' => $name, 'price' => $price, 'image' => $imgSrc, 'qty' => 1];
}


send(['success' => true, 'cart_count' => count($_SESSION['cart']), 'price' => $price, 'image' => $imgSrc]);

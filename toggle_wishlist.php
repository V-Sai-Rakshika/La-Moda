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

if (!is_logged_in())                      send(['error' => 'login required']);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') send(['error' => 'Invalid method']);

csrf_verify();

$name   = clean($_POST['name']   ?? '', 200);
$action = clean($_POST['action'] ?? '', 10);

if (!$name || !in_array($action, ['add', 'remove'])) send(['error' => 'Invalid input']);

$username = current_user()['username'];

// ── Fetch real product data from DB ──
$product = $products->findOne(['name' => $name]);
if (!$product) send(['error' => 'Product not found']);

$price  = (int)($product['new_price'] ?? 0);
$image  = (string)($product['image']  ?? '');
$imgSrc = (strpos($image, 'http') === 0) ? $image : "images/" . $image;

if ($action === 'add') {
    // Upsert into wishlist collection
    $wishlist->updateOne(
        ['username' => $username, 'product_name' => $name],
        ['$set' => [
            'username'     => $username,
            'product_name' => $name,
            'price'        => $price,
            'image'        => $imgSrc,
            'added_at'     => date('Y-m-d H:i:s'),
        ]],
        ['upsert' => true]
    );
} else {
    $wishlist->deleteOne(['username' => $username, 'product_name' => $name]);
}

// Keep session in sync for navbar count
$_SESSION['wishlist'] = array_map(
    fn($doc) => (string)$doc['product_name'],
    iterator_to_array($wishlist->find(['username' => $username], ['projection' => ['product_name' => 1]]))
);

send(['success' => true, 'action' => $action]);
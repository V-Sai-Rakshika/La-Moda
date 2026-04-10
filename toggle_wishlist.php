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

$name   = clean($_POST['name']   ?? '', 200);
$action = clean($_POST['action'] ?? '', 10);
if (!$name || !in_array($action, ['add', 'remove'])) send(['error' => 'Invalid input']);

$username = current_user()['username'];

// ■■ Fetch real product data from DB ■■
$product = $products->findOne(['name' => $name]);
if (!$product) send(['error' => 'Product not found']);

$price  = (int)($product['new_price'] ?? 0);
$image  = (string)($product['image'] ?? '');
$imgSrc = (strpos($image, 'http') === 0) ? $image : "images/" . $image;

// Load current wishlist_items array from user doc
$userDoc   = $users->findOne(['username' => $username]);
$wishItems = is_array($userDoc['wishlist_items'] ?? null) ? $userDoc['wishlist_items'] : [];

if ($action === 'add') {
    // Upsert into wishlist collection (for wishlist page)
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
    // Also push into users.wishlist_items for navbar badge sync
    if (!in_array($name, $wishItems)) {
        $wishItems[] = $name;
    }
} else {
    // Remove from wishlist collection
    $wishlist->deleteOne(['username' => $username, 'product_name' => $name]);
    // Remove from users.wishlist_items
    $wishItems = array_values(array_filter($wishItems, fn($n) => $n !== $name));
}

// Save updated wishlist_items back to user doc
$users->updateOne(
    ['username' => $username],
    ['$set' => ['wishlist_items' => array_values($wishItems)]]
);

// Keep session in sync
$_SESSION['wishlist'] = $wishItems;

send(['success' => true, 'action' => $action]);
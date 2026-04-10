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

$name   = clean($_POST['name'] ?? '', 200);
$size   = clean($_POST['size'] ?? '', 10);
$action = clean($_POST['action'] ?? '', 10);

if (!$name || !in_array($action, ['plus', 'minus'])) send(['error' => 'Invalid input']);

$username = current_user()['username'];
$userDoc  = $users->findOne(['username' => $username]);
$cart     = is_array($userDoc['cart'] ?? null) ? $userDoc['cart'] : [];

$removed  = false;
$newQty   = 0;

foreach ($cart as $i => &$item) {
    if (($item['name'] ?? '') === $name && ($item['size'] ?? '') === $size) {
        $stock = (int)($item['stock'] ?? 999);
        if ($action === 'plus') {
            if ($item['qty'] >= $stock) send(['error' => "Only $stock left in stock"]);
            $item['qty']++;
        } else {
            $item['qty']--;
        }
        if ($item['qty'] <= 0) {
            array_splice($cart, $i, 1);
            $removed = true;
        } else {
            $newQty = $item['qty'];
        }
        break;
    }
}
unset($item);

$cart = array_values($cart);
$users->updateOne(['username' => $username], ['$set' => ['cart' => $cart]]);
$_SESSION['cart'] = $cart;

if ($removed) send(['removed' => true, 'cart_count' => count($cart)]);
send(['qty' => $newQty, 'cart_count' => count($cart)]);
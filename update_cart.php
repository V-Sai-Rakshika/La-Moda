<?php
ob_start();
session_start();
include "auth.php";

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

if (!$name || !in_array($action, ['plus', 'minus'])) send(['error' => 'Invalid input']);

if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

foreach ($_SESSION['cart'] as $i => &$item) {
    if (isset($item['name']) && $item['name'] === $name) {
        $item['qty'] = ($action === 'plus') ? $item['qty'] + 1 : $item['qty'] - 1;

        if ($item['qty'] <= 0) {
            unset($_SESSION['cart'][$i]);
            $_SESSION['cart'] = array_values($_SESSION['cart']);
            send(['removed' => true]);
        }

        send(['qty' => $item['qty']]);
    }
}
unset($item);

send(['error' => 'Item not found in cart']);
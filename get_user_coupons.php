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

if (!is_logged_in()) send(['coupons' => []]);

$username = current_user()['username'];
$cursor   = $db->coupons->find(
    ['username' => $username, 'used' => false],
    ['sort' => ['created_at' => -1], 'limit' => 10]
);

$coupons = [];
foreach ($cursor as $c) {
    $coupons[] = [
        'code'     => (string)($c['code']     ?? ''),
        'discount' => (int)($c['discount']    ?? 5),
    ];
}

send(['coupons' => $coupons]);
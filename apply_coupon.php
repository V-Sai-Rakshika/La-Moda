<?php
ob_start();
session_start();
include __DIR__ . "/db.php";
include __DIR__ . "/auth.php";

function send(array $d): void { ob_end_clean(); header('Content-Type: application/json'); echo json_encode($d); exit(); }

if (!is_logged_in())                       send(['error' => 'Not logged in']);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') send(['error' => 'Invalid method']);
csrf_verify();

$code     = clean($_POST['code'] ?? '', 20);
$username = current_user()['username'];

if (!$code) send(['error' => 'No coupon code provided']);

$coupon = $db->coupons->findOne(['code' => $code, 'username' => $username, 'used' => false]);
if (!$coupon) send(['error' => 'Invalid or already used coupon code']);

send(['success' => true, 'discount' => (int)($coupon['discount'] ?? 5)]);
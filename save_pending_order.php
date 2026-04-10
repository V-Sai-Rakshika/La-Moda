<?php
/**
 * save_pending_order.php
 * Called by cart.php / navbar.php BEFORE launching Cashfree UPI.
 * Saves the order with status='pending_payment' so payment_return.php
 * can finalize it and award the coupon.
 */
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

if (!is_logged_in()) send(['error' => 'Not logged in']);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') send(['error' => 'Invalid method']);
csrf_verify();

$username  = current_user()['username'];
$isBuyNow  = ($_POST['order_type'] ?? '') === 'buynow';

// ── Load cart items ──
if ($isBuyNow) {
    $bnName = clean($_POST['bn_product_name'] ?? '', 200);
    $bnSize = clean($_POST['bn_size'] ?? '', 10);
    $bnQty  = max(1, (int)($_POST['bn_qty'] ?? 1));
    $product = $products->findOne(['name' => $bnName]);
    if (!$product) send(['error' => 'Product not found']);
    $bnPrice = (int)($product['new_price'] ?? 0);
    $bnImg   = (string)($product['image'] ?? '');
    $bnImg   = (strpos($bnImg, 'http') === 0) ? $bnImg : "images/" . $bnImg;
    $cartItems = [[
        'name'  => $bnName, 'price' => $bnPrice,
        'image' => $bnImg,  'qty'   => $bnQty, 'size' => $bnSize,
    ]];
} else {
    $userDoc   = $users->findOne(['username' => $username]);
    $cartItems = is_array($userDoc['cart'] ?? null) ? $userDoc['cart'] : [];
    if (empty($cartItems)) send(['error' => 'Cart is empty']);
}

// ── Address ──
$cleaned = [];
if (!empty($_POST['using_saved'])) {
    $addrIdx = (int)($_POST['use_saved_addr'] ?? 0);
    $u       = $users->findOne(['username' => $username]);
    $addrs   = is_array($u['addresses'] ?? null) ? $u['addresses'] : [];
    if (empty($addrs[$addrIdx])) send(['error' => 'Saved address not found']);
    $sa      = $addrs[$addrIdx];
    $cleaned = [
        'country'  => (string)($sa['country']  ?? ''),
        'full_name'=> (string)($sa['full_name'] ?? ''),
        'mobile'   => (string)($sa['mobile']    ?? ''),
        'email'    => (string)($sa['email']     ?? ''),
        'flat'     => (string)($sa['flat']      ?? ''),
        'area'     => (string)($sa['area']      ?? ''),
        'landmark' => (string)($sa['landmark']  ?? ''),
        'pincode'  => (string)($sa['pincode']   ?? ''),
        'city'     => (string)($sa['city']      ?? ''),
        'state'    => (string)($sa['state']     ?? ''),
    ];
} else {
    $cleaned = [
        'country'  => clean($_POST['country']   ?? '', 100),
        'full_name'=> clean($_POST['full_name']  ?? '', 100),
        'mobile'   => clean($_POST['mobile']     ?? '', 10),
        'email'    => clean($_POST['email']      ?? '', 150),
        'flat'     => clean($_POST['flat']       ?? '', 200),
        'area'     => clean($_POST['area']       ?? '', 200),
        'landmark' => clean($_POST['landmark']   ?? '', 200),
        'pincode'  => clean($_POST['pincode']    ?? '', 6),
        'city'     => clean($_POST['city']       ?? '', 100),
        'state'    => clean($_POST['state']      ?? '', 100),
    ];
}

// ── Totals ──
$sub      = array_sum(array_map(fn($i) => (int)($i['price'] ?? 0) * (int)($i['qty'] ?? 1), $cartItems));
$delivery = 0;
if ($sub > 0 && $sub < 500)          $delivery = 50;
elseif ($sub >= 500 && $sub <= 1000) $delivery = 40;

$couponCode     = clean($_POST['coupon_code'] ?? '', 20);
$couponDiscount = 0;
if ($couponCode) {
    $coupon = $db->coupons->findOne(['code' => $couponCode, 'username' => $username, 'used' => false]);
    if ($coupon) $couponDiscount = (int)($coupon['discount'] ?? 5);
}
$total = $sub + $delivery;
if ($couponDiscount > 0) $total = (int)round($total * (1 - $couponDiscount / 100));

// ── Save pending order ──
$cashfreeOrderId = clean($_POST['cashfree_order_id'] ?? '', 100);

$pendingDoc = [
    'username'         => $username,
    'order_type'       => $isBuyNow ? 'buynow' : 'cart',
    'item_name'        => $isBuyNow ? ($cartItems[0]['name'] ?? '') : '__cart__',
    'cart_items'       => $cartItems,
    'subtotal'         => $sub,
    'delivery_charge'  => $delivery,
    'coupon_used'      => $couponCode,
    'coupon_discount'  => $couponDiscount,
    'item_price'       => $total,
    'payment_method'   => 'upi',
    'status'           => 'pending_payment',
    'cashfree_order_id'=> $cashfreeOrderId,
    'placed_at'        => date('Y-m-d H:i:s'),
    'full_name'        => $cleaned['full_name'],
    'mobile'           => $cleaned['mobile'],
    'email'            => $cleaned['email'] ?? '',
    'country'          => $cleaned['country'],
    'flat'             => $cleaned['flat'],
    'area'             => $cleaned['area'],
    'landmark'         => $cleaned['landmark'],
    'pincode'          => $cleaned['pincode'],
    'city'             => $cleaned['city'],
    'state'            => $cleaned['state'] ?? '',
];

$result = $orders->insertOne($pendingDoc);
$pendingId = (string)$result->getInsertedId();

// Store in session so payment_return.php can find it
$_SESSION['pending_upi_order_id'] = $pendingId;
$_SESSION['pending_upi_username'] = $username;

send(['success' => true, 'pending_id' => $pendingId]);
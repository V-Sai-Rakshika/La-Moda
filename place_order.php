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

if (!is_logged_in()) send(['error' => 'Please log in to place an order']);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') send(['error' => 'Invalid method']);
csrf_verify();

$username  = current_user()['username'];
$isBuyNow  = ($_POST['order_type'] ?? '') === 'buynow';

// ── Load cart items ──
if ($isBuyNow) {
    // Buy Now: single product from POST
    $bnName  = clean($_POST['bn_product_name'] ?? '', 200);
    $bnSize  = clean($_POST['bn_size'] ?? '', 10);
    $bnQty   = max(1, (int)($_POST['bn_qty'] ?? 1));
    $product = $products->findOne(['name' => $bnName]);
    if (!$product) send(['error' => 'Product not found']);
    $bnPrice = (int)($product['new_price'] ?? 0);
    $bnImg   = (string)($product['image'] ?? '');
    $bnImg   = (strpos($bnImg, 'http') === 0) ? $bnImg : "images/" . $bnImg;
    $cartItems = [[
        'name'  => $bnName,
        'price' => $bnPrice,
        'image' => $bnImg,
        'qty'   => $bnQty,
        'size'  => $bnSize,
    ]];
} else {
    // Cart checkout: load from MongoDB
    $userDoc   = $users->findOne(['username' => $username]);
    $cartItems = is_array($userDoc['cart'] ?? null) ? $userDoc['cart'] : [];
    if (empty($cartItems)) send(['error' => 'Your cart is empty']);
}

// ── Resolve address ──
$cleaned = [];
if (!empty($_POST['using_saved'])) {
    $addrIdx = (int)($_POST['use_saved_addr'] ?? 0);
    $u       = $users->findOne(['username' => $username]);
    $addrs   = is_array($u['addresses'] ?? null) ? $u['addresses'] : [];
    if (empty($addrs[$addrIdx])) send(['error' => 'Saved address not found']);
    $sa      = $addrs[$addrIdx];
    $cleaned = [
        'country'   => (string)($sa['country']   ?? ''),
        'full_name' => (string)($sa['full_name']  ?? ''),
        'mobile'    => (string)($sa['mobile']     ?? ''),
        'email'     => (string)($sa['email']      ?? ''),
        'flat'      => (string)($sa['flat']       ?? ''),
        'area'      => (string)($sa['area']       ?? ''),
        'landmark'  => (string)($sa['landmark']   ?? ''),
        'pincode'   => (string)($sa['pincode']    ?? ''),
        'city'      => (string)($sa['city']       ?? ''),
        'state'     => (string)($sa['state']      ?? ''),
        'delivery_instructions' => (string)($sa['delivery_instructions'] ?? ''),
    ];
} else {
    $full_name = clean($_POST['full_name'] ?? '', 100);
    $mobile    = clean($_POST['mobile']    ?? '', 10);
    $pincode   = clean($_POST['pincode']   ?? '', 6);
    $country   = clean($_POST['country']   ?? '', 100);
    $flat      = clean($_POST['flat']      ?? '', 200);
    $area      = clean($_POST['area']      ?? '', 200);
    $landmark  = clean($_POST['landmark']  ?? '', 200);
    $city      = clean($_POST['city']      ?? '', 100);
    $state     = clean($_POST['state']     ?? '', 100);
    $email     = clean($_POST['email']     ?? '', 150);
    $delivery_instructions = clean($_POST['delivery_instructions'] ?? '', 500);

    if (!$full_name) send(['error' => 'Full Name is required']);
    if (!preg_match('/^[A-Za-z\s]{2,100}$/', $full_name))
        send(['error' => 'Full Name must contain letters only']);
    if (!$mobile) send(['error' => 'Mobile Number is required']);
    if (!preg_match('/^[6-9][0-9]{9}$/', $mobile))
        send(['error' => 'Enter a valid 10-digit mobile number']);
    if (!$pincode) send(['error' => 'Pincode is required']);
    if (!preg_match('/^[1-9][0-9]{5}$/', $pincode))
        send(['error' => 'Enter a valid 6-digit pincode']);
    if (!$country) send(['error' => 'Country is required']);
    if (!$flat)    send(['error' => 'Flat/House No. is required']);
    if (!$area)    send(['error' => 'Area/Street is required']);
    if (!$landmark) send(['error' => 'Landmark is required']);
    if (!$city)    send(['error' => 'City is required']);

    $cleaned = compact('country','full_name','mobile','email','flat','area','landmark','pincode','city','state','delivery_instructions');

    // Save address if requested
    if (!empty($_POST['save_address'])) {
        $addrEntry = $cleaned;
        $addrEntry['is_default'] = !empty($_POST['is_default']) ? true : false;
        $u = $users->findOne(['username' => $username]);
        $addrs = is_array($u['addresses'] ?? null) ? $u['addresses'] : [];
        // If set as default, unset others
        if ($addrEntry['is_default']) {
            foreach ($addrs as &$a) $a['is_default'] = false;
            unset($a);
        }
        $addrs[] = $addrEntry;
        $users->updateOne(['username' => $username], ['$set' => ['addresses' => $addrs]]);
    }
}

// ── Calculate totals ──
$sub = array_sum(array_map(fn($i) => (int)($i['price'] ?? 0) * (int)($i['qty'] ?? 1), $cartItems));
$delivery = 0;
if ($sub > 0 && $sub <= 1000) $delivery = ($sub < 500) ? 50 : 40;
if ($sub > 1000) $delivery = 0;

// ── Apply coupon ──
$couponCode    = clean($_POST['coupon_code'] ?? '', 20);
$couponDiscount = 0;
if ($couponCode) {
    $coupon = $db->coupons->findOne(['code' => $couponCode, 'username' => $username, 'used' => false]);
    if ($coupon) {
        $couponDiscount = (int)($coupon['discount'] ?? 5);
    }
}

$total = $sub + $delivery;
if ($couponDiscount > 0) $total = (int)round($total * (1 - $couponDiscount / 100));

// ── Payment method ──
$paymentMethod = clean($_POST['payment_method'] ?? 'cod', 20);

// ── Save order ──
$orderDoc = [
    'username'        => $username,
    'order_type'      => $isBuyNow ? 'buynow' : 'cart',
    'item_name'       => $isBuyNow ? $bnName : '__cart__',
    'cart_items'      => $cartItems,
    'subtotal'        => $sub,
    'delivery_charge' => $delivery,
    'coupon_used'     => $couponCode,
    'coupon_discount' => $couponDiscount,
    'item_price'      => $total,
    'payment_method'  => $paymentMethod,
    'status'          => 'placed',
    'placed_at'       => date('Y-m-d H:i:s'),
    'full_name'       => $cleaned['full_name'],
    'mobile'          => $cleaned['mobile'],
    'email'           => $cleaned['email'] ?? '',
    'country'         => $cleaned['country'],
    'flat'            => $cleaned['flat'],
    'area'            => $cleaned['area'],
    'landmark'        => $cleaned['landmark'],
    'pincode'         => $cleaned['pincode'],
    'city'            => $cleaned['city'],
    'state'           => $cleaned['state'] ?? '',
    'delivery_instructions' => $cleaned['delivery_instructions'] ?? '',
];

$result = $orders->insertOne($orderDoc);

// ── Mark coupon used ──
if ($couponCode && $couponDiscount > 0) {
    $db->coupons->updateOne(
        ['code' => $couponCode, 'username' => $username],
        ['$set' => ['used' => true, 'used_at' => date('Y-m-d H:i:s')]]
    );
}

// ── Award new coupon ──
$newCouponCode = strtoupper(substr(md5(uniqid($username, true)), 0, 7));
$newDiscount   = rand(5, 10);
$db->coupons->insertOne([
    'code'       => $newCouponCode,
    'username'   => $username,
    'discount'   => $newDiscount,
    'used'       => false,
    'created_at' => date('Y-m-d H:i:s'),
    'expires_at' => date('Y-m-d H:i:s', strtotime('+30 days')),
]);

// ── Clear cart (only for cart checkout, not Buy Now) ──
if (!$isBuyNow) {
    $users->updateOne(['username' => $username], ['$set' => ['cart' => []]]);
    $_SESSION['cart'] = [];
}

send([
    'success'  => true,
    'order_id' => (string)$result->getInsertedId(),
    'coupon'   => ['code' => $newCouponCode, 'discount' => $newDiscount],
    'total'    => $total,
]);
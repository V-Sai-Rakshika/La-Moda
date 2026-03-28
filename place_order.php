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

if (!is_logged_in())                       send(['error' => 'Please log in to place an order']);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') send(['error' => 'Invalid method']);
csrf_verify();

$username  = current_user()['username'];
$cartItems = $_SESSION['cart'] ?? [];

// ── Resolve address ──
$cleaned = [];

if (!empty($_POST['using_saved'])) {
    // Use saved address from user profile
    $addrIdx = (int)($_POST['use_saved_addr'] ?? 0);
    $u = $users->findOne(['username' => $username]);
    $addrs = [];
    if (!empty($u['addresses'])) {
        $addrs = is_array($u['addresses']) ? $u['addresses'] : iterator_to_array($u['addresses']);
    }
    if (empty($addrs[$addrIdx])) send(['error' => 'Saved address not found']);
    $sa = $addrs[$addrIdx];
    $cleaned = [
        'country'   => (string)($sa['country']   ?? ''),
        'full_name' => (string)($sa['full_name']  ?? ''),
        'mobile'    => (string)($sa['mobile']     ?? ''),
        'flat'      => (string)($sa['flat']       ?? ''),
        'area'      => (string)($sa['area']       ?? ''),
        'landmark'  => (string)($sa['landmark']   ?? ''),
        'pincode'   => (string)($sa['pincode']    ?? ''),
        'city'      => (string)($sa['city']       ?? ''),
        'state'     => '',
    ];
} else {
    // Manual address — validate
    $full_name = clean($_POST['full_name'] ?? '', 100);
    $mobile    = clean($_POST['mobile']    ?? '', 10);
    $pincode   = clean($_POST['pincode']   ?? '', 6);
    $country   = clean($_POST['country']   ?? '', 100);
    $flat      = clean($_POST['flat']      ?? '', 200);
    $area      = clean($_POST['area']      ?? '', 200);
    $landmark  = clean($_POST['landmark']  ?? '', 200);
    $city      = clean($_POST['city']      ?? '', 100);

    if (!$full_name) send(['error' => 'Full Name is required']);
    if (!preg_match('/^[A-Za-z\s]{2,100}$/', $full_name))
        send(['error' => 'Full Name must contain letters only']);
    if (!$mobile) send(['error' => 'Mobile Number is required']);
    if (!preg_match('/^[6-9][0-9]{9}$/', $mobile))
        send(['error' => 'Enter valid 10-digit mobile number']);
    if (!$pincode) send(['error' => 'Pincode is required']);
    if (!preg_match('/^[1-9][0-9]{5}$/', $pincode))
        send(['error' => 'Enter valid 6-digit pincode']);
    if (!$country)  send(['error' => 'Country is required']);
    if (!$flat)     send(['error' => 'Flat / House No. is required']);
    if (!$area)     send(['error' => 'Area / Street is required']);
    if (!$landmark) send(['error' => 'Landmark is required']);
    if (!$city)     send(['error' => 'City is required']);

    $cleaned = compact('country','full_name','mobile','flat','area','landmark','pincode','city');
    $cleaned['state'] = '';

    // Save address if requested
    if (!empty($_POST['save_address'])) {
        $addrToSave = $cleaned;
        $addrToSave['email'] = clean($_POST['email'] ?? '', 150);
        $users->updateOne(
            ['username' => $username],
            ['$addToSet' => ['addresses' => $addrToSave]]
        );
    }
}

$email         = clean($_POST['email']                ?? '', 150);
$instructions  = clean($_POST['delivery_instructions'] ?? '', 500);
$paymentMethod = clean($_POST['payment_method']        ?? 'cod', 20);
$couponCode    = clean($_POST['coupon_code']            ?? '', 20);
$itemName      = clean($_POST['item_name']              ?? '', 200);
$isCart        = ($itemName === '__cart__' || $itemName === '');

// ── Coupon validation ──
$couponDiscount = 0;
if ($couponCode) {
    $coupon = $db->coupons->findOne([
        'code'     => $couponCode,
        'username' => $username,
        'used'     => false,
    ]);
    if ($coupon) {
        $couponDiscount = (int)($coupon['discount'] ?? 0);
    }
}

// ── Build order ──
if ($isCart) {
    if (empty($cartItems)) send(['error' => 'Your cart is empty']);
    $rawTotal = array_sum(array_map(fn($i) => (int)($i['price']??0)*(int)($i['qty']??1), $cartItems));
    $delivery = 0;
    if ($rawTotal > 0 && $rawTotal < 500)        $delivery = 50;
    elseif ($rawTotal >= 500 && $rawTotal <= 1000) $delivery = 40;
    $total = $rawTotal + $delivery;
    if ($couponDiscount > 0) $total = (int)round($total * (1 - $couponDiscount/100));

    $order = array_merge($cleaned, [
        'username'        => $username,
        'email'           => $email,
        'item_name'       => '__cart__',
        'item_price'      => $total,
        'cart_items'      => $cartItems,
        'payment_method'  => $paymentMethod,
        'coupon_used'     => $couponCode ?: null,
        'coupon_discount' => $couponDiscount,
        'instructions'    => $instructions,
        'status'          => 'placed',
        'placed_at'       => date('Y-m-d H:i:s'),
    ]);
} else {
    $product = $products->findOne(['name' => $itemName]);
    if (!$product) send(['error' => 'Product not found: ' . $itemName]);
    $rawPrice = (int)($product['new_price'] ?? 0);
    $delivery = 0;
    if ($rawPrice > 0 && $rawPrice < 500)         $delivery = 50;
    elseif ($rawPrice >= 500 && $rawPrice <= 1000)  $delivery = 40;
    $total = $rawPrice + $delivery;
    if ($couponDiscount > 0) $total = (int)round($total * (1 - $couponDiscount/100));

    $order = array_merge($cleaned, [
        'username'        => $username,
        'email'           => $email,
        'item_name'       => $itemName,
        'item_price'      => $total,
        'payment_method'  => $paymentMethod,
        'coupon_used'     => $couponCode ?: null,
        'coupon_discount' => $couponDiscount,
        'instructions'    => $instructions,
        'status'          => 'placed',
        'placed_at'       => date('Y-m-d H:i:s'),
    ]);
}

$orders->insertOne($order);

// Mark coupon as used
if ($couponCode && $couponDiscount > 0) {
    $db->coupons->updateOne(
        ['code' => $couponCode, 'username' => $username],
        ['$set' => ['used' => true, 'used_at' => date('Y-m-d H:i:s')]]
    );
}

// Clear cart only for cart checkout
if ($isCart) $_SESSION['cart'] = [];

// Award new coupon to user
$chars       = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
$newCode     = '';
for ($i = 0; $i < 7; $i++) $newCode .= $chars[random_int(0, strlen($chars)-1)];
$discountPct = random_int(5, 10);

$db->coupons->insertOne([
    'code'       => $newCode,
    'username'   => $username,
    'discount'   => $discountPct,
    'used'       => false,
    'created_at' => date('Y-m-d H:i:s'),
]);

send([
    'success' => true,
    'coupon'  => ['code' => $newCode, 'discount' => $discountPct],
]);
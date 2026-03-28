<?php
session_start();
include __DIR__ . '/db.php';
include __DIR__ . '/auth.php';

echo "<h2>Checkout Debug</h2>";
echo "<pre style='background:#f5f5f5;padding:15px;font-size:13px;'>";

// Check 1 — is user logged in?
echo "1. Logged in: " . (is_logged_in() ? "✅ YES — " . current_user()['username'] : "❌ NO — you must login first") . "\n";

// Check 2 — cart contents
$cart = $_SESSION['cart'] ?? [];
echo "2. Cart items: " . count($cart) . "\n";
foreach ($cart as $i => $item) {
    echo "   [{$i}] " . ($item['name'] ?? '?') . " × " . ($item['qty'] ?? 1) . " @ ₹" . ($item['price'] ?? 0) . "\n";
}

// Check 3 — CSRF token
echo "3. CSRF token in session: " . (!empty($_SESSION['csrf_token']) ? "✅ " . substr($_SESSION['csrf_token'],0,16) . "..." : "❌ MISSING") . "\n";

// Check 4 — orders collection writable?
try {
    $testInsert = $orders->insertOne(['_test' => true, 'delete_me' => true]);
    $orders->deleteOne(['_id' => $testInsert->getInsertedId()]);
    echo "4. Orders collection writable: ✅ YES\n";
} catch (\Exception $e) {
    echo "4. Orders collection writable: ❌ ERROR — " . $e->getMessage() . "\n";
}

// Check 5 — what does place_order.php actually return?
echo "\n5. Simulating place_order.php POST...\n";

if (is_logged_in() && !empty($cart)) {
    $postData = [
        'csrf_token'            => $_SESSION['csrf_token'] ?? '',
        'item_name'             => '__cart__',
        'country'               => 'India',
        'state'                 => 'Tamil Nadu',
        'full_name'             => 'Test User',
        'mobile'                => '9999999999',
        'flat'                  => 'No 1 Test Street',
        'area'                  => 'Test Area',
        'landmark'              => 'Test Landmark',
        'pincode'               => '600001',
        'city'                  => 'Chennai',
        'delivery_instructions' => '',
    ];

    $ch = curl_init('http://localhost/LaModa/place_order.php');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIE, 'PHPSESSID=' . session_id());
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    echo "   HTTP Status: $httpCode\n";
    echo "   Response: $response\n";

    $json = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo "\n   ⚠ Response is NOT valid JSON — raw output shown above\n";
        echo "   This usually means PHP threw an error before the JSON was output\n";
    }
} else {
    echo "   ⚠ Skipped — not logged in or cart empty\n";
}

echo "</pre>";
echo "<br><a href='cart.php'>← Back to Cart</a>";
echo "<br><br><b style='color:red'>Delete this file after checking!</b>";
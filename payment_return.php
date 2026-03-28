<?php
/**
 * payment_return.php
 * Cashfree redirects user here after payment (success or failure).
 * Verifies the payment and shows result.
 */

session_start();
include __DIR__ . "/db.php";
include __DIR__ . "/auth.php";

$appId     = getenv('CASHFREE_APP_ID')     ?: 'TEST102xxxxx_your_app_id';
$secretKey = getenv('CASHFREE_SECRET_KEY') ?: 'cfsk_ma_test_xxxxx_your_secret';
$isTest    = true;

$orderId = $_GET['order_id'] ?? '';
$status  = 'unknown';
$message = '';

if ($orderId) {
    // Verify payment status via Cashfree API
    $baseUrl = $isTest
        ? "https://sandbox.cashfree.com/pg/orders/{$orderId}/payments"
        : "https://api.cashfree.com/pg/orders/{$orderId}/payments";

    $ch = curl_init($baseUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'x-client-id: '     . $appId,
            'x-client-secret: ' . $secretKey,
            'x-api-version: 2023-08-01',
        ],
    ]);
    $resp    = curl_exec($ch);
    $httpCode= curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $data = json_decode($resp, true);

    if ($httpCode === 200 && !empty($data)) {
        $payment = is_array($data) ? ($data[0] ?? $data) : $data;
        $status  = strtolower($payment['payment_status'] ?? 'unknown');
    }
}

$success = ($status === 'success');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title><?= $success ? 'Payment Successful' : 'Payment Status' ?> | La Moda</title>
<link rel="stylesheet" href="styles.css">
<style>
body{display:flex;align-items:center;justify-content:center;min-height:100vh;background:#f8f8f7;}
.box{background:#fff;border-radius:16px;padding:48px 36px;text-align:center;max-width:420px;box-shadow:0 4px 24px rgba(0,0,0,.08);}
.icon{font-size:56px;margin-bottom:16px;}
h1{font-size:22px;margin-bottom:8px;font-family:serif;}
p{color:#888;font-size:14px;margin-bottom:20px;}
.btn{padding:12px 28px;background:#8B2500;color:#fff;border:none;border-radius:8px;font-size:14px;font-weight:700;cursor:pointer;text-decoration:none;display:inline-block;}
.order-id{font-size:11px;color:#bbb;margin-top:10px;}
</style>
</head>
<body>
<div class="box">
    <?php if ($success): ?>
    <div class="icon">🎉</div>
    <h1>Payment Successful!</h1>
    <p>Your UPI payment was confirmed.<br>Your order is being processed.</p>
    <a href="index.php" class="btn">Continue Shopping</a>
    <p class="order-id">Order ID: <?= htmlspecialchars($orderId) ?></p>
    <?php else: ?>
    <div class="icon">⚠️</div>
    <h1>Payment <?= htmlspecialchars(ucfirst($status)) ?></h1>
    <p>Your payment could not be completed.<br>No money was deducted.</p>
    <a href="cart.php" class="btn">Return to Cart</a>
    <p class="order-id">Order ID: <?= htmlspecialchars($orderId) ?></p>
    <?php endif; ?>
</div>
</body>
</html>
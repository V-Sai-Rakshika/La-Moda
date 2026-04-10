<?php
/**
 * payment_return.php
 * Cashfree redirects here after UPI payment.
 * Verifies payment → finalizes pending order → awards coupon → shows result.
 */
session_start();
include __DIR__ . "/db.php";
include __DIR__ . "/auth.php";

if (!isset($_ENV['CASHFREE_APP_ID']) || !isset($_ENV['CASHFREE_SECRET_KEY'])) {
    die("Cashfree env not configured");
}

$appId     = $_ENV['CASHFREE_APP_ID'];
$secretKey = $_ENV['CASHFREE_SECRET_KEY'];
$isTest    = true;

$cfOrderId = $_GET['order_id'] ?? '';
$status    = 'unknown';

if ($cfOrderId) {
    $baseUrl = $isTest
        ? "https://sandbox.cashfree.com/pg/orders/{$cfOrderId}/payments"
        : "https://api.cashfree.com/pg/orders/{$cfOrderId}/payments";

    $ch = curl_init($baseUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'x-client-id: '     . $appId,
            'x-client-secret: ' . $secretKey,
            'x-api-version: 2023-08-01',
        ],
    ]);
    $resp     = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $data = json_decode($resp, true);
    if ($httpCode === 200 && !empty($data)) {
        $payment = is_array($data) ? ($data[0] ?? $data) : $data;
        $status  = strtolower($payment['payment_status'] ?? 'unknown');
    }
}

$success     = ($status === 'success');
$couponCode  = '';
$couponDisc  = 0;
$orderFinalized = false;

// ── Finalize pending order on success ──
if ($success) {
    // Try session first, then search DB by cashfree_order_id
    $pendingId  = $_SESSION['pending_upi_order_id'] ?? null;
    $username   = $_SESSION['pending_upi_username']  ?? (is_logged_in() ? current_user()['username'] : null);

    $pendingOrder = null;

    if ($pendingId) {
        try {
            $pendingOrder = $orders->findOne(['_id' => new MongoDB\BSON\ObjectId($pendingId)]);
        } catch (\Throwable $e) { }
    }

    // Fallback: find by cashfree_order_id
    if (!$pendingOrder && $cfOrderId) {
        $pendingOrder = $orders->findOne([
            'cashfree_order_id' => $cfOrderId,
            'status'            => 'pending_payment',
        ]);
    }

    if ($pendingOrder) {
        $username    = (string)($pendingOrder['username'] ?? $username);
        $isBuyNow    = ($pendingOrder['order_type'] ?? '') === 'buynow';
        $couponUsed  = (string)($pendingOrder['coupon_used'] ?? '');
        $couponDiscount = (int)($pendingOrder['coupon_discount'] ?? 0);

        // Update order status to 'placed'
        $orders->updateOne(
            ['_id' => $pendingOrder['_id']],
            ['$set' => [
                'status'             => 'placed',
                'payment_status'     => 'success',
                'cashfree_order_id'  => $cfOrderId,
                'paid_at'            => date('Y-m-d H:i:s'),
            ]]
        );

        // Mark coupon used
        if ($couponUsed && $couponDiscount > 0) {
            $db->coupons->updateOne(
                ['code' => $couponUsed, 'username' => $username],
                ['$set' => ['used' => true, 'used_at' => date('Y-m-d H:i:s')]]
            );
        }

        // Clear cart for cart orders
        if (!$isBuyNow && $username) {
            $users->updateOne(['username' => $username], ['$set' => ['cart' => []]]);
            $_SESSION['cart'] = [];
        }

        // Award new coupon
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

        $couponCode = $newCouponCode;
        $couponDisc = $newDiscount;
        $orderFinalized = true;

        // Clear session
        unset($_SESSION['pending_upi_order_id'], $_SESSION['pending_upi_username']);
    }
}

$redirect = $success ? 'index.php' : 'cart.php?payment=failed';
$waitSecs = 6; // slightly longer so user can read coupon
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title><?= $success ? 'Payment Successful' : 'Payment Failed' ?> | La Moda</title>
    <?php if (!$couponCode): // auto-redirect only if no coupon to show ?>
    <meta http-equiv="refresh" content="<?= $waitSecs ?>;url=<?= htmlspecialchars($redirect) ?>">
    <?php endif; ?>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@700&family=Jost:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        *{box-sizing:border-box;margin:0;padding:0;}
        body{display:flex;align-items:center;justify-content:center;min-height:100vh;
             background:#f8f7f5;font-family:'Jost',sans-serif;padding:20px;}
        .card{background:#fff;border-radius:20px;padding:48px 36px;text-align:center;
              max-width:460px;width:100%;box-shadow:0 8px 40px rgba(0,0,0,.10);}
        .icon{font-size:64px;margin-bottom:16px;line-height:1;}
        h1{font-family:'Cormorant Garamond',serif;font-size:26px;margin-bottom:8px;color:#1e1e1e;}
        .sub{font-size:14px;color:#888;margin-bottom:20px;line-height:1.6;}
        .coupon-box{margin:18px 0;padding:18px;background:#fff5f2;border:1.5px dashed #8B2500;border-radius:12px;}
        .coupon-label{font-size:12px;color:#8B2500;font-weight:600;margin-bottom:6px;}
        .coupon-code{font-size:28px;font-weight:800;color:#8B2500;letter-spacing:3px;font-family:monospace;margin-bottom:4px;}
        .coupon-desc{font-size:12px;color:#888;}
        .coupon-copy{margin-top:8px;padding:6px 16px;background:#8B2500;color:#fff;border:none;border-radius:6px;
                     font-size:12px;font-weight:600;cursor:pointer;font-family:'Jost',sans-serif;}
        .coupon-copy:hover{background:#5c1800;}
        .redirect-bar{height:4px;background:#e8e8e8;border-radius:2px;overflow:hidden;margin:16px 0 8px;}
        .redirect-fill{height:100%;background:#8B2500;border-radius:2px;
            animation:fill <?= $waitSecs ?>s linear forwards;}
        @keyframes fill{from{width:0}to{width:100%}}
        .redirect-text{font-size:11px;color:#bbb;margin-bottom:12px;}
        .btn{display:inline-block;padding:12px 32px;background:#8B2500;color:#fff;border:none;
             border-radius:8px;font-size:14px;font-weight:600;cursor:pointer;text-decoration:none;
             font-family:'Jost',sans-serif;margin-top:8px;}
        .btn:hover{background:#5c1800;}
        .btn-outline{background:#fff;color:#8B2500;border:1.5px solid #8B2500;margin-left:8px;}
        .copied{background:#16a34a!important;}
    </style>
</head>
<body>
<div class="card">
    <?php if ($success): ?>
        <div class="icon">🎉</div>
        <h1>Payment Successful!</h1>
        <p class="sub">
            Your UPI payment was confirmed and your order is placed.<br>
            We'll deliver it to you soon 💗
        </p>

        <?php if ($couponCode): ?>
        <div class="coupon-box">
            <p class="coupon-label">🎁 You won a coupon for your next order!</p>
            <div class="coupon-code" id="couponCodeText"><?= htmlspecialchars($couponCode) ?></div>
            <p class="coupon-desc"><?= $couponDisc ?>% off · Valid for 30 days</p>
            <button class="coupon-copy" onclick="copyCoupon()">📋 Copy Code</button>
        </div>
        <?php endif; ?>

    <?php else: ?>
        <div class="icon"><?= $status === 'pending' ? '⏳' : '❌' ?></div>
        <h1><?= $status === 'pending' ? 'Payment Pending' : 'Payment Failed' ?></h1>
        <p class="sub">
            <?php if ($status === 'pending'): ?>
                Your payment is being verified. If money was deducted, your order will be confirmed shortly.
            <?php else: ?>
                Your payment could not be processed. No money was deducted.<br>Please try again.
            <?php endif; ?>
        </p>
    <?php endif; ?>

    <?php if (!$couponCode): ?>
    <div class="redirect-bar"><div class="redirect-fill"></div></div>
    <p class="redirect-text">Redirecting in <?= $waitSecs ?> seconds…</p>
    <?php endif; ?>

    <a href="<?= htmlspecialchars($redirect) ?>" class="btn">
        <?= $success ? 'Continue Shopping →' : 'Back to Cart →' ?>
    </a>
    <?php if ($success): ?>
    <a href="my_orders.php" class="btn btn-outline">My Orders</a>
    <?php endif; ?>
</div>

<script>
function copyCoupon() {
    const code = document.getElementById('couponCodeText')?.textContent || '';
    if (navigator.clipboard) {
        navigator.clipboard.writeText(code).then(() => {
            const btn = document.querySelector('.coupon-copy');
            btn.textContent = '✅ Copied!';
            btn.classList.add('copied');
            setTimeout(() => { btn.textContent = '📋 Copy Code'; btn.classList.remove('copied'); }, 2000);
        });
    }
}
<?php if ($couponCode): ?>
// Auto-redirect after showing coupon
setTimeout(() => { window.location = '<?= htmlspecialchars($redirect) ?>'; }, <?= $waitSecs * 1000 ?>);
<?php endif; ?>
</script>
</body>
</html>
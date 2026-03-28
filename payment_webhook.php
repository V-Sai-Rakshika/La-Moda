<?php
/**
 * payment_webhook.php
 * Cashfree calls this URL server-to-server after payment completion.
 * Use this to reliably update order status in your DB.
 *
 * Set this URL in Cashfree dashboard → Developers → Webhooks
 */

ob_start();
include __DIR__ . "/db.php";

$secretKey = getenv('CASHFREE_SECRET_KEY') ?: 'cfsk_ma_test_xxxxx_your_secret';

// Read raw POST body
$rawBody   = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_WEBHOOK_SIGNATURE'] ?? '';
$timestamp = $_SERVER['HTTP_X_WEBHOOK_TIMESTAMP']  ?? '';

// ── Verify signature ──
$signedPayload = $timestamp . $rawBody;
$expectedSig   = base64_encode(hash_hmac('sha256', $signedPayload, $secretKey, true));

if (!hash_equals($expectedSig, $signature)) {
    http_response_code(401);
    ob_end_clean();
    echo json_encode(['error' => 'Invalid signature']);
    exit();
}

$data    = json_decode($rawBody, true);
$orderId = $data['data']['order']['order_id']      ?? '';
$status  = strtolower($data['data']['payment']['payment_status'] ?? '');

if ($orderId && $status === 'success') {
    // Update order status in MongoDB
    $orders->updateOne(
        ['cashfree_order_id' => $orderId],
        ['$set' => [
            'status'          => 'paid',
            'payment_status'  => 'success',
            'paid_at'         => date('Y-m-d H:i:s'),
        ]]
    );
}

ob_end_clean();
http_response_code(200);
echo json_encode(['received' => true]);
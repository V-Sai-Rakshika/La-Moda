<?php
/**
 * cashfree_payment.php
 * Creates a Cashfree payment order and returns the payment session ID.
 * Called via fetch() from the cart/checkout JS.
 *
 * TEST credentials — get yours from:
 *   https://merchant.cashfree.com/merchants/signup
 *   → Settings → API Keys → Test Mode
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

if (!is_logged_in())                       send(['error' => 'Not logged in']);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') send(['error' => 'Invalid method']);
csrf_verify();

// ── Cashfree credentials ──
// Set these as environment variables (never hardcode in production)
if (!isset($_ENV['CASHFREE_APP_ID']) || !isset($_ENV['CASHFREE_SECRET_KEY'])) {
    send(['error' => 'Cashfree env not configured']);
}

$appId     = $_ENV['CASHFREE_APP_ID'];
$secretKey = $_ENV['CASHFREE_SECRET_KEY'];

$isTest    = true; // set to false for production

$baseUrl = $isTest
    ? 'https://sandbox.cashfree.com/pg/orders'
    : 'https://api.cashfree.com/pg/orders';

// ── Build order details ──
$amount   = (float)($_POST['amount']   ?? 0);
$currency = 'INR';
$user     = current_user();
$username = $user['username'];

if ($amount <= 0) send(['error' => 'Invalid amount']);

// Generate unique order ID
$orderId = 'LM_' . strtoupper(substr(md5($username . time()), 0, 10));

// Customer details
$customerEmail = clean($_POST['email']  ?? '', 150) ?: $username . '@lamoda.com';
$customerPhone = clean($_POST['mobile'] ?? '', 15)  ?: '9999999999';
$customerName  = clean($_POST['name']   ?? '', 100) ?: $username;

// ── Create Cashfree order ──
$payload = json_encode([
    'order_id'     => $orderId,
    'order_amount' => $amount,
    'order_currency' => $currency,
    'customer_details' => [
        'customer_id'    => $username,
        'customer_name'  => $customerName,
        'customer_email' => $customerEmail,
        'customer_phone' => $customerPhone,
    ],
    'order_meta' => [
        'return_url'   => (isset($_SERVER['HTTPS']) ? 'https' : 'http')
                          . '://' . $_SERVER['HTTP_HOST'] . '/payment_return.php?order_id={order_id}',
        'notify_url'   => (isset($_SERVER['HTTPS']) ? 'https' : 'http')
                          . '://' . $_SERVER['HTTP_HOST'] . '/payment_webhook.php',
    ],
    'idempotency_key' => uniqid(),

    'order_meta' => [
    'return_url' => "http://localhost/LaModa/payment_return.php?order_id={$orderId}"
    ],
]);

$ch = curl_init($baseUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $payload,
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'x-client-id: '     . $appId,
        'x-client-secret: ' . $secretKey,
        'x-api-version: 2023-08-01',
    ],
    CURLOPT_TIMEOUT => 30,
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr  = curl_error($ch);
curl_close($ch);

if ($curlErr) {
    send(['error' => 'Payment gateway unreachable: ' . $curlErr]);
}

$data = json_decode($response, true);

if ($httpCode !== 200 || empty($data['payment_session_id'])) {
    $msg = $data['message'] ?? $data['error'] ?? 'Payment gateway error';
    send(['error' => $msg, 'raw' => $data]);
}

// Store the cashfree order ID in session for verification later
$_SESSION['cf_order_id']  = $orderId;
$_SESSION['cf_cf_order']  = $data['cf_order_id'] ?? '';

send([
    'success'            => true,
    'order_id'           => $orderId,
    'payment_session_id' => $data['payment_session_id'],
    'test_mode'          => $isTest,
]);
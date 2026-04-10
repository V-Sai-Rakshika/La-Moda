<?php
ob_start();
session_start();
include __DIR__ . "/db.php";
include __DIR__ . "/mailer.php";

function send(array $d): void {
    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode($d);
    exit();
}

if (!isset($_SESSION['admin'])) send(['error' => 'Unauthorized']);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') send(['error' => 'Invalid method']);

$oid    = trim($_POST['oid']    ?? '');
$status = trim($_POST['status'] ?? '');
$allowed = ['placed', 'shipped', 'in transit', 'delivered'];

if (!$oid || !in_array($status, $allowed)) send(['error' => 'Invalid input']);

try {
    $objectId = new MongoDB\BSON\ObjectId($oid);

    // Fetch order before updating (for email)
    $order = $orders->findOne(['_id' => $objectId]);

    $orders->updateOne(
        ['_id' => $objectId],
        ['$set' => [
            'status'     => $status,
            'updated_at' => date('Y-m-d H:i:s'),
        ]]
    );

    // Send delivery email
    if ($status === 'delivered' && $order && !empty($order['email'])) {
        $orderArr = is_array($order) ? $order : iterator_to_array($order);
        $html = buildDeliveredEmail($orderArr);
        sendOrderEmail($orderArr, '✅ Your La Moda order has been delivered!', $html);
    }

    send(['success' => true]);
} catch (\Throwable $e) {
    send(['error' => 'Could not update: ' . $e->getMessage()]);
}
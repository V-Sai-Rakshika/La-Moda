<?php
/**
 * order_tracking.php
 * Shows order tracking timeline for a specific order.
 * Usage: order_tracking.php?id=<order_id>
 */
session_start();
include __DIR__ . "/db.php";
include __DIR__ . "/auth.php";
require_login();

$oid      = clean($_GET['id'] ?? '', 50);
$username = current_user()['username'];
$order    = null;

if ($oid) {
    try {
        $order = $orders->findOne([
            '_id'      => new MongoDB\BSON\ObjectId($oid),
            'username' => $username,
        ]);
    } catch (\Throwable $e) {}
}

if (!$order) {
    header("Location: my_orders.php");
    exit();
}

$status   = strtolower((string)($order['status'] ?? 'placed'));
$placedAt = (string)($order['placed_at'] ?? '');
$total    = (int)($order['item_price']   ?? 0);
$method   = ucfirst((string)($order['payment_method'] ?? 'cod'));
$items    = is_array($order['cart_items'] ?? null) ? $order['cart_items'] : [];
$city     = (string)($order['city'] ?? '');
$name     = (string)($order['full_name'] ?? '');

$steps = [
    'placed'     => ['label' => 'Order Placed',    'icon' => '✅', 'desc' => 'Your order has been confirmed'],
    'shipped'    => ['label' => 'Shipped',          'icon' => '📦', 'desc' => 'Your order is on its way'],
    'in transit' => ['label' => 'In Transit',       'icon' => '🚚', 'desc' => 'Out for delivery'],
    'delivered'  => ['label' => 'Delivered',        'icon' => '🎉', 'desc' => 'Order delivered successfully'],
];

$statusOrder   = ['placed', 'shipped', 'in transit', 'delivered'];
$currentIndex  = array_search($status, $statusOrder);
if ($currentIndex === false) $currentIndex = 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Track Order | La Moda</title>
<link rel="stylesheet" href="styles.css">
<style>
.track-wrap{max-width:640px;margin:0 auto;padding:20px 16px 60px;}
.track-card{background:#fff;border-radius:14px;box-shadow:0 2px 16px rgba(0,0,0,.06);overflow:hidden;margin-bottom:16px;}
.track-header{padding:16px 20px;background:linear-gradient(135deg,#8B2500,#5c1800);color:#fff;}
.track-header h2{font-family:var(--font-display,serif);font-size:20px;margin-bottom:4px;}
.track-header p{font-size:12px;opacity:.8;}
.track-body{padding:24px 20px;}

/* Timeline */
.timeline{position:relative;padding-left:32px;}
.timeline::before{content:'';position:absolute;left:12px;top:0;bottom:0;width:2px;background:#f0f0f0;}
.tl-step{position:relative;margin-bottom:28px;}
.tl-step:last-child{margin-bottom:0;}
.tl-dot{position:absolute;left:-32px;width:26px;height:26px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:13px;border:2px solid #e0e0e0;background:#fff;z-index:1;}
.tl-dot.done{border-color:#16a34a;background:#f0fdf4;}
.tl-dot.active{border-color:#8B2500;background:#fff5f2;box-shadow:0 0 0 4px rgba(139,37,0,.1);}
.tl-dot.pending{border-color:#e0e0e0;background:#fafafa;}
.tl-label{font-size:14px;font-weight:600;margin-bottom:2px;}
.tl-label.done{color:#16a34a;}
.tl-label.active{color:#8B2500;}
.tl-label.pending{color:#aaa;}
.tl-desc{font-size:12px;color:#888;}
.tl-time{font-size:11px;color:#bbb;margin-top:2px;}

/* Order items */
.order-item-row{display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid #f5f5f5;}
.order-item-row img{width:48px;height:56px;object-fit:cover;border-radius:6px;background:#f5f5f5;}
.order-item-row:last-child{border-bottom:none;}
.summary-row{display:flex;justify-content:space-between;font-size:13px;padding:4px 0;}
.summary-row.total{font-weight:700;font-size:15px;border-top:1px solid #eee;margin-top:8px;padding-top:10px;}
</style>
</head>
<body>
<?php include __DIR__ . "/navbar.php"; ?>

<div class="track-wrap">
<h1 class="page-title">📦 Track Order</h1>

<div class="track-card">
  <div class="track-header">
    <h2>Order #<?= substr((string)$order['_id'], -8) ?></h2>
    <p>Placed on <?= $placedAt ? date('d M Y, g:i A', strtotime($placedAt)) : '—' ?></p>
  </div>

  <div class="track-body">
    <!-- Timeline -->
    <div class="timeline">
    <?php foreach ($statusOrder as $si => $stepKey):
        $step = $steps[$stepKey] ?? ['label'=>$stepKey,'icon'=>'•','desc'=>''];
        if ($si < $currentIndex)      $cls = 'done';
        elseif ($si === $currentIndex) $cls = 'active';
        else                           $cls = 'pending';
    ?>
    <div class="tl-step">
      <div class="tl-dot <?= $cls ?>">
        <?php if ($cls === 'done'): ?>✓
        <?php elseif ($cls === 'active'): ?><span style="font-size:10px;">●</span>
        <?php else: ?><span style="font-size:10px;color:#ddd;">○</span>
        <?php endif; ?>
      </div>
      <div class="tl-label <?= $cls ?>"><?= $step['icon'] ?> <?= htmlspecialchars($step['label']) ?></div>
      <div class="tl-desc"><?= htmlspecialchars($step['desc']) ?></div>
      <?php if ($cls === 'active'): ?>
      <div class="tl-time" style="color:#8B2500;font-weight:600;">← Current Status</div>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
    </div>

    <!-- Delivery address -->
    <?php if ($name || $city): ?>
    <div style="margin-top:20px;padding:12px 14px;background:#f9f9f9;border-radius:10px;font-size:13px;">
      <p style="font-weight:600;margin-bottom:4px;">📍 Delivery to</p>
      <p><?= htmlspecialchars($name) ?></p>
      <p><?= htmlspecialchars((string)($order['flat']??'')) ?>, <?= htmlspecialchars((string)($order['area']??'')) ?></p>
      <p><?= htmlspecialchars($city) ?> – <?= htmlspecialchars((string)($order['pincode']??'')) ?></p>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- Items -->
<div class="track-card">
  <div class="track-body">
    <p style="font-size:13px;font-weight:700;margin-bottom:12px;">Items in this order</p>
    <?php foreach ($items as $item):
      $iImg = (string)($item['image'] ?? '');
      if ($iImg && strpos($iImg,'http')===false && strpos($iImg,'images/')===false) $iImg='images/'.$iImg;
    ?>
    <div class="order-item-row">
      <img src="<?= htmlspecialchars($iImg ?: 'https://placehold.co/48x56/f5f5f5/aaa?text=?') ?>"
           onerror="this.src='https://placehold.co/48x56/f5f5f5/aaa?text=?'" alt="">
      <div style="flex:1;">
        <strong style="font-size:13px;"><?= htmlspecialchars((string)($item['name']??'')) ?></strong>
        <?php if (!empty($item['size'])): ?>
        <span style="font-size:11px;background:#f5f5f5;padding:1px 8px;border-radius:4px;margin-left:6px;">
          Size: <?= htmlspecialchars($item['size']) ?>
        </span>
        <?php endif; ?>
        <div style="font-size:12px;color:#888;margin-top:2px;">Qty: <?= (int)($item['qty']??1) ?></div>
      </div>
      <div style="font-size:14px;font-weight:700;color:#8B2500;">
        ₹<?= (int)($item['price']??0) * (int)($item['qty']??1) ?>
      </div>
    </div>
    <?php endforeach; ?>

    <div style="margin-top:14px;">
      <div class="summary-row"><span>Subtotal</span><span>₹<?= (int)($order['subtotal']??$total) ?></span></div>
      <div class="summary-row"><span>Delivery</span><span>₹<?= (int)($order['delivery_charge']??0) ?></span></div>
      <?php if (!empty($order['coupon_discount']) && $order['coupon_discount'] > 0): ?>
      <div class="summary-row" style="color:#16a34a;"><span>🎟️ Coupon (<?= $order['coupon_discount'] ?>% off)</span><span>−₹<?= $total - (int)($order['subtotal']??$total) - (int)($order['delivery_charge']??0) ?></span></div>
      <?php endif; ?>
      <div class="summary-row total"><span>Total</span><span>₹<?= $total ?></span></div>
      <div class="summary-row" style="font-size:12px;color:#888;margin-top:4px;"><span>Payment</span><span><?= htmlspecialchars($method) ?></span></div>
    </div>
  </div>
</div>

<a href="my_orders.php" style="display:inline-block;padding:11px 24px;border:1.5px solid #8B2500;color:#8B2500;border-radius:8px;font-size:13px;font-weight:600;text-decoration:none;">← Back to My Orders</a>
</div>

<footer><h1>☆ La Moda ☆</h1><p>Wear the Moment</p></footer>
</body>
</html>
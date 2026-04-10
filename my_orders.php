<?php
session_start();
include __DIR__ . "/db.php";
include __DIR__ . "/auth.php";
require_login();

$username = current_user()['username'];

// Fetch orders for this user (most recent first, exclude pending)
$cursor = $orders->find(
    ['username' => $username, 'status' => ['$ne' => 'pending_payment']],
    ['sort' => ['placed_at' => -1], 'limit' => 50]
);
$userOrders = iterator_to_array($cursor);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>My Orders | La Moda</title>
<link rel="stylesheet" href="styles.css">
<style>
.orders-wrap{max-width:720px;margin:0 auto;padding:20px 16px 60px;}
.order-card{background:#fff;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,.06);margin-bottom:16px;overflow:hidden;}
.order-header{display:flex;align-items:center;justify-content:space-between;padding:14px 16px;border-bottom:1px solid #f5f5f5;flex-wrap:wrap;gap:8px;}
.order-id{font-size:11px;color:#aaa;font-family:monospace;}
.order-date{font-size:12px;color:#888;}
.order-status{padding:4px 12px;border-radius:20px;font-size:11px;font-weight:700;text-transform:capitalize;}
.status-placed{background:#fef9ec;color:#d97706;}
.status-shipped{background:#eff6ff;color:#2563eb;}
.status-in-transit{background:#f0fdf4;color:#059669;}
.status-delivered{background:#f0fdf4;color:#16a34a;border:1px solid #bbf7d0;}
.status-paid{background:#f0fdf4;color:#16a34a;}
.order-body{padding:14px 16px;}
.order-item{display:flex;align-items:center;gap:12px;margin-bottom:10px;}
.order-item img{width:52px;height:60px;object-fit:cover;border-radius:8px;background:#f5f5f5;flex-shrink:0;}
.order-item-info{flex:1;}
.order-item-info strong{display:block;font-size:13px;margin-bottom:2px;}
.order-item-info span{font-size:12px;color:#888;}
.order-item-price{font-size:14px;font-weight:700;color:#8B2500;white-space:nowrap;}
.order-footer{display:flex;align-items:center;justify-content:space-between;padding:12px 16px;background:#fafafa;border-top:1px solid #f5f5f5;flex-wrap:wrap;gap:8px;}
.order-total{font-size:14px;font-weight:700;}
.order-method{font-size:11px;color:#888;background:#f0f0f0;padding:3px 10px;border-radius:20px;}
.order-addr{font-size:11px;color:#888;margin-top:4px;}
.empty-orders{text-align:center;padding:60px 20px;color:#aaa;}
.empty-orders .icon{font-size:52px;margin-bottom:12px;}
@media(max-width:500px){.order-header{flex-direction:column;align-items:flex-start;}}
</style>
</head>
<body>
<?php include __DIR__ . "/navbar.php"; ?>

<div class="orders-wrap">
<h1 class="page-title">📦 My Orders</h1>

<?php if (empty($userOrders)): ?>
<div class="empty-orders">
  <div class="icon">📦</div>
  <p style="font-size:15px;margin-bottom:8px;">No orders yet</p>
  <p style="font-size:13px;margin-bottom:20px;">Start shopping to see your orders here!</p>
  <a href="index.php" style="padding:11px 28px;background:#8B2500;color:#fff;border-radius:8px;text-decoration:none;font-weight:700;font-size:13px;">Browse Products →</a>
</div>
<?php else: ?>

<?php foreach ($userOrders as $ord):
  $oid     = (string)($ord['_id'] ?? '');
  $status  = (string)($ord['status'] ?? 'placed');
  $placed  = (string)($ord['placed_at'] ?? '');
  $total   = (int)($ord['item_price'] ?? 0);
  $method  = ucfirst((string)($ord['payment_method'] ?? 'cod'));
  $city    = (string)($ord['city'] ?? '');
  $name    = (string)($ord['full_name'] ?? '');
  $items   = is_array($ord['cart_items'] ?? null) ? $ord['cart_items'] : [];
  if (empty($items) && !empty($ord['item_name']) && $ord['item_name'] !== '__cart__') {
      $items = [['name'=>$ord['item_name'],'price'=>$total,'qty'=>1,'image'=>'']];
  }
  $statusClass = 'status-'.str_replace(' ','-',$status);
?>
<div class="order-card">
  <div class="order-header">
    <div>
      <div class="order-id">Order #<?= substr($oid,-8) ?></div>
      <div class="order-date"><?= $placed ? date('d M Y, g:i A', strtotime($placed)) : '' ?></div>
    </div>
    <span class="order-status <?= htmlspecialchars($statusClass) ?>"><?= htmlspecialchars($status) ?></span>
  </div>
  <div class="order-body">
    <?php foreach (array_slice($items, 0, 3) as $item):
      $iImg = (string)($item['image'] ?? '');
      if ($iImg && strpos($iImg,'http')===false && strpos($iImg,'images/')===false) $iImg = 'images/'.$iImg;
    ?>
    <div class="order-item">
      <img src="<?= htmlspecialchars($iImg ?: 'https://placehold.co/52x60/f5f5f5/aaa?text=?') ?>"
           onerror="this.src='https://placehold.co/52x60/f5f5f5/aaa?text=?'" alt="">
      <div class="order-item-info">
        <strong><?= htmlspecialchars((string)($item['name'] ?? '')) ?></strong>
        <span>Qty: <?= (int)($item['qty'] ?? 1) ?><?= !empty($item['size']) ? ' · Size: '.htmlspecialchars($item['size']) : '' ?></span>
      </div>
      <div class="order-item-price">₹<?= (int)($item['price'] ?? 0) * (int)($item['qty'] ?? 1) ?></div>
    </div>
    <?php endforeach; ?>
    <?php if (count($items) > 3): ?>
    <p style="font-size:12px;color:#aaa;margin-top:4px;">+<?= count($items)-3 ?> more item(s)</p>
    <?php endif; ?>
    <?php if ($name || $city): ?>
    <p class="order-addr">📍 <?= htmlspecialchars($name) ?><?= $city ? ", $city" : '' ?></p>
    <?php endif; ?>
  </div>
  <!-- Timeline -->
  <div style="padding:12px 16px 0;">
    <?php
    $_sts = ['placed','shipped','in transit','delivered'];
    $_ci  = (int)array_search($status, $_sts);
    if ($_ci < 0) $_ci = 0;
    ?>
    <div style="display:flex;align-items:center;">
    <?php foreach ($_sts as $_ti => $_ts): $__done = $_ti <= $_ci; $__act = $_ti === $_ci; ?>
        <?php if ($_ti > 0): ?>
        <div style="flex:1;height:2px;background:<?= $__done?'#8B2500':'#eee' ?>;"></div>
        <?php endif; ?>
        <div title="<?= ucfirst($_ts) ?>" style="width:24px;height:24px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:700;flex-shrink:0;background:<?= $__done?'#8B2500':'#eee' ?>;color:<?= $__done?'#fff':'#bbb' ?>;<?= $__act?'box-shadow:0 0 0 3px #fff0eb;':'' ?>">
            <?= $__done?'✓':($_ti+1) ?>
        </div>
    <?php endforeach; ?>
    </div>
    <div style="display:flex;margin-top:4px;margin-bottom:10px;">
    <?php foreach ($_sts as $_ti => $_ts): ?>
    <span style="flex:1;font-size:9px;color:<?= $_ti<=$_ci?'#8B2500':'#bbb' ?>;font-weight:<?= $_ti===$_ci?'700':'400' ?>;text-align:<?= $_ti===0?'left':($_ti===count($_sts)-1?'right':'center') ?>;text-transform:capitalize;"><?= ucfirst($_ts) ?></span>
    <?php endforeach; ?>
    </div>
  </div>
  <div class="order-footer">
    <div class="order-total">Total: ₹<?= $total ?></div>
    <span class="order-method"><?= htmlspecialchars($method) ?></span>
  </div>
</div>
<?php endforeach; ?>
<?php endif; ?>
</div>

<footer><h1>☆ La Moda ☆</h1><p>Wear the Moment</p></footer>
</body>
</html>
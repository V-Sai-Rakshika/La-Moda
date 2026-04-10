<?php
session_start();
include __DIR__ . "/db.php";
include __DIR__ . "/auth.php";
require_login();

$username = current_user()['username'];
$now      = date('Y-m-d H:i:s');

// Fetch all coupons for this user
$allCoupons = iterator_to_array(
    $db->coupons->find(
        ['username' => $username],
        ['sort' => ['created_at' => -1]]
    )
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>La Moda | My Coupons</title>
<link rel="stylesheet" href="styles.css">
<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>👗</text></svg>">
<style>
.coupons-wrap { max-width: 760px; margin: 0 auto; padding: 30px 20px 60px; }
.coupons-title { font-family: var(--font-display); font-size: 28px; margin-bottom: 6px; }
.coupons-sub   { font-size: 13px; color: #999; margin-bottom: 28px; }

.coupon-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 16px; }

.coupon-card {
  border-radius: 14px;
  overflow: hidden;
  position: relative;
  box-shadow: 0 2px 12px rgba(0,0,0,.07);
}
.coupon-card.valid   { border: 1.5px dashed #8B2500; background: #fff; }
.coupon-card.used    { border: 1.5px solid #e0e0e0; background: #fafafa; opacity: .7; }
.coupon-card.expired { border: 1.5px solid #fecaca; background: #fff8f8; opacity: .7; }

.coupon-top {
  background: linear-gradient(135deg, #8B2500, #c0392b);
  padding: 18px 20px 14px;
  color: #fff;
  position: relative;
}
.coupon-card.used    .coupon-top { background: linear-gradient(135deg, #aaa, #ccc); }
.coupon-card.expired .coupon-top { background: linear-gradient(135deg, #e57373, #ef9a9a); }

.coupon-pct   { font-size: 36px; font-weight: 800; font-family: var(--font-display); line-height: 1; }
.coupon-off   { font-size: 13px; opacity: .85; margin-top: 2px; }
.coupon-badge {
  position: absolute; top: 12px; right: 14px;
  padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 700;
}
.badge-valid   { background: #fff; color: #8B2500; }
.badge-used    { background: #fff; color: #888; }
.badge-expired { background: #fff; color: #e53935; }

.coupon-bottom { padding: 14px 20px 16px; }
.coupon-code-wrap {
  display: flex; align-items: center; gap: 8px;
  margin-bottom: 10px;
}
.coupon-code {
  font-size: 20px; font-weight: 800; letter-spacing: 3px; color: #8B2500;
  font-family: monospace;
}
.coupon-card.used    .coupon-code { color: #aaa; }
.coupon-card.expired .coupon-code { color: #e57373; }

.copy-btn {
  padding: 4px 10px; background: #fff5f2; border: 1px solid #ffd6c4;
  color: #8B2500; border-radius: 6px; font-size: 11px; font-weight: 600;
  cursor: pointer; font-family: inherit;
}
.copy-btn:hover { background: #8B2500; color: #fff; }

.coupon-meta { font-size: 11px; color: #aaa; }
.coupon-meta span { display: block; margin-top: 2px; }

.empty-coupons {
  text-align: center; padding: 80px 20px;
  color: #bbb; font-family: var(--font-display); font-size: 20px;
}
.empty-coupons a {
  display: inline-block; margin-top: 18px; padding: 10px 24px;
  background: #8B2500; color: #fff; border-radius: 8px; font-size: 14px;
  font-family: var(--font-body); text-decoration: none;
}

/* Notch effect */
.coupon-notch {
  display: flex; align-items: center; gap: 0;
  margin: 0 -1px;
}
.notch-line { flex: 1; border-top: 1.5px dashed #e0e0e0; }
.notch-circle {
  width: 20px; height: 20px; border-radius: 50%;
  background: var(--bg); border: 1.5px solid #e0e0e0;
  flex-shrink: 0;
}
</style>
</head>
<body>
<?php include __DIR__ . "/navbar.php"; ?>

<div class="coupons-wrap">
  <h1 class="coupons-title">🎟️ My Coupons</h1>
  <p class="coupons-sub">Your discount coupons — earned after every order. Valid for 30 days.</p>

  <?php if (empty($allCoupons)): ?>
  <div class="empty-coupons">
    <div style="font-size:56px;margin-bottom:14px;">🎟️</div>
    <p>No coupons yet!</p>
    <p style="font-size:14px;font-family:var(--font-body);margin-top:8px;">Place an order to earn your first coupon.</p>
    <a href="index.php">Shop Now →</a>
  </div>
  <?php else: ?>

  <div class="coupon-grid">
    <?php foreach ($allCoupons as $c):
      $code      = (string)($c['code']       ?? '');
      $discount  = (int)($c['discount']      ?? 5);
      $used      = (bool)($c['used']         ?? false);
      $createdAt = (string)($c['created_at'] ?? '');
      $expiresAt = (string)($c['expires_at'] ?? '');

      // Determine state
      $isExpired = $expiresAt && $expiresAt < $now;
      $state = $used ? 'used' : ($isExpired ? 'expired' : 'valid');

      $badgeText  = $used ? 'USED' : ($isExpired ? 'EXPIRED' : 'ACTIVE');
      $badgeClass = 'badge-' . $state;
    ?>
    <div class="coupon-card <?= $state ?>">
      <div class="coupon-top">
        <div class="coupon-badge <?= $badgeClass ?>"><?= $badgeText ?></div>
        <div class="coupon-pct"><?= $discount ?>%</div>
        <div class="coupon-off">OFF on your next order</div>
      </div>
      <div class="coupon-notch">
        <div class="notch-circle"></div>
        <div class="notch-line"></div>
        <div class="notch-circle"></div>
      </div>
      <div class="coupon-bottom">
        <div class="coupon-code-wrap">
          <div class="coupon-code" id="cc-<?= htmlspecialchars($code) ?>"><?= htmlspecialchars($code) ?></div>
          <?php if ($state === 'valid'): ?>
          <button class="copy-btn" onclick="copyCoupon('<?= htmlspecialchars($code) ?>')">Copy</button>
          <?php endif; ?>
        </div>
        <div class="coupon-meta">
          <?php if ($createdAt): ?><span>Earned: <?= htmlspecialchars(date('d M Y', strtotime($createdAt))) ?></span><?php endif; ?>
          <?php if ($expiresAt && $state === 'valid'): ?>
            <span style="color:#8B2500;font-weight:600;">Expires: <?= htmlspecialchars(date('d M Y', strtotime($expiresAt))) ?></span>
          <?php elseif ($expiresAt && $state === 'expired'): ?>
            <span style="color:#e57373;">Expired: <?= htmlspecialchars(date('d M Y', strtotime($expiresAt))) ?></span>
          <?php endif; ?>
          <?php if ($used && !empty($c['used_at'])): ?>
            <span>Used on: <?= htmlspecialchars(date('d M Y', strtotime($c['used_at']))) ?></span>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <?php endif; ?>
</div>

<footer><h1>☆ La Moda ☆</h1><p>Wear the Moment</p></footer>

<div id="toast" class="toast"></div>
<script>
function copyCoupon(code) {
  navigator.clipboard.writeText(code).then(() => {
    const t = document.getElementById('toast');
    t.textContent = '✅ Coupon code copied!';
    t.className = 'toast show';
    clearTimeout(t._t);
    t._t = setTimeout(() => t.className = 'toast', 2500);
  });
}
</script>
</body>
</html>
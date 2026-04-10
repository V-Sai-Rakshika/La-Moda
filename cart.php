<?php
session_start();
include __DIR__ . "/db.php";
include __DIR__ . "/auth.php";

// Sync cart from MongoDB
if (is_logged_in()) {
    $__cartUser = $users->findOne(['username' => current_user()['username']]);
    if (!empty($__cartUser['cart']) && is_array($__cartUser['cart'])) {
        $_SESSION['cart'] = $__cartUser['cart'];
    }
}

// Remove item
if (isset($_GET['remove'])) {
    $r = clean($_GET['remove'], 200);
    $_SESSION['cart'] = array_values(array_filter($_SESSION['cart'] ?? [], fn($i) => ($i['name'] ?? '') !== $r));
    if (is_logged_in()) {
        $users->updateOne(['username' => current_user()['username']], ['$set' => ['cart' => $_SESSION['cart']]]);
    }
    header("Location: cart.php"); exit();
}

$cart = $_SESSION['cart'] ?? [];
$sub  = 0;
foreach ($cart as $item) {
    $sub += (int)($item['price'] ?? 0) * (int)($item['qty'] ?? 1);
}
$sub      = (int)$sub;
$delivery = 0;
if ($sub > 0 && $sub < 500)          $delivery = 50;
elseif ($sub >= 500 && $sub <= 1000) $delivery = 40;
$delivery = (int)$delivery;
$total    = $sub + $delivery;

$needed_for_free    = max(0, 1001 - $sub);
$needed_for_cheaper = max(0, 500  - $sub);

// Load saved addresses
$savedAddresses = [];
if (is_logged_in()) {
    $u = $users->findOne(['username' => current_user()['username']]);
    if (!empty($u['addresses']) && is_array($u['addresses'])) {
        $savedAddresses = $u['addresses'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>La Moda | My Cart</title>
<link rel="stylesheet" href="styles.css">
<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>👗</text></svg>">
<style>
/* ── Overlays ── */
.ov{display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,.52);z-index:8000;overflow-y:auto;padding:20px 12px 60px;}
.ov.open{display:block;}
.ob{background:#fff;width:540px;max-width:100%;margin:0 auto;border-radius:14px;padding:24px 20px 20px;position:relative;}
.ob h2{font-size:19px;font-family:var(--font-display,serif);margin-bottom:3px;}
.ov-sub{font-size:12px;color:#aaa;margin-bottom:14px;}
.ov-close{position:absolute;top:11px;right:14px;background:none;border:none;font-size:22px;cursor:pointer;color:#999;line-height:1;}

/* ── Form ── */
.ff{margin-bottom:10px;}
.ff label{display:block;font-size:11px;font-weight:600;color:#666;text-transform:uppercase;letter-spacing:.4px;margin-bottom:4px;}
.ff input,.ff select,.ff textarea{width:100%;padding:9px 12px;border:1.5px solid #e0e0e0;border-radius:8px;font-size:13px;font-family:inherit;outline:none;box-sizing:border-box;transition:border-color .18s;}
.ff input:focus,.ff select:focus,.ff textarea:focus{border-color:#8B2500;}
.ff input.bad{border-color:#dc2626!important;background:#fff8f8;}
.ff input.good{border-color:#16a34a!important;}
.ff .emsg{font-size:11px;color:#dc2626;margin-top:3px;display:none;}
.ff .emsg.show{display:block;}
.ff .hint{font-size:11px;color:#bbb;margin-top:3px;}
.frow{display:grid;grid-template-columns:1fr 1fr;gap:10px;}

/* ── Steps ── */
.steps{display:flex;align-items:center;gap:6px;margin-bottom:16px;}
.step{display:flex;align-items:center;gap:6px;font-size:12px;font-weight:500;color:#bbb;}
.step.done{color:#16a34a;}.step.active{color:#8B2500;font-weight:700;}
.step-dot{width:22px;height:22px;border-radius:50%;border:2px solid currentColor;display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:700;flex-shrink:0;}
.step-line{flex:1;height:1px;background:#e0e0e0;min-width:20px;}

/* ── Saved addresses ── */
.addr-cards{display:flex;gap:10px;margin-bottom:12px;flex-wrap:wrap;}
.addr-card{flex:1;min-width:170px;padding:12px 14px;border:1.5px solid #e0e0e0;border-radius:10px;cursor:pointer;transition:all .18s;font-size:12px;}
.addr-card:hover,.addr-card.sel{border-color:#8B2500;background:#fff5f2;}
.addr-card strong{display:block;font-size:13px;margin-bottom:4px;}
.addr-btns{display:flex;gap:8px;margin-bottom:14px;flex-wrap:wrap;}
.addr-btn{padding:8px 16px;border:1.5px solid #e0e0e0;border-radius:20px;font-size:12px;font-weight:500;background:#fff;cursor:pointer;font-family:inherit;transition:all .18s;}
.addr-btn.sel{border-color:#8B2500;background:#8B2500;color:#fff;}

/* ── Payment tabs ── */
.ptabs{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:10px;}
.ptab{padding:7px 13px;border:1.5px solid #e0e0e0;border-radius:20px;font-size:12px;font-weight:500;background:#fff;cursor:pointer;font-family:inherit;transition:all .18s;}
.ptab.on{border-color:#8B2500;background:#8B2500;color:#fff;}
.ppanel{display:none;padding:13px;background:#fafafa;border:1px solid #eee;border-radius:8px;margin-bottom:10px;font-size:13px;}
.ppanel.on{display:block;}
.cfield{width:100%;padding:9px 12px;border:1.5px solid #e0e0e0;border-radius:8px;font-size:13px;font-family:inherit;outline:none;margin-bottom:8px;box-sizing:border-box;}
.cfield:focus{border-color:#8B2500;}

/* ── EMI ── */
.emi-grid{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-top:6px;}
.emi-opt{padding:10px;border:1.5px solid #e0e0e0;border-radius:8px;text-align:center;cursor:pointer;font-family:inherit;background:#fff;transition:all .18s;}
.emi-opt.on{border-color:#8B2500!important;background:#fff5f2!important;color:#8B2500;}
.emi-opt strong{display:block;font-size:14px;}

/* ── Banks ── */
.bank-list{display:flex;flex-direction:column;gap:6px;margin-top:6px;}
.bank-opt{display:flex;align-items:center;gap:10px;padding:10px 12px;border:1.5px solid #e0e0e0;border-radius:8px;cursor:pointer;font-size:13px;transition:all .18s;}
.bank-opt.on{border-color:#8B2500!important;background:#fff5f2!important;}
.bank-logo{width:28px;height:28px;border-radius:6px;display:flex;align-items:center;justify-content:center;font-size:14px;flex-shrink:0;}

/* ── Bank details box ── */
.bank-details-box{margin-top:12px;padding:14px;background:#fef9ec;border:1px solid #fde68a;border-radius:10px;}
.bank-details-box p{font-size:12px;font-weight:700;color:#555;margin-bottom:10px;}
.bank-field{width:100%;padding:9px 12px;border:1.5px solid #e0e0e0;border-radius:8px;font-size:13px;font-family:inherit;outline:none;margin-bottom:8px;box-sizing:border-box;}
.bank-field:focus{border-color:#8B2500;}

/* ── Buttons ── */
.sbtn{width:100%;padding:13px;background:#8B2500;color:#fff;border:none;border-radius:8px;font-size:14px;font-weight:700;cursor:pointer;font-family:inherit;margin-top:12px;transition:background .2s;}
.sbtn:hover:not(:disabled){background:#5c1800;}
.sbtn:disabled{opacity:.6;cursor:not-allowed;}
.sbtn-outline{width:100%;padding:11px;border:1.5px solid #8B2500;color:#8B2500;background:#fff;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;font-family:inherit;margin-top:8px;}

/* ── Coupon popup ── */
.coupon-popup{background:#fff;width:420px;max-width:100%;margin:0 auto;border-radius:14px;padding:28px 24px 24px;position:relative;}
.coupon-chip{display:inline-flex;align-items:center;gap:8px;padding:10px 16px;border:1.5px solid #e0e0e0;border-radius:10px;cursor:pointer;font-size:13px;font-family:inherit;background:#fff;transition:all .18s;width:100%;margin-bottom:8px;text-align:left;}
.coupon-chip:hover,.coupon-chip.sel{border-color:#8B2500;background:#fff5f2;}
.coupon-chip .cc{font-weight:800;color:#8B2500;letter-spacing:1px;font-size:14px;font-family:monospace;}
.coupon-chip .cd{font-size:11px;color:#888;margin-left:4px;}
.coupon-chip .ck{margin-left:auto;font-size:16px;}

/* ── Success ── */
#successOv{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9999;align-items:center;justify-content:center;}
#successOv.open{display:flex;}
#successBox{background:#fff;border-radius:16px;padding:36px 28px;text-align:center;width:340px;max-width:95vw;}
.coupon-reveal{margin:14px 0;padding:14px;background:#fff5f2;border:1.5px dashed #8B2500;border-radius:10px;}
.c-code{font-size:22px;font-weight:800;color:#8B2500;letter-spacing:2px;margin:6px 0;font-family:monospace;}

/* ── Cart layout ── */
.list-box{background:#fff;border-radius:14px;box-shadow:0 2px 16px rgba(0,0,0,.06);overflow:hidden;}
.list-item{display:flex;align-items:flex-start;gap:14px;padding:16px;border-bottom:1px solid #f5f5f5;}
.list-img{width:80px;height:90px;border-radius:10px;overflow:hidden;flex-shrink:0;background:#f5f5f5;}
.list-img img{width:100%;height:100%;object-fit:cover;}
.list-details{flex:1;min-width:0;}
.list-details h3{font-size:14px;font-weight:600;margin-bottom:3px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.size-tag{display:inline-block;background:#f5f5f5;color:#666;font-size:11px;padding:2px 8px;border-radius:4px;margin-bottom:4px;}
.list-price{font-size:12px;color:#888;margin-bottom:6px;}
.stock-warn{font-size:11px;color:#dc2626;font-weight:600;margin-bottom:4px;}
.list-qty-row{display:flex;align-items:center;gap:12px;flex-wrap:wrap;}
.qty-box{display:flex;align-items:center;background:#f5f5f5;border-radius:8px;overflow:hidden;}
.qty-btn{width:32px;height:32px;border:none;background:none;font-size:18px;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:background .15s;color:#333;}
.qty-btn:hover:not(:disabled){background:#e8e8e8;}
.qty-btn:disabled{opacity:.4;}
.qty-box span{min-width:30px;text-align:center;font-size:13px;font-weight:700;}
.list-subtotal{font-size:12px;color:#8B2500;font-weight:600;}
.remove-link{font-size:11px;color:#dc2626;margin-top:6px;display:inline-block;}
.list-actions{flex-shrink:0;padding-top:4px;}
.list-item-price{font-size:15px;font-weight:700;color:#8B2500;}
.summary-wrap{padding:16px;}
.del-info{background:#f9f9f9;border:1px solid #eee;border-radius:10px;padding:12px 14px;margin-bottom:10px;}
.del-row{display:flex;justify-content:space-between;font-size:13px;padding:3px 0;}
.del-row.total{font-weight:700;font-size:15px;border-top:1px solid #eee;margin-top:6px;padding-top:8px;}
.del-row.saving{color:#16a34a;font-weight:600;}
.del-banner{padding:10px 14px;border-radius:8px;font-size:12px;font-weight:500;margin-bottom:12px;}
.del-free{background:#f0fdf4;color:#16a34a;border:1px solid #bbf7d0;}
.del-paid{background:#fef9ec;color:#d97706;border:1px solid #fde68a;}
.cart-total-box{padding:16px;border-top:1px solid #f0f0f0;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;}
.cart-total-box h2{font-size:18px;margin:0;}
.checkout-btn{padding:13px 28px;background:#8B2500;color:#fff;border:none;border-radius:8px;font-size:14px;font-weight:700;cursor:pointer;font-family:inherit;transition:background .2s;white-space:nowrap;}
.checkout-btn:hover{background:#5c1800;}
.list-footer{padding:14px 16px;font-size:13px;border-top:1px solid #f5f5f5;}
.list-footer a{color:#8B2500;text-decoration:none;font-weight:500;}

@media(max-width:600px){
  .ob,.coupon-popup{padding:16px 12px;}
  .frow{grid-template-columns:1fr;}
  .list-item{gap:10px;padding:12px;}
  .list-img{width:64px;height:74px;}
  .cart-total-box{flex-direction:column;align-items:stretch;}
  .checkout-btn{width:100%;text-align:center;}
  .ptabs{gap:4px;}
  .ptab{padding:6px 10px;font-size:11px;}
  .addr-cards{flex-direction:column;}
}
</style>
</head>
<body>
<?php include __DIR__ . "/navbar.php"; ?>

<div class="page-wrap">
<h1 class="page-title">🛒 My Cart</h1>

<?php if (empty($cart)): ?>
<div class="empty-state">
  <div class="empty-icon">🛒</div>
  <p>Your cart is empty.</p>
  <a href="index.php" class="empty-link">Start Shopping →</a>
</div>
<?php else: ?>

<div class="list-box">
  <?php foreach ($cart as $item):
    $iName  = (string)($item['name']  ?? '');
    $iPrice = (int)($item['price']    ?? 0);
    $iQty   = (int)($item['qty']      ?? 1);
    $iImg   = (string)($item['image'] ?? '');
    $iSize  = (string)($item['size']  ?? '');
    $iStock = isset($item['stock']) ? (int)$item['stock'] : null;
    $iKey   = urlencode($iName);
  ?>
  <div class="list-item" id="cr-<?= $iKey ?>">
    <div class="list-img">
      <img src="<?= htmlspecialchars($iImg) ?>" alt="<?= htmlspecialchars($iName) ?>"
           onerror="this.src='https://placehold.co/80x90/f5f5f5/aaa?text=?'">
    </div>
    <div class="list-details">
      <h3><?= htmlspecialchars($iName) ?></h3>
      <?php if ($iSize): ?><div class="size-tag">Size: <?= htmlspecialchars($iSize) ?></div><?php endif; ?>
      <p class="list-price">MRP ₹<?= $iPrice ?></p>
      <?php if ($iStock !== null): ?>
        <?php if ($iStock === 0): ?><p class="stock-warn">❌ Out of stock</p>
        <?php elseif ($iStock <= 5): ?><p class="stock-warn">⚠ Only <?= $iStock ?> left!</p>
        <?php endif; ?>
      <?php endif; ?>
      <div class="list-qty-row">
        <div class="qty-box" data-name="<?= htmlspecialchars($iName) ?>" data-size="<?= htmlspecialchars($iSize) ?>" data-price="<?= $iPrice ?>">
          <button class="qty-btn minus">−</button>
          <span><?= $iQty ?></span>
          <button class="qty-btn plus">+</button>
        </div>
        <span class="list-subtotal" id="sub-<?= $iKey ?>">₹<?= $iPrice * $iQty ?></span>
      </div>
      <a href="cart.php?remove=<?= $iKey ?>" class="remove-link">Remove ✕</a>
    </div>
    <div class="list-actions">
      <div class="list-item-price" id="prc-<?= $iKey ?>">₹<?= $iPrice * $iQty ?></div>
    </div>
  </div>
  <?php endforeach; ?>

  <div class="summary-wrap">
    <div class="del-info">
      <div class="del-row"><span>Subtotal</span><span>₹<span id="subDisplay"><?= $sub ?></span></span></div>
      <?php if ($delivery === 0 && $sub > 0): ?>
        <div class="del-row saving"><span>🚚 Delivery</span><span>FREE</span></div>
      <?php else: ?>
        <div class="del-row"><span>Delivery</span><span>₹<span id="delDisplay"><?= $delivery ?></span></span></div>
      <?php endif; ?>
      <div class="del-row saving" id="couponRow" style="display:none;"><span>🎟️ Coupon</span><span>−₹<span id="couponSaveAmt">0</span></span></div>
      <div class="del-row total"><span>Total</span><span>₹<span id="totalDisplay"><?= $total ?></span></span></div>
    </div>

    <div id="delBanner"></div>
  </div>

  <div class="cart-total-box">
    <h2>Total: ₹<span id="grandTotal"><?= $total ?></span></h2>
    <button class="checkout-btn" id="checkoutBtn">Proceed to Checkout →</button>
  </div>
  <div class="list-footer"><a href="index.php">← Continue Shopping</a></div>
</div>
<?php endif; ?>
</div>

<footer><h1>☆ La Moda ☆</h1><p>Wear the Moment</p></footer>

<!-- ════ COUPON POPUP (between checkout → address) ════ -->
<div id="ovCoupon" class="ov">
<div class="coupon-popup">
  <button class="ov-close" onclick="closeOv('ovCoupon')">✕</button>
  <h2 style="font-family:var(--font-display,serif);font-size:20px;margin-bottom:4px;">🎟️ Do you have a coupon?</h2>
  <p style="font-size:12px;color:#aaa;margin-bottom:16px;">Apply a coupon code for a discount on this order</p>

  <!-- Saved coupons list -->
  <div id="myCouponsList" style="margin-bottom:14px;display:none;">
    <p style="font-size:11px;font-weight:600;color:#888;text-transform:uppercase;letter-spacing:.4px;margin-bottom:8px;">Your available coupons</p>
    <div id="myCouponsItems"></div>
  </div>

  <!-- Manual entry -->
  <div style="display:flex;gap:8px;margin-bottom:4px;">
    <input type="text" id="popCouponInput" placeholder="Enter coupon code" maxlength="20"
           style="flex:1;padding:10px 12px;border:1.5px solid #e0e0e0;border-radius:8px;font-size:13px;font-family:inherit;outline:none;text-transform:uppercase;">
    <button id="popApplyCouponBtn"
      style="padding:10px 18px;background:#8B2500;color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:700;cursor:pointer;font-family:inherit;white-space:nowrap;">
      Apply
    </button>
  </div>
  <p id="popCouponMsg" style="font-size:12px;margin-bottom:14px;min-height:18px;"></p>

  <button id="popContinueBtn" class="sbtn" style="margin-top:4px;">
    Continue to Address →
  </button>
  <p style="font-size:11px;color:#bbb;text-align:center;margin-top:10px;cursor:pointer;" onclick="skipCoupon()">
    Skip — I don't have a coupon
  </p>
</div>
</div>

<!-- ════ ADDRESS OVERLAY ════ -->
<div id="ovAddr" class="ov">
<div class="ob">
  <button class="ov-close" onclick="closeOv('ovAddr')">✕</button>
  <div class="steps">
    <div class="step active"><div class="step-dot">1</div>&nbsp;Address</div>
    <div class="step-line"></div>
    <div class="step"><div class="step-dot">2</div>&nbsp;Payment</div>
  </div>
  <h2>Delivery Address</h2>
  <p class="ov-sub">Where should we deliver your order?</p>

  <?php if (!empty($savedAddresses)): ?>
  <p style="font-size:12px;font-weight:600;color:#555;margin-bottom:8px;">Your saved addresses</p>
  <div class="addr-cards" id="savedAddrCards">
    <?php foreach ($savedAddresses as $ai => $sa): ?>
    <div class="addr-card<?= $ai===0?' sel':'' ?>" data-idx="<?= $ai ?>" onclick="selectSavedAddr(<?= $ai ?>)">
      <strong><?= htmlspecialchars((string)($sa['full_name']??'Address '.($ai+1))) ?></strong>
      <?= htmlspecialchars((string)($sa['flat']??'')) ?>, <?= htmlspecialchars((string)($sa['area']??'')) ?><br>
      <?= htmlspecialchars((string)($sa['city']??'')) ?> – <?= htmlspecialchars((string)($sa['pincode']??'')) ?><br>
      📱 <?= htmlspecialchars((string)($sa['mobile']??'')) ?>
      <?php if (!empty($sa['is_default'])): ?><br><span style="color:#8B2500;font-size:10px;font-weight:700;">★ Default</span><?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div>
  <div class="addr-btns">
    <button class="addr-btn sel" id="btnUseSaved" onclick="setAddrMode('saved')">Use selected</button>
    <button class="addr-btn"     id="btnNewAddr"  onclick="setAddrMode('new')">+ New address</button>
  </div>
  <?php endif; ?>

  <form id="addrForm" <?= !empty($savedAddresses)?'style="display:none"':'' ?> novalidate>
    <?= csrf_field() ?>
    <div class="ff">
      <label>Country *</label>
      <input list="ccl" id="f_country" name="country" placeholder="Select or type your country" maxlength="100" autocomplete="off">
      <datalist id="ccl">
        <option value="India"><option value="United States"><option value="United Kingdom">
        <option value="Australia"><option value="Canada"><option value="UAE"><option value="Singapore">
        <option value="Germany"><option value="France"><option value="Japan"><option value="New Zealand">
      </datalist>
      <p class="emsg" id="e_country">⚠ Country is required</p>
    </div>
    <div class="ff">
      <label>Full Name *</label>
      <input type="text" id="f_name" name="full_name" placeholder="e.g. Priya Sharma" maxlength="100">
      <p class="emsg" id="e_name">⚠ Letters and spaces only</p>
    </div>
    <div class="ff">
      <label>Mobile Number *</label>
      <input type="tel" id="f_mobile" name="mobile" placeholder="10-digit number" maxlength="10" inputmode="numeric" pattern="[0-9]*">
      <p class="emsg" id="e_mobile">⚠ Valid 10-digit number</p>
    </div>
    <div class="ff">
      <label>Email Address</label>
      <input type="email" id="f_email" name="email" placeholder="you@example.com" maxlength="150">
      <p class="hint">For order confirmation</p>
    </div>
    <div class="ff">
      <label>Flat / House No. *</label>
      <input type="text" id="f_flat" name="flat" placeholder="e.g. Flat 4B, Sunrise Apartments" maxlength="200">
      <p class="emsg" id="e_flat">⚠ Required</p>
    </div>
    <div class="ff">
      <label>Area / Street *</label>
      <input type="text" id="f_area" name="area" placeholder="e.g. T. Nagar" maxlength="200">
      <p class="emsg" id="e_area">⚠ Required</p>
    </div>
    <div class="ff">
      <label>Landmark *</label>
      <input type="text" id="f_landmark" name="landmark" placeholder="e.g. Near SBI Bank" maxlength="200">
      <p class="emsg" id="e_landmark">⚠ Required</p>
    </div>
    <div class="frow">
      <div class="ff">
        <label>Pincode *</label>
        <input type="text" id="f_pincode" name="pincode" placeholder="6-digit pincode" maxlength="6" inputmode="numeric">
        <p class="emsg" id="e_pincode">⚠ Valid 6-digit pincode</p>
        <p id="pincode_state_msg" style="font-size:11px;margin-top:3px;display:none;"></p>
      </div>
      <div class="ff">
        <label>City *</label>
        <input type="text" id="f_city" name="city" placeholder="e.g. Chennai" maxlength="100">
        <p class="emsg" id="e_city">⚠ Required</p>
      </div>
    </div>
    <div class="ff">
      <label>State / Province</label>
      <input type="text" id="f_state" name="state" placeholder="e.g. Tamil Nadu" maxlength="100">
    </div>
    <label style="display:flex;align-items:center;gap:8px;font-size:13px;color:#444;margin:8px 0;cursor:pointer;">
      <input type="checkbox" name="save_address" value="1" style="accent-color:#8B2500;"> Save this address
    </label>
    <label style="display:flex;align-items:center;gap:8px;font-size:13px;color:#444;margin-bottom:10px;cursor:pointer;">
      <input type="checkbox" name="is_default" value="1" style="accent-color:#8B2500;"> Set as Default Address
    </label>
    <div class="ff">
      <label>Delivery Instructions <span style="font-weight:400;color:#bbb;">(optional)</span></label>
      <textarea name="delivery_instructions" rows="2" maxlength="500" placeholder="Any special instructions…" style="resize:vertical;"></textarea>
    </div>
  </form>

  <!-- Applied coupon display -->
  <div id="appliedCouponBar" style="display:none;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:8px 12px;font-size:12px;color:#16a34a;font-weight:600;margin-bottom:10px;">
    🎟️ Coupon <span id="appliedCouponCode" style="font-family:monospace;font-size:14px;"></span> applied — <span id="appliedCouponPct"></span>% off!
    <span onclick="removeCoupon()" style="color:#dc2626;cursor:pointer;margin-left:8px;font-weight:700;">✕ Remove</span>
  </div>

  <input type="hidden" id="selectedAddrIdx" value="<?= empty($savedAddresses)?-1:0 ?>">
  <button class="sbtn" id="proceedToPayBtn">Proceed to Pay →</button>
</div>
</div>

<!-- ════ PAYMENT OVERLAY ════ -->
<div id="ovPay" class="ov">
<div class="ob">
  <button class="ov-close" onclick="closeOv('ovPay');openOv('ovAddr')">✕</button>
  <div class="steps">
    <div class="step done"><div class="step-dot">✓</div>&nbsp;Address</div>
    <div class="step-line"></div>
    <div class="step active"><div class="step-dot">2</div>&nbsp;Payment</div>
  </div>
  <h2>Choose Payment</h2>
  <p class="ov-sub">Select how you'd like to pay</p>
  <div style="font-size:14px;font-weight:600;color:#8B2500;margin-bottom:4px;">
    Amount: ₹<span id="payAmtSpan"><?= $total ?></span>
    <span id="couponSavingLine" style="font-size:12px;color:#16a34a;margin-left:8px;display:none;"></span>
  </div>
  <div id="payDeliveryLine" style="font-size:12px;color:#888;margin-bottom:14px;">
    Subtotal ₹<span id="paySubSpan"><?= $sub ?></span>
    + Delivery ₹<span id="payDelSpan"><?= $delivery ?></span>
    <?php if ($delivery === 0 && $sub > 0): ?><span style="color:#16a34a;font-weight:600;">(FREE 🎉)</span><?php endif; ?>
  </div>

  <div class="ptabs">
    <button class="ptab on" data-m="cod">💵 COD</button>
    <button class="ptab" data-m="upi">📱 UPI</button>
    <button class="ptab" data-m="card">💳 Card</button>
    <button class="ptab" data-m="emi">📅 EMI</button>
    <button class="ptab" data-m="bank">🏦 Net Banking</button>
  </div>

  <!-- COD -->
  <div class="ppanel on" id="pm_cod">
    <div style="display:flex;align-items:center;gap:12px;">
      <span style="font-size:32px;">💵</span>
      <div>
        <strong style="display:block;font-size:14px;margin-bottom:4px;">Cash on Delivery</strong>
        <span style="font-size:12px;color:#888;">Pay when your order arrives. No advance needed.</span>
      </div>
    </div>
  </div>

  <!-- UPI -->
  <div class="ppanel" id="pm_upi">
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px;">
      <img src="https://cashfree.com/devss/assets/images/logo/cashfree.svg" alt="Cashfree" height="20">
      <span style="font-size:12px;color:#888;">Secure UPI via Cashfree</span>
    </div>
    <div id="upiCashfreeBox" style="text-align:center;padding:14px;background:#f9f9f9;border-radius:8px;">
      <div id="upiLoadBtn">
        <button type="button" id="initCashfreeBtn"
          style="padding:10px 24px;background:#8B2500;color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:700;cursor:pointer;font-family:inherit;">
          Pay ₹<span id="cfAmtLabel"><?= $total ?></span> via UPI
        </button>
        <p style="font-size:11px;color:#aaa;margin-top:6px;">Redirected to Cashfree secure checkout</p>
      </div>
      <div id="upiLoadingBox" style="display:none;padding:16px;font-size:13px;color:#888;">Opening payment page…</div>
      <p id="cfUpiMsg" style="font-size:12px;margin-top:8px;"></p>
    </div>
    <p style="font-size:11px;color:#aaa;margin-top:8px;">🔒 PCI DSS Level 1 certified</p>
  </div>

  <!-- Card -->
  <div class="ppanel" id="pm_card">
    <div style="background:#fff5f2;color:#8B2500;border:1px solid #ffd6c4;border-radius:20px;font-size:11px;font-weight:600;padding:3px 10px;display:inline-block;margin-bottom:8px;">🎁 5% cashback on cards!</div>
    <input class="cfield" type="text" id="cardNum"  placeholder="Card Number (16 digits)" maxlength="19">
    <input class="cfield" type="text" id="cardName" placeholder="Name on Card (letters only)" maxlength="60">
    <p id="cardNameErr" style="font-size:11px;color:#dc2626;margin:-4px 0 8px;display:none;">⚠ Letters and spaces only</p>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
      <input class="cfield" style="margin:0" type="text" id="cardExp" placeholder="MM / YY" maxlength="7">
      <input class="cfield" style="margin:0" type="text" id="cardCvv" placeholder="CVV" maxlength="3">
    </div>
    <p style="font-size:11px;color:#aaa;margin-top:8px;">🔒 256-bit encrypted — demo only</p>
  </div>

  <!-- EMI -->
  <div class="ppanel" id="pm_emi">
    <p style="font-size:12px;color:#555;margin-bottom:8px;">Select your EMI plan:</p>
    <div class="emi-grid" id="emiGrid">
      <?php foreach ([3,6,9,12] as $t):
        $emi = $total > 0 ? (int)ceil($total/$t) : 0;
        $int = $t <= 6 ? 'No Cost' : '1.5% p.m.';
      ?>
      <button type="button" class="emi-opt" data-t="<?= $t ?>">
        <strong><?= $t ?> months</strong>
        <span class="emi-mo" style="font-size:13px;font-weight:600;color:#1e1e1e;display:block;">₹<?= $emi ?>/mo</span>
        <span style="font-size:10px;color:#8B2500;"><?= $int ?></span>
      </button>
      <?php endforeach; ?>
    </div>
    <!-- Bank + account details for EMI -->
    <div id="emiDetailsBox" style="display:none;margin-top:12px;">
      <p style="font-size:12px;font-weight:600;margin-bottom:8px;">Select bank for EMI:</p>
      <?php foreach (['HDFC Bank','ICICI Bank','SBI Card','Axis Bank','Kotak Bank'] as $bnk): ?>
      <label style="display:flex;align-items:center;gap:8px;font-size:13px;padding:6px 0;border-bottom:1px solid #f5f5f5;cursor:pointer;">
        <input type="radio" name="emiBank" value="<?= $bnk ?>" style="accent-color:#8B2500;"> <?= $bnk ?>
      </label>
      <?php endforeach; ?>
      <div class="bank-details-box" id="emiBankDetailsBox" style="display:none;margin-top:10px;">
        <p>Enter your bank account details</p>
        <input class="bank-field" type="text" id="emiAccNum"  placeholder="Account Number" maxlength="18" inputmode="numeric">
        <input class="bank-field" type="text" id="emiIfsc"    placeholder="IFSC Code (e.g. SBIN0001234)" maxlength="11" style="text-transform:uppercase;">
        <input class="bank-field" type="text" id="emiAccName" placeholder="Account Holder Name" maxlength="80">
        <p style="font-size:10px;color:#aaa;margin-top:4px;">🔒 Demo mode — no real transaction</p>
      </div>
    </div>
  </div>

  <!-- Net Banking -->
  <div class="ppanel" id="pm_bank">
    <p style="font-size:12px;color:#555;margin-bottom:8px;">Select your bank:</p>
    <div class="bank-list" id="bankList">
      <?php foreach ([
        ['sbi',   '#1a3d7c','🏛️','State Bank of India',  'Net Banking / YONO'],
        ['hdfc',  '#004c8c','🏦','HDFC Bank',            'NetBanking'],
        ['icici', '#b02121','🏦','ICICI Bank',           'iMobile / Net Banking'],
        ['axis',  '#97144d','🏦','Axis Bank',            'Internet Banking'],
        ['kotak', '#ed1c24','🏦','Kotak Mahindra Bank',  'Net Banking'],
      ] as [$bk,$col,$ico,$bn,$bs]): ?>
      <div class="bank-opt" data-b="<?= $bk ?>">
        <div class="bank-logo" style="background:<?= $col ?>;"><?= $ico ?></div>
        <div><strong style="display:block;"><?= $bn ?></strong><span style="font-size:11px;color:#888;"><?= $bs ?></span></div>
      </div>
      <?php endforeach; ?>
    </div>
    <div class="bank-details-box" id="bankDetailsBox" style="display:none;">
      <p>Enter your bank account details</p>
      <input class="bank-field" type="text" id="bankAccNum"  placeholder="Account Number" maxlength="18" inputmode="numeric">
      <input class="bank-field" type="text" id="bankIfsc"    placeholder="IFSC Code (e.g. SBIN0001234)" maxlength="11" style="text-transform:uppercase;">
      <input class="bank-field" type="text" id="bankAccName" placeholder="Account Holder Name" maxlength="80">
      <p style="font-size:10px;color:#aaa;margin-top:4px;">🔒 Demo mode — no real transaction</p>
    </div>
  </div>

  <input type="hidden" id="finalPayMethod" value="cod">
  <button class="sbtn" id="placeOrderBtn">Confirm &amp; Place Order 🎉</button>
  <button class="sbtn-outline" onclick="closeOv('ovPay');openOv('ovAddr')">← Back to Address</button>
</div>
</div>

<!-- ════ SUCCESS ════ -->
<div id="successOv">
<div id="successBox">
  <div style="font-size:48px;margin-bottom:10px;">🎉</div>
  <h2 style="font-size:20px;margin-bottom:6px;">Order Placed!</h2>
  <p style="font-size:13px;color:#888;margin-bottom:14px;">Thank you! Your order is confirmed.<br>We'll deliver it soon 💗</p>
  <div id="couponReveal" style="display:none;" class="coupon-reveal">
    <p style="font-size:12px;color:#8B2500;font-weight:600;margin-bottom:4px;">🎁 You won a coupon!</p>
    <div class="c-code" id="couponCodeDisplay"></div>
    <p style="font-size:11px;color:#888;" id="couponDiscountText"></p>
    <p style="font-size:10px;color:#aaa;margin-top:4px;">Valid 30 days · Next order only</p>
  </div>
  <button onclick="window.location='index.php'"
    style="padding:11px 28px;background:#8B2500;color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:700;cursor:pointer;margin-top:10px;">
    Continue Shopping →
  </button>
</div>
</div>

<script>
const cartCSRF      = <?= json_encode(csrf_token()) ?>;
const cartPageItems = <?= json_encode(array_values($cart)) ?>;
const cartLoggedIn  = <?= is_logged_in() ? 'true' : 'false' ?>;
let gSub      = <?= (int)$sub ?>;
let gDelivery = <?= (int)$delivery ?>;
let gTotal    = <?= (int)$total ?>;
let couponPct = 0, couponApplied = '';
let addrMode  = '<?= empty($savedAddresses) ? "new" : "saved" ?>';
let selectedAddrIdx = <?= empty($savedAddresses) ? -1 : 0 ?>;
let selectedEmiTenure = null;
let selectedBank      = null;

/* ── Helpers ── */
function openOv(id)  { document.getElementById(id).classList.add('open');    document.body.style.overflow='hidden'; }
function closeOv(id) { document.getElementById(id).classList.remove('open'); document.body.style.overflow=''; }
['ovCoupon','ovAddr','ovPay'].forEach(id=>{
  document.getElementById(id)?.addEventListener('click',e=>{ if(e.target.id===id) closeOv(id); });
});
function toast(msg,type){
  const t=document.getElementById('toast'); if(!t) return;
  t.textContent=msg; t.className='toast show'+(type==='error'?' toast-error':'');
  clearTimeout(t._tid); t._tid=setTimeout(()=>t.classList.remove('show'),3200);
}

/* ── Checkout → Coupon popup first ── */
document.getElementById('checkoutBtn')?.addEventListener('click',()=>{
  if(!cartLoggedIn){ if(typeof openLogin==='function') openLogin(); else toast('Please log in','error'); return; }
  openCouponPopup();
});

function openCouponPopup(){
  // Reset popup state
  document.getElementById('popCouponInput').value = '';
  document.getElementById('popCouponMsg').textContent = '';
  // Reflect already-applied coupon
  if(couponApplied){
    document.getElementById('popCouponInput').value = couponApplied;
    document.getElementById('popCouponMsg').style.color='#16a34a';
    document.getElementById('popCouponMsg').textContent='✅ '+couponPct+'% discount applied!';
  }
  // Fetch user's available coupons
  fetch('get_user_coupons.php').then(r=>r.json()).then(res=>{
    const list=document.getElementById('myCouponsItems');
    const wrap=document.getElementById('myCouponsList');
    if(res.coupons&&res.coupons.length>0){
      wrap.style.display='block';
      list.innerHTML=res.coupons.map(c=>`
        <button class="coupon-chip${couponApplied===c.code?' sel':''}" onclick="selectCouponChip('${c.code}',${c.discount})">
          <span class="cc">${c.code}</span>
          <span class="cd">${c.discount}% off</span>
          <span class="ck">${couponApplied===c.code?'✅':'→'}</span>
        </button>`).join('');
    } else {
      wrap.style.display='none';
    }
  }).catch(()=>{});
  openOv('ovCoupon');
}

function selectCouponChip(code,discount){
  document.getElementById('popCouponInput').value=code.toUpperCase();
  applyCouponCode(code,discount);
}

function applyCouponCode(code,discountHint){
  const msg=document.getElementById('popCouponMsg');
  if(!code){ msg.style.color='#dc2626'; msg.textContent='Enter a coupon code'; return; }
  const fd=new FormData(); fd.append('csrf_token',cartCSRF); fd.append('code',code);
  fetch('apply_coupon.php',{method:'POST',body:fd}).then(r=>r.json()).then(res=>{
    if(res.error){ msg.style.color='#dc2626'; msg.textContent='❌ '+res.error; couponPct=0; couponApplied=''; updateAppliedBar(); }
    else{
      couponPct=res.discount; couponApplied=code;
      msg.style.color='#16a34a'; msg.textContent='✅ '+res.discount+'% discount applied!';
      recalc(); updateAppliedBar();
      // Refresh chip highlights
      document.querySelectorAll('.coupon-chip').forEach(c=>{ c.classList.toggle('sel',c.querySelector('.cc')?.textContent===code); c.querySelector('.ck').textContent=c.querySelector('.cc')?.textContent===code?'✅':'→'; });
    }
  }).catch(()=>{ msg.style.color='#dc2626'; msg.textContent='Could not verify coupon'; });
}

function updateAppliedBar(){
  const bar=document.getElementById('appliedCouponBar');
  if(couponApplied&&couponPct>0){
    bar.style.display='block';
    document.getElementById('appliedCouponCode').textContent=couponApplied;
    document.getElementById('appliedCouponPct').textContent=couponPct;
  } else {
    bar.style.display='none';
  }
}

function removeCoupon(){
  couponPct=0; couponApplied='';
  updateAppliedBar(); recalc();
  const sl=document.getElementById('couponSavingLine'); if(sl) sl.style.display='none';
}

function skipCoupon(){
  couponPct=0; couponApplied='';
  closeOv('ovCoupon');
  openOv('ovAddr');
}

document.getElementById('popApplyCouponBtn')?.addEventListener('click',function(){
  const code=document.getElementById('popCouponInput').value.trim().toUpperCase();
  applyCouponCode(code);
});
document.getElementById('popCouponInput')?.addEventListener('input',function(){
  this.value=this.value.toUpperCase();
});

document.getElementById('popContinueBtn')?.addEventListener('click',function(){
  closeOv('ovCoupon');
  openOv('ovAddr');
});

/* ── Recalc ── */
function recalc(){
  gSub=cartPageItems.reduce((s,i)=>s+(parseInt(i.price)||0)*(parseInt(i.qty)||1),0);
  if(gSub<=0)        gDelivery=0;
  else if(gSub>1000) gDelivery=0;
  else if(gSub>=500) gDelivery=40;
  else               gDelivery=50;
  const disc=couponPct>0?Math.round((gSub+gDelivery)*couponPct/100):0;
  gTotal=gSub+gDelivery-disc;
  const q=id=>document.getElementById(id);
  if(q('grandTotal'))    q('grandTotal').textContent=gTotal;
  if(q('subDisplay'))    q('subDisplay').textContent=gSub;
  if(q('delDisplay'))    q('delDisplay').textContent=gDelivery;
  if(q('totalDisplay'))  q('totalDisplay').textContent=gTotal;
  if(q('payAmtSpan'))    q('payAmtSpan').textContent=gTotal;
  if(q('cfAmtLabel'))    q('cfAmtLabel').textContent=gTotal;
  if(q('paySubSpan'))    q('paySubSpan').textContent=gSub;
  if(q('payDelSpan'))    q('payDelSpan').textContent=gDelivery;
  if(q('couponRow'))     q('couponRow').style.display=disc>0?'':'none';
  if(q('couponSaveAmt')) q('couponSaveAmt').textContent=disc;
  const sl=q('couponSavingLine');
  if(sl){ if(disc>0){sl.style.display='inline';sl.textContent='('+couponPct+'% off)';}else sl.style.display='none'; }
  // Update delivery banner dynamically
  const banner = document.getElementById('delBanner');
  if (banner) {
    if (gSub <= 0) {
      banner.innerHTML = '';
    } else if (gDelivery === 0) {
      banner.innerHTML = '<div class="del-banner del-free">🎉 You qualify for FREE delivery!</div>';
    } else if (gSub >= 500) {
      const need = 1001 - gSub;
      banner.innerHTML = '<div class="del-banner del-paid">🚚 ₹40 delivery. Spend ₹' + need + ' more for FREE!</div>';
    } else {
      const need = 500 - gSub;
      banner.innerHTML = '<div class="del-banner del-paid">🚚 ₹50 delivery. Spend ₹' + need + ' more to reduce to ₹40!</div>';
    }
  }
  // Recalc EMI monthly amounts
  document.querySelectorAll('#emiGrid .emi-opt').forEach(o=>{
    const t=parseInt(o.dataset.t)||1;
    o.querySelector('.emi-mo').textContent='₹'+Math.ceil(gTotal/t)+'/mo';
  });
}

recalc(); // initialise banner and totals on load

/* ── Qty controls ── */
document.addEventListener('click',function(e){
  const isP=e.target.classList.contains('plus'), isM=e.target.classList.contains('minus');
  if(!isP&&!isM) return;
  const box=e.target.closest('.qty-box'); if(!box) return;
  e.target.disabled=true;
  const name=box.dataset.name, size=box.dataset.size||'';
  const fd=new FormData();
  fd.append('csrf_token',cartCSRF); fd.append('name',name); fd.append('size',size); fd.append('action',isP?'plus':'minus');
  fetch('update_cart.php',{method:'POST',body:fd}).then(r=>r.json()).then(res=>{
    if(res.error){ toast(res.error,'error'); box.querySelectorAll('button').forEach(b=>b.disabled=false); return; }
    if(res.removed){
      document.getElementById('cr-'+encodeURIComponent(name))?.remove();
      const idx=cartPageItems.findIndex(x=>x.name===name); if(idx>-1) cartPageItems.splice(idx,1);
      if(!cartPageItems.length){ location.reload(); return; }
    } else {
      box.querySelector('span').textContent=res.qty;
      box.querySelectorAll('button').forEach(b=>b.disabled=false);
      const it=cartPageItems.find(x=>x.name===name); if(it) it.qty=res.qty;
      const s=(parseInt(box.dataset.price)||0)*res.qty;
      const se=document.getElementById('sub-'+encodeURIComponent(name)); if(se) se.textContent='₹'+s;
      const pe=document.getElementById('prc-'+encodeURIComponent(name)); if(pe) pe.textContent='₹'+s;
    }
    recalc();
  }).catch(()=>{ box.querySelectorAll('button').forEach(b=>b.disabled=false); });
});

/* ── Address mode ── */
function setAddrMode(mode){
  addrMode=mode;
  const form=document.getElementById('addrForm');
  document.getElementById('btnUseSaved')?.classList.toggle('sel',mode==='saved');
  document.getElementById('btnNewAddr')?.classList.toggle('sel',mode==='new');
  if(form) form.style.display=mode==='new'?'':'none';
}
function selectSavedAddr(idx){
  selectedAddrIdx=idx;
  document.querySelectorAll('.addr-card').forEach(c=>c.classList.remove('sel'));
  document.querySelector(`.addr-card[data-idx="${idx}"]`)?.classList.add('sel');
  setAddrMode('saved');
}

/* ── Address validation ── */
const addrRules={
  f_country:  {ok:v=>v.trim().length>=2,                eid:'e_country'},
  f_name:     {ok:v=>/^[A-Za-z\s]{2,}$/.test(v.trim()), eid:'e_name'},
  f_mobile:   {ok:v=>/^[6-9][0-9]{9}$/.test(v.trim()),  eid:'e_mobile'},
  f_flat:     {ok:v=>v.trim().length>=2,                eid:'e_flat'},
  f_area:     {ok:v=>v.trim().length>=2,                eid:'e_area'},
  f_landmark: {ok:v=>v.trim().length>=2,                eid:'e_landmark'},
  f_pincode:  {ok:v=>/^[1-9][0-9]{5}$/.test(v.trim()),  eid:'e_pincode'},
  f_city:     {ok:v=>v.trim().length>=2,                eid:'e_city'},
};
function validateAddr(){
  let ok=true,first=null;
  Object.entries(addrRules).forEach(([fid,rule])=>{
    const el=document.getElementById(fid),em=document.getElementById(rule.eid); if(!el) return;
    const pass=rule.ok(el.value);
    el.classList.toggle('bad',!pass); el.classList.toggle('good',pass&&el.value.trim().length>0);
    if(em) em.classList.toggle('show',!pass);
    if(!pass){ok=false;if(!first)first=el;}
  });
  if(first){first.scrollIntoView({behavior:'smooth',block:'center'});first.focus();}
  return ok;
}

/* ── Proceed to Pay ── */
document.getElementById('proceedToPayBtn')?.addEventListener('click',function(){
  if(addrMode==='new'&&!validateAddr()) return;
  closeOv('ovAddr'); openOv('ovPay'); recalc();
});

/* ── Payment tabs ── */
document.querySelectorAll('.ptab').forEach(tab=>{
  tab.addEventListener('click',()=>{
    document.querySelectorAll('.ptab').forEach(t=>t.classList.remove('on'));
    document.querySelectorAll('.ppanel').forEach(p=>p.classList.remove('on'));
    tab.classList.add('on');
    document.getElementById('finalPayMethod').value=tab.dataset.m;
    document.getElementById('pm_'+tab.dataset.m)?.classList.add('on');
  });
});

/* ── EMI — JS variable tracking ── */
document.querySelectorAll('#emiGrid .emi-opt').forEach(o=>{
  o.addEventListener('click',()=>{
    document.querySelectorAll('#emiGrid .emi-opt').forEach(x=>x.classList.remove('on'));
    o.classList.add('on');
    selectedEmiTenure=parseInt(o.dataset.t);
    document.getElementById('emiDetailsBox').style.display='block';
  });
});
// Show bank account fields when EMI bank is selected
document.querySelectorAll('input[name="emiBank"]').forEach(r=>{
  r.addEventListener('change',()=>{
    document.getElementById('emiBankDetailsBox').style.display='block';
  });
});

/* ── Bank — JS variable tracking ── */
document.querySelectorAll('#bankList .bank-opt').forEach(b=>{
  b.addEventListener('click',()=>{
    document.querySelectorAll('#bankList .bank-opt').forEach(x=>x.classList.remove('on'));
    b.classList.add('on');
    selectedBank=b.dataset.b;
    document.getElementById('bankDetailsBox').style.display='block';
  });
});

/* ── IFSC formatting ── */
['bankIfsc','emiIfsc'].forEach(id=>{
  document.getElementById(id)?.addEventListener('input',function(){ this.value=this.value.toUpperCase().replace(/[^A-Z0-9]/g,''); });
});
['bankAccNum','emiAccNum'].forEach(id=>{
  document.getElementById(id)?.addEventListener('input',function(){ this.value=this.value.replace(/[^0-9]/g,''); });
});

/* ── Card formatting ── */
document.getElementById('cardName')?.addEventListener('input',function(){ this.value=this.value.replace(/[^A-Za-z\s]/g,''); const e=document.getElementById('cardNameErr'); if(e) e.style.display='none'; });
document.getElementById('cardNum')?.addEventListener('input',function(){const d=this.value.replace(/\D/g,'').substring(0,16);this.value=d.match(/.{1,4}/g)?.join(' ')||d;});
document.getElementById('cardExp')?.addEventListener('input',function(){const d=this.value.replace(/\D/g,'');this.value=d.length>=2?d.substring(0,2)+' / '+d.substring(2,4):d;});
document.getElementById('cardCvv')?.addEventListener('input',function(){this.value=this.value.replace(/\D/g,'').substring(0,3);});

/* ── Cashfree UPI ── */
document.getElementById('initCashfreeBtn')?.addEventListener('click',function(){
  const btn=this;
  btn.disabled=true;
  document.getElementById('upiLoadBtn').style.display='none';
  document.getElementById('upiLoadingBox').style.display='block';
  document.getElementById('cfUpiMsg').textContent='';

  function showErr(msg){
    document.getElementById('cfUpiMsg').style.color='#dc2626';
    document.getElementById('cfUpiMsg').textContent='❌ '+msg;
    document.getElementById('upiLoadingBox').style.display='none';
    document.getElementById('upiLoadBtn').style.display='block';
    btn.disabled=false;
  }

  // Step 1: Create Cashfree payment order
  const cfFd=new FormData();
  cfFd.append('csrf_token',cartCSRF); cfFd.append('amount',gTotal);
  cfFd.append('name',document.getElementById('f_name')?.value||'');
  cfFd.append('mobile',document.getElementById('f_mobile')?.value||'');
  cfFd.append('email',document.getElementById('f_email')?.value||'');
  fetch('cashfree_payment.php',{method:'POST',body:cfFd}).then(r=>r.json()).then(res=>{
    if(res.error){ showErr(res.error); return; }
    const sessionId=res.payment_session_id;
    const cfOrderId=res.order_id||'';

    // Step 2: Save pending order to DB (so payment_return.php can finalize & award coupon)
    const pFd=new FormData();
    pFd.append('csrf_token',cartCSRF);
    pFd.append('order_type','cart');
    pFd.append('coupon_code',couponApplied);
    pFd.append('cashfree_order_id',cfOrderId);
    if(addrMode==='saved'&&selectedAddrIdx>=0){
      pFd.append('using_saved','1'); pFd.append('use_saved_addr',selectedAddrIdx);
    } else {
      ['country','full_name','mobile','email','flat','area','landmark','pincode','city','state','delivery_instructions'].forEach(n=>{
        const el=document.querySelector('#addrForm [name="'+n+'"]'); if(el) pFd.append(n,el.value);
      });
    }
    fetch('save_pending_order.php',{method:'POST',body:pFd}).then(r=>r.json()).then(pr=>{
      if(pr.error){ showErr('Could not save order: '+pr.error); return; }
      // Step 3: Launch Cashfree SDK
      const s=document.createElement('script');
      s.src='https://sdk.cashfree.com/js/v3/cashfree.js';
      s.onload=()=>{ const cf=Cashfree({mode:res.test_mode?'sandbox':'production'}); cf.checkout({paymentSessionId:sessionId,redirectTarget:'_self'}); };
      s.onerror=()=>{ showErr('Could not load payment SDK'); };
      document.head.appendChild(s);
    }).catch(()=>{ showErr('Network error saving order'); });
  }).catch(()=>{ showErr('Network error'); });
});

/* ── Place Order ── */
document.getElementById('placeOrderBtn')?.addEventListener('click',function(){
  const method=document.getElementById('finalPayMethod').value;

  if(method==='card'){
    const cn=(document.getElementById('cardNum')?.value||'').replace(/\s/g,'');
    const cname=(document.getElementById('cardName')?.value||'').trim();
    const exp=(document.getElementById('cardExp')?.value||'').trim();
    const cvv=(document.getElementById('cardCvv')?.value||'').trim();
    if(cn.length<16){toast('Enter valid 16-digit card number','error');return;}
    if(!cname){toast('Enter name on card','error');return;}
    if(!/^[A-Za-z\s]+$/.test(cname)){toast('Name on card: letters only','error');return;}
    if(!/^\d{2} \/ \d{2}$/.test(exp)){toast('Enter valid expiry MM / YY','error');return;}
    if(cvv.length<3){toast('Enter 3-digit CVV','error');return;}
  }

  if(method==='emi'){
    if(!selectedEmiTenure){toast('Select an EMI tenure','error');return;}
    if(!document.querySelector('input[name="emiBank"]:checked')){toast('Select your bank for EMI','error');return;}
    const acc=(document.getElementById('emiAccNum')?.value||'').trim();
    const ifc=(document.getElementById('emiIfsc')?.value||'').trim();
    const anm=(document.getElementById('emiAccName')?.value||'').trim();
    if(!acc){toast('Enter account number for EMI','error');return;}
    if(!/^[A-Z]{4}0[A-Z0-9]{6}$/.test(ifc)){toast('Enter valid IFSC code (e.g. SBIN0001234)','error');return;}
    if(!anm){toast('Enter account holder name for EMI','error');return;}
  }

  if(method==='bank'){
    if(!selectedBank){toast('Select your bank','error');return;}
    const acc=(document.getElementById('bankAccNum')?.value||'').trim();
    const ifc=(document.getElementById('bankIfsc')?.value||'').trim();
    const anm=(document.getElementById('bankAccName')?.value||'').trim();
    if(!acc){toast('Enter account number','error');return;}
    if(!/^[A-Z]{4}0[A-Z0-9]{6}$/.test(ifc)){toast('Enter valid IFSC code (e.g. SBIN0001234)','error');return;}
    if(!anm){toast('Enter account holder name','error');return;}
  }

  this.disabled=true; this.textContent='Placing order…';

  const fd=new FormData();
  fd.append('csrf_token',cartCSRF);
  fd.append('order_type','cart');
  fd.append('payment_method',method);
  fd.append('coupon_code',couponApplied);

  if(addrMode==='saved'&&selectedAddrIdx>=0){
    fd.append('using_saved','1'); fd.append('use_saved_addr',selectedAddrIdx);
  } else {
    ['country','full_name','mobile','email','flat','area','landmark','pincode','city','state','delivery_instructions'].forEach(n=>{
      const el=document.querySelector(`#addrForm [name="${n}"]`); if(el) fd.append(n,el.value);
    });
    const sa=document.querySelector('#addrForm [name="save_address"]');
    const sd=document.querySelector('#addrForm [name="is_default"]');
    if(sa?.checked) fd.append('save_address','1');
    if(sd?.checked) fd.append('is_default','1');
  }

  fetch('place_order.php',{method:'POST',body:fd})
    .then(r=>{if(!r.ok)throw new Error('HTTP '+r.status);return r.json();})
    .then(res=>{
      if(res.error){toast(res.error,'error');this.disabled=false;this.textContent='Confirm & Place Order 🎉';return;}
      closeOv('ovPay');
      if(res.coupon){
        document.getElementById('couponReveal').style.display='block';
        document.getElementById('couponCodeDisplay').textContent=res.coupon.code;
        document.getElementById('couponDiscountText').textContent=res.coupon.discount+'% off your next order';
      }
      document.getElementById('successOv').classList.add('open');
      cartPageItems.length=0;
      document.querySelectorAll('.cart-count-badge').forEach(e=>e.textContent='0');
    })
    .catch(err=>{toast('Network error: '+err.message,'error');this.disabled=false;this.textContent='Confirm & Place Order 🎉';});
});
</script>
<script src="form_validation.js"></script>
</body>
</html><?php
session_start();
include __DIR__ . "/db.php";
include __DIR__ . "/auth.php";

// Sync cart from MongoDB
if (is_logged_in()) {
    $__cartUser = $users->findOne(['username' => current_user()['username']]);
    if (!empty($__cartUser['cart']) && is_array($__cartUser['cart'])) {
        $_SESSION['cart'] = $__cartUser['cart'];
    }
}

// Remove item
if (isset($_GET['remove'])) {
    $r = clean($_GET['remove'], 200);
    $_SESSION['cart'] = array_values(array_filter($_SESSION['cart'] ?? [], fn($i) => ($i['name'] ?? '') !== $r));
    if (is_logged_in()) {
        $users->updateOne(['username' => current_user()['username']], ['$set' => ['cart' => $_SESSION['cart']]]);
    }
    header("Location: cart.php"); exit();
}

$cart = $_SESSION['cart'] ?? [];
$sub  = 0;
foreach ($cart as $item) {
    $sub += (int)($item['price'] ?? 0) * (int)($item['qty'] ?? 1);
}
$sub      = (int)$sub;
$delivery = 0;
if ($sub > 0 && $sub < 500)          $delivery = 50;
elseif ($sub >= 500 && $sub <= 1000) $delivery = 40;
$delivery = (int)$delivery;
$total    = $sub + $delivery;

$needed_for_free    = max(0, 1001 - $sub);
$needed_for_cheaper = max(0, 500  - $sub);

// Load saved addresses
$savedAddresses = [];
if (is_logged_in()) {
    $u = $users->findOne(['username' => current_user()['username']]);
    if (!empty($u['addresses']) && is_array($u['addresses'])) {
        $savedAddresses = $u['addresses'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>La Moda | My Cart</title>
<link rel="stylesheet" href="styles.css">
<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>👗</text></svg>">
<style>
/* ── Overlays ── */
.ov{display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,.52);z-index:8000;overflow-y:auto;padding:20px 12px 60px;}
.ov.open{display:block;}
.ob{background:#fff;width:540px;max-width:100%;margin:0 auto;border-radius:14px;padding:24px 20px 20px;position:relative;}
.ob h2{font-size:19px;font-family:var(--font-display,serif);margin-bottom:3px;}
.ov-sub{font-size:12px;color:#aaa;margin-bottom:14px;}
.ov-close{position:absolute;top:11px;right:14px;background:none;border:none;font-size:22px;cursor:pointer;color:#999;line-height:1;}

/* ── Form ── */
.ff{margin-bottom:10px;}
.ff label{display:block;font-size:11px;font-weight:600;color:#666;text-transform:uppercase;letter-spacing:.4px;margin-bottom:4px;}
.ff input,.ff select,.ff textarea{width:100%;padding:9px 12px;border:1.5px solid #e0e0e0;border-radius:8px;font-size:13px;font-family:inherit;outline:none;box-sizing:border-box;transition:border-color .18s;}
.ff input:focus,.ff select:focus,.ff textarea:focus{border-color:#8B2500;}
.ff input.bad{border-color:#dc2626!important;background:#fff8f8;}
.ff input.good{border-color:#16a34a!important;}
.ff .emsg{font-size:11px;color:#dc2626;margin-top:3px;display:none;}
.ff .emsg.show{display:block;}
.ff .hint{font-size:11px;color:#bbb;margin-top:3px;}
.frow{display:grid;grid-template-columns:1fr 1fr;gap:10px;}

/* ── Steps ── */
.steps{display:flex;align-items:center;gap:6px;margin-bottom:16px;}
.step{display:flex;align-items:center;gap:6px;font-size:12px;font-weight:500;color:#bbb;}
.step.done{color:#16a34a;}.step.active{color:#8B2500;font-weight:700;}
.step-dot{width:22px;height:22px;border-radius:50%;border:2px solid currentColor;display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:700;flex-shrink:0;}
.step-line{flex:1;height:1px;background:#e0e0e0;min-width:20px;}

/* ── Saved addresses ── */
.addr-cards{display:flex;gap:10px;margin-bottom:12px;flex-wrap:wrap;}
.addr-card{flex:1;min-width:170px;padding:12px 14px;border:1.5px solid #e0e0e0;border-radius:10px;cursor:pointer;transition:all .18s;font-size:12px;}
.addr-card:hover,.addr-card.sel{border-color:#8B2500;background:#fff5f2;}
.addr-card strong{display:block;font-size:13px;margin-bottom:4px;}
.addr-btns{display:flex;gap:8px;margin-bottom:14px;flex-wrap:wrap;}
.addr-btn{padding:8px 16px;border:1.5px solid #e0e0e0;border-radius:20px;font-size:12px;font-weight:500;background:#fff;cursor:pointer;font-family:inherit;transition:all .18s;}
.addr-btn.sel{border-color:#8B2500;background:#8B2500;color:#fff;}

/* ── Payment tabs ── */
.ptabs{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:10px;}
.ptab{padding:7px 13px;border:1.5px solid #e0e0e0;border-radius:20px;font-size:12px;font-weight:500;background:#fff;cursor:pointer;font-family:inherit;transition:all .18s;}
.ptab.on{border-color:#8B2500;background:#8B2500;color:#fff;}
.ppanel{display:none;padding:13px;background:#fafafa;border:1px solid #eee;border-radius:8px;margin-bottom:10px;font-size:13px;}
.ppanel.on{display:block;}
.cfield{width:100%;padding:9px 12px;border:1.5px solid #e0e0e0;border-radius:8px;font-size:13px;font-family:inherit;outline:none;margin-bottom:8px;box-sizing:border-box;}
.cfield:focus{border-color:#8B2500;}

/* ── EMI ── */
.emi-grid{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-top:6px;}
.emi-opt{padding:10px;border:1.5px solid #e0e0e0;border-radius:8px;text-align:center;cursor:pointer;font-family:inherit;background:#fff;transition:all .18s;}
.emi-opt.on{border-color:#8B2500!important;background:#fff5f2!important;color:#8B2500;}
.emi-opt strong{display:block;font-size:14px;}

/* ── Banks ── */
.bank-list{display:flex;flex-direction:column;gap:6px;margin-top:6px;}
.bank-opt{display:flex;align-items:center;gap:10px;padding:10px 12px;border:1.5px solid #e0e0e0;border-radius:8px;cursor:pointer;font-size:13px;transition:all .18s;}
.bank-opt.on{border-color:#8B2500!important;background:#fff5f2!important;}
.bank-logo{width:28px;height:28px;border-radius:6px;display:flex;align-items:center;justify-content:center;font-size:14px;flex-shrink:0;}

/* ── Bank details box ── */
.bank-details-box{margin-top:12px;padding:14px;background:#fef9ec;border:1px solid #fde68a;border-radius:10px;}
.bank-details-box p{font-size:12px;font-weight:700;color:#555;margin-bottom:10px;}
.bank-field{width:100%;padding:9px 12px;border:1.5px solid #e0e0e0;border-radius:8px;font-size:13px;font-family:inherit;outline:none;margin-bottom:8px;box-sizing:border-box;}
.bank-field:focus{border-color:#8B2500;}

/* ── Buttons ── */
.sbtn{width:100%;padding:13px;background:#8B2500;color:#fff;border:none;border-radius:8px;font-size:14px;font-weight:700;cursor:pointer;font-family:inherit;margin-top:12px;transition:background .2s;}
.sbtn:hover:not(:disabled){background:#5c1800;}
.sbtn:disabled{opacity:.6;cursor:not-allowed;}
.sbtn-outline{width:100%;padding:11px;border:1.5px solid #8B2500;color:#8B2500;background:#fff;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;font-family:inherit;margin-top:8px;}

/* ── Coupon popup ── */
.coupon-popup{background:#fff;width:420px;max-width:100%;margin:0 auto;border-radius:14px;padding:28px 24px 24px;position:relative;}
.coupon-chip{display:inline-flex;align-items:center;gap:8px;padding:10px 16px;border:1.5px solid #e0e0e0;border-radius:10px;cursor:pointer;font-size:13px;font-family:inherit;background:#fff;transition:all .18s;width:100%;margin-bottom:8px;text-align:left;}
.coupon-chip:hover,.coupon-chip.sel{border-color:#8B2500;background:#fff5f2;}
.coupon-chip .cc{font-weight:800;color:#8B2500;letter-spacing:1px;font-size:14px;font-family:monospace;}
.coupon-chip .cd{font-size:11px;color:#888;margin-left:4px;}
.coupon-chip .ck{margin-left:auto;font-size:16px;}

/* ── Success ── */
#successOv{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9999;align-items:center;justify-content:center;}
#successOv.open{display:flex;}
#successBox{background:#fff;border-radius:16px;padding:36px 28px;text-align:center;width:340px;max-width:95vw;}
.coupon-reveal{margin:14px 0;padding:14px;background:#fff5f2;border:1.5px dashed #8B2500;border-radius:10px;}
.c-code{font-size:22px;font-weight:800;color:#8B2500;letter-spacing:2px;margin:6px 0;font-family:monospace;}

/* ── Cart layout ── */
.list-box{background:#fff;border-radius:14px;box-shadow:0 2px 16px rgba(0,0,0,.06);overflow:hidden;}
.list-item{display:flex;align-items:flex-start;gap:14px;padding:16px;border-bottom:1px solid #f5f5f5;}
.list-img{width:80px;height:90px;border-radius:10px;overflow:hidden;flex-shrink:0;background:#f5f5f5;}
.list-img img{width:100%;height:100%;object-fit:cover;}
.list-details{flex:1;min-width:0;}
.list-details h3{font-size:14px;font-weight:600;margin-bottom:3px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.size-tag{display:inline-block;background:#f5f5f5;color:#666;font-size:11px;padding:2px 8px;border-radius:4px;margin-bottom:4px;}
.list-price{font-size:12px;color:#888;margin-bottom:6px;}
.stock-warn{font-size:11px;color:#dc2626;font-weight:600;margin-bottom:4px;}
.list-qty-row{display:flex;align-items:center;gap:12px;flex-wrap:wrap;}
.qty-box{display:flex;align-items:center;background:#f5f5f5;border-radius:8px;overflow:hidden;}
.qty-btn{width:32px;height:32px;border:none;background:none;font-size:18px;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:background .15s;color:#333;}
.qty-btn:hover:not(:disabled){background:#e8e8e8;}
.qty-btn:disabled{opacity:.4;}
.qty-box span{min-width:30px;text-align:center;font-size:13px;font-weight:700;}
.list-subtotal{font-size:12px;color:#8B2500;font-weight:600;}
.remove-link{font-size:11px;color:#dc2626;margin-top:6px;display:inline-block;}
.list-actions{flex-shrink:0;padding-top:4px;}
.list-item-price{font-size:15px;font-weight:700;color:#8B2500;}
.summary-wrap{padding:16px;}
.del-info{background:#f9f9f9;border:1px solid #eee;border-radius:10px;padding:12px 14px;margin-bottom:10px;}
.del-row{display:flex;justify-content:space-between;font-size:13px;padding:3px 0;}
.del-row.total{font-weight:700;font-size:15px;border-top:1px solid #eee;margin-top:6px;padding-top:8px;}
.del-row.saving{color:#16a34a;font-weight:600;}
.del-banner{padding:10px 14px;border-radius:8px;font-size:12px;font-weight:500;margin-bottom:12px;}
.del-free{background:#f0fdf4;color:#16a34a;border:1px solid #bbf7d0;}
.del-paid{background:#fef9ec;color:#d97706;border:1px solid #fde68a;}
.cart-total-box{padding:16px;border-top:1px solid #f0f0f0;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;}
.cart-total-box h2{font-size:18px;margin:0;}
.checkout-btn{padding:13px 28px;background:#8B2500;color:#fff;border:none;border-radius:8px;font-size:14px;font-weight:700;cursor:pointer;font-family:inherit;transition:background .2s;white-space:nowrap;}
.checkout-btn:hover{background:#5c1800;}
.list-footer{padding:14px 16px;font-size:13px;border-top:1px solid #f5f5f5;}
.list-footer a{color:#8B2500;text-decoration:none;font-weight:500;}

@media(max-width:600px){
  .ob,.coupon-popup{padding:16px 12px;}
  .frow{grid-template-columns:1fr;}
  .list-item{gap:10px;padding:12px;}
  .list-img{width:64px;height:74px;}
  .cart-total-box{flex-direction:column;align-items:stretch;}
  .checkout-btn{width:100%;text-align:center;}
  .ptabs{gap:4px;}
  .ptab{padding:6px 10px;font-size:11px;}
  .addr-cards{flex-direction:column;}
}
</style>
</head>
<body>
<?php include __DIR__ . "/navbar.php"; ?>

<div class="page-wrap">
<h1 class="page-title">🛒 My Cart</h1>

<?php if (empty($cart)): ?>
<div class="empty-state">
  <div class="empty-icon">🛒</div>
  <p>Your cart is empty.</p>
  <a href="index.php" class="empty-link">Start Shopping →</a>
</div>
<?php else: ?>

<div class="list-box">
  <?php foreach ($cart as $item):
    $iName  = (string)($item['name']  ?? '');
    $iPrice = (int)($item['price']    ?? 0);
    $iQty   = (int)($item['qty']      ?? 1);
    $iImg   = (string)($item['image'] ?? '');
    $iSize  = (string)($item['size']  ?? '');
    $iStock = isset($item['stock']) ? (int)$item['stock'] : null;
    $iKey   = urlencode($iName);
  ?>
  <div class="list-item" id="cr-<?= $iKey ?>">
    <div class="list-img">
      <img src="<?= htmlspecialchars($iImg) ?>" alt="<?= htmlspecialchars($iName) ?>"
           onerror="this.src='https://placehold.co/80x90/f5f5f5/aaa?text=?'">
    </div>
    <div class="list-details">
      <h3><?= htmlspecialchars($iName) ?></h3>
      <?php if ($iSize): ?><div class="size-tag">Size: <?= htmlspecialchars($iSize) ?></div><?php endif; ?>
      <p class="list-price">MRP ₹<?= $iPrice ?></p>
      <?php if ($iStock !== null): ?>
        <?php if ($iStock === 0): ?><p class="stock-warn">❌ Out of stock</p>
        <?php elseif ($iStock <= 5): ?><p class="stock-warn">⚠ Only <?= $iStock ?> left!</p>
        <?php endif; ?>
      <?php endif; ?>
      <div class="list-qty-row">
        <div class="qty-box" data-name="<?= htmlspecialchars($iName) ?>" data-size="<?= htmlspecialchars($iSize) ?>" data-price="<?= $iPrice ?>">
          <button class="qty-btn minus">−</button>
          <span><?= $iQty ?></span>
          <button class="qty-btn plus">+</button>
        </div>
        <span class="list-subtotal" id="sub-<?= $iKey ?>">₹<?= $iPrice * $iQty ?></span>
      </div>
      <a href="cart.php?remove=<?= $iKey ?>" class="remove-link">Remove ✕</a>
    </div>
    <div class="list-actions">
      <div class="list-item-price" id="prc-<?= $iKey ?>">₹<?= $iPrice * $iQty ?></div>
    </div>
  </div>
  <?php endforeach; ?>

  <div class="summary-wrap">
    <div class="del-info">
      <div class="del-row"><span>Subtotal</span><span>₹<span id="subDisplay"><?= $sub ?></span></span></div>
      <?php if ($delivery === 0 && $sub > 0): ?>
        <div class="del-row saving"><span>🚚 Delivery</span><span>FREE</span></div>
      <?php else: ?>
        <div class="del-row"><span>Delivery</span><span>₹<span id="delDisplay"><?= $delivery ?></span></span></div>
      <?php endif; ?>
      <div class="del-row saving" id="couponRow" style="display:none;"><span>🎟️ Coupon</span><span>−₹<span id="couponSaveAmt">0</span></span></div>
      <div class="del-row total"><span>Total</span><span>₹<span id="totalDisplay"><?= $total ?></span></span></div>
    </div>

    <div id="delBanner"></div>
  </div>

  <div class="cart-total-box">
    <h2>Total: ₹<span id="grandTotal"><?= $total ?></span></h2>
    <button class="checkout-btn" id="checkoutBtn">Proceed to Checkout →</button>
  </div>
  <div class="list-footer"><a href="index.php">← Continue Shopping</a></div>
</div>
<?php endif; ?>
</div>

<footer><h1>☆ La Moda ☆</h1><p>Wear the Moment</p></footer>

<!-- ════ COUPON POPUP (between checkout → address) ════ -->
<div id="ovCoupon" class="ov">
<div class="coupon-popup">
  <button class="ov-close" onclick="closeOv('ovCoupon')">✕</button>
  <h2 style="font-family:var(--font-display,serif);font-size:20px;margin-bottom:4px;">🎟️ Do you have a coupon?</h2>
  <p style="font-size:12px;color:#aaa;margin-bottom:16px;">Apply a coupon code for a discount on this order</p>

  <!-- Saved coupons list -->
  <div id="myCouponsList" style="margin-bottom:14px;display:none;">
    <p style="font-size:11px;font-weight:600;color:#888;text-transform:uppercase;letter-spacing:.4px;margin-bottom:8px;">Your available coupons</p>
    <div id="myCouponsItems"></div>
  </div>

  <!-- Manual entry -->
  <div style="display:flex;gap:8px;margin-bottom:4px;">
    <input type="text" id="popCouponInput" placeholder="Enter coupon code" maxlength="20"
           style="flex:1;padding:10px 12px;border:1.5px solid #e0e0e0;border-radius:8px;font-size:13px;font-family:inherit;outline:none;text-transform:uppercase;">
    <button id="popApplyCouponBtn"
      style="padding:10px 18px;background:#8B2500;color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:700;cursor:pointer;font-family:inherit;white-space:nowrap;">
      Apply
    </button>
  </div>
  <p id="popCouponMsg" style="font-size:12px;margin-bottom:14px;min-height:18px;"></p>

  <button id="popContinueBtn" class="sbtn" style="margin-top:4px;">
    Continue to Address →
  </button>
  <p style="font-size:11px;color:#bbb;text-align:center;margin-top:10px;cursor:pointer;" onclick="skipCoupon()">
    Skip — I don't have a coupon
  </p>
</div>
</div>

<!-- ════ ADDRESS OVERLAY ════ -->
<div id="ovAddr" class="ov">
<div class="ob">
  <button class="ov-close" onclick="closeOv('ovAddr')">✕</button>
  <div class="steps">
    <div class="step active"><div class="step-dot">1</div>&nbsp;Address</div>
    <div class="step-line"></div>
    <div class="step"><div class="step-dot">2</div>&nbsp;Payment</div>
  </div>
  <h2>Delivery Address</h2>
  <p class="ov-sub">Where should we deliver your order?</p>

  <?php if (!empty($savedAddresses)): ?>
  <p style="font-size:12px;font-weight:600;color:#555;margin-bottom:8px;">Your saved addresses</p>
  <div class="addr-cards" id="savedAddrCards">
    <?php foreach ($savedAddresses as $ai => $sa): ?>
    <div class="addr-card<?= $ai===0?' sel':'' ?>" data-idx="<?= $ai ?>" onclick="selectSavedAddr(<?= $ai ?>)">
      <strong><?= htmlspecialchars((string)($sa['full_name']??'Address '.($ai+1))) ?></strong>
      <?= htmlspecialchars((string)($sa['flat']??'')) ?>, <?= htmlspecialchars((string)($sa['area']??'')) ?><br>
      <?= htmlspecialchars((string)($sa['city']??'')) ?> – <?= htmlspecialchars((string)($sa['pincode']??'')) ?><br>
      📱 <?= htmlspecialchars((string)($sa['mobile']??'')) ?>
      <?php if (!empty($sa['is_default'])): ?><br><span style="color:#8B2500;font-size:10px;font-weight:700;">★ Default</span><?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div>
  <div class="addr-btns">
    <button class="addr-btn sel" id="btnUseSaved" onclick="setAddrMode('saved')">Use selected</button>
    <button class="addr-btn"     id="btnNewAddr"  onclick="setAddrMode('new')">+ New address</button>
  </div>
  <?php endif; ?>

  <form id="addrForm" <?= !empty($savedAddresses)?'style="display:none"':'' ?> novalidate>
    <?= csrf_field() ?>
    <div class="ff">
      <label>Country *</label>
      <input list="ccl" id="f_country" name="country" placeholder="Select or type your country" maxlength="100" autocomplete="off">
      <datalist id="ccl">
        <option value="India"><option value="United States"><option value="United Kingdom">
        <option value="Australia"><option value="Canada"><option value="UAE"><option value="Singapore">
        <option value="Germany"><option value="France"><option value="Japan"><option value="New Zealand">
      </datalist>
      <p class="emsg" id="e_country">⚠ Country is required</p>
    </div>
    <div class="ff">
      <label>Full Name *</label>
      <input type="text" id="f_name" name="full_name" placeholder="e.g. Priya Sharma" maxlength="100">
      <p class="emsg" id="e_name">⚠ Letters and spaces only</p>
    </div>
    <div class="ff">
      <label>Mobile Number *</label>
      <input type="tel" id="f_mobile" name="mobile" placeholder="10-digit number" maxlength="10" inputmode="numeric" pattern="[0-9]*">
      <p class="emsg" id="e_mobile">⚠ Valid 10-digit number</p>
    </div>
    <div class="ff">
      <label>Email Address</label>
      <input type="email" id="f_email" name="email" placeholder="you@example.com" maxlength="150">
      <p class="hint">For order confirmation</p>
    </div>
    <div class="ff">
      <label>Flat / House No. *</label>
      <input type="text" id="f_flat" name="flat" placeholder="e.g. Flat 4B, Sunrise Apartments" maxlength="200">
      <p class="emsg" id="e_flat">⚠ Required</p>
    </div>
    <div class="ff">
      <label>Area / Street *</label>
      <input type="text" id="f_area" name="area" placeholder="e.g. T. Nagar" maxlength="200">
      <p class="emsg" id="e_area">⚠ Required</p>
    </div>
    <div class="ff">
      <label>Landmark *</label>
      <input type="text" id="f_landmark" name="landmark" placeholder="e.g. Near SBI Bank" maxlength="200">
      <p class="emsg" id="e_landmark">⚠ Required</p>
    </div>
    <div class="frow">
      <div class="ff">
        <label>Pincode *</label>
        <input type="text" id="f_pincode" name="pincode" placeholder="6-digit pincode" maxlength="6" inputmode="numeric">
        <p class="emsg" id="e_pincode">⚠ Valid 6-digit pincode</p>
        <p id="pincode_state_msg" style="font-size:11px;margin-top:3px;display:none;"></p>
      </div>
      <div class="ff">
        <label>City *</label>
        <input type="text" id="f_city" name="city" placeholder="e.g. Chennai" maxlength="100">
        <p class="emsg" id="e_city">⚠ Required</p>
      </div>
    </div>
    <div class="ff">
      <label>State / Province</label>
      <input type="text" id="f_state" name="state" placeholder="e.g. Tamil Nadu" maxlength="100">
    </div>
    <label style="display:flex;align-items:center;gap:8px;font-size:13px;color:#444;margin:8px 0;cursor:pointer;">
      <input type="checkbox" name="save_address" value="1" style="accent-color:#8B2500;"> Save this address
    </label>
    <label style="display:flex;align-items:center;gap:8px;font-size:13px;color:#444;margin-bottom:10px;cursor:pointer;">
      <input type="checkbox" name="is_default" value="1" style="accent-color:#8B2500;"> Set as Default Address
    </label>
    <div class="ff">
      <label>Delivery Instructions <span style="font-weight:400;color:#bbb;">(optional)</span></label>
      <textarea name="delivery_instructions" rows="2" maxlength="500" placeholder="Any special instructions…" style="resize:vertical;"></textarea>
    </div>
  </form>

  <!-- Applied coupon display -->
  <div id="appliedCouponBar" style="display:none;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:8px 12px;font-size:12px;color:#16a34a;font-weight:600;margin-bottom:10px;">
    🎟️ Coupon <span id="appliedCouponCode" style="font-family:monospace;font-size:14px;"></span> applied — <span id="appliedCouponPct"></span>% off!
    <span onclick="removeCoupon()" style="color:#dc2626;cursor:pointer;margin-left:8px;font-weight:700;">✕ Remove</span>
  </div>

  <input type="hidden" id="selectedAddrIdx" value="<?= empty($savedAddresses)?-1:0 ?>">
  <button class="sbtn" id="proceedToPayBtn">Proceed to Pay →</button>
</div>
</div>

<!-- ════ PAYMENT OVERLAY ════ -->
<div id="ovPay" class="ov">
<div class="ob">
  <button class="ov-close" onclick="closeOv('ovPay');openOv('ovAddr')">✕</button>
  <div class="steps">
    <div class="step done"><div class="step-dot">✓</div>&nbsp;Address</div>
    <div class="step-line"></div>
    <div class="step active"><div class="step-dot">2</div>&nbsp;Payment</div>
  </div>
  <h2>Choose Payment</h2>
  <p class="ov-sub">Select how you'd like to pay</p>
  <div style="font-size:14px;font-weight:600;color:#8B2500;margin-bottom:4px;">
    Amount: ₹<span id="payAmtSpan"><?= $total ?></span>
    <span id="couponSavingLine" style="font-size:12px;color:#16a34a;margin-left:8px;display:none;"></span>
  </div>
  <div id="payDeliveryLine" style="font-size:12px;color:#888;margin-bottom:14px;">
    Subtotal ₹<span id="paySubSpan"><?= $sub ?></span>
    + Delivery ₹<span id="payDelSpan"><?= $delivery ?></span>
    <?php if ($delivery === 0 && $sub > 0): ?><span style="color:#16a34a;font-weight:600;">(FREE 🎉)</span><?php endif; ?>
  </div>

  <div class="ptabs">
    <button class="ptab on" data-m="cod">💵 COD</button>
    <button class="ptab" data-m="upi">📱 UPI</button>
    <button class="ptab" data-m="card">💳 Card</button>
    <button class="ptab" data-m="emi">📅 EMI</button>
    <button class="ptab" data-m="bank">🏦 Net Banking</button>
  </div>

  <!-- COD -->
  <div class="ppanel on" id="pm_cod">
    <div style="display:flex;align-items:center;gap:12px;">
      <span style="font-size:32px;">💵</span>
      <div>
        <strong style="display:block;font-size:14px;margin-bottom:4px;">Cash on Delivery</strong>
        <span style="font-size:12px;color:#888;">Pay when your order arrives. No advance needed.</span>
      </div>
    </div>
  </div>

  <!-- UPI -->
  <div class="ppanel" id="pm_upi">
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px;">
      <img src="https://cashfree.com/devss/assets/images/logo/cashfree.svg" alt="Cashfree" height="20">
      <span style="font-size:12px;color:#888;">Secure UPI via Cashfree</span>
    </div>
    <div id="upiCashfreeBox" style="text-align:center;padding:14px;background:#f9f9f9;border-radius:8px;">
      <div id="upiLoadBtn">
        <button type="button" id="initCashfreeBtn"
          style="padding:10px 24px;background:#8B2500;color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:700;cursor:pointer;font-family:inherit;">
          Pay ₹<span id="cfAmtLabel"><?= $total ?></span> via UPI
        </button>
        <p style="font-size:11px;color:#aaa;margin-top:6px;">Redirected to Cashfree secure checkout</p>
      </div>
      <div id="upiLoadingBox" style="display:none;padding:16px;font-size:13px;color:#888;">Opening payment page…</div>
      <p id="cfUpiMsg" style="font-size:12px;margin-top:8px;"></p>
    </div>
    <p style="font-size:11px;color:#aaa;margin-top:8px;">🔒 PCI DSS Level 1 certified</p>
  </div>

  <!-- Card -->
  <div class="ppanel" id="pm_card">
    <div style="background:#fff5f2;color:#8B2500;border:1px solid #ffd6c4;border-radius:20px;font-size:11px;font-weight:600;padding:3px 10px;display:inline-block;margin-bottom:8px;">🎁 5% cashback on cards!</div>
    <input class="cfield" type="text" id="cardNum"  placeholder="Card Number (16 digits)" maxlength="19">
    <input class="cfield" type="text" id="cardName" placeholder="Name on Card (letters only)" maxlength="60">
    <p id="cardNameErr" style="font-size:11px;color:#dc2626;margin:-4px 0 8px;display:none;">⚠ Letters and spaces only</p>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
      <input class="cfield" style="margin:0" type="text" id="cardExp" placeholder="MM / YY" maxlength="7">
      <input class="cfield" style="margin:0" type="text" id="cardCvv" placeholder="CVV" maxlength="3">
    </div>
    <p style="font-size:11px;color:#aaa;margin-top:8px;">🔒 256-bit encrypted — demo only</p>
  </div>

  <!-- EMI -->
  <div class="ppanel" id="pm_emi">
    <p style="font-size:12px;color:#555;margin-bottom:8px;">Select your EMI plan:</p>
    <div class="emi-grid" id="emiGrid">
      <?php foreach ([3,6,9,12] as $t):
        $emi = $total > 0 ? (int)ceil($total/$t) : 0;
        $int = $t <= 6 ? 'No Cost' : '1.5% p.m.';
      ?>
      <button type="button" class="emi-opt" data-t="<?= $t ?>">
        <strong><?= $t ?> months</strong>
        <span class="emi-mo" style="font-size:13px;font-weight:600;color:#1e1e1e;display:block;">₹<?= $emi ?>/mo</span>
        <span style="font-size:10px;color:#8B2500;"><?= $int ?></span>
      </button>
      <?php endforeach; ?>
    </div>
    <!-- Bank + account details for EMI -->
    <div id="emiDetailsBox" style="display:none;margin-top:12px;">
      <p style="font-size:12px;font-weight:600;margin-bottom:8px;">Select bank for EMI:</p>
      <?php foreach (['HDFC Bank','ICICI Bank','SBI Card','Axis Bank','Kotak Bank'] as $bnk): ?>
      <label style="display:flex;align-items:center;gap:8px;font-size:13px;padding:6px 0;border-bottom:1px solid #f5f5f5;cursor:pointer;">
        <input type="radio" name="emiBank" value="<?= $bnk ?>" style="accent-color:#8B2500;"> <?= $bnk ?>
      </label>
      <?php endforeach; ?>
      <div class="bank-details-box" id="emiBankDetailsBox" style="display:none;margin-top:10px;">
        <p>Enter your bank account details</p>
        <input class="bank-field" type="text" id="emiAccNum"  placeholder="Account Number" maxlength="18" inputmode="numeric">
        <input class="bank-field" type="text" id="emiIfsc"    placeholder="IFSC Code (e.g. SBIN0001234)" maxlength="11" style="text-transform:uppercase;">
        <input class="bank-field" type="text" id="emiAccName" placeholder="Account Holder Name" maxlength="80">
        <p style="font-size:10px;color:#aaa;margin-top:4px;">🔒 Demo mode — no real transaction</p>
      </div>
    </div>
  </div>

  <!-- Net Banking -->
  <div class="ppanel" id="pm_bank">
    <p style="font-size:12px;color:#555;margin-bottom:8px;">Select your bank:</p>
    <div class="bank-list" id="bankList">
      <?php foreach ([
        ['sbi',   '#1a3d7c','🏛️','State Bank of India',  'Net Banking / YONO'],
        ['hdfc',  '#004c8c','🏦','HDFC Bank',            'NetBanking'],
        ['icici', '#b02121','🏦','ICICI Bank',           'iMobile / Net Banking'],
        ['axis',  '#97144d','🏦','Axis Bank',            'Internet Banking'],
        ['kotak', '#ed1c24','🏦','Kotak Mahindra Bank',  'Net Banking'],
      ] as [$bk,$col,$ico,$bn,$bs]): ?>
      <div class="bank-opt" data-b="<?= $bk ?>">
        <div class="bank-logo" style="background:<?= $col ?>;"><?= $ico ?></div>
        <div><strong style="display:block;"><?= $bn ?></strong><span style="font-size:11px;color:#888;"><?= $bs ?></span></div>
      </div>
      <?php endforeach; ?>
    </div>
    <div class="bank-details-box" id="bankDetailsBox" style="display:none;">
      <p>Enter your bank account details</p>
      <input class="bank-field" type="text" id="bankAccNum"  placeholder="Account Number" maxlength="18" inputmode="numeric">
      <input class="bank-field" type="text" id="bankIfsc"    placeholder="IFSC Code (e.g. SBIN0001234)" maxlength="11" style="text-transform:uppercase;">
      <input class="bank-field" type="text" id="bankAccName" placeholder="Account Holder Name" maxlength="80">
      <p style="font-size:10px;color:#aaa;margin-top:4px;">🔒 Demo mode — no real transaction</p>
    </div>
  </div>

  <input type="hidden" id="finalPayMethod" value="cod">
  <button class="sbtn" id="placeOrderBtn">Confirm &amp; Place Order 🎉</button>
  <button class="sbtn-outline" onclick="closeOv('ovPay');openOv('ovAddr')">← Back to Address</button>
</div>
</div>

<!-- ════ SUCCESS ════ -->
<div id="successOv">
<div id="successBox">
  <div style="font-size:48px;margin-bottom:10px;">🎉</div>
  <h2 style="font-size:20px;margin-bottom:6px;">Order Placed!</h2>
  <p style="font-size:13px;color:#888;margin-bottom:14px;">Thank you! Your order is confirmed.<br>We'll deliver it soon 💗</p>
  <div id="couponReveal" style="display:none;" class="coupon-reveal">
    <p style="font-size:12px;color:#8B2500;font-weight:600;margin-bottom:4px;">🎁 You won a coupon!</p>
    <div class="c-code" id="couponCodeDisplay"></div>
    <p style="font-size:11px;color:#888;" id="couponDiscountText"></p>
    <p style="font-size:10px;color:#aaa;margin-top:4px;">Valid 30 days · Next order only</p>
  </div>
  <button onclick="window.location='index.php'"
    style="padding:11px 28px;background:#8B2500;color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:700;cursor:pointer;margin-top:10px;">
    Continue Shopping →
  </button>
</div>
</div>

<script>
const cartCSRF      = <?= json_encode(csrf_token()) ?>;
const cartPageItems = <?= json_encode(array_values($cart)) ?>;
const cartLoggedIn  = <?= is_logged_in() ? 'true' : 'false' ?>;
let gSub      = <?= (int)$sub ?>;
let gDelivery = <?= (int)$delivery ?>;
let gTotal    = <?= (int)$total ?>;
let couponPct = 0, couponApplied = '';
let addrMode  = '<?= empty($savedAddresses) ? "new" : "saved" ?>';
let selectedAddrIdx = <?= empty($savedAddresses) ? -1 : 0 ?>;
let selectedEmiTenure = null;
let selectedBank      = null;

/* ── Helpers ── */
function openOv(id)  { document.getElementById(id).classList.add('open');    document.body.style.overflow='hidden'; }
function closeOv(id) { document.getElementById(id).classList.remove('open'); document.body.style.overflow=''; }
['ovCoupon','ovAddr','ovPay'].forEach(id=>{
  document.getElementById(id)?.addEventListener('click',e=>{ if(e.target.id===id) closeOv(id); });
});
function toast(msg,type){
  const t=document.getElementById('toast'); if(!t) return;
  t.textContent=msg; t.className='toast show'+(type==='error'?' toast-error':'');
  clearTimeout(t._tid); t._tid=setTimeout(()=>t.classList.remove('show'),3200);
}

/* ── Checkout → Coupon popup first ── */
document.getElementById('checkoutBtn')?.addEventListener('click',()=>{
  if(!cartLoggedIn){ if(typeof openLogin==='function') openLogin(); else toast('Please log in','error'); return; }
  openCouponPopup();
});

function openCouponPopup(){
  // Reset popup state
  document.getElementById('popCouponInput').value = '';
  document.getElementById('popCouponMsg').textContent = '';
  // Reflect already-applied coupon
  if(couponApplied){
    document.getElementById('popCouponInput').value = couponApplied;
    document.getElementById('popCouponMsg').style.color='#16a34a';
    document.getElementById('popCouponMsg').textContent='✅ '+couponPct+'% discount applied!';
  }
  // Fetch user's available coupons
  fetch('get_user_coupons.php').then(r=>r.json()).then(res=>{
    const list=document.getElementById('myCouponsItems');
    const wrap=document.getElementById('myCouponsList');
    if(res.coupons&&res.coupons.length>0){
      wrap.style.display='block';
      list.innerHTML=res.coupons.map(c=>`
        <button class="coupon-chip${couponApplied===c.code?' sel':''}" onclick="selectCouponChip('${c.code}',${c.discount})">
          <span class="cc">${c.code}</span>
          <span class="cd">${c.discount}% off</span>
          <span class="ck">${couponApplied===c.code?'✅':'→'}</span>
        </button>`).join('');
    } else {
      wrap.style.display='none';
    }
  }).catch(()=>{});
  openOv('ovCoupon');
}

function selectCouponChip(code,discount){
  document.getElementById('popCouponInput').value=code.toUpperCase();
  applyCouponCode(code,discount);
}

function applyCouponCode(code,discountHint){
  const msg=document.getElementById('popCouponMsg');
  if(!code){ msg.style.color='#dc2626'; msg.textContent='Enter a coupon code'; return; }
  const fd=new FormData(); fd.append('csrf_token',cartCSRF); fd.append('code',code);
  fetch('apply_coupon.php',{method:'POST',body:fd}).then(r=>r.json()).then(res=>{
    if(res.error){ msg.style.color='#dc2626'; msg.textContent='❌ '+res.error; couponPct=0; couponApplied=''; updateAppliedBar(); }
    else{
      couponPct=res.discount; couponApplied=code;
      msg.style.color='#16a34a'; msg.textContent='✅ '+res.discount+'% discount applied!';
      recalc(); updateAppliedBar();
      // Refresh chip highlights
      document.querySelectorAll('.coupon-chip').forEach(c=>{ c.classList.toggle('sel',c.querySelector('.cc')?.textContent===code); c.querySelector('.ck').textContent=c.querySelector('.cc')?.textContent===code?'✅':'→'; });
    }
  }).catch(()=>{ msg.style.color='#dc2626'; msg.textContent='Could not verify coupon'; });
}

function updateAppliedBar(){
  const bar=document.getElementById('appliedCouponBar');
  if(couponApplied&&couponPct>0){
    bar.style.display='block';
    document.getElementById('appliedCouponCode').textContent=couponApplied;
    document.getElementById('appliedCouponPct').textContent=couponPct;
  } else {
    bar.style.display='none';
  }
}

function removeCoupon(){
  couponPct=0; couponApplied='';
  updateAppliedBar(); recalc();
  const sl=document.getElementById('couponSavingLine'); if(sl) sl.style.display='none';
}

function skipCoupon(){
  couponPct=0; couponApplied='';
  closeOv('ovCoupon');
  openOv('ovAddr');
}

document.getElementById('popApplyCouponBtn')?.addEventListener('click',function(){
  const code=document.getElementById('popCouponInput').value.trim().toUpperCase();
  applyCouponCode(code);
});
document.getElementById('popCouponInput')?.addEventListener('input',function(){
  this.value=this.value.toUpperCase();
});

document.getElementById('popContinueBtn')?.addEventListener('click',function(){
  closeOv('ovCoupon');
  openOv('ovAddr');
});

/* ── Recalc ── */
function recalc(){
  gSub=cartPageItems.reduce((s,i)=>s+(parseInt(i.price)||0)*(parseInt(i.qty)||1),0);
  if(gSub<=0)        gDelivery=0;
  else if(gSub>1000) gDelivery=0;
  else if(gSub>=500) gDelivery=40;
  else               gDelivery=50;
  const disc=couponPct>0?Math.round((gSub+gDelivery)*couponPct/100):0;
  gTotal=gSub+gDelivery-disc;
  const q=id=>document.getElementById(id);
  if(q('grandTotal'))    q('grandTotal').textContent=gTotal;
  if(q('subDisplay'))    q('subDisplay').textContent=gSub;
  if(q('delDisplay'))    q('delDisplay').textContent=gDelivery;
  if(q('totalDisplay'))  q('totalDisplay').textContent=gTotal;
  if(q('payAmtSpan'))    q('payAmtSpan').textContent=gTotal;
  if(q('cfAmtLabel'))    q('cfAmtLabel').textContent=gTotal;
  if(q('paySubSpan'))    q('paySubSpan').textContent=gSub;
  if(q('payDelSpan'))    q('payDelSpan').textContent=gDelivery;
  if(q('couponRow'))     q('couponRow').style.display=disc>0?'':'none';
  if(q('couponSaveAmt')) q('couponSaveAmt').textContent=disc;
  const sl=q('couponSavingLine');
  if(sl){ if(disc>0){sl.style.display='inline';sl.textContent='('+couponPct+'% off)';}else sl.style.display='none'; }
  // Update delivery banner dynamically
  const banner = document.getElementById('delBanner');
  if (banner) {
    if (gSub <= 0) {
      banner.innerHTML = '';
    } else if (gDelivery === 0) {
      banner.innerHTML = '<div class="del-banner del-free">🎉 You qualify for FREE delivery!</div>';
    } else if (gSub >= 500) {
      const need = 1001 - gSub;
      banner.innerHTML = '<div class="del-banner del-paid">🚚 ₹40 delivery. Spend ₹' + need + ' more for FREE!</div>';
    } else {
      const need = 500 - gSub;
      banner.innerHTML = '<div class="del-banner del-paid">🚚 ₹50 delivery. Spend ₹' + need + ' more to reduce to ₹40!</div>';
    }
  }
  // Recalc EMI monthly amounts
  document.querySelectorAll('#emiGrid .emi-opt').forEach(o=>{
    const t=parseInt(o.dataset.t)||1;
    o.querySelector('.emi-mo').textContent='₹'+Math.ceil(gTotal/t)+'/mo';
  });
}

recalc(); // initialise banner and totals on load

/* ── Qty controls ── */
document.addEventListener('click',function(e){
  const isP=e.target.classList.contains('plus'), isM=e.target.classList.contains('minus');
  if(!isP&&!isM) return;
  const box=e.target.closest('.qty-box'); if(!box) return;
  e.target.disabled=true;
  const name=box.dataset.name, size=box.dataset.size||'';
  const fd=new FormData();
  fd.append('csrf_token',cartCSRF); fd.append('name',name); fd.append('size',size); fd.append('action',isP?'plus':'minus');
  fetch('update_cart.php',{method:'POST',body:fd}).then(r=>r.json()).then(res=>{
    if(res.error){ toast(res.error,'error'); box.querySelectorAll('button').forEach(b=>b.disabled=false); return; }
    if(res.removed){
      document.getElementById('cr-'+encodeURIComponent(name))?.remove();
      const idx=cartPageItems.findIndex(x=>x.name===name); if(idx>-1) cartPageItems.splice(idx,1);
      if(!cartPageItems.length){ location.reload(); return; }
    } else {
      box.querySelector('span').textContent=res.qty;
      box.querySelectorAll('button').forEach(b=>b.disabled=false);
      const it=cartPageItems.find(x=>x.name===name); if(it) it.qty=res.qty;
      const s=(parseInt(box.dataset.price)||0)*res.qty;
      const se=document.getElementById('sub-'+encodeURIComponent(name)); if(se) se.textContent='₹'+s;
      const pe=document.getElementById('prc-'+encodeURIComponent(name)); if(pe) pe.textContent='₹'+s;
    }
    recalc();
  }).catch(()=>{ box.querySelectorAll('button').forEach(b=>b.disabled=false); });
});

/* ── Address mode ── */
function setAddrMode(mode){
  addrMode=mode;
  const form=document.getElementById('addrForm');
  document.getElementById('btnUseSaved')?.classList.toggle('sel',mode==='saved');
  document.getElementById('btnNewAddr')?.classList.toggle('sel',mode==='new');
  if(form) form.style.display=mode==='new'?'':'none';
}
function selectSavedAddr(idx){
  selectedAddrIdx=idx;
  document.querySelectorAll('.addr-card').forEach(c=>c.classList.remove('sel'));
  document.querySelector(`.addr-card[data-idx="${idx}"]`)?.classList.add('sel');
  setAddrMode('saved');
}

/* ── Address validation ── */
const addrRules={
  f_country:  {ok:v=>v.trim().length>=2,                eid:'e_country'},
  f_name:     {ok:v=>/^[A-Za-z\s]{2,}$/.test(v.trim()), eid:'e_name'},
  f_mobile:   {ok:v=>/^[6-9][0-9]{9}$/.test(v.trim()),  eid:'e_mobile'},
  f_flat:     {ok:v=>v.trim().length>=2,                eid:'e_flat'},
  f_area:     {ok:v=>v.trim().length>=2,                eid:'e_area'},
  f_landmark: {ok:v=>v.trim().length>=2,                eid:'e_landmark'},
  f_pincode:  {ok:v=>/^[1-9][0-9]{5}$/.test(v.trim()),  eid:'e_pincode'},
  f_city:     {ok:v=>v.trim().length>=2,                eid:'e_city'},
};
function validateAddr(){
  let ok=true,first=null;
  Object.entries(addrRules).forEach(([fid,rule])=>{
    const el=document.getElementById(fid),em=document.getElementById(rule.eid); if(!el) return;
    const pass=rule.ok(el.value);
    el.classList.toggle('bad',!pass); el.classList.toggle('good',pass&&el.value.trim().length>0);
    if(em) em.classList.toggle('show',!pass);
    if(!pass){ok=false;if(!first)first=el;}
  });
  if(first){first.scrollIntoView({behavior:'smooth',block:'center'});first.focus();}
  return ok;
}

/* ── Proceed to Pay ── */
document.getElementById('proceedToPayBtn')?.addEventListener('click',function(){
  if(addrMode==='new'&&!validateAddr()) return;
  closeOv('ovAddr'); openOv('ovPay'); recalc();
});

/* ── Payment tabs ── */
document.querySelectorAll('.ptab').forEach(tab=>{
  tab.addEventListener('click',()=>{
    document.querySelectorAll('.ptab').forEach(t=>t.classList.remove('on'));
    document.querySelectorAll('.ppanel').forEach(p=>p.classList.remove('on'));
    tab.classList.add('on');
    document.getElementById('finalPayMethod').value=tab.dataset.m;
    document.getElementById('pm_'+tab.dataset.m)?.classList.add('on');
  });
});

/* ── EMI — JS variable tracking ── */
document.querySelectorAll('#emiGrid .emi-opt').forEach(o=>{
  o.addEventListener('click',()=>{
    document.querySelectorAll('#emiGrid .emi-opt').forEach(x=>x.classList.remove('on'));
    o.classList.add('on');
    selectedEmiTenure=parseInt(o.dataset.t);
    document.getElementById('emiDetailsBox').style.display='block';
  });
});
// Show bank account fields when EMI bank is selected
document.querySelectorAll('input[name="emiBank"]').forEach(r=>{
  r.addEventListener('change',()=>{
    document.getElementById('emiBankDetailsBox').style.display='block';
  });
});

/* ── Bank — JS variable tracking ── */
document.querySelectorAll('#bankList .bank-opt').forEach(b=>{
  b.addEventListener('click',()=>{
    document.querySelectorAll('#bankList .bank-opt').forEach(x=>x.classList.remove('on'));
    b.classList.add('on');
    selectedBank=b.dataset.b;
    document.getElementById('bankDetailsBox').style.display='block';
  });
});

/* ── IFSC formatting ── */
['bankIfsc','emiIfsc'].forEach(id=>{
  document.getElementById(id)?.addEventListener('input',function(){ this.value=this.value.toUpperCase().replace(/[^A-Z0-9]/g,''); });
});
['bankAccNum','emiAccNum'].forEach(id=>{
  document.getElementById(id)?.addEventListener('input',function(){ this.value=this.value.replace(/[^0-9]/g,''); });
});

/* ── Card formatting ── */
document.getElementById('cardName')?.addEventListener('input',function(){ this.value=this.value.replace(/[^A-Za-z\s]/g,''); const e=document.getElementById('cardNameErr'); if(e) e.style.display='none'; });
document.getElementById('cardNum')?.addEventListener('input',function(){const d=this.value.replace(/\D/g,'').substring(0,16);this.value=d.match(/.{1,4}/g)?.join(' ')||d;});
document.getElementById('cardExp')?.addEventListener('input',function(){const d=this.value.replace(/\D/g,'');this.value=d.length>=2?d.substring(0,2)+' / '+d.substring(2,4):d;});
document.getElementById('cardCvv')?.addEventListener('input',function(){this.value=this.value.replace(/\D/g,'').substring(0,3);});

/* ── Cashfree UPI ── */
document.getElementById('initCashfreeBtn')?.addEventListener('click',function(){
  const btn=this;
  btn.disabled=true;
  document.getElementById('upiLoadBtn').style.display='none';
  document.getElementById('upiLoadingBox').style.display='block';
  document.getElementById('cfUpiMsg').textContent='';

  function showErr(msg){
    document.getElementById('cfUpiMsg').style.color='#dc2626';
    document.getElementById('cfUpiMsg').textContent='❌ '+msg;
    document.getElementById('upiLoadingBox').style.display='none';
    document.getElementById('upiLoadBtn').style.display='block';
    btn.disabled=false;
  }

  // Step 1: Create Cashfree payment order
  const cfFd=new FormData();
  cfFd.append('csrf_token',cartCSRF); cfFd.append('amount',gTotal);
  cfFd.append('name',document.getElementById('f_name')?.value||'');
  cfFd.append('mobile',document.getElementById('f_mobile')?.value||'');
  cfFd.append('email',document.getElementById('f_email')?.value||'');
  fetch('cashfree_payment.php',{method:'POST',body:cfFd}).then(r=>r.json()).then(res=>{
    if(res.error){ showErr(res.error); return; }
    const sessionId=res.payment_session_id;
    const cfOrderId=res.order_id||'';

    // Step 2: Save pending order to DB (so payment_return.php can finalize & award coupon)
    const pFd=new FormData();
    pFd.append('csrf_token',cartCSRF);
    pFd.append('order_type','cart');
    pFd.append('coupon_code',couponApplied);
    pFd.append('cashfree_order_id',cfOrderId);
    if(addrMode==='saved'&&selectedAddrIdx>=0){
      pFd.append('using_saved','1'); pFd.append('use_saved_addr',selectedAddrIdx);
    } else {
      ['country','full_name','mobile','email','flat','area','landmark','pincode','city','state','delivery_instructions'].forEach(n=>{
        const el=document.querySelector('#addrForm [name="'+n+'"]'); if(el) pFd.append(n,el.value);
      });
    }
    fetch('save_pending_order.php',{method:'POST',body:pFd}).then(r=>r.json()).then(pr=>{
      if(pr.error){ showErr('Could not save order: '+pr.error); return; }
      // Step 3: Launch Cashfree SDK
      const s=document.createElement('script');
      s.src='https://sdk.cashfree.com/js/v3/cashfree.js';
      s.onload=()=>{ const cf=Cashfree({mode:res.test_mode?'sandbox':'production'}); cf.checkout({paymentSessionId:sessionId,redirectTarget:'_self'}); };
      s.onerror=()=>{ showErr('Could not load payment SDK'); };
      document.head.appendChild(s);
    }).catch(()=>{ showErr('Network error saving order'); });
  }).catch(()=>{ showErr('Network error'); });
});

/* ── Place Order ── */
document.getElementById('placeOrderBtn')?.addEventListener('click',function(){
  const method=document.getElementById('finalPayMethod').value;

  if(method==='card'){
    const cn=(document.getElementById('cardNum')?.value||'').replace(/\s/g,'');
    const cname=(document.getElementById('cardName')?.value||'').trim();
    const exp=(document.getElementById('cardExp')?.value||'').trim();
    const cvv=(document.getElementById('cardCvv')?.value||'').trim();
    if(cn.length<16){toast('Enter valid 16-digit card number','error');return;}
    if(!cname){toast('Enter name on card','error');return;}
    if(!/^[A-Za-z\s]+$/.test(cname)){toast('Name on card: letters only','error');return;}
    if(!/^\d{2} \/ \d{2}$/.test(exp)){toast('Enter valid expiry MM / YY','error');return;}
    if(cvv.length<3){toast('Enter 3-digit CVV','error');return;}
  }

  if(method==='emi'){
    if(!selectedEmiTenure){toast('Select an EMI tenure','error');return;}
    if(!document.querySelector('input[name="emiBank"]:checked')){toast('Select your bank for EMI','error');return;}
    const acc=(document.getElementById('emiAccNum')?.value||'').trim();
    const ifc=(document.getElementById('emiIfsc')?.value||'').trim();
    const anm=(document.getElementById('emiAccName')?.value||'').trim();
    if(!acc){toast('Enter account number for EMI','error');return;}
    if(!/^[A-Z]{4}0[A-Z0-9]{6}$/.test(ifc)){toast('Enter valid IFSC code (e.g. SBIN0001234)','error');return;}
    if(!anm){toast('Enter account holder name for EMI','error');return;}
  }

  if(method==='bank'){
    if(!selectedBank){toast('Select your bank','error');return;}
    const acc=(document.getElementById('bankAccNum')?.value||'').trim();
    const ifc=(document.getElementById('bankIfsc')?.value||'').trim();
    const anm=(document.getElementById('bankAccName')?.value||'').trim();
    if(!acc){toast('Enter account number','error');return;}
    if(!/^[A-Z]{4}0[A-Z0-9]{6}$/.test(ifc)){toast('Enter valid IFSC code (e.g. SBIN0001234)','error');return;}
    if(!anm){toast('Enter account holder name','error');return;}
  }

  this.disabled=true; this.textContent='Placing order…';

  const fd=new FormData();
  fd.append('csrf_token',cartCSRF);
  fd.append('order_type','cart');
  fd.append('payment_method',method);
  fd.append('coupon_code',couponApplied);

  if(addrMode==='saved'&&selectedAddrIdx>=0){
    fd.append('using_saved','1'); fd.append('use_saved_addr',selectedAddrIdx);
  } else {
    ['country','full_name','mobile','email','flat','area','landmark','pincode','city','state','delivery_instructions'].forEach(n=>{
      const el=document.querySelector(`#addrForm [name="${n}"]`); if(el) fd.append(n,el.value);
    });
    const sa=document.querySelector('#addrForm [name="save_address"]');
    const sd=document.querySelector('#addrForm [name="is_default"]');
    if(sa?.checked) fd.append('save_address','1');
    if(sd?.checked) fd.append('is_default','1');
  }

  fetch('place_order.php',{method:'POST',body:fd})
    .then(r=>{if(!r.ok)throw new Error('HTTP '+r.status);return r.json();})
    .then(res=>{
      if(res.error){toast(res.error,'error');this.disabled=false;this.textContent='Confirm & Place Order 🎉';return;}
      closeOv('ovPay');
      if(res.coupon){
        document.getElementById('couponReveal').style.display='block';
        document.getElementById('couponCodeDisplay').textContent=res.coupon.code;
        document.getElementById('couponDiscountText').textContent=res.coupon.discount+'% off your next order';
      }
      document.getElementById('successOv').classList.add('open');
      cartPageItems.length=0;
      document.querySelectorAll('.cart-count-badge').forEach(e=>e.textContent='0');
    })
    .catch(err=>{toast('Network error: '+err.message,'error');this.disabled=false;this.textContent='Confirm & Place Order 🎉';});
});
</script>
<script src="form_validation.js"></script>
</body>
</html>
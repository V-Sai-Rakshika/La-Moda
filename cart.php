<?php
session_start();
include __DIR__ . "/db.php";
include __DIR__ . "/auth.php";

// Remove item
if (isset($_GET['remove'])) {
    $r = clean($_GET['remove'], 200);
    $_SESSION['cart'] = array_values(array_filter($_SESSION['cart'] ?? [], fn($i) => ($i['name']??'') !== $r));
    header("Location: cart.php"); exit();
}

$cart  = $_SESSION['cart'] ?? [];
$sub   = array_sum(array_map(fn($i) => (int)($i['price']??0) * (int)($i['qty']??1), $cart));

// Delivery charge
$delivery = 0;
if ($sub > 0 && $sub < 500)        $delivery = 50;
elseif ($sub >= 500 && $sub <= 1000) $delivery = 40;

$total = $sub + $delivery;

// Load saved addresses for logged-in user
$savedAddresses = [];
if (is_logged_in()) {
    $u = $users->findOne(['username' => current_user()['username']]);
    if (!empty($u['addresses'])) {
        $savedAddresses = is_array($u['addresses']) ? $u['addresses'] : iterator_to_array($u['addresses']);
    }
}

// Valid coupon check
$couponDiscount = 0;
$couponCode     = '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>My Cart | La Moda</title>
<link rel="stylesheet" href="styles.css">
<style>
/* ══ OVERLAY SYSTEM ══ */
.ov{display:none;position:fixed;inset:0;background:rgba(0,0,0,.52);z-index:8000;overflow-y:auto;padding:30px 16px 60px;}
.ov.open{display:block;}
.ob{background:#fff;width:520px;max-width:100%;margin:0 auto;border-radius:14px;padding:26px 24px 22px;position:relative;}
.ob h2{font-size:19px;font-family:var(--font-display,serif);margin-bottom:3px;}
.ob .sub{font-size:12px;color:#aaa;margin-bottom:14px;}
.ov-close{position:absolute;top:11px;right:14px;background:none;border:none;font-size:21px;cursor:pointer;color:#999;line-height:1;}

/* ══ FORM FIELDS ══ */
.ff{margin-bottom:10px;}
.ff label{display:block;font-size:11px;font-weight:600;color:#666;text-transform:uppercase;letter-spacing:.4px;margin-bottom:4px;}
.ff input,.ff select,.ff textarea{
  width:100%;padding:9px 12px;border:1.5px solid #e0e0e0;border-radius:8px;
  font-size:13px;font-family:inherit;outline:none;box-sizing:border-box;
  transition:border-color .18s;
}
.ff input:focus,.ff select:focus,.ff textarea:focus{border-color:#8B2500;}
.ff input.bad {border-color:#dc2626!important;background:#fff8f8;}
.ff input.good{border-color:#16a34a!important;}
.ff .hint{font-size:11px;color:#bbb;margin-top:3px;}
.ff .emsg{font-size:11px;color:#dc2626;margin-top:3px;display:none;}
.ff .emsg.show{display:block;}
.frow{display:grid;grid-template-columns:1fr 1fr;gap:10px;}

/* ══ ADDRESS CARDS ══ */
.addr-cards{display:flex;gap:10px;margin-bottom:14px;flex-wrap:wrap;}
.addr-card{
  flex:1;min-width:200px;
  padding:12px 14px;border:1.5px solid #e0e0e0;border-radius:10px;
  cursor:pointer;transition:all .18s;font-size:12px;
}
.addr-card:hover,.addr-card.sel{border-color:#8B2500;background:#fff5f2;}
.addr-card strong{display:block;font-size:13px;margin-bottom:4px;}
.addr-btns{display:flex;gap:8px;margin-bottom:14px;flex-wrap:wrap;}
.addr-btn{padding:8px 16px;border:1.5px solid #e0e0e0;border-radius:20px;font-size:12px;font-weight:500;background:#fff;cursor:pointer;font-family:inherit;transition:all .18s;}
.addr-btn:hover,.addr-btn.sel{border-color:#8B2500;background:#8B2500;color:#fff;}

/* ══ DELIVERY BANNER ══ */
.del-banner{
  padding:9px 14px;border-radius:8px;font-size:12px;font-weight:500;
  margin-bottom:12px;display:flex;align-items:center;gap:8px;
}
.del-free{background:#f0fdf4;color:#16a34a;border:1px solid #bbf7d0;}
.del-paid{background:#fef9ec;color:#d97706;border:1px solid #fde68a;}

/* ══ STEP INDICATOR ══ */
.steps{display:flex;align-items:center;gap:6px;margin-bottom:16px;}
.step{
  display:flex;align-items:center;gap:6px;
  font-size:12px;font-weight:500;color:#bbb;
}
.step.done{color:#16a34a;}
.step.active{color:#8B2500;font-weight:700;}
.step-dot{
  width:22px;height:22px;border-radius:50%;
  border:2px solid currentColor;
  display:flex;align-items:center;justify-content:center;
  font-size:10px;font-weight:700;flex-shrink:0;
}
.step-line{flex:1;height:1px;background:#e0e0e0;min-width:20px;}

/* ══ PAYMENT TABS ══ */
.ptabs{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:10px;}
.ptab{padding:7px 13px;border:1.5px solid #e0e0e0;border-radius:20px;font-size:12px;font-weight:500;background:#fff;cursor:pointer;transition:all .18s;font-family:inherit;}
.ptab:hover{border-color:#8B2500;color:#8B2500;}
.ptab.on{border-color:#8B2500;background:#8B2500;color:#fff;}
.ppanel{display:none;padding:13px;background:#fafafa;border:1px solid #eee;border-radius:8px;margin-bottom:10px;font-size:13px;}
.ppanel.on{display:block;}

/* ══ UPI ══ */
.upi-row{display:flex;border:1.5px solid #e0e0e0;border-radius:8px;overflow:hidden;margin-bottom:6px;}
.upi-row input{flex:1;padding:9px 12px;border:none;outline:none;font-size:13px;font-family:inherit;}
.upi-row button{padding:9px 14px;background:#8B2500;color:#fff;border:none;font-size:12px;font-weight:600;cursor:pointer;}
.umsg{font-size:12px;margin-top:4px;}
.umsg.ok{color:#16a34a;} .umsg.fail{color:#dc2626;}

/* ══ CARD ══ */
.cfield{width:100%;padding:9px 12px;border:1.5px solid #e0e0e0;border-radius:8px;font-size:13px;font-family:inherit;outline:none;margin-bottom:8px;box-sizing:border-box;}
.cfield:focus{border-color:#8B2500;}
.cashback-badge{display:inline-block;background:#fff5f2;color:#8B2500;border:1px solid #ffd6c4;border-radius:20px;font-size:11px;font-weight:600;padding:3px 10px;margin-bottom:8px;}

/* ══ EMI ══ */
.emi-grid{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-top:6px;}
.emi-opt{padding:10px;border:1.5px solid #e0e0e0;border-radius:8px;text-align:center;cursor:pointer;transition:all .18s;font-family:inherit;background:#fff;}
.emi-opt:hover,.emi-opt.on{border-color:#8B2500;background:#fff5f2;color:#8B2500;}
.emi-opt strong{display:block;font-size:14px;}
.emi-opt span{font-size:11px;color:#888;}

/* ══ BANK ══ */
.bank-list{display:flex;flex-direction:column;gap:6px;margin-top:6px;}
.bank-opt{display:flex;align-items:center;gap:10px;padding:10px 12px;border:1.5px solid #e0e0e0;border-radius:8px;cursor:pointer;font-size:13px;transition:all .18s;}
.bank-opt:hover,.bank-opt.on{border-color:#8B2500;background:#fff5f2;}
.bank-logo{width:28px;height:28px;border-radius:6px;display:flex;align-items:center;justify-content:center;font-size:14px;flex-shrink:0;}

/* ══ SUBMIT BTN ══ */
.sbtn{width:100%;padding:12px;background:#8B2500;color:#fff;border:none;border-radius:8px;font-size:14px;font-weight:700;cursor:pointer;font-family:inherit;margin-top:12px;transition:background .2s;}
.sbtn:hover:not(:disabled){background:#5c1800;}
.sbtn:disabled{opacity:.6;cursor:not-allowed;}
.sbtn-outline{width:100%;padding:11px;border:1.5px solid #8B2500;color:#8B2500;background:#fff;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;font-family:inherit;margin-top:8px;}

/* ══ SUCCESS ══ */
#successOv{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9999;align-items:center;justify-content:center;}
#successOv.open{display:flex;}
#successBox{background:#fff;border-radius:16px;padding:36px 28px;text-align:center;width:320px;max-width:95vw;}
.coupon-reveal{margin:14px 0;padding:14px;background:#fff5f2;border:1.5px dashed #8B2500;border-radius:10px;}
.coupon-code{font-size:22px;font-weight:700;color:#8B2500;letter-spacing:2px;margin:6px 0;}

/* ══ DELIVERY INFO ══ */
.del-info{background:#fff;border:1px solid #eee;border-radius:10px;padding:12px 14px;margin-bottom:10px;}
.del-row{display:flex;justify-content:space-between;font-size:13px;padding:3px 0;}
.del-row.total{font-weight:700;font-size:14px;border-top:1px solid #eee;margin-top:6px;padding-top:8px;}
.del-row.free-tag{color:#16a34a;font-weight:600;}
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
    $name = (string)($item['name']??'');
    $price= (int)($item['price']??0);
    $qty  = (int)($item['qty']??1);
    $img  = (string)($item['image']??'');
    $stk  = isset($item['stock']) ? (int)$item['stock'] : null;
  ?>
  <div class="list-item" id="cr-<?= urlencode($name) ?>">
    <div class="list-left">
      <div class="list-img">
        <img src="<?= htmlspecialchars($img) ?>" alt="<?= htmlspecialchars($name) ?>"
             onerror="this.src='https://placehold.co/100x100/f5f5f5/aaa?text=?'">
      </div>
      <div class="list-details">
        <h3><?= htmlspecialchars($name) ?></h3>
        <p class="list-price">₹<?= $price ?> each</p>
        <?php if ($stk !== null && $stk <= 5): ?>
        <p style="font-size:11px;color:#dc2626;font-weight:600;margin-top:2px;">
          <?= $stk === 0 ? '❌ Out of stock' : "⚠ Only $stk left!" ?>
        </p>
        <?php endif; ?>
        <div class="list-qty-row">
          <div class="qty-box" data-name="<?= htmlspecialchars($name) ?>" data-price="<?= $price ?>">
            <button class="qty-btn minus">−</button>
            <span><?= $qty ?></span>
            <button class="qty-btn plus">+</button>
          </div>
          <span class="list-subtotal" id="sub-<?= urlencode($name) ?>">Subtotal: ₹<?= $price*$qty ?></span>
        </div>
        <a href="cart.php?remove=<?= urlencode($name) ?>" class="remove-link">Remove ✕</a>
      </div>
    </div>
    <div class="list-actions">
      <div class="list-item-price" id="prc-<?= urlencode($name) ?>">₹<?= $price*$qty ?></div>
    </div>
  </div>
  <?php endforeach; ?>

  <!-- Order summary -->
  <div class="del-info">
    <div class="del-row"><span>Subtotal</span><span>₹<span id="subDisplay"><?= $sub ?></span></span></div>
    <?php if ($delivery === 0 && $sub > 0): ?>
    <div class="del-row free-tag"><span>🚚 Delivery</span><span>FREE</span></div>
    <?php elseif ($delivery > 0): ?>
    <div class="del-row"><span>Delivery</span><span>₹<span id="delDisplay"><?= $delivery ?></span></span></div>
    <?php endif; ?>
    <div class="del-row total"><span>Total</span><span>₹<span id="totalDisplay"><?= $total ?></span></span></div>
  </div>

  <?php if ($delivery === 0 && $sub > 0): ?>
  <div class="del-banner del-free">🎉 You qualify for FREE delivery!</div>
  <?php elseif ($sub >= 500): ?>
  <div class="del-banner del-paid">🚚 ₹40 delivery charge. Spend ₹<?= 1001 - $sub ?> more for FREE delivery!</div>
  <?php else: ?>
  <div class="del-banner del-paid">🚚 ₹50 delivery charge. Spend ₹<?= 500 - $sub ?> more to get ₹40 delivery!</div>
  <?php endif; ?>

  <div class="cart-total-box">
    <h2>Total: ₹<span id="grandTotal"><?= $total ?></span></h2>
    <button class="checkout-btn" id="checkoutBtn">Proceed to Checkout →</button>
  </div>
  <div class="list-footer"><a href="index.php">← Continue Shopping</a></div>
</div>
<?php endif; ?>
</div>

<footer><h1>☆ La Moda ☆</h1><p>Wear the Moment</p></footer>

<!-- ════════════════════════════════════
     STEP 1 — ADDRESS OVERLAY
════════════════════════════════════ -->
<div id="ovAddr" class="ov">
<div class="ob">
  <button class="ov-close" onclick="closeOv('ovAddr')">✕</button>

  <div class="steps">
    <div class="step active"><div class="step-dot">1</div> Address</div>
    <div class="step-line"></div>
    <div class="step"><div class="step-dot">2</div> Payment</div>
  </div>

  <h2>Delivery Address</h2>
  <p class="sub">Where should we deliver your order?</p>

  <?php if (!empty($savedAddresses)): ?>
  <!-- Saved addresses -->
  <p style="font-size:12px;font-weight:600;color:#555;margin-bottom:8px;">Your saved addresses</p>
  <div class="addr-cards" id="savedAddrCards">
    <?php foreach ($savedAddresses as $ai => $sa): ?>
    <div class="addr-card" data-idx="<?= $ai ?>" onclick="selectSavedAddr(<?= $ai ?>)">
      <strong><?= htmlspecialchars((string)($sa['full_name']??'Address '.($ai+1))) ?></strong>
      <?= htmlspecialchars((string)($sa['flat']??'')) ?>, <?= htmlspecialchars((string)($sa['area']??'')) ?><br>
      <?= htmlspecialchars((string)($sa['city']??'')) ?> - <?= htmlspecialchars((string)($sa['pincode']??'')) ?><br>
      <?= htmlspecialchars((string)($sa['country']??'')) ?><br>
      📱 <?= htmlspecialchars((string)($sa['mobile']??'')) ?>
    </div>
    <?php endforeach; ?>
  </div>
  <div class="addr-btns">
    <button class="addr-btn sel" id="btnUseSaved" onclick="setAddrMode('saved')">Use selected address</button>
    <button class="addr-btn" id="btnNewAddr" onclick="setAddrMode('new')">+ Enter new address</button>
  </div>
  <?php endif; ?>

  <form id="addrForm" <?= !empty($savedAddresses) ? 'style="display:none"' : '' ?> novalidate>
    <?= csrf_field() ?>
    <input type="hidden" name="item_name" value="__cart__">

    <div class="ff">
      <label>Country *</label>
      <input list="ccl" id="f_country" name="country" placeholder="e.g. India" maxlength="100" autocomplete="off">
      <datalist id="ccl">
        <option value="India"><option value="United States"><option value="United Kingdom">
        <option value="Australia"><option value="Canada"><option value="UAE"><option value="Singapore">
      </datalist>
      <p class="hint">Select or type your country</p>
      <p class="emsg" id="e_country">⚠ Country is required</p>
    </div>

    <div class="ff">
      <label>Full Name *</label>
      <input type="text" id="f_name" name="full_name" placeholder="e.g. Priya Sharma" maxlength="100">
      <p class="hint">Letters and spaces only</p>
      <p class="emsg" id="e_name">⚠ Name must contain letters only</p>
    </div>

    <div class="ff">
      <label>Mobile Number *</label>
      <input type="tel" id="f_mobile" name="mobile" placeholder="10-digit number" maxlength="10">
      <p class="hint">Starting with 6, 7, 8 or 9</p>
      <p class="emsg" id="e_mobile">⚠ Enter a valid 10-digit number</p>
    </div>

    <div class="ff">
      <label>Email Address</label>
      <input type="email" id="f_email" name="email" placeholder="you@example.com" maxlength="150">
      <p class="hint">For order confirmation and delivery updates</p>
    </div>

    <div class="ff">
      <label>Flat / House No. / Building *</label>
      <input type="text" id="f_flat" name="flat" placeholder="e.g. Flat 4B, Sunrise Apartments" maxlength="200">
      <p class="hint">Letters, numbers and hyphen (-) only</p>
      <p class="emsg" id="e_flat">⚠ This field is required</p>
    </div>

    <div class="ff">
      <label>Area / Street *</label>
      <input type="text" id="f_area" name="area" placeholder="e.g. T. Nagar, Anna Nagar" maxlength="200">
      <p class="hint">Letters, numbers and hyphen (-) only</p>
      <p class="emsg" id="e_area">⚠ This field is required</p>
    </div>

    <div class="ff">
      <label>Landmark *</label>
      <input type="text" id="f_landmark" name="landmark" placeholder="e.g. Near SBI Bank" maxlength="200">
      <p class="emsg" id="e_landmark">⚠ This field is required</p>
    </div>

    <div class="frow">
      <div class="ff">
        <label>Pincode *</label>
        <input type="text" id="f_pincode" name="pincode" placeholder="6-digit pincode" maxlength="6">
        <p class="hint">Cannot start with 0</p>
        <p class="emsg" id="e_pincode">⚠ Enter valid 6-digit pincode</p>
      </div>
      <div class="ff">
        <label>City *</label>
        <input type="text" id="f_city" name="city" placeholder="e.g. Chennai" maxlength="100">
        <p class="emsg" id="e_city">⚠ City is required</p>
      </div>
    </div>

    <div class="ff">
      <label>Coupon Code <span style="font-weight:400;color:#bbb;">(optional)</span></label>
      <div style="display:flex;gap:8px;">
        <input type="text" id="f_coupon" name="coupon_code" placeholder="Enter coupon code" maxlength="20"
               style="flex:1;padding:9px 12px;border:1.5px solid #e0e0e0;border-radius:8px;font-size:13px;font-family:inherit;outline:none;box-sizing:border-box;">
        <button type="button" id="applyCouponBtn"
          style="padding:9px 16px;background:#8B2500;color:#fff;border:none;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;font-family:inherit;white-space:nowrap;">
          Apply
        </button>
      </div>
      <p id="couponMsg" style="font-size:12px;margin-top:4px;"></p>
    </div>

    <label style="display:flex;align-items:center;gap:8px;font-size:13px;color:#444;margin:8px 0;cursor:pointer;">
      <input type="checkbox" name="save_address" value="1" id="saveAddrCheck" style="accent-color:#8B2500;">
      Save this address for future orders
    </label>

    <div class="ff">
      <label>Delivery Instructions <span style="font-weight:400;color:#bbb;">(optional)</span></label>
      <textarea name="delivery_instructions" maxlength="500" rows="2"
        placeholder="Any special instructions…" style="resize:vertical;"></textarea>
    </div>
  </form>

  <input type="hidden" id="selectedAddrIdx" value="-1">
  <button class="sbtn" id="proceedToPayBtn">Proceed to Pay →</button>
</div>
</div>

<!-- ════════════════════════════════════
     STEP 2 — PAYMENT OVERLAY
════════════════════════════════════ -->
<div id="ovPay" class="ov">
<div class="ob">
  <button class="ov-close" onclick="closeOv('ovPay');openOv('ovAddr')">✕</button>

  <div class="steps">
    <div class="step done"><div class="step-dot">✓</div> Address</div>
    <div class="step-line"></div>
    <div class="step active"><div class="step-dot">2</div> Payment</div>
  </div>

  <h2>Choose Payment</h2>
  <p class="sub">Select how you'd like to pay</p>

  <div id="payAmtLine" style="font-size:14px;font-weight:600;color:#8B2500;margin-bottom:14px;">
    Amount: ₹<span id="payAmtSpan"><?= $total ?></span>
    <span id="couponSavingLine" style="font-size:12px;color:#16a34a;margin-left:8px;display:none;"></span>
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
        <span style="font-size:12px;color:#888;">Pay cash when your order arrives. No advance needed.</span>
      </div>
    </div>
  </div>

  <!-- UPI -->
  <div class="ppanel" id="pm_upi">
    <div class="upi-row">
      <input type="text" id="upiInput" placeholder="yourname@okaxis  or  9876543210@upi" maxlength="60">
      <button type="button" id="upiVerifyBtn">Verify</button>
    </div>
    <p style="font-size:11px;color:#aaa;">Supported: @okaxis @ybl @paytm @upi @oksbi @okicici @ibl</p>
    <p id="upiMsg" class="umsg"></p>
    <div id="upiQrBox" style="display:none;text-align:center;margin-top:10px;padding:14px;background:#f9f9f9;border-radius:8px;">
      <img id="upiQrImg" src="" alt="UPI QR"
           style="width:140px;height:140px;border-radius:8px;margin:0 auto 8px;">
      <div style="font-size:16px;font-weight:700;color:#8B2500;">₹<span id="upiAmtLabel"><?= $total ?></span></div>
      <p style="font-size:11px;color:#888;margin-top:4px;">Scan with PhonePe, GPay, Paytm or BHIM</p>
    </div>
  </div>

  <!-- Card -->
  <div class="ppanel" id="pm_card">
    <div class="cashback-badge">🎁 5% cashback on card payments!</div>
    <input class="cfield" type="text" id="cardNum"  placeholder="Card Number (16 digits)" maxlength="19">
    <input class="cfield" type="text" id="cardName" placeholder="Name on Card" maxlength="60">
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
      <input class="cfield" style="margin:0;" type="text" id="cardExp" placeholder="MM / YY" maxlength="7">
      <input class="cfield" style="margin:0;" type="text" id="cardCvv" placeholder="CVV" maxlength="3">
    </div>
    <p style="font-size:11px;color:#aaa;margin-top:8px;">🔒 Secured by 256-bit encryption — demo only</p>
  </div>

  <!-- EMI -->
  <div class="ppanel" id="pm_emi">
    <p style="font-size:12px;color:#555;margin-bottom:8px;">Select your EMI plan:</p>
    <div class="emi-grid">
      <?php foreach ([3,6,9,12] as $t):
        $emi = $total > 0 ? ceil($total/$t) : 0;
        $int = $t <= 6 ? 'No Cost' : '1.5% p.m.';
      ?>
      <button type="button" class="emi-opt" data-t="<?= $t ?>">
        <strong><?= $t ?> months</strong>
        <span>₹<?= $emi ?>/month</span>
        <span style="font-size:10px;color:#8B2500;"><?= $int ?></span>
      </button>
      <?php endforeach; ?>
    </div>
    <p style="font-size:11px;color:#aaa;margin-top:8px;">EMI via credit card or select bank debit cards</p>

    <!-- EMI Bank Details popup trigger -->
    <div id="emiDetailsBox" style="display:none;margin-top:10px;padding:12px;background:#fff;border:1px solid #eee;border-radius:8px;">
      <p style="font-size:12px;font-weight:600;margin-bottom:8px;">Select your bank for EMI:</p>
      <?php foreach (['HDFC Bank','ICICI Bank','SBI Card','Axis Bank','Kotak Bank'] as $b): ?>
      <label style="display:flex;align-items:center;gap:8px;font-size:13px;padding:6px 0;cursor:pointer;border-bottom:1px solid #f5f5f5;">
        <input type="radio" name="emiBank" value="<?= $b ?>" style="accent-color:#8B2500;"> <?= $b ?>
      </label>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Net Banking -->
  <div class="ppanel" id="pm_bank">
    <p style="font-size:12px;color:#555;margin-bottom:8px;">Select your bank:</p>
    <div class="bank-list">
      <div class="bank-opt" data-b="sbi">
        <div class="bank-logo" style="background:#1a3d7c;">🏛️</div>
        <div><strong style="display:block;">State Bank of India</strong><span style="font-size:11px;color:#888;">Net Banking / YONO</span></div>
      </div>
      <div class="bank-opt" data-b="hdfc">
        <div class="bank-logo" style="background:#004c8c;">🏦</div>
        <div><strong style="display:block;">HDFC Bank</strong><span style="font-size:11px;color:#888;">NetBanking</span></div>
      </div>
      <div class="bank-opt" data-b="icici">
        <div class="bank-logo" style="background:#b02121;">🏦</div>
        <div><strong style="display:block;">ICICI Bank</strong><span style="font-size:11px;color:#888;">iMobile / Net Banking</span></div>
      </div>
      <div class="bank-opt" data-b="axis">
        <div class="bank-logo" style="background:#97144d;">🏦</div>
        <div><strong style="display:block;">Axis Bank</strong><span style="font-size:11px;color:#888;">Internet Banking</span></div>
      </div>
      <div class="bank-opt" data-b="kotak">
        <div class="bank-logo" style="background:#ed1c24;">🏦</div>
        <div><strong style="display:block;">Kotak Mahindra Bank</strong><span style="font-size:11px;color:#888;">Net Banking</span></div>
      </div>
    </div>
    <div id="bankRedirectBox" style="display:none;margin-top:10px;padding:10px 14px;background:#fef9ec;border:1px solid #fde68a;border-radius:8px;font-size:12px;">
      <strong>Redirecting to <span id="bankName"></span></strong><br>
      You'll be taken to your bank's secure net banking portal to complete payment.
    </div>
  </div>

  <input type="hidden" id="finalPayMethod" value="cod">
  <button class="sbtn" id="placeOrderBtn">Confirm & Place Order 🎉</button>
  <button class="sbtn-outline" onclick="closeOv('ovPay');openOv('ovAddr')">← Back to Address</button>
</div>
</div>

<!-- ════════════ SUCCESS OVERLAY ════════════ -->
<div id="successOv">
<div id="successBox">
  <div style="font-size:48px;margin-bottom:10px;">🎉</div>
  <h2 style="font-size:20px;margin-bottom:6px;">Order Placed!</h2>
  <p id="successMsg" style="font-size:13px;color:#888;margin-bottom:14px;">
    Thank you! Your order is confirmed.<br>We'll deliver it to you soon 💗
  </p>
  <div id="couponReveal" style="display:none;" class="coupon-reveal">
    <p style="font-size:12px;color:#8B2500;font-weight:600;margin-bottom:4px;">🎁 You won a coupon!</p>
    <div class="coupon-code" id="couponCodeDisplay"></div>
    <p style="font-size:11px;color:#888;" id="couponDiscountText"></p>
    <p style="font-size:11px;color:#aaa;margin-top:4px;">Valid on your next order only</p>
  </div>
  <button onclick="window.location='index.php'"
    style="padding:11px 28px;background:#8B2500;color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:700;cursor:pointer;margin-top:10px;">
    Continue Shopping
  </button>
</div>
</div>

<div id="toast" class="toast"></div>

<script>
const CSRF       = <?= json_encode(csrf_token()) ?>;
const cartData   = <?= json_encode(array_values($cart)) ?>;
const isLoggedIn = <?= is_logged_in() ? 'true' : 'false' ?>;
let gSub      = <?= $sub ?>;
let gDelivery = <?= $delivery ?>;
let gTotal    = <?= $total ?>;
let couponPct = 0;
let couponApplied = '';
let addrMode  = '<?= empty($savedAddresses) ? "new" : "saved" ?>';
let selectedAddrIdx = <?= empty($savedAddresses) ? -1 : 0 ?>;

/* ── Toast ── */
function toast(msg, type) {
  const t = document.getElementById('toast');
  if (!t) return;
  t.textContent = msg;
  t.className = 'toast show' + (type==='error' ? ' toast-error' : '');
  clearTimeout(t._tid);
  t._tid = setTimeout(() => t.classList.remove('show'), 3200);
}

/* ── Overlay open/close ── */
function openOv(id) {
  document.getElementById(id).classList.add('open');
  document.body.style.overflow = 'hidden';
}
function closeOv(id) {
  document.getElementById(id).classList.remove('open');
  document.body.style.overflow = '';
}
['ovAddr','ovPay'].forEach(id => {
  document.getElementById(id)?.addEventListener('click', e => {
    if (e.target.id === id) closeOv(id);
  });
});

/* ── Checkout button ── */
document.getElementById('checkoutBtn')?.addEventListener('click', () => {
  if (!isLoggedIn) { toast('Please log in to checkout','error'); return; }
  openOv('ovAddr');
});

/* ── Recalc prices ── */
function recalc() {
  gSub = cartData.reduce((s,i)=>(i.price||0)*(i.qty||1)+s, 0);
  if (gSub <= 0) gDelivery = 0;
  else if (gSub > 1000) gDelivery = 0;
  else if (gSub >= 500) gDelivery = 40;
  else gDelivery = 50;

  let finalTotal = gSub + gDelivery;
  if (couponPct > 0) finalTotal = Math.round(finalTotal * (1 - couponPct/100));

  gTotal = finalTotal;
  document.getElementById('grandTotal').textContent  = gTotal;
  const sd = document.getElementById('subDisplay');   if(sd) sd.textContent = gSub;
  const dd = document.getElementById('delDisplay');   if(dd) dd.textContent = gDelivery;
  const td = document.getElementById('totalDisplay'); if(td) td.textContent = gTotal;
  const pa = document.getElementById('payAmtSpan');   if(pa) pa.textContent = gTotal;
  const ua = document.getElementById('upiAmtLabel');  if(ua) ua.textContent = gTotal;
}

/* ── Qty controls ── */
document.addEventListener('click', function(e) {
  const isP = e.target.classList.contains('plus');
  const isM = e.target.classList.contains('minus');
  if (!isP && !isM) return;
  const box = e.target.closest('.qty-box'); if (!box) return;
  e.target.disabled = true;
  const name = box.dataset.name;
  const fd = new FormData();
  fd.append('csrf_token', CSRF); fd.append('name', name); fd.append('action', isP?'plus':'minus');
  fetch('update_cart.php', {method:'POST',body:fd}).then(r=>r.json()).then(res=>{
    if (res.removed) {
      document.getElementById('cr-'+encodeURIComponent(name))?.remove();
      const i = cartData.findIndex(x=>x.name===name); if(i>-1) cartData.splice(i,1);
      if (!cartData.length) { location.reload(); return; }
    } else {
      box.querySelector('span').textContent = res.qty;
      box.querySelectorAll('button').forEach(b=>b.disabled=false);
      const it = cartData.find(x=>x.name===name); if(it) it.qty=res.qty;
      const sub = it ? it.price*res.qty : 0;
      const se = document.getElementById('sub-'+encodeURIComponent(name)); if(se) se.textContent='Subtotal: ₹'+sub;
      const pe = document.getElementById('prc-'+encodeURIComponent(name)); if(pe) pe.textContent='₹'+sub;
    }
    recalc();
  }).catch(()=>{ toast('Network error','error'); box.querySelectorAll('button').forEach(b=>b.disabled=false); });
});

/* ── Address mode toggle ── */
function setAddrMode(mode) {
  addrMode = mode;
  const form = document.getElementById('addrForm');
  const btnS = document.getElementById('btnUseSaved');
  const btnN = document.getElementById('btnNewAddr');
  if (mode === 'new') {
    form.style.display = '';
    if (btnS) btnS.classList.remove('sel');
    if (btnN) btnN.classList.add('sel');
  } else {
    form.style.display = 'none';
    if (btnS) btnS.classList.add('sel');
    if (btnN) btnN.classList.remove('sel');
  }
}

function selectSavedAddr(idx) {
  selectedAddrIdx = idx;
  document.querySelectorAll('.addr-card').forEach(c => c.classList.remove('sel'));
  document.querySelector(`.addr-card[data-idx="${idx}"]`)?.classList.add('sel');
  addrMode = 'saved';
  setAddrMode('saved');
}
// Default select first saved address
if (document.querySelector('.addr-card')) {
  document.querySelector('.addr-card')?.classList.add('sel');
}

/* ── Coupon apply ── */
document.getElementById('applyCouponBtn')?.addEventListener('click', function() {
  const code = document.getElementById('f_coupon').value.trim();
  const msg  = document.getElementById('couponMsg');
  if (!code) { msg.style.color='#dc2626'; msg.textContent='Enter a coupon code'; return; }
  const fd = new FormData();
  fd.append('csrf_token', CSRF); fd.append('code', code);
  fetch('apply_coupon.php', {method:'POST',body:fd}).then(r=>r.json()).then(res=>{
    if (res.error) { msg.style.color='#dc2626'; msg.textContent='❌ '+res.error; couponPct=0; couponApplied=''; }
    else {
      couponPct = res.discount;
      couponApplied = code;
      msg.style.color='#16a34a';
      msg.textContent='✅ '+res.discount+'% discount applied!';
      recalc();
      const savLine = document.getElementById('couponSavingLine');
      if (savLine) { savLine.style.display='inline'; savLine.textContent='('+res.discount+'% off applied)'; }
    }
  }).catch(()=>{ msg.style.color='#dc2626'; msg.textContent='Could not verify coupon'; });
});

/* ── Address validation ── */
const addrRules = {
  f_country:  { ok: v => v.trim().length >= 2,               msg: '⚠ Country is required' },
  f_name:     { ok: v => /^[A-Za-z\s]{2,}$/.test(v.trim()),  msg: '⚠ Letters and spaces only' },
  f_mobile:   { ok: v => /^[6-9][0-9]{9}$/.test(v.trim()),   msg: '⚠ Valid 10-digit number required' },
  f_flat:     { ok: v => /^[A-Za-z0-9\s\-]{3,}$/.test(v.trim()), msg: '⚠ Required (letters, numbers, hyphen only)' },
  f_area:     { ok: v => /^[A-Za-z0-9\s\-]{3,}$/.test(v.trim()), msg: '⚠ Required (letters, numbers, hyphen only)' },
  f_landmark: { ok: v => v.trim().length >= 2,               msg: '⚠ Landmark is required' },
  f_pincode:  { ok: v => /^[1-9][0-9]{5}$/.test(v.trim()),   msg: '⚠ Valid 6-digit pincode required' },
  f_city:     { ok: v => v.trim().length >= 2,               msg: '⚠ City is required' },
};

Object.keys(addrRules).forEach(id => {
  const el = document.getElementById(id); if(!el) return;
  el.addEventListener('input', ()=>liveV(id));
  el.addEventListener('blur',  ()=>{ if(el.value.trim()) liveV(id); });
});
function liveV(id) {
  const el=document.getElementById(id), rule=addrRules[id];
  const em=document.getElementById('e_'+id.replace('f_',''));
  if (!el||!rule) return;
  const pass=rule.ok(el.value);
  el.classList.toggle('bad',  !pass && el.value.trim().length>0);
  el.classList.toggle('good',  pass && el.value.trim().length>0);
  if (em) em.classList.toggle('show', !pass && el.value.trim().length>0);
  return pass;
}
function validateAddr() {
  let ok=true, first=null;
  Object.keys(addrRules).forEach(id=>{
    const el=document.getElementById(id), rule=addrRules[id];
    const em=document.getElementById('e_'+id.replace('f_',''));
    if(!el) return;
    const pass=rule.ok(el.value);
    el.classList.toggle('bad', !pass); el.classList.toggle('good', pass&&el.value.trim().length>0);
    if(em){em.textContent=rule.msg; em.classList.toggle('show',!pass);}
    if(!pass){ok=false; if(!first) first=el;}
  });
  if(first){first.scrollIntoView({behavior:'smooth',block:'center'}); first.focus();}
  return ok;
}

// Block invalid chars
document.getElementById('f_name')?.addEventListener('keypress',e=>{ if(!/[A-Za-z\s]/.test(e.key)) e.preventDefault(); });
['f_mobile','f_pincode'].forEach(id=>{ document.getElementById(id)?.addEventListener('keypress',e=>{ if(!/[0-9]/.test(e.key)) e.preventDefault(); }); });
['f_flat','f_area'].forEach(id=>{ document.getElementById(id)?.addEventListener('keypress',e=>{ if(!/[A-Za-z0-9\s\-]/.test(e.key)) e.preventDefault(); }); });

/* ── Proceed to Pay ── */
document.getElementById('proceedToPayBtn')?.addEventListener('click', function() {
  if (addrMode === 'new') {
    if (!validateAddr()) { toast('Please fill all required fields','error'); return; }
  }
  closeOv('ovAddr');
  openOv('ovPay');
  recalc();
});

/* ── Payment tabs ── */
document.querySelectorAll('.ptab').forEach(tab=>{
  tab.addEventListener('click',()=>{
    document.querySelectorAll('.ptab').forEach(t=>t.classList.remove('on'));
    document.querySelectorAll('.ppanel').forEach(p=>p.classList.remove('on'));
    tab.classList.add('on');
    const m=tab.dataset.m;
    document.getElementById('finalPayMethod').value=m;
    document.getElementById('pm_'+m)?.classList.add('on');
  });
});

/* ── EMI option ── */
document.querySelectorAll('.emi-opt').forEach(o=>{
  o.addEventListener('click',()=>{
    document.querySelectorAll('.emi-opt').forEach(x=>x.classList.remove('on'));
    o.classList.add('on');
    document.getElementById('emiDetailsBox').style.display='block';
  });
});

/* ── Bank option ── */
document.querySelectorAll('.bank-opt').forEach(b=>{
  b.addEventListener('click',()=>{
    document.querySelectorAll('.bank-opt').forEach(x=>x.classList.remove('on'));
    b.classList.add('on');
    const box=document.getElementById('bankRedirectBox');
    const nm=document.getElementById('bankName');
    if(box&&nm){ nm.textContent=b.querySelector('strong').textContent; box.style.display='block'; }
  });
});

/* ── UPI verify ── */
document.getElementById('upiVerifyBtn')?.addEventListener('click', function(){
  const val=document.getElementById('upiInput').value.trim();
  const msg=document.getElementById('upiMsg');
  if(!val){msg.className='umsg fail';msg.textContent='❌ Enter your UPI ID';return;}
  if(!/^[a-zA-Z0-9._+\-]+@[a-zA-Z0-9]+$/.test(val)){msg.className='umsg fail';msg.textContent='❌ Invalid format. Example: name@okaxis';return;}
  this.disabled=true; this.textContent='Verifying…';
  setTimeout(()=>{
    msg.className='umsg ok'; msg.textContent='✅ UPI verified! Scan QR or click Place Order.';
    this.textContent='✓ Verified';
    const qb=document.getElementById('upiQrBox'); if(qb) qb.style.display='block';
    const qi=document.getElementById('upiQrImg');
    if(qi) qi.src=`https://api.qrserver.com/v1/create-qr-code/?size=140x140&data=upi://pay?pa=${encodeURIComponent(val)}%26pn=LaModa%26am=${gTotal}%26cu=INR`;
  }, 900);
});

/* ── Card format ── */
document.getElementById('cardNum')?.addEventListener('input',function(){const d=this.value.replace(/\D/g,'').substring(0,16);this.value=d.match(/.{1,4}/g)?.join(' ')||d;});
document.getElementById('cardExp')?.addEventListener('input',function(){const d=this.value.replace(/\D/g,'');this.value=d.length>=2?d.substring(0,2)+' / '+d.substring(2,4):d;});
document.getElementById('cardCvv')?.addEventListener('input',function(){this.value=this.value.replace(/\D/g,'').substring(0,3);});

/* ── Place Order ── */
document.getElementById('placeOrderBtn')?.addEventListener('click', function(){
  const method = document.getElementById('finalPayMethod').value;

  // Payment-specific checks
  if (method==='upi'){
    if (!document.getElementById('upiMsg').classList.contains('ok')){toast('Please verify your UPI ID','error');return;}
  }
  if (method==='card'){
    const cn=document.getElementById('cardNum').value.replace(/\s/g,'');
    if(cn.length<16){toast('Enter a valid 16-digit card number','error');return;}
    if(!document.getElementById('cardName').value.trim()){toast('Enter name on card','error');return;}
    const exp=document.getElementById('cardExp').value.trim();
    if(!/^\d{2} \/ \d{2}$/.test(exp)){toast('Enter valid card expiry (MM / YY)','error');return;}
    if(!document.getElementById('cardCvv').value.trim()){toast('Enter card CVV','error');return;}
  }
  if (method==='emi'){
    if(!document.querySelector('.emi-opt.on')){toast('Select an EMI tenure','error');return;}
    if(!document.querySelector('input[name="emiBank"]:checked')){toast('Select your bank for EMI','error');return;}
  }
  if (method==='bank'){
    if(!document.querySelector('.bank-opt.on')){toast('Select your bank','error');return;}
  }

  this.disabled=true; this.textContent='Placing order…';

  // Build FormData
  const fd = new FormData();
  fd.append('csrf_token', CSRF);
  fd.append('item_name', '__cart__');
  fd.append('payment_method', method);
  fd.append('coupon_code', couponApplied);

  if (addrMode === 'saved' && selectedAddrIdx >= 0) {
    // Send saved address index — place_order.php will read from user doc
    fd.append('use_saved_addr', selectedAddrIdx);
    fd.append('using_saved', '1');
  } else {
    // Send form fields
    ['country','full_name','mobile','email','flat','area','landmark','pincode','city','delivery_instructions'].forEach(n=>{
      const el=document.querySelector(`#addrForm [name="${n}"]`);
      if(el) fd.append(n, el.value);
    });
    const sa=document.getElementById('saveAddrCheck');
    if(sa?.checked) fd.append('save_address','1');
  }

  fetch('place_order.php',{method:'POST',body:fd})
    .then(r=>{if(!r.ok) throw new Error('HTTP '+r.status);return r.json();})
    .then(res=>{
      if(res.error){ toast(res.error,'error'); this.disabled=false; this.textContent='Confirm & Place Order 🎉'; return; }
      closeOv('ovPay');
      // Show coupon if awarded
      if(res.coupon){
        document.getElementById('couponReveal').style.display='block';
        document.getElementById('couponCodeDisplay').textContent=res.coupon.code;
        document.getElementById('couponDiscountText').textContent=res.coupon.discount+'% off on your next order';
      }
      if(method==='card'){
        document.getElementById('successMsg').innerHTML='Card payment confirmed! 🎉<br>Your order is on its way 💗';
      } else if(method==='upi'){
        document.getElementById('successMsg').innerHTML='UPI payment confirmed! 🎉<br>Your order is on its way 💗';
      }
      document.getElementById('successOv').classList.add('open');
      cartData.length=0;
      document.querySelectorAll('.cart-count-badge').forEach(el=>el.textContent='0');
    })
    .catch(err=>{ toast('Network error — '+err.message,'error'); this.disabled=false; this.textContent='Confirm & Place Order 🎉'; });
});
</script>
</body>
</html>
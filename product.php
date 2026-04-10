<?php
session_start();
include __DIR__ . "/db.php";
include __DIR__ . "/auth.php";

$name     = clean($_GET['name'] ?? '', 200);
$wishlist = $_SESSION['wishlist'] ?? [];

if (!$name) { header("Location: index.php"); exit(); }

$product = $products->findOne(['name' => $name]);
if (!$product) { header("Location: index.php"); exit(); }

// Track recently viewed
$rv = $_SESSION['recently_viewed'] ?? [];
$rv = array_values(array_filter($rv, fn($n) => $n !== $name));
array_unshift($rv, $name);
$_SESSION['recently_viewed'] = array_slice($rv, 0, 6);

// Ratings
$ratingsData = iterator_to_array(
    $ratings->find(['product_name' => $name], ['sort' => ['rated_at' => -1]])
);
$ratingCount = count($ratingsData);
$avgRating   = 0;
if ($ratingCount > 0)
    $avgRating = round(array_sum(array_map(fn($r) => (int)($r['stars'] ?? 0), $ratingsData)) / $ratingCount, 1);

$userRating = null;
if (is_logged_in())
    $userRating = $ratings->findOne(['product_name' => $name, 'username' => current_user()['username']]);

// Recently viewed (excluding current)
$recentlyViewed = [];
if (!empty($_SESSION['recently_viewed'])) {
    $rvNames = array_values(array_filter($_SESSION['recently_viewed'], fn($n) => $n !== $name));
    if ($rvNames)
        $recentlyViewed = iterator_to_array($products->find(['name' => ['$in' => $rvNames]]));
}

// Related products
$category = (string)($product['category'] ?? '');
$related  = iterator_to_array(
    $products->find(['category' => $category, 'name' => ['$ne' => $name]], ['limit' => 6])
);

// Cart state
$inCart   = false;
$cartQty  = 0;
$cartSize = '';
if (is_logged_in()) {
    $uDoc = $users->findOne(['username' => current_user()['username']]);
    foreach ((is_array($uDoc['cart'] ?? null) ? $uDoc['cart'] : []) as $ci) {
        if (($ci['name'] ?? '') === $name) {
            $inCart = true; $cartQty = (int)($ci['qty'] ?? 1); $cartSize = (string)($ci['size'] ?? ''); break;
        }
    }
} else {
    foreach ($_SESSION['cart'] ?? [] as $ci) {
        if (($ci['name'] ?? '') === $name) {
            $inCart = true; $cartQty = (int)($ci['qty'] ?? 1); $cartSize = (string)($ci['size'] ?? ''); break;
        }
    }
}

$imgSrc  = (string)($product['image'] ?? '');
$imgSrc  = strpos($imgSrc,'http') === 0 ? $imgSrc : "images/$imgSrc";
$pName   = htmlspecialchars($name);
$pDesc   = htmlspecialchars((string)($product['description'] ?? ''));
$pOld    = (int)($product['old_price'] ?? 0);
$pNew    = (int)($product['new_price']  ?? 0);
$pStock  = (int)($product['stock']      ?? 99);
$pDisc   = ($pOld > 0 && $pNew < $pOld) ? round((($pOld-$pNew)/$pOld)*100) : 0;
$inWish  = in_array($name, $wishlist);

$sizes       = array_values((array)($product['sizes'] ?? []));
$isAccessory = ($category === 'accessories');
$needsSize   = !$isAccessory && !empty($sizes);
$sizesJson   = htmlspecialchars(json_encode($sizes));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title><?= $pName ?> | La Moda</title>
<link rel="stylesheet" href="styles.css">
<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>👗</text></svg>">
</head>
<body>

<?php include __DIR__ . "/navbar.php"; ?>

<div class="pdp-wrap">
  <div class="pdp-main">

    <!-- Image -->
    <div class="pdp-img-wrap">
      <img src="<?= htmlspecialchars($imgSrc) ?>" alt="<?= $pName ?>" class="pdp-img">
      <?php if ($pDisc > 0): ?>
      <span class="pdp-discount-badge"><?= $pDisc ?>% OFF</span>
      <?php endif; ?>
      <button class="heart-btn pdp-heart <?= $inWish?'active':'' ?>" data-name="<?= $pName ?>" aria-label="Wishlist">
        <?= $inWish ? '❤️' : '🤍' ?>
      </button>
    </div>

    <!-- Info -->
    <div class="pdp-info">
      <p class="pdp-category"><?= htmlspecialchars(ucfirst($category)) ?></p>
      <h1 class="pdp-title"><?= $pName ?></h1>

      <!-- Rating display -->
      <div class="pdp-rating-row">
        <div class="star-display">
          <?php for ($s=1;$s<=5;$s++): ?>
          <span class="star <?= $s<=$avgRating?'filled':'' ?>">★</span>
          <?php endfor; ?>
        </div>
        <span class="pdp-rating-text">
          <?= $avgRating>0 ? "$avgRating / 5" : 'No ratings yet' ?>
          <?php if ($ratingCount>0): ?>(<?= $ratingCount ?> review<?= $ratingCount>1?'s':'' ?>)<?php endif; ?>
        </span>
      </div>

      <?php if ($pDesc): ?><p class="pdp-desc"><?= $pDesc ?></p><?php endif; ?>

      <!-- Price -->
      <div class="pdp-price-row">
        <span class="pdp-new">₹<?= $pNew ?></span>
        <?php if ($pOld > 0): ?><span class="pdp-old">₹<?= $pOld ?></span><?php endif; ?>
        <?php if ($pDisc > 0): ?><span class="discount-badge"><?= $pDisc ?>% OFF</span><?php endif; ?>
      </div>

      <!-- Stock -->
      <?php if ($pStock <= 0): ?>
      <p class="stock-out">❌ Out of stock</p>
      <?php elseif ($pStock <= 5): ?>
      <p class="stock-low">⚠ Only <?= $pStock ?> left!</p>
      <?php endif; ?>

      <!-- NO size selector shown here — popup handles it -->

      <!-- Cart actions -->
      <div class="pdp-actions" id="pdpActions">
        <?php if ($pStock <= 0): ?>
          <button class="add-btn pdp-add-btn" disabled style="opacity:.6;cursor:not-allowed;">Out of Stock</button>

        <?php elseif ($inCart): ?>
          <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
            <div class="qty-box" id="pdpQtyBox"
                 data-name="<?= $pName ?>"
                 data-size="<?= htmlspecialchars($cartSize) ?>"
                 style="width:auto;min-width:130px;">
              <button class="qty-btn minus">−</button>
              <span id="pdpQtyNum"><?= $cartQty ?></span>
              <button class="qty-btn plus">+</button>
            </div>
            <a href="cart.php" class="add-btn" style="text-decoration:none;text-align:center;width:auto;padding:12px 24px;">
              Go to Cart →
            </a>
          </div>

        <?php else: ?>
          <div class="cart-controls">
            <button class="add-btn pdp-add-btn" id="pdpAddBtn"
                    data-name="<?= $pName ?>"
                    data-needs-size="<?= $needsSize?'1':'0' ?>"
                    data-sizes="<?= $sizesJson ?>"
                    data-size="">
              Add to Cart
            </button>
          </div>
        <?php endif; ?>

        <?php if ($pStock > 0): ?>
        <button class="buy-now-btn pdp-buy-btn" id="pdpBuyBtn"
                data-name="<?= $pName ?>"
                data-price="<?= $pNew ?>"
                data-needs-size="<?= $needsSize?'1':'0' ?>"
                data-sizes="<?= $sizesJson ?>"
                data-size="">
          Buy Now
        </button>
        <?php endif; ?>
      </div>

      <!-- Info strip -->
      <div class="pdp-info-strip">
        <div class="info-chip">🚚 Free delivery on orders above ₹1000</div>
        <div class="info-chip">↩️ 7-day easy returns</div>
        <div class="info-chip">✅ Secure checkout</div>
      </div>
    </div>
  </div>

  <!-- Ratings & Reviews -->
  <div class="pdp-section">
    <h2 class="pdp-section-title">⭐ Ratings & Reviews</h2>

    <?php if (is_logged_in()): ?>
    <div class="rate-box">
      <p class="pdp-section-label"><?= $userRating ? 'Your rating (click to update)' : 'Rate this product' ?></p>
      <div class="star-picker" id="starPicker" data-current="<?= (int)($userRating['stars'] ?? 0) ?>">
        <?php for ($s=1;$s<=5;$s++): ?>
        <span class="star-pick <?= ($userRating && $s<=(int)$userRating['stars'])?'selected':'' ?>"
              data-val="<?= $s ?>">★</span>
        <?php endfor; ?>
      </div>
      <textarea id="reviewText" class="review-textarea" maxlength="500" rows="3"
        placeholder="Write a review (optional)…"><?= htmlspecialchars((string)($userRating['review'] ?? '')) ?></textarea>
      <button class="form-submit-btn" id="submitRating" style="max-width:200px;">Submit Rating</button>
    </div>
    <?php else: ?>
    <p class="pdp-login-prompt"><a onclick="openLogin()">Login</a> to rate this product.</p>
    <?php endif; ?>

    <div class="reviews-list">
      <?php if (empty($ratingsData)): ?>
      <p class="no-reviews">No reviews yet. Be the first!</p>
      <?php else: ?>
      <?php foreach ($ratingsData as $r): ?>
      <div class="review-card">
        <div class="review-header">
          <span class="reviewer-name"><?= htmlspecialchars((string)($r['username']??'User')) ?></span>
          <div class="star-display small">
            <?php for ($s=1;$s<=5;$s++): ?>
            <span class="star <?= $s<=(int)($r['stars']??0)?'filled':'' ?>">★</span>
            <?php endfor; ?>
          </div>
          <span class="review-date"><?php
            $dt = (string)($r['rated_at'] ?? '');
            echo $dt ? date('d M Y', strtotime($dt)) : '';
          ?></span>
        </div>
        <?php if (!empty($r['review'])): ?>
        <p class="review-body"><?= htmlspecialchars((string)$r['review']) ?></p>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

  <!-- Related -->
  <?php if (!empty($related)): ?>
  <div class="pdp-section">
    <h2 class="pdp-section-title">You might also like</h2>
    <div class="product-scroll" style="padding:12px 0 24px;">
      <?php foreach ($related as $row): include __DIR__."/_product_card.php"; endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- Recently viewed -->
  <?php if (!empty($recentlyViewed)): ?>
  <div class="pdp-section">
    <h2 class="pdp-section-title">Recently Viewed</h2>
    <div class="product-scroll" style="padding:12px 0 24px;">
      <?php foreach ($recentlyViewed as $row): include __DIR__."/_product_card.php"; endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

</div>

<footer><h1>☆ La Moda ☆</h1><p>Wear the Moment</p></footer>
<div id="toast" class="toast"></div>

<script>
/* product.php page script */

// Own CSRF token — cannot rely on navbar's `const CSRF` as const is not on window
const pdpCSRF = <?= json_encode(csrf_token()) ?>;

// ── Override addToCart for PDP: reload page after success so PHP shows qty box ──
const _origAddToCart = window.addToCart;
window.addToCart = function(btn) {
    if (!btn.classList.contains('pdp-add-btn')) {
        _origAddToCart && _origAddToCart(btn);
        return;
    }
    btn.disabled = true;
    btn.textContent = 'Adding…';
    const fd = new FormData();
    fd.append('csrf_token', pdpCSRF);
    fd.append('name',  btn.dataset.name);
    fd.append('size',  btn.dataset.size || '');
    fetch('add_to_cart.php', { method:'POST', body:fd })
        .then(r => r.json())
        .then(res => {
            if (res.error) {
                showToast(res.error, 'error');
                btn.disabled = false;
                btn.textContent = 'Add to Cart';
                return;
            }
            showToast('Added to cart 💗');
            setTimeout(() => location.reload(), 700);
        })
        .catch(() => {
            showToast('Network error', 'error');
            btn.disabled = false;
            btn.textContent = 'Add to Cart';
        });
};

// ── Qty box on PDP (shown when already in cart) ───────────────────────────
(function() {
    const box = document.getElementById('pdpQtyBox');
    if (!box) return;

    function pdpQty(action) {
        const fd = new FormData();
        fd.append('csrf_token', pdpCSRF);
        fd.append('name',   box.dataset.name);
        fd.append('size',   box.dataset.size || '');
        fd.append('action', action);
        fetch('update_cart.php', { method:'POST', body:fd })
            .then(r => r.json())
            .then(res => {
                box.querySelectorAll('button').forEach(b => b.disabled = false);
                if (res.removed) {
                    // Removed from cart — reload for clean state
                    document.querySelectorAll('.cart-count-badge').forEach(el => {
                        el.textContent = Math.max(0, parseInt(el.textContent||0)-1);
                    });
                    location.reload();
                } else {
                    document.getElementById('pdpQtyNum').textContent = res.qty;
                }
            })
            .catch(() => box.querySelectorAll('button').forEach(b => b.disabled = false));
    }

    box.querySelector('.minus')?.addEventListener('click', function() { this.disabled=true; pdpQty('minus'); });
    box.querySelector('.plus')?.addEventListener('click',  function() { this.disabled=true; pdpQty('plus');  });
})();

// ── Star picker ───────────────────────────────────────────────────────────
(function() {
    const picker = document.getElementById('starPicker');
    if (!picker) return;

    let chosen = parseInt(picker.dataset.current) || 0;

    function paint(n) {
        picker.querySelectorAll('.star-pick').forEach(function(s, i) {
            s.classList.toggle('selected', i < n);
        });
    }

    paint(chosen); // paint initial state

    picker.querySelectorAll('.star-pick').forEach(function(star) {
        star.addEventListener('mouseenter', function() {
            paint(parseInt(this.dataset.val));
        });
        star.addEventListener('mouseleave', function() {
            paint(chosen);
        });
        star.addEventListener('click', function() {
            chosen = parseInt(this.dataset.val);
            picker.dataset.current = chosen;
            paint(chosen);
        });
    });
})();

// ── Submit rating ─────────────────────────────────────────────────────────
document.getElementById('submitRating')?.addEventListener('click', function() {
    const stars  = parseInt(document.getElementById('starPicker')?.dataset.current || '0');
    const review = (document.getElementById('reviewText')?.value || '').trim();
    if (!stars) { showToast('Please select a star rating', 'error'); return; }

    this.disabled = true;
    this.textContent = 'Submitting…';
    const self = this;

    const fd = new FormData();
    fd.append('csrf_token', pdpCSRF);
    fd.append('product_name', <?= json_encode($name) ?>);
    fd.append('stars',  stars);
    fd.append('review', review);

    fetch('rate_product.php', { method:'POST', body:fd })
        .then(r => r.json())
        .then(res => {
            if (res.error) {
                showToast(res.error, 'error');
                self.disabled = false;
                self.textContent = 'Submit Rating';
                return;
            }
            showToast('Rating saved! ⭐');
            self.textContent = '✓ Saved';
            setTimeout(() => location.reload(), 1200);
        })
        .catch(() => {
            showToast('Network error', 'error');
            self.disabled = false;
            self.textContent = 'Submit Rating';
        });
});

// ── showToast fallback (navbar defines it but just in case) ──────────────
if (typeof showToast === 'undefined') {
    window.showToast = function(msg, type) {
        const t = document.getElementById('toast');
        if (!t) return;
        t.textContent = msg;
        t.className = 'toast show' + (type==='error'?' toast-error':'');
        clearTimeout(t._t);
        t._t = setTimeout(() => t.classList.remove('show'), 3000);
    };
}
</script>
</body>
</html>
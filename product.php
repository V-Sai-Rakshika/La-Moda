<?php
session_start();
include __DIR__ . "/db.php";
include __DIR__ . "/auth.php";

$name    = clean($_GET['name'] ?? '', 200);
$wishlist = $_SESSION['wishlist'] ?? [];

if (!$name) { header("Location: index.php"); exit(); }

// Fetch product from DB
$product = $products->findOne(['name' => $name]);
if (!$product) { header("Location: index.php"); exit(); }

// Track recently viewed (store last 6 product names in session)
$rv = $_SESSION['recently_viewed'] ?? [];
$rv = array_values(array_filter($rv, fn($n) => $n !== $name)); // remove if already in list
array_unshift($rv, $name);                                      // add to front
$_SESSION['recently_viewed'] = array_slice($rv, 0, 6);         // keep max 6

// Fetch ratings for this product
$ratingsData = iterator_to_array(
    $ratings->find(['product_name' => $name], ['sort' => ['rated_at' => -1]])
);
$avgRating = 0;
$ratingCount = count($ratingsData);
if ($ratingCount > 0) {
    $avgRating = round(array_sum(array_map(fn($r) => (int)($r['stars'] ?? 0), $ratingsData)) / $ratingCount, 1);
}

// Check if current user already rated
$userRating = null;
if (is_logged_in()) {
    $userRating = $ratings->findOne(['product_name' => $name, 'username' => current_user()['username']]);
}

// Fetch recently viewed products (excluding current)
$recentlyViewed = [];
if (!empty($_SESSION['recently_viewed'])) {
    $rvNames = array_filter($_SESSION['recently_viewed'], fn($n) => $n !== $name);
    if ($rvNames) {
        $recentlyViewed = iterator_to_array(
            $products->find(['name' => ['$in' => array_values($rvNames)]])
        );
    }
}

// Related products (same category, different name)
$category = (string)($product['category'] ?? '');
$related  = iterator_to_array(
    $products->find(
        ['category' => $category, 'name' => ['$ne' => $name]],
        ['limit' => 6]
    )
);

$imgSrc  = (string)($product['image'] ?? '');
$imgSrc  = (strpos($imgSrc, 'http') === 0) ? $imgSrc : "images/" . $imgSrc;
$pName   = htmlspecialchars($name);
$pDesc   = htmlspecialchars((string)($product['description'] ?? ''));
$pOld    = (int)($product['old_price'] ?? 0);
$pNew    = (int)($product['new_price']  ?? 0);
$pDisc   = ($pOld > 0 && $pNew < $pOld) ? round((($pOld - $pNew) / $pOld) * 100) : 0;
$inWish  = in_array($name, $wishlist);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $pName ?> | La Moda</title>
<link rel="stylesheet" href="styles.css">
</head>
<body>

<?php include __DIR__ . "/navbar.php"; ?>

<div class="pdp-wrap">

    <!-- ── Product main section ── -->
    <div class="pdp-main">

        <!-- Image -->
        <div class="pdp-img-wrap">
            <img src="<?= htmlspecialchars($imgSrc) ?>" alt="<?= $pName ?>" class="pdp-img" id="pdpMainImg">
            <?php if ($pDisc > 0): ?>
            <span class="pdp-discount-badge"><?= $pDisc ?>% OFF</span>
            <?php endif; ?>
            <button class="heart-btn pdp-heart <?= $inWish ? 'active' : '' ?>"
                data-name="<?= $pName ?>" aria-label="Wishlist">
                <?= $inWish ? '❤️' : '🤍' ?>
            </button>
        </div>

        <!-- Info -->
        <div class="pdp-info">
            <p class="pdp-category"><?= htmlspecialchars(ucfirst($category)) ?></p>
            <h1 class="pdp-title"><?= $pName ?></h1>

            <!-- Star rating display -->
            <div class="pdp-rating-row">
                <div class="star-display">
                    <?php for ($s = 1; $s <= 5; $s++): ?>
                    <span class="star <?= $s <= $avgRating ? 'filled' : '' ?>">★</span>
                    <?php endfor; ?>
                </div>
                <span class="pdp-rating-text">
                    <?= $avgRating > 0 ? $avgRating . ' / 5' : 'No ratings yet' ?>
                    <?php if ($ratingCount > 0): ?>
                    (<?= $ratingCount ?> review<?= $ratingCount > 1 ? 's' : '' ?>)
                    <?php endif; ?>
                </span>
            </div>

            <?php if ($pDesc): ?>
            <p class="pdp-desc"><?= $pDesc ?></p>
            <?php endif; ?>

            <!-- Price -->
            <div class="pdp-price-row">
                <span class="pdp-new">₹<?= $pNew ?></span>
                <?php if ($pOld > 0): ?>
                <span class="pdp-old">₹<?= $pOld ?></span>
                <?php endif; ?>
                <?php if ($pDisc > 0): ?>
                <span class="discount-badge"><?= $pDisc ?>% OFF</span>
                <?php endif; ?>
            </div>

            <!-- Size selector (shown if product has sizes in DB, fallback to standard set) -->
            <?php
            $sizes = $product['sizes'] ?? ['XS','S','M','L','XL','XXL'];
            if (is_object($sizes)) $sizes = iterator_to_array($sizes);
            ?>
            <?php if (!empty($sizes)): ?>
            <div class="pdp-size-section">
                <p class="pdp-section-label">Select Size</p>
                <div class="size-grid" id="sizeGrid">
                    <?php foreach ($sizes as $sz): ?>
                    <button class="size-btn" data-size="<?= htmlspecialchars((string)$sz) ?>">
                        <?= htmlspecialchars((string)$sz) ?>
                    </button>
                    <?php endforeach; ?>
                </div>
                <p class="size-error" id="sizeError" style="display:none;color:#c0392b;font-size:13px;margin-top:6px;">
                    Please select a size
                </p>
            </div>
            <?php endif; ?>

            <!-- Actions -->
            <div class="pdp-actions">
                <div class="cart-controls">
                    <button class="add-btn pdp-add-btn" data-name="<?= $pName ?>">
                        Add to Cart
                    </button>
                </div>
                <button class="buy-now-btn pdp-buy-btn" data-name="<?= $pName ?>">
                    Buy Now
                </button>
            </div>

            <!-- Delivery info strip -->
            <div class="pdp-info-strip">
                <div class="info-chip">🚚 Free delivery on orders above ₹499</div>
                <div class="info-chip">↩️ 7-day easy returns</div>
                <div class="info-chip">✅ Secure checkout</div>
            </div>
        </div>
    </div>

    <!-- ── Rate this product ── -->
    <div class="pdp-section">
        <h2 class="pdp-section-title">⭐ Ratings & Reviews</h2>

        <?php if (is_logged_in()): ?>
        <div class="rate-box">
            <p class="pdp-section-label">
                <?= $userRating ? 'Your rating (click to update)' : 'Rate this product' ?>
            </p>
            <div class="star-picker" id="starPicker"
                 data-current="<?= (int)($userRating['stars'] ?? 0) ?>">
                <?php for ($s = 1; $s <= 5; $s++): ?>
                <span class="star-pick <?= ($userRating && $s <= (int)$userRating['stars']) ? 'selected' : '' ?>"
                      data-val="<?= $s ?>">★</span>
                <?php endfor; ?>
            </div>
            <textarea id="reviewText" class="review-textarea"
                maxlength="500" rows="3"
                placeholder="Write a review (optional, max 500 characters)…"
            ><?= htmlspecialchars((string)($userRating['review'] ?? '')) ?></textarea>
            <button class="form-submit-btn" id="submitRating" style="max-width:200px;">
                Submit Rating
            </button>
        </div>
        <?php else: ?>
        <p class="pdp-login-prompt">
            <a onclick="openLogin()">Login</a> to rate this product.
        </p>
        <?php endif; ?>

        <!-- Reviews list -->
        <div class="reviews-list" id="reviewsList">
            <?php if (empty($ratingsData)): ?>
            <p class="no-reviews">No reviews yet. Be the first!</p>
            <?php else: ?>
            <?php foreach ($ratingsData as $r): ?>
            <div class="review-card">
                <div class="review-header">
                    <span class="reviewer-name"><?= htmlspecialchars((string)($r['username'] ?? 'User')) ?></span>
                    <div class="star-display small">
                        <?php for ($s = 1; $s <= 5; $s++): ?>
                        <span class="star <?= $s <= (int)($r['stars'] ?? 0) ? 'filled' : '' ?>">★</span>
                        <?php endfor; ?>
                    </div>
                    <span class="review-date"><?php
                        $dt = $r['rated_at'] ?? null;
                        echo $dt ? date('d M Y', (int)((string)$dt)/1000) : '';
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

    <!-- ── Related products ── -->
    <?php if (!empty($related)): ?>
    <div class="pdp-section">
        <h2 class="pdp-section-title">You might also like</h2>
        <div class="product-container">
            <?php foreach ($related as $row): include __DIR__ . "/_product_card.php"; endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- ── Recently viewed ── -->
    <?php if (!empty($recentlyViewed)): ?>
    <div class="pdp-section">
        <h2 class="pdp-section-title">Recently Viewed</h2>
        <div class="product-container">
            <?php foreach ($recentlyViewed as $row): include __DIR__ . "/_product_card.php"; endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

</div>

<footer>
    <h1>☆ La Moda ☆</h1>
    <p>Wear the Moment</p>
</footer>

<div id="toast" class="toast"></div>

<script>
const CSRF = <?= json_encode(csrf_token()) ?>;

// ── Size selector ──
let selectedSize = null;
document.querySelectorAll(".size-btn").forEach(btn => {
    btn.addEventListener("click", () => {
        document.querySelectorAll(".size-btn").forEach(b => b.classList.remove("selected"));
        btn.classList.add("selected");
        selectedSize = btn.dataset.size;
        document.getElementById("sizeError")?.style.setProperty("display","none");
    });
});

function requireSize() {
    const grid = document.getElementById("sizeGrid");
    if (!grid) return true; // no size selector = skip check
    if (!selectedSize) {
        document.getElementById("sizeError").style.display = "block";
        return false;
    }
    return true;
}

// ── Add to cart ──
document.querySelector(".pdp-add-btn")?.addEventListener("click", function() {
    if (!requireSize()) return;
    if (!isLoggedIn) { openLogin(); return; }
    this.disabled = true;
    const d = new FormData();
    d.append("csrf_token", CSRF);
    d.append("name", this.dataset.name);
    if (selectedSize) d.append("size", selectedSize);
    fetch("add_to_cart.php", { method:"POST", body:d })
        .then(r => r.json())
        .then(res => {
            if (res.error) { showToast(res.error,'error'); this.disabled=false; return; }
            showToast("Added to cart 💗");
            this.textContent = "✓ Added";
            setTimeout(() => { this.textContent="Add to Cart"; this.disabled=false; }, 2000);
            updateCartCount();
        })
        .catch(() => { showToast("Network error",'error'); this.disabled=false; });
});

// ── Buy Now ──
document.querySelector(".pdp-buy-btn")?.addEventListener("click", function() {
    if (!requireSize()) return;
    if (!isLoggedIn) { openLogin(); return; }
    document.getElementById("buyNowForm")?.reset();
    document.getElementById("buyItemName").value = this.dataset.name;
    document.querySelector("#buyNowForm [name='csrf_token']").value = CSRF;
    document.querySelectorAll(".modal").forEach(m => m.style.display="none");
    document.getElementById("buyNowModal").style.display = "block";
});

// ── Star picker ──
const picker = document.getElementById("starPicker");
if (picker) {
    let hovered = 0;
    let selected = parseInt(picker.dataset.current) || 0;

    picker.querySelectorAll(".star-pick").forEach(star => {
        star.addEventListener("mouseenter", () => {
            hovered = parseInt(star.dataset.val);
            highlightStars(hovered);
        });
        star.addEventListener("mouseleave", () => {
            hovered = 0;
            highlightStars(selected);
        });
        star.addEventListener("click", () => {
            selected = parseInt(star.dataset.val);
            picker.dataset.current = selected;
            highlightStars(selected);
        });
    });

    function highlightStars(n) {
        picker.querySelectorAll(".star-pick").forEach((s, i) => {
            s.classList.toggle("selected", i < n);
        });
    }
}

// ── Submit rating ──
document.getElementById("submitRating")?.addEventListener("click", function() {
    const stars  = parseInt(document.getElementById("starPicker")?.dataset.current || "0");
    const review = document.getElementById("reviewText")?.value.trim() || "";
    if (!stars) { showToast("Please select a star rating", 'error'); return; }

    this.disabled = true; this.textContent = "Submitting…";

    const d = new FormData();
    d.append("csrf_token", CSRF);
    d.append("product_name", <?= json_encode($name) ?>);
    d.append("stars",  stars);
    d.append("review", review);

    fetch("rate_product.php", { method:"POST", body:d })
        .then(r => r.json())
        .then(res => {
            if (res.error) { showToast(res.error,'error'); this.disabled=false; this.textContent="Submit Rating"; return; }
            showToast("Rating saved! ⭐");
            this.textContent = "✓ Saved";
            // Reload review section
            setTimeout(() => location.reload(), 1200);
        })
        .catch(() => { showToast("Network error",'error'); this.disabled=false; this.textContent="Submit Rating"; });
});

function showToast(msg, type) {
    const t = document.getElementById("toast");
    t.textContent = msg;
    t.className = 'toast show' + (type==='error'?' toast-error':'');
    clearTimeout(t._t);
    t._t = setTimeout(() => t.classList.remove("show"), 3000);
}
function updateCartCount() {
    document.querySelectorAll(".cart-count-badge").forEach(el => el.textContent = parseInt(el.textContent||0)+1);
}
</script>

</body>
</html>
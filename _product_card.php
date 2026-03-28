<?php
/**
 * _product_card.php — reusable product card
 * Expects: $row (MongoDB doc), $wishlist (array of product names)
 */

$_image   = trim((string)($row['image'] ?? ''));
$_rawName = trim((string)($row['name'] ?? 'No Name'));
$_name    = htmlspecialchars($_rawName);
$_desc    = htmlspecialchars((string)($row['description'] ?? ''));
$_old     = (int)($row['old_price'] ?? 0);
$_new     = (int)($row['new_price'] ?? 0);
$_disc    = ($_old > 0 && $_new > 0 && $_new < $_old)
              ? round((($_old - $_new) / $_old) * 100) : 0;
$_inWish  = in_array($_rawName, $wishlist ?? []);
$_pdpUrl  = "product.php?name=" . urlencode($_rawName);

// ── Image path ──────────────────────────────────────────────────────────────
// If the DB stores a full URL (http/https), use it directly.
// If it stores just a filename like "kurti1.jpg", prefix with images/ folder.
// If blank, show a grey placeholder so layout doesn't break.
if ($_image === '') {
    $_imgSrc = "https://placehold.co/300x300/f5f5f5/aaa?text=No+Image";
} elseif (strpos($_image, 'http') === 0) {
    $_imgSrc = $_image;
} else {
    // Remove any accidental leading slash or backslash before prefixing
    $_imgSrc = "images/" . ltrim($_image, '/\\');
}

// ── Rating (stored on product doc for speed — updated by rate_product.php) ──
$_stars  = (float)($row['avg_rating']   ?? 0);
$_rCount = (int)($row['rating_count']  ?? 0);
?>
<div class="card">

    <!-- Wishlist heart -->
    <button class="heart-btn <?= $_inWish ? 'active' : '' ?>"
        data-name="<?= $_name ?>"
        aria-label="Add to wishlist">
        <?= $_inWish ? '❤️' : '🤍' ?>
    </button>

    <!-- Image → links to product detail page -->
    <a href="<?= $_pdpUrl ?>" class="card-img-link">
        <div class="img-box">
            <img src="<?= htmlspecialchars($_imgSrc) ?>"
                 alt="<?= $_name ?>"
                 loading="lazy"
                 onerror="this.src='https://placehold.co/300x300/f5f5f5/aaa?text=No+Image'">
        </div>
    </a>

    <!-- Name → links to product detail page -->
    <a href="<?= $_pdpUrl ?>" class="card-name-link">
        <h3><?= $_name ?></h3>
    </a>

    <?php if ($_desc): ?>
    <p class="description"><?= $_desc ?></p>
    <?php endif; ?>

    <!-- Star rating (only shown if product has been rated) -->
    <?php if ($_stars > 0): ?>
    <div class="card-stars">
        <?php for ($s = 1; $s <= 5; $s++): ?>
        <span class="star <?= $s <= round($_stars) ? 'filled' : '' ?>">★</span>
        <?php endfor; ?>
        <?php if ($_rCount): ?>
        <span class="card-rcount">(<?= $_rCount ?>)</span>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if ($_old > 0): ?><p class="old">₹<?= $_old ?></p><?php endif; ?>
    <p class="new">₹<?= $_new ?></p>
    <?php if ($_disc > 0): ?>
    <span class="discount-badge"><?= $_disc ?>% OFF</span>
    <?php endif; ?>

    <div class="btn-group">
        <div class="cart-controls">
            <button class="add-btn" data-name="<?= $_name ?>">
                Add to Cart
            </button>
        </div>
        <button class="buy-now-btn" data-name="<?= $_name ?>">
            Buy Now
        </button>
    </div>

</div>
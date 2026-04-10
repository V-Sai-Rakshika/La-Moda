<?php


$_image   = trim((string)($row['image'] ?? ''));
$_rawName = trim((string)($row['name']  ?? 'No Name'));
$_name    = htmlspecialchars($_rawName);
$_desc    = htmlspecialchars((string)($row['description'] ?? ''));
$_old     = (int)($row['old_price'] ?? 0);
$_new     = (int)($row['new_price'] ?? 0);
$_disc    = ($_old > 0 && $_new > 0 && $_new < $_old)
              ? round((($_old - $_new) / $_old) * 100) : 0;
$_inWish  = in_array($_rawName, $wishlist ?? []);
$_pdpUrl  = 'product.php?name=' . urlencode($_rawName);

// Stock & size
$_stock       = (int)($row['stock']    ?? 99);
$_cat         = strtolower(trim((string)($row['category'] ?? '')));
$_sizes       = is_array($row['sizes'] ?? null) ? $row['sizes'] : [];
$_isAccessory = ($_cat === 'accessories');
$_needsSize   = !$_isAccessory && !empty($_sizes);
$_sizesJson   = htmlspecialchars(json_encode(array_values($_sizes)));

// Image — exact same logic as original
if ($_image === '') {
    $_imgSrc = 'https://placehold.co/300x380/f5f5f5/aaa?text=No+Image';
} elseif (strpos($_image, 'http') === 0) {
    $_imgSrc = $_image;
} else {
    $_imgSrc = 'images/' . ltrim($_image, '/\\');
}

// Rating
$_stars  = (float)($row['avg_rating']    ?? 0);
$_rCount = (int)($row['rating_count'] ?? 0);

// Stock label — only show when low or out
$_stockLabel = '';
$_stockClass = '';
if ($_stock <= 0) {
    $_stockLabel = '❌ Out of stock';
    $_stockClass = 'stock-out';
} elseif ($_stock <= 5) {
    $_stockLabel = "⚠ Only $_stock left!";
    $_stockClass = 'stock-low';
}
// (no label when plenty in stock — keeps card clean)

// Flash / discount
$_flash = (string)($row['flash_sale'] ?? 'no');
?>
<div class="card<?= $_flash === 'yes' ? ' flash-card' : '' ?>">

    <!-- Wishlist heart — unchanged -->
    <button class="heart-btn <?= $_inWish ? 'active' : '' ?>"
            data-name="<?= $_name ?>"
            aria-label="Add to wishlist">
        <?= $_inWish ? '❤️' : '🤍' ?>
    </button>

    <!-- Image — unchanged -->
    <a href="<?= $_pdpUrl ?>" class="card-img-link">
        <div class="img-box">
            <img src="<?= htmlspecialchars($_imgSrc) ?>"
                 alt="<?= $_name ?>"
                 loading="lazy"
                 onerror="this.src='https://placehold.co/300x380/f5f5f5/aaa?text=?'">
        </div>
    </a>

    <!-- Name — unchanged -->
    <a href="<?= $_pdpUrl ?>" class="card-name-link">
        <h3><?= $_name ?></h3>
    </a>

    <?php if ($_desc): ?>
    <p class="description"><?= $_desc ?></p>
    <?php endif; ?>

    
    <?php if ($_flash === 'yes'): ?>
    <div class="flash-badge">⚡ Flash Deal</div>
    <?php endif; ?>

    <?php if ($_disc >= 5): ?>
    <div class="discount-badge">-<?= $_disc ?>% OFF</div>
    <?php endif; ?>

    <!-- Rating — unchanged -->
    <?php if ($_stars > 0 && $_rCount > 0): ?>
    <div class="card-stars">
        <?php for ($s = 1; $s <= 5; $s++): ?>
        <span class="star <?= $s <= round($_stars) ? 'filled' : '' ?>">★</span>
        <?php endfor; ?>
        <span class="card-rcount">(<?= $_rCount ?>)</span>
    </div>
    <?php endif; ?>

    <!-- Price — unchanged -->
    <?php if ($_old > 0 && $_old > $_new): ?>
    <p class="old">MRP ₹<?= $_old ?></p>
    <?php endif; ?>
    <p class="new">₹<?= $_new ?></p>

    <!-- Stock warning — only when low/out -->
    <?php if ($_stockLabel): ?>
    <p class="<?= $_stockClass ?>" style="margin:3px 0 5px;"><?= $_stockLabel ?></p>
    <?php endif; ?>

    <!-- Buttons — sizes stored in data attr, popup handles selection -->
    <div class="btn-group">
        <div class="cart-controls" id="cc-<?= urlencode($_rawName) ?>">
            <?php
            // Check if already in cart
            $_inCart = false; $_cartQty = 0;
            if (isset($cartData) && is_array($cartData)) {
                foreach ($cartData as $_ci) {
                    if (($_ci['name'] ?? '') === $_rawName) {
                        $_inCart   = true;
                        $_cartQty  = (int)($_ci['qty'] ?? 1);
                        break;
                    }
                }
            }
            ?>
            <?php if ($_inCart): ?>
            <div class="qty-box" data-name="<?= $_name ?>">
                <button class="qty-btn minus">−</button>
                <span><?= $_cartQty ?></span>
                <button class="qty-btn plus">+</button>
            </div>
            <?php else: ?>
            <button class="add-btn"
                    data-name="<?= $_name ?>"
                    data-size=""
                    data-needs-size="<?= $_needsSize ? '1' : '0' ?>"
                    data-sizes="<?= $_sizesJson ?>"
                    <?= $_stock <= 0 ? 'disabled' : '' ?>>
                <?= $_stock <= 0 ? 'Out of Stock' : 'Add to Cart' ?>
            </button>
            <?php endif; ?>
        </div>

        <?php if ($_stock > 0): ?>
        <button class="buy-now-btn"
                data-name="<?= $_name ?>"
                data-price="<?= $_new ?>"
                data-size=""
                data-needs-size="<?= $_needsSize ? '1' : '0' ?>"
                data-sizes="<?= $_sizesJson ?>">
            Buy Now
        </button>
        <?php endif; ?>
    </div>
</div>
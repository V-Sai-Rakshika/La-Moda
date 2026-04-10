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

// ── New fields for stock & size ─────────────────────────────────────────────
$_stock       = (int)($row['stock'] ?? 99);
$_cat         = strtolower(trim((string)($row['category'] ?? '')));
$_sizes       = is_array($row['sizes'] ?? null) ? $row['sizes'] : [];
$_isAccessory = ($_cat === 'accessories');
$_needsSize   = !$_isAccessory && !empty($_sizes);
$_safeKey     = urlencode($_rawName);

// ── Image path ──────────────────────────────────────────────────────────────
if ($_image === '') {
    $_imgSrc = "https://placehold.co/300x300/f5f5f5/aaa?text=No+Image";
} elseif (strpos($_image, 'http') === 0) {
    $_imgSrc = $_image;
} else {
    $_imgSrc = "images/" . ltrim($_image, '/\\');
}

// ── Rating ──────────────────────────────────────────────────────────────────
$_stars  = (float)($row['avg_rating'] ?? 0);
$_rCount = (int)($row['rating_count'] ?? 0);

// ── Stock label ─────────────────────────────────────────────────────────────
if ($_stock <= 0) {
    $_stockLabel = '❌ Out of stock';
    $_stockColor = '#dc2626';
} elseif ($_stock <= 5) {
    $_stockLabel = "⚠ Only $_stock left!";
    $_stockColor = '#ea580c';
} else {
    $_stockLabel = '✅ In stock';
    $_stockColor = '#16a34a';
}
?>
<div class="card">
    <!-- Wishlist heart — UNCHANGED from original -->
    <button class="heart-btn <?= $_inWish ? 'active' : '' ?>"
        data-name="<?= $_name ?>"
        aria-label="Add to wishlist">
        <?= $_inWish ? '❤️' : '🤍' ?>
    </button>

    <!-- Image — UNCHANGED from original -->
    <a href="<?= $_pdpUrl ?>" class="card-img-link">
        <div class="img-box">
            <img src="<?= htmlspecialchars($_imgSrc) ?>"
                 alt="<?= $_name ?>"
                 loading="lazy"
                 onerror="this.src='https://placehold.co/300x300/f5f5f5/aaa?text=No+Image'">
        </div>
    </a>

    <!-- Name — UNCHANGED from original -->
    <a href="<?= $_pdpUrl ?>" class="card-name-link">
        <h3><?= $_name ?></h3>
    </a>

    <?php if ($_desc): ?>
    <p class="description"><?= $_desc ?></p>
    <?php endif; ?>

    <!-- Rating — UNCHANGED from original -->
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

    <?php if ($_old > 0): ?>
        <p class="old">MRP ₹<?= $_old ?></p>
    <?php endif; ?>
    <p class="new">₹<?= $_new ?></p>
    <?php if ($_disc > 0): ?>
        <span class="discount-badge"><?= $_disc ?>% OFF</span>
    <?php endif; ?>

    <!-- ── ADDED: Stock status ── -->
    <p style="font-size:11px;font-weight:700;color:<?= $_stockColor ?>;margin:3px 0 5px;"><?= $_stockLabel ?></p>

    <!-- ── ADDED: Size selector (clothing only) ── -->
    <?php if ($_needsSize && $_stock > 0): ?>
    <div id="ssw-<?= $_safeKey ?>" style="margin:0 0 7px;padding:7px 8px;border:2px dashed #f97316;border-radius:8px;background:#fff7ed;">
        <p id="sslbl-<?= $_safeKey ?>" style="font-size:10px;font-weight:800;color:#dc2626;text-transform:uppercase;margin:0 0 5px;line-height:1.3;">⚠ Pick a size:</p>
        <div style="display:flex;flex-wrap:wrap;gap:4px;">
            <?php foreach ($_sizes as $_sz): ?>
            <span class="sz-chip"
                  data-sz="<?= htmlspecialchars($_sz) ?>"
                  data-key="<?= $_safeKey ?>"
                  style="display:inline-block;padding:3px 9px;border:1.5px solid #d1d5db;border-radius:5px;font-size:11px;font-weight:600;cursor:pointer;background:#fff;">
                <?= htmlspecialchars($_sz) ?>
            </span>
            <?php endforeach; ?>
        </div>
        <p id="serr-<?= $_safeKey ?>" style="display:none;color:#dc2626;font-size:10px;font-weight:700;margin:4px 0 0;">☝ Select a size to continue!</p>
    </div>
    <?php endif; ?>

    <div class="btn-group">
        <div class="cart-controls">
            <!-- add-btn: IDENTICAL to original, only added data-needs-size -->
            <button class="add-btn"
                    data-name="<?= $_name ?>"
                    data-needs-size="<?= $_needsSize ? '1' : '0' ?>"
                    data-size=""
                    <?= $_stock <= 0 ? 'disabled' : '' ?>>
                <?= $_stock <= 0 ? 'Out of Stock' : 'Add to Cart' ?>
            </button>
        </div>
        <!-- buy-now-btn: IDENTICAL to original, only added data-needs-size -->
        <?php if ($_stock > 0): ?>
        <button
            class="buy-now-btn"
            data-name="<?= $_rawName ?>"
            data-price="<?= $_new ?>"
            data-size=""
            data-needs-size="<?= $_needsSize ? '1' : '0' ?>"
        >
            Buy Now
        </button>
        <?php endif; ?>
    </div>
</div>

<script>
(function(){
    var key   = <?= json_encode($_safeKey) ?>;
    var needs = <?= $_needsSize && $_stock > 0 ? 'true' : 'false' ?>;
    if (!needs) return;

    var ssw  = document.getElementById('ssw-'   + key);
    var lbl  = document.getElementById('sslbl-' + key);
    var err  = document.getElementById('serr-'  + key);
    if (!ssw) return;

    ssw.querySelectorAll('.sz-chip').forEach(function(chip){
        chip.addEventListener('click', function(){
            // reset all chips
            ssw.querySelectorAll('.sz-chip').forEach(function(c){
                c.style.background  = '#fff';
                c.style.borderColor = '#d1d5db';
                c.style.color       = '';
            });
            // activate this chip
            this.style.background  = '#16a34a';
            this.style.borderColor = '#16a34a';
            this.style.color       = '#fff';

            var sz   = this.dataset.sz;
            // write chosen size into both buttons of THIS card
            var card   = ssw.closest('.card');
            var addBtn = card ? card.querySelector('.add-btn')     : null;
            var buyBtn = card ? card.querySelector('.buy-now-btn') : null;
            if (addBtn) addBtn.dataset.size = sz;
            if (buyBtn) buyBtn.dataset.size = sz;

            // turn wrapper green
            ssw.style.borderColor = '#16a34a';
            ssw.style.borderStyle = 'solid';
            ssw.style.background  = '#f0fdf4';
            if (lbl) { lbl.style.color = '#15803d'; lbl.textContent = '✅ Size: ' + sz; }
            if (err) err.style.display = 'none';
        });
    });
})();
</script>

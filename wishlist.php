<?php
ob_start();
session_start(); include "db.php"; include "auth.php";
require_login('index.php');

if (isset($_GET['remove'])) {
    $n = clean($_GET['remove'], 200);
    $wishlist->deleteOne(['username' => current_user()['username'], 'product_name' => $n]);
    $_SESSION['wishlist'] = array_values(array_filter($_SESSION['wishlist'] ?? [], fn($x) => $x !== $n));
    header("Location: wishlist.php"); exit();
}

$username      = current_user()['username'];
$wishlistItems = iterator_to_array($wishlist->find(['username' => $username], ['sort' => ['added_at' => -1]]));
ob_end_clean();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>La Moda | My Wishlist</title>
<link rel="stylesheet" href="styles.css">
<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>👗</text></svg>">
</head>
<body>
<?php include "navbar.php"; ?>

<div class="page-wrap">
    <h1 class="page-title">♡ My Wishlist</h1>

    <?php if (empty($wishlistItems)): ?>
    <div class="empty-state">
        <div class="empty-icon">🤍</div>
        <p>Your wishlist is empty.</p>
        <a href="index.php" class="empty-link">Explore items to add →</a>
    </div>
    <?php else: ?>
    <div class="list-box">
        <?php foreach ($wishlistItems as $item): ?>
        <div class="list-item">
            <div class="list-left">
                <div class="list-img">
                    <img src="<?= htmlspecialchars((string)$item['image']) ?>"
                         alt="<?= htmlspecialchars((string)$item['product_name']) ?>">
                </div>
                <div class="list-details">
                    <h3><?= htmlspecialchars((string)$item['product_name']) ?></h3>
                    <p class="list-price">₹<?= (int)$item['price'] ?></p>
                    <a href="wishlist.php?remove=<?= urlencode((string)$item['product_name']) ?>"
                       class="remove-link">Remove ✕</a>
                </div>
            </div>
            <div class="list-actions">
                <p class="list-item-price">₹<?= (int)$item['price'] ?></p>
                <button class="move-cart-btn"
                    data-name="<?= htmlspecialchars((string)$item['product_name']) ?>">
                    Move to Cart 🛒
                </button>
            </div>
        </div>
        <?php endforeach; ?>
        <div class="list-footer">
            <a href="index.php">← Continue Shopping</a>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
document.querySelectorAll(".move-cart-btn").forEach(btn => {
    btn.addEventListener("click", () => {
        btn.disabled = true; btn.textContent = "Adding…";
        const d = new FormData();
        d.append("csrf_token", CSRF); d.append("name", btn.dataset.name);
        fetch("add_to_cart.php", {method:"POST", body:d}).then(r=>r.json())
            .then(res => {
                if (res.error) { showToast(res.error,'error'); btn.disabled=false; btn.textContent="Move to Cart 🛒"; return; }
                showToast("Moved to cart 🛒");
                btn.textContent = "✓ In Cart";
            })
            .catch(() => { showToast("Network error",'error'); btn.disabled=false; btn.textContent="Move to Cart 🛒"; });
    });
});
</script>
</body>
</html>
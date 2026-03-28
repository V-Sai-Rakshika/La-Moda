<?php
session_start();
include __DIR__ . "/db.php";
include __DIR__ . "/auth.php";

$search   = clean($_GET['search'] ?? '', 200);
$wishlist = $_SESSION['wishlist'] ?? [];

if (isset($_GET['delete'])) {
    $del = clean($_GET['delete'], 200);
    $_SESSION['search_history'] = array_values(array_filter(
        $_SESSION['search_history'] ?? [], fn($i) => $i !== $del
    ));
}
if ($search && !in_array($search, $_SESSION['search_history'] ?? [])) {
    $_SESSION['search_history'][] = $search;
}

// Recently viewed
$recentlyViewed = [];
if (!empty($_SESSION['recently_viewed'])) {
    $recentlyViewed = iterator_to_array(
        $products->find(['name' => ['$in' => $_SESSION['recently_viewed']]])
    );
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>La Moda | Fashion Store</title>
<link rel="stylesheet" href="styles.css">
</head>
<body>

<?php include __DIR__ . "/navbar.php"; ?>

<!-- Search history bar -->
<?php if (!empty($_SESSION['search_history'])): ?>
<div class="search-history-bar" id="searchHistoryBar" style="display:none;">
    <div class="search-history-inner">
        <span class="sh-label">Recent:</span>
        <?php foreach (array_unique(array_reverse($_SESSION['search_history'])) as $item): ?>
        <a href="index.php?search=<?= urlencode($item) ?>" class="sh-chip"><?= htmlspecialchars($item) ?></a>
        <a href="index.php?delete=<?= urlencode($item) ?>" class="sh-delete" title="Remove">✕</a>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<img src="images/banner.png" class="banner-img" alt="La Moda Banner">

<!-- FLASH DEALS -->
<h2 class="section-title">⚡ Today's Flash Deals</h2>
<p class="section-subtitle">Limited time offers — grab yours now!</p>
<div class="scroll-row-wrap">
<div class="product-scroll">
<?php
$cursor = $search
    ? $products->find(['$or' => [
        ['name'        => ['$regex' => preg_quote($search,'/'), '$options'=>'i']],
        ['description' => ['$regex' => preg_quote($search,'/'), '$options'=>'i']],
      ]])
    : $products->find(['flash_sale' => 'yes']);

$count = 0;
foreach ($cursor as $row) {
    $count++;
    include __DIR__ . "/_product_card.php";
}
if ($count === 0) {
    echo '<p style="padding:20px;color:#999;">No flash deals found.</p>';
}
?>
</div>
</div>

<!-- CATEGORY SECTIONS -->
<?php foreach (['traditional','dresses','casual','accessories'] as $cat): ?>
<section>
    <h2 class="section-title"><?= ucfirst($cat) ?> Wear</h2>
    <div class="scroll-row-wrap">
    <div class="product-scroll">
    <?php
    $catCount = 0;
    foreach ($products->find(['category' => $cat]) as $row) {
        $catCount++;
        include __DIR__ . "/_product_card.php";
    }
    if ($catCount === 0) {
        echo '<p style="padding:20px;color:#999;">No products found.</p>';
    }
    ?>
    </div>
    </div>
</section>
<?php endforeach; ?>

<!-- RECENTLY VIEWED -->
<?php if (!empty($recentlyViewed)): ?>
<section>
    <h2 class="section-title">👁 Recently Viewed</h2>
    <div class="scroll-row-wrap">
    <div class="product-scroll">
    <?php foreach ($recentlyViewed as $row): include __DIR__ . "/_product_card.php"; endforeach; ?>
    </div>
    </div>
</section>
<?php endif; ?>

<footer>
    <h1>☆ La Moda ☆</h1>
    <p>Wear the Moment</p>
</footer>

</body>
</html>
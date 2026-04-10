<?php
session_start();
include __DIR__ . "/db.php";
include __DIR__ . "/auth.php";

$sub     = clean($_GET['sub'] ?? '', 100);
$wishlist = $_SESSION['wishlist'] ?? [];

$filter = ['category' => 'accessories'];

if ($sub) {
    // Match subcategory exactly — values in DB: jewellery, watch, bag, ring, necklace, earring, bracelet, handbag
    $filter['subcategory'] = ['$regex' => preg_quote($sub, '/'), '$options' => 'i'];
}

$cursor = $products->find($filter);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Accessories | La Moda</title>
<link rel="stylesheet" href="styles.css">
<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>👗</text></svg>">
</head>
<body>
<?php include __DIR__ . "/navbar.php"; ?>

<div class="category-layout">
    <aside class="side-menu">

        <h3>All</h3>
        <a href="accessories.php" class="<?= $sub === '' ? 'active' : '' ?>">
            All Accessories
        </a>

        <h3>Jewellery</h3>
        <a href="?sub=set" class="<?= $sub === 'set' ? 'active' : '' ?>">Jewellery Set</a>
        <a href="?sub=ring"      class="<?= $sub === 'ring'      ? 'active' : '' ?>">Rings</a>
        <a href="?sub=necklace"  class="<?= $sub === 'necklace'  ? 'active' : '' ?>">Necklace</a>
        <a href="?sub=ear"   class="<?= $sub === 'ear'   ? 'active' : '' ?>">Earrings</a>
        <a href="?sub=bracelet"  class="<?= $sub === 'bracelet'  ? 'active' : '' ?>">Bracelet</a>
        <a href="?sub=waist"  class="<?= $sub === 'waist'  ? 'active' : '' ?>">Waist Chain</a>

        <h3>Watch</h3>
        <a href="?sub=analog" class="<?= $sub === 'watch' ? 'active' : '' ?>">Analog Watches</a>
        <a href="?sub=digital" class="<?= $sub === 'watch' ? 'active' : '' ?>">Digital Watches</a>

        <h3>Bag</h3>
        <a href="?sub=back"     class="<?= $sub === 'back'     ? 'active' : '' ?>">Backpack</a>
        <a href="?sub=handbag" class="<?= $sub === 'handbag' ? 'active' : '' ?>">Handbag</a>

    </aside>

    <div class="product-grid">
        <?php
        $count = 0;
        foreach ($cursor as $row) {
            $count++;
            include __DIR__ . "/_product_card.php";
        }
        if ($count === 0) {
            echo '<p style="padding:30px;color:#999;font-size:15px;">No products found.</p>';
        }
        ?>
    </div>
</div>

<footer><h1>☆ La Moda ☆</h1><p>Wear the Moment</p></footer>
</body>
</html>
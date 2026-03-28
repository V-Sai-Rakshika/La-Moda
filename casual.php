<?php
session_start();
include __DIR__ . "/db.php";
include __DIR__ . "/auth.php";

$sub     = clean($_GET['sub'] ?? '', 100);
$wishlist = $_SESSION['wishlist'] ?? [];

$filter = ['category' => 'casual'];

if ($sub) {
    // Match subcategory exactly — values in DB: shirt, t-shirt, top, jeans, trouser, skirt, bottom
    $filter['subcategory'] = ['$regex' => preg_quote($sub, '/'), '$options' => 'i'];
}

$cursor = $products->find($filter);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Casual | La Moda</title>
<link rel="stylesheet" href="styles.css">
</head>
<body>
<?php include __DIR__ . "/navbar.php"; ?>

<div class="category-layout">
    <aside class="side-menu">

        <h3>All</h3>
        <a href="casual.php" class="<?= $sub === '' ? 'active' : '' ?>">
            All Casual
        </a>

        <h3>Tops</h3>
        <a href="?sub=shirt"  class="<?= $sub === 'shirt'  ? 'active' : '' ?>">Shirt</a>
        <a href="?sub=t-shirt" class="<?= $sub === 't-shirt' ? 'active' : '' ?>">T-Shirt</a>
        <a href="?sub=top"    class="<?= $sub === 'top'    ? 'active' : '' ?>">Casual Top</a>

        <h3>Bottoms</h3>
        <a href="?sub=jeans"   class="<?= $sub === 'jeans'   ? 'active' : '' ?>">Jeans</a>
        <a href="?sub=trouser" class="<?= $sub === 'trouser' ? 'active' : '' ?>">Trouser</a>
        <a href="?sub=skirt"   class="<?= $sub === 'skirt'   ? 'active' : '' ?>">Skirt</a>

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
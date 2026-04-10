<?php
session_start(); include __DIR__ . "/db.php"; include __DIR__ . "/auth.php";
$sub = clean($_GET['sub'] ?? '', 100);
$wishlist = $_SESSION['wishlist'] ?? [];
$filter = ['category' => 'traditional'];
if ($sub) $filter['subcategory'] = ['$regex' => preg_quote($sub,'/'), '$options'=>'i'];
$cursor = $products->find($filter);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Traditional | La Moda</title>
<link rel="stylesheet" href="styles.css">
<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>👗</text></svg>">
</head>
<body>
<?php include __DIR__ . "/navbar.php"; ?>
<div class="category-layout">
    <aside class="side-menu">
        <h3>Traditional</h3>
        <a href="traditional.php"              class="<?= !$sub?'active':'' ?>">All</a>
        <a href="?sub=kurti set"   class="<?= $sub==='kurti set'  ?'active':'' ?>">Kurti Set</a>
        <a href="?sub=short kurti" class="<?= $sub==='short kurti'?'active':'' ?>">Short Kurti</a>
        <a href="?sub=saree"       class="<?= $sub==='saree'      ?'active':'' ?>">Saree</a>
        <a href="?sub=lehenga"     class="<?= $sub==='lehenga'    ?'active':'' ?>">Lehenga</a>
        <a href="?sub=half saree"  class="<?= $sub==='half saree' ?'active':'' ?>">Half Saree</a>
    </aside>
    <div class="product-grid">
        <?php foreach ($cursor as $row): include __DIR__ . "/_product_card.php"; endforeach; ?>
    </div>
</div>
<footer><h1>☆ La Moda ☆</h1><p>Wear the Moment</p></footer>
</body>
</html>
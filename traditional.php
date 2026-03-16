<?php include "db.php"; ?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Traditional | La Moda</title>
<link rel="stylesheet" href="styles.css">
</head>
<body>

<nav class="navbar">
<h2 class="logo">La Moda</h2>
<ul>
<li><a href="index.php">Home</a></li>
<li><a href="traditional.php">Traditional</a></li>
<li><a href="dresses.php">Dresses</a></li>
<li><a href="casual.php">Casual</a></li>
<li><a href="accessories.php">Accessories</a></li>
</ul>
</nav>

<div style="display:flex">

<div class="side-menu" style="width:220px;padding:20px;">
<h3>Traditional</h3>
<a href="?sub=kurti set">Kurti Set</a>
<a href="?sub=short kurti">Short Kurti</a>
<a href="?sub=saree">Saree</a>
<a href="?sub=lehenga">Lehenga</a>
<a href="?sub=half saree">Half Saree</a>
</div>

<div class="product-container">

<?php
$sub=$_GET['sub'] ?? '';

$sql=$sub
? "SELECT * FROM products WHERE category='traditional' AND subcategory='$sub'"
: "SELECT * FROM products WHERE category='traditional'";

$res=mysqli_query($conn,$sql);

while($row=mysqli_fetch_assoc($res)){
$discount=round((($row['old_price']-$row['new_price'])/$row['old_price'])*100);
?>

<div class="card">

<?php if($row['flash_sale']=="yes"){ ?>
<div class="sale-tag">SALE</div>
<?php } ?>

<a href="<?= $row['link'] ?>" target="_blank">

<div class="img-box">
<img src="images/<?= $row['image'] ?>">
</div>

<h3><?= $row['name'] ?></h3>
<p><?= $row['description'] ?></p>

<p class="old">₹<?= $row['old_price'] ?></p>
<p class="new">₹<?= $row['new_price'] ?></p>
<p style="color:brown"><?= $discount ?>% OFF</p>

</a>
</div>

<?php } ?>

</div>
</div>
</body>
</html>

<?php include "db.php"; ?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Accessories | La Moda</title>
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

<div class="side-menu" style="width:230px;padding:20px;">
<h3>Jewellery</h3>
<a href="?sub=jewellery&subsub=jewellery set">Jewellery Set</a>
<a href="?sub=jewellery&subsub=rings">Rings</a>
<a href="?sub=jewellery&subsub=chain">Chain</a>
<a href="?sub=jewellery&subsub=earrings">Earrings</a>
<a href="?sub=jewellery&subsub=waist chain">Waist Chain</a>

<h3>Watch</h3>
<a href="?sub=watch&subsub=analog watch">Analog Watch</a>
<a href="?sub=watch&subsub=smart watch">Smart Watch</a>

<h3>Bag</h3>
<a href="?sub=bag&subsub=shoulder bag">Shoulder Bag</a>
<a href="?sub=bag&subsub=sling bag">Sling Bag</a>
<a href="?sub=bag&subsub=handbag">Handbag</a>
</div>

<div class="product-container">

<?php
$sub=$_GET['sub'] ?? '';
$subsub=$_GET['subsub'] ?? '';

$sql=($sub && $subsub)
? "SELECT * FROM products WHERE category='accessories' AND subcategory='$sub' AND sub_subcategory='$subsub'"
: "SELECT * FROM products WHERE category='accessories'";

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

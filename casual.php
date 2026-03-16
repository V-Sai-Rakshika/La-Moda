<?php include "db.php"; ?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Casual | La Moda</title>
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
<h3>Top</h3>
<a href="?sub=top&subsub=shirt">Shirt</a>
<a href="?sub=top&subsub=t shirt">T-Shirt</a>
<a href="?sub=top&subsub=casual top">Casual Top</a>

<h3>Bottom</h3>
<a href="?sub=bottom&subsub=jeans">Jeans</a>
<a href="?sub=bottom&subsub=trouser">Trouser</a>
<a href="?sub=bottom&subsub=skirt">Skirt</a>
</div>

<div class="product-container">

<?php
$sub=$_GET['sub'] ?? '';
$subsub=$_GET['subsub'] ?? '';

$sql=($sub && $subsub)
? "SELECT * FROM products WHERE category='casual' AND subcategory='$sub' AND sub_subcategory='$subsub'"
: "SELECT * FROM products WHERE category='casual'";

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

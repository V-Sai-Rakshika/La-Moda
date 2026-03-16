<?php include "db.php"; ?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>La Moda | Flash Sale</title>
<link rel="stylesheet" href="styles.css">
</head>

<body>

<nav class="navbar">
<h2 class="logo">La Moda</h2>
<ul>
<li><a href="#">Home</a></li>
<li><a href="traditional.php">Traditional</a></li>
<li><a href="dresses.php">Dresses</a></li>
<li><a href="casual.php">Casual</a></li>
<li><a href="accessories.php">Accessories</a></li>
<li><a href="#contact">contact</a></li>
</ul>
</nav>

<img src="images/banner.png">

<h2>⚡ Today's Flash Deals</h2>

<div class="product-container">
<?php
$sql="SELECT * FROM products WHERE flash_sale='yes'";
$res=mysqli_query($conn,$sql);

while($row=mysqli_fetch_assoc($res)){
?>

<div class="card">
<a href="<?= $row['link'] ?>" target="_blank">

<div class="img-box">
<img src="images/<?= $row['image'] ?>">
</div>

<h3><?= $row['name'] ?></h3>
<p class="new">₹<?= $row['new_price'] ?></p>

</a>
</div>

<?php } ?>
</div>

<h3>Sale ends in:
<span id="timer"></span>
</h3>

<script>
let end=new Date().getTime()+7200000;

setInterval(()=>{
let now=new Date().getTime();
let d=end-now;

let h=Math.floor((d%(1000*60*60*24))/(1000*60*60));
let m=Math.floor((d%(1000*60*60))/(1000*60));
let s=Math.floor((d%(1000*60))/1000);

document.getElementById("timer").innerHTML=
h+"h "+m+"m "+s+"s";
},1000);
</script>

<?php
$categories = ['traditional','dresses','casual','accessories'];

foreach($categories as $cat){
?>
<section id="<?= $cat ?>">
<h2><?= ucfirst($cat) ?> Wear</h2>
<div class="product-container">

<?php
$sql = "SELECT * FROM products WHERE category='$cat'";
$res = mysqli_query($conn,$sql);

while($row=mysqli_fetch_assoc($res)){
?>
<div class="card">
<a href="<?= $row['link'] ?>" target="_blank">
<div class="img-box">
  <img src="images/<?= $row['image'] ?>">
</div>
</a>
<h3><?= $row['name'] ?></h3>
<p class="description"><?= $row['description'] ?></p>
<p class="old">₹<?= $row['old_price'] ?></p>
<p class="new">₹<?= $row['new_price'] ?></p>
</div>
<?php } ?>

</div>
</section>
<?php } ?>

<footer id="contact">
<h1>☆ La Moda ☆</h1>
<p>Wear the Moment</p>
<p>Email: lamoda@email.com</p>
<p>© 2026 La Moda Flash Sale</p>
</footer>

</body>
</html>

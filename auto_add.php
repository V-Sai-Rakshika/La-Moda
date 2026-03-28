<?php include "db.php"; ?>

<h2>Add Product via Link</h2>

<form method="POST">
Product Link:<br>
<input name="link" required><br><br>

Name:<br>
<input name="name"><br><br>

Image URL:<br>
<input name="image"><br><br>

Price:<br>
<input name="price"><br><br>

Category:<br>
<select name="category">
<option>traditional</option>
<option>dresses</option>
<option>casual</option>
<option>accessories</option>
</select><br><br>

<button name="submit">Add</button>
</form>

<?php
if(isset($_POST['submit'])){

$products->insertOne([
    "name" => $_POST['name'],
    "image" => $_POST['image'],
    "new_price" => (int)$_POST['price'],
    "link" => $_POST['link'],
    "category" => $_POST['category'],
    "flash_sale" => "no"
]);

echo "Product Added!";
}
?>
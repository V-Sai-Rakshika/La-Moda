<?php include "db.php"; ?>

<?php
if(isset($_POST['submit'])){

$name=$_POST['name'];
$desc=$_POST['description'];
$old=$_POST['old_price'];
$new=$_POST['new_price'];
$img=$_POST['image'];
$link=$_POST['link'];
$cat=$_POST['category'];
$sub=$_POST['subcategory'];
$subsub=$_POST['sub_subcategory'];
$flash=$_POST['flash_sale'];

$sql="INSERT INTO products
(name,description,old_price,new_price,image,link,category,subcategory,sub_subcategory,flash_sale)
VALUES('$name','$desc','$old','$new','$img','$link','$cat','$sub','$subsub','$flash')";

mysqli_query($conn,$sql);

header("Location:add_product.php?success=1");
exit();
}
?>

<h2>Add Product</h2>

<?php
if(isset($_GET['success'])){
echo "<p style='color:green;'>Product Added!</p>";
}
?>

<form method="POST">

Name:<br>
<input name="name" required><br><br>

Description:<br>
<input name="description" required><br><br>

Old Price:<br>
<input name="old_price" required><br><br>

New Price:<br>
<input name="new_price" required><br><br>

Image Name:<br>
<input name="image" required><br><br>

Product Link:<br>
<input name="link"><br><br>

Category:<br>
<select name="category">
<option>traditional</option>
<option>dresses</option>
<option>casual</option>
<option>accessories</option>
</select><br><br>

Subcategory:<br>
<input name="subcategory"><br><br>

Sub-subcategory:<br>
<input name="sub_subcategory"><br><br>

Flash Sale:<br>
<select name="flash_sale">
<option value="no">No</option>
<option value="yes">Yes</option>
</select><br><br>

<button name="submit">Add Product</button>

</form>

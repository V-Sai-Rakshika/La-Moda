<?php
session_start();


if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}
?>


<!DOCTYPE html>
<html>
<head>
<title>Checkout</title>
<style>
body { font-family: Arial; background:#f5f5f5; }
.form-box {
    width: 300px;
    margin: 100px auto;
    background: white;
    padding: 20px;
    border-radius: 10px;
}
input {
    width: 100%;
    padding: 8px;
    margin: 5px 0;
}
button {
    width: 100%;
    padding: 10px;
    background: green;
    color: white;
    border: none;
}
</style>
</head>


<body>


<div class="form-box">


<h2>Delivery Details</h2>


<form method="POST" action="place_order.php">
<input type="text" name="name" placeholder="Name" required>
<input type="text" name="phone" placeholder="Phone" required>
<input type="text" name="address" placeholder="Address" required>
<input type="text" name="city" placeholder="City" required>
<input type="text" name="country" placeholder="Country" required>


<button type="submit">Place Order</button>
</form>


</div>


</body>
</html>


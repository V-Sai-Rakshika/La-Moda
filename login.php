<?php
session_start();
 
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // FIX: Store as array with 'name' key — index.php expects $_SESSION['user']['name']
    $_SESSION['user'] = [
        'name' => $_POST['name'],
        'username' => $_POST['name']
    ];
    header("Location: index.php");
    exit();
}
?>
 
<!DOCTYPE html>
<html>
<head>
<title>Login – La Moda</title>
<style>
body {
    font-family: Arial, sans-serif;
    background: linear-gradient(to right, #f8f8f8, #fce4ec, #f8f8f8);
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
    margin: 0;
}
.login-box {
    background: white;
    padding: 40px;
    border-radius: 12px;
    box-shadow: 0 8px 20px rgba(0,0,0,0.1);
    width: 280px;
    text-align: center;
}
input {
    width: 100%;
    padding: 10px;
    margin: 8px 0;
    border: 1px solid #ccc;
    border-radius: 6px;
    box-sizing: border-box;
}
button {
    width: 100%;
    padding: 10px;
    background: brown;
    color: white;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    margin-top: 10px;
}
button:hover { background: #7b2c2c; }
a { color: brown; }
</style>
</head>
<body>
 
<div class="login-box">
    <h2>Welcome Back 🤗</h2>
 
    <form method="POST">
        <input type="text" name="name" placeholder="Enter your name" required>
        <button type="submit">Login</button>
    </form>
 
    <p style="margin-top:15px;">
        Don't have an account? 
        <a href="index.php">Sign Up</a>
    </p>
</div>
 
</body>
</html>
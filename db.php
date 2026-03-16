<?php
$conn = new mysqli("localhost", "root", "", "lamoda");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>

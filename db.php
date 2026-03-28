<?php

require_once __DIR__ . '/vendor/autoload.php';
use MongoDB\BSON\UTCDateTime;

$client = new MongoDB\Client("mongodb+srv://La-Moda:cvzSobAymREFy6Id@cluster.gsdccdn.mongodb.net/lamodaDB?retryWrites=true&w=majority");

$db = $client->lamoda;
$products = $db->products;
$users    = $db->users;
$orders   = $db->orders;
$wishlist = $db->wishlist;
$ratings  = $db->ratings;
$visits   = $db->visits;

try {
    $client = new MongoDB\Client("mongodb+srv://La-Moda:cvzSobAymREFy6Id@cluster.gsdccdn.mongodb.net/lamodaDB?retryWrites=true&w=majority");
    $db = $client->lamodaDB;
} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage());
}

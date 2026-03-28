<?php
/**
 * db.php — MongoDB connection
 * Works for BOTH local XAMPP and Render (Atlas)
 *
 * LOCAL (XAMPP):  uses localhost automatically
 * RENDER (Atlas): set MONGODB_URI in Render dashboard → Environment tab
 *
 * Collections: products, users, orders, wishlist, ratings, visits, coupons
 */

require_once __DIR__ . '/vendor/autoload.php';

// Priority: environment variable (Render/Atlas) → localhost fallback (XAMPP)
$mongoUri    = getenv('MONGODB_URI') ?: 'mongodb://localhost:27017';
$mongoDbName = getenv('MONGODB_DB')  ?: 'lamoda';


try {
    $client = new MongoDB\Client($mongoUri, [], [
        // typeMap: return arrays instead of BSON objects — avoids BSONArray serialize issues
        'typeMap' => [
            'array'    => 'array',
            'document' => 'array',
            'root'     => 'array',
        ]
    ]);
    $db = $client->selectDatabase($mongoDbName);
} catch (\Exception $e) {
    http_response_code(500);
    die('<h2 style="font-family:sans-serif;color:#c0392b;padding:40px;">
        Database connection failed. Check MONGODB_URI.<br>
        <small style="color:#666;">' . htmlspecialchars($e->getMessage()) . '</small>
    </h2>');
}

$products = $db->products;
$users    = $db->users;
$orders   = $db->orders;
$wishlist = $db->wishlist;
$ratings  = $db->ratings;
$visits   = $db->visits;

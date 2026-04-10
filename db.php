<?php
require_once __DIR__ . '/vendor/autoload.php';

if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
}
if (!isset($_ENV['MONGODB_URI']) || !isset($_ENV['MONGODB_DB'])) {
    die("MongoDB environment variables not set");
}

$mongoUri    = $_ENV['MONGODB_URI'];
$mongoDbName = $_ENV['MONGODB_DB'];

try {
    $client = new MongoDB\Client($mongoUri, [], [
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
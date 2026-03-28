<?php
/**
 * migrate.php — Run ONCE to copy products from MySQL → MongoDB
 * DELETE THIS FILE immediately after running it.
 *
 * Visit: localhost/LaModa/migrate.php
 */

// ── MongoDB connection ──
require_once __DIR__ . '/vendor/autoload.php';
$mongoClient = new MongoDB\Client("mongodb://localhost:27017");
$db          = $mongoClient->lamoda;
$mongoProd   = $db->products;

// ── MySQL connection — fill in YOUR details ──
$mysqlHost = 'localhost';
$mysqlUser = 'root';        // default XAMPP user
$mysqlPass = '';            // default XAMPP password (empty)
$mysqlDB   = 'lamoda';      // ← YOUR MySQL database name here

$pdo = new PDO(
    "mysql:host=$mysqlHost;dbname=$mysqlDB;charset=utf8",
    $mysqlUser,
    $mysqlPass,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

// ── Change 'products' to your actual MySQL table name if different ──
$mysqlTable = 'products';

$rows = $pdo->query("SELECT * FROM `$mysqlTable`")->fetchAll(PDO::FETCH_ASSOC);

if (empty($rows)) {
    die("❌ No rows found in MySQL table '$mysqlTable'. Check the table name.");
}

echo "<h2>Found " . count($rows) . " products in MySQL</h2>";
echo "<pre style='font-size:12px;background:#f5f5f5;padding:10px;'>";

$inserted  = 0;
$skipped   = 0;
$errors    = [];

foreach ($rows as $row) {
    // Map MySQL columns → MongoDB fields
    // Adjust the column names below to match YOUR MySQL table columns
    $doc = [
        'name'        => (string)($row['name']        ?? $row['product_name'] ?? $row['title'] ?? ''),
        'description' => (string)($row['description'] ?? $row['desc']         ?? ''),
        'category'    => strtolower((string)($row['category']    ?? '')),   // stored lowercase
        'subcategory' => strtolower((string)($row['subcategory'] ?? '')),
        'old_price'   => (int)($row['old_price']  ?? $row['mrp']          ?? 0),
        'new_price'   => (int)($row['new_price']  ?? $row['price']        ?? $row['selling_price'] ?? 0),
        'image'       => (string)($row['image']   ?? $row['image_url']    ?? $row['img'] ?? ''),
        'flash_sale'  => (isset($row['flash_sale']) && ($row['flash_sale'] == 1 || $row['flash_sale'] === 'yes'))
                            ? 'yes' : 'no',
        'link'        => '',   // removed external links
        'avg_rating'  => 0,
        'rating_count'=> 0,
    ];

    if (empty($doc['name'])) {
        $skipped++;
        $errors[] = "Skipped row (no name): " . json_encode($row);
        continue;
    }

    // Skip if already exists in MongoDB
    $existing = $mongoProd->findOne(['name' => $doc['name']]);
    if ($existing) {
        echo "⚠ Already exists: {$doc['name']}\n";
        $skipped++;
        continue;
    }

    $mongoProd->insertOne($doc);
    echo "✅ Inserted: {$doc['name']} | category: {$doc['category']} | price: ₹{$doc['new_price']}\n";
    $inserted++;
}

echo "</pre>";
echo "<h2 style='color:green'>Done! Inserted: $inserted | Skipped: $skipped</h2>";

if (!empty($errors)) {
    echo "<h3>Issues:</h3><ul>";
    foreach ($errors as $e) echo "<li>" . htmlspecialchars($e) . "</li>";
    echo "</ul>";
}

echo "<br><strong style='color:red'>⚠ DELETE this file now! (migrate.php)</strong>";
echo "<br><a href='index.php'>→ Go to Homepage</a>";
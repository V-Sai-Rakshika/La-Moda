<?php
/**
 * recently_viewed.php — AJAX endpoint
 * Returns the last 6 recently viewed products for the current user.
 * Called by product pages to show "Recently Viewed" strip.
 * Already tracked via $_SESSION['recently_viewed'] in product.php.
 */
ob_start();
session_start();
include __DIR__ . "/db.php";
include __DIR__ . "/auth.php";

header('Content-Type: application/json');
ob_end_clean();

$rv = $_SESSION['recently_viewed'] ?? [];
if (empty($rv)) { echo json_encode([]); exit; }

$cursor = $products->find(
    ['name' => ['$in' => $rv]],
    ['projection' => ['name'=>1,'new_price'=>1,'old_price'=>1,'image'=>1,'avg_rating'=>1,'stock'=>1], 'limit'=>6]
);

$result = [];
$map = [];
foreach ($cursor as $p) { $map[(string)$p['name']] = $p; }
// Preserve recently-viewed order
foreach ($rv as $name) {
    if (isset($map[$name])) {
        $p = $map[$name];
        $img = (string)($p['image'] ?? '');
        $result[] = [
            'name'   => (string)$p['name'],
            'price'  => (int)($p['new_price'] ?? 0),
            'old'    => (int)($p['old_price']  ?? 0),
            'image'  => strpos($img,'http')===0 ? $img : "images/$img",
            'rating' => (float)($p['avg_rating'] ?? 0),
            'stock'  => (int)($p['stock'] ?? 99),
        ];
    }
}
echo json_encode($result);
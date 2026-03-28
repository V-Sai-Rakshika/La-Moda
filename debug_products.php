<?php
require_once __DIR__ . '/vendor/autoload.php';
session_start();
include __DIR__ . '/db.php';

echo "<h2>All Products — Subcategory Debug</h2>";
echo "<table border='1' cellpadding='8' style='border-collapse:collapse;font-size:13px;font-family:monospace;'>";
echo "<tr style='background:#eee'>
        <th>Name</th>
        <th>Category</th>
        <th>subcategory</th>
        <th>sub_subcategory</th>
        <th>flash_sale</th>
      </tr>";

$allProducts = $products->find([], ['sort' => ['category' => 1]]);

foreach ($allProducts as $p) {
    $cat    = (string)($p['category']        ?? '—');
    $sub    = (string)($p['subcategory']     ?? '—');
    $subsub = (string)($p['sub_subcategory'] ?? '—');
    $flash  = (string)($p['flash_sale']      ?? '—');
    $name   = (string)($p['name']            ?? '—');

    $bg = match($cat) {
        'casual'      => '#e8f4fd',
        'accessories' => '#fef9e7',
        'traditional' => '#fef0f0',
        'dresses'     => '#f0fff0',
        default       => '#fff',
    };

    echo "<tr style='background:$bg'>
            <td>" . htmlspecialchars($name)   . "</td>
            <td><b>" . htmlspecialchars($cat) . "</b></td>
            <td>" . htmlspecialchars($sub)    . "</td>
            <td>" . htmlspecialchars($subsub) . "</td>
            <td>" . htmlspecialchars($flash)  . "</td>
          </tr>";
}

echo "</table>";
echo "<br><a href='index.php'>← Back to site</a>";
echo "<br><br><b style='color:red'>Delete this file after checking!</b>";
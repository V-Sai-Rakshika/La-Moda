<?php
session_start();
require_once __DIR__ . '/vendor/autoload.php';
include __DIR__ . '/db.php';

$sampleProducts = [
    ['name'=>'Silk Saree', 'description'=>'Beautiful silk saree', 'category'=>'traditional',
     'subcategory'=>'saree', 'old_price'=>2500, 'new_price'=>1999,
     'image'=>'https://placehold.co/400x500/f5e6d3/8B2500?text=Silk+Saree',
     'flash_sale'=>'yes', 'stock'=>10, 'sizes'=>['S','M','L','XL'], 'avg_rating'=>4.5, 'rating_count'=>12],

    ['name'=>'Floral Kurti', 'description'=>'Casual floral kurti', 'category'=>'traditional',
     'subcategory'=>'kurti', 'old_price'=>1200, 'new_price'=>899,
     'image'=>'https://placehold.co/400x500/fce4ec/8B2500?text=Floral+Kurti',
     'flash_sale'=>'no', 'stock'=>25, 'sizes'=>['XS','S','M','L'], 'avg_rating'=>4.2, 'rating_count'=>8],

    ['name'=>'Maxi Dress', 'description'=>'Elegant maxi dress', 'category'=>'dresses',
     'subcategory'=>'maxi', 'old_price'=>3000, 'new_price'=>2199,
     'image'=>'https://placehold.co/400x500/e8f5e9/2e7d32?text=Maxi+Dress',
     'flash_sale'=>'yes', 'stock'=>15, 'sizes'=>['S','M','L','XL','XXL'], 'avg_rating'=>4.7, 'rating_count'=>20],

    ['name'=>'Denim Jacket', 'description'=>'Classic denim jacket', 'category'=>'casual',
     'subcategory'=>'jacket', 'old_price'=>2800, 'new_price'=>1999,
     'image'=>'https://placehold.co/400x500/e3f2fd/1565c0?text=Denim+Jacket',
     'flash_sale'=>'no', 'stock'=>8, 'sizes'=>['S','M','L','XL'], 'avg_rating'=>4.4, 'rating_count'=>15],

    ['name'=>'Casual Jeans', 'description'=>'Comfortable everyday jeans', 'category'=>'casual',
     'subcategory'=>'jeans', 'old_price'=>1800, 'new_price'=>1299,
     'image'=>'https://placehold.co/400x500/ede7f6/4527a0?text=Casual+Jeans',
     'flash_sale'=>'no', 'stock'=>30, 'sizes'=>['28','30','32','34','36'], 'avg_rating'=>4.1, 'rating_count'=>9],

    ['name'=>'Gold Necklace', 'description'=>'Elegant gold-tone necklace', 'category'=>'accessories',
     'subcategory'=>'jewellery', 'old_price'=>1500, 'new_price'=>999,
     'image'=>'https://placehold.co/400x500/fff8e1/f57f17?text=Gold+Necklace',
     'flash_sale'=>'yes', 'stock'=>5, 'sizes'=>[], 'avg_rating'=>4.8, 'rating_count'=>25],

    ['name'=>'Leather Handbag', 'description'=>'Stylish leather handbag', 'category'=>'accessories',
     'subcategory'=>'bag', 'old_price'=>3500, 'new_price'=>2499,
     'image'=>'https://placehold.co/400x500/fbe9e7/bf360c?text=Leather+Bag',
     'flash_sale'=>'no', 'stock'=>12, 'sizes'=>[], 'avg_rating'=>4.6, 'rating_count'=>18],

    ['name'=>'Embroidered Lehenga', 'description'=>'Festive embroidered lehenga', 'category'=>'traditional',
     'subcategory'=>'lehenga', 'old_price'=>8000, 'new_price'=>5999,
     'image'=>'https://placehold.co/400x500/fce4ec/880e4f?text=Lehenga',
     'flash_sale'=>'yes', 'stock'=>3, 'sizes'=>['S','M','L'], 'avg_rating'=>4.9, 'rating_count'=>31],
];

$inserted = 0;
$skipped  = 0;
foreach ($sampleProducts as $p) {
    $existing = $products->findOne(['name' => $p['name']]);
    if (!$existing) {
        $products->insertOne($p);
        $inserted++;
    } else {
        $skipped++;
    }
}

echo '<div style="font-family:sans-serif;padding:40px;max-width:500px;">';
echo '<h2 style="color:#8B2500;">✅ Sample Products Inserted</h2>';
echo "<p>Inserted: <strong>$inserted</strong> products</p>";
echo "<p>Skipped (already exist): <strong>$skipped</strong></p>";
echo '<p style="margin-top:20px;"><a href="index.php" style="background:#8B2500;color:white;padding:10px 20px;border-radius:8px;text-decoration:none;">→ Go to Homepage</a></p>';
echo '<p style="color:#e53935;font-size:12px;margin-top:16px;">⚠ Delete or rename this file now so it cannot be run again.</p>';
echo '</div>';
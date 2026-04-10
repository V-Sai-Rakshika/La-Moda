<?php
/**
 * admin_geo_fix.php
 * PATCH for admin.php — replace the city aggregation query with this.
 * Normalizes city names to Title Case so "Chennai", "chennai", "CHENNAI"
 * all count as the same city.
 *
 * HOW TO USE:
 * In admin.php, find the geo chart aggregation (looks like):
 *   $geoCursor = $orders->aggregate([...group by city...])
 * Replace it with this snippet:
 */

// ── Geo chart: city order counts (case-insensitive) ──
$geoCursor = $orders->aggregate([
    // Only count real orders (exclude pending UPI)
    ['$match' => ['status' => ['$ne' => 'pending_payment']]],

    // Normalize: trim whitespace + title-case the city
    ['$addFields' => [
        'cityNorm' => [
            '$trim' => [
                'input' => [
                    '$reduce' => [
                        'input'       => ['$split' => [['$toLower' => '$city'], ' ']],
                        'initialValue'=> '',
                        'in'          => [
                            '$concat' => [
                                '$$value',
                                ['$cond' => [
                                    ['$eq' => ['$$value', '']],
                                    '',
                                    ' ',
                                ]],
                                ['$toUpper' => ['$substrCP' => ['$$this', 0, 1]]],
                                ['$substrCP' => [
                                    '$$this',
                                    1,
                                    ['$subtract' => [['$strLenCP' => '$$this'], 1]],
                                ]],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ]],

    // Filter out empty cities
    ['$match' => ['cityNorm' => ['$ne' => '']]],

    // Group by normalized city
    ['$group' => [
        '_id'   => '$cityNorm',
        'count' => ['$sum' => 1],
    ]],

    ['$sort'  => ['count' => -1]],
    ['$limit' => 20],
]);

$geoData = [];
foreach ($geoCursor as $row) {
    $city  = (string)($row['_id'] ?? '');
    $count = (int)($row['count']  ?? 0);
    if ($city) $geoData[] = [$city, $count];
}

/* ──────────────────────────────────────────────────────
   In admin.php's JavaScript, the geo chart data is built like:
   geoData = <?= json_encode($geoData) ?>;
   That stays the same — only the PHP aggregation above changes.
─────────────────────────────────────────────────────── */
<?php
/**
 * sorting_helper.php
 * Include in traditional.php, dresses.php, casual.php, accessories.php, index.php
 * to add sort + filter dropdowns without changing the UI.
 *
 * Usage:
 *   include 'sorting_helper.php';
 *   // Then use $sortQuery and $filterQuery to modify your MongoDB query
 *
 * The sort SELECT and filter will be output by renderSortFilter().
 */

$_sortParam   = clean($_GET['sort']   ?? 'featured', 20);
$_filterParam = clean($_GET['filter'] ?? '', 20);
$_search      = clean($_GET['search'] ?? '', 200);

$_allowedSorts = ['featured','newest','price_asc','price_desc','bestsellers','top_rated'];
if (!in_array($_sortParam, $_allowedSorts)) $_sortParam = 'featured';

// Build MongoDB sort
$sortQuery = match($_sortParam) {
    'newest'      => ['_id' => -1],
    'price_asc'   => ['new_price' => 1],
    'price_desc'  => ['new_price' => -1],
    'bestsellers' => ['rating_count' => -1],
    'top_rated'   => ['avg_rating' => -1],
    default       => ['flash_sale' => -1, '_id' => -1], // featured
};

function renderSortFilter(string $extraParams = ''): void {
    global $_sortParam, $_filterParam;
    $sorts = [
        'featured'    => '⭐ Featured',
        'newest'      => '🆕 Newest',
        'price_asc'   => '💰 Price: Low → High',
        'price_desc'  => '💰 Price: High → Low',
        'bestsellers' => '🔥 Best Sellers',
        'top_rated'   => '⭐ Top Rated',
    ];
    echo '<div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px;align-items:center;">';
    echo '<form method="GET" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">';
    // Pass through existing GET params
    foreach ($_GET as $k => $v) {
        if ($k !== 'sort' && $k !== 'filter') {
            echo '<input type="hidden" name="'.htmlspecialchars($k).'" value="'.htmlspecialchars($v).'">';
        }
    }
    echo '<label style="font-size:12px;color:#666;font-weight:600;">Sort by:</label>';
    echo '<select name="sort" onchange="this.form.submit()" style="padding:7px 12px;border:1.5px solid #e0e0e0;border-radius:20px;font-size:12px;font-family:inherit;outline:none;cursor:pointer;">';
    foreach ($sorts as $val => $label) {
        $selected = $val === $_sortParam ? ' selected' : '';
        echo "<option value=\"$val\"$selected>$label</option>";
    }
    echo '</select>';
    echo '</form>';
    echo '</div>';
}
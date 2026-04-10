<?php
session_start();
include __DIR__ . "/db.php";
include __DIR__ . "/auth.php";

$search   = clean($_GET['search'] ?? '', 200);
$wishlist = $_SESSION['wishlist'] ?? [];

if (isset($_GET['delete'])) {
    $del = clean($_GET['delete'], 200);
    $_SESSION['search_history'] = array_values(array_filter(
        $_SESSION['search_history'] ?? [], fn($i) => $i !== $del
    ));
}
if ($search && !in_array($search, $_SESSION['search_history'] ?? [])) {
    $_SESSION['search_history'][] = $search;
}

// Recently viewed
$recentlyViewed = [];
if (!empty($_SESSION['recently_viewed'])) {
    $recentlyViewed = iterator_to_array(
        $products->find(['name' => ['$in' => $_SESSION['recently_viewed']]])
    );
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>La Moda | Fashion Store</title>
<link rel="stylesheet" href="styles.css">
<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>👗</text></svg>">
</head>
<body>

<?php include __DIR__ . "/navbar.php"; ?>

<!-- Search history bar -->
<?php if (!empty($_SESSION['search_history'])): ?>
<div class="search-history-bar" id="searchHistoryBar" style="display:none;">
    <div class="search-history-inner">
        <span class="sh-label">Recent:</span>
        <?php foreach (array_unique(array_reverse($_SESSION['search_history'])) as $item): ?>
        <a href="index.php?search=<?= urlencode($item) ?>" class="sh-chip"><?= htmlspecialchars($item) ?></a>
        <a href="index.php?delete=<?= urlencode($item) ?>" class="sh-delete" title="Remove">✕</a>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<img src="images/banner.png" class="banner-img" alt="La Moda Banner">

<!-- FLASH DEALS -->
 <!--
  FLASH SALE TIMER — paste this ABOVE your flash deals section in index.php
  The timer counts down 24 hours, resets automatically, and persists across page refresh.
-->

<div class="flash-timer-bar" id="flashTimerBar">
  <div class="flash-timer-inner">
    <span class="flash-fire">⚡</span>
    <span class="flash-label">Flash Sale ends in</span>
    <div class="timer-blocks">
      <div class="timer-block"><span id="th">00</span><label>HRS</label></div>
      <div class="timer-sep">:</div>
      <div class="timer-block"><span id="tm">00</span><label>MIN</label></div>
      <div class="timer-sep">:</div>
      <div class="timer-block"><span id="ts">00</span><label>SEC</label></div>
    </div>
    <span class="flash-urgency" id="flashUrgency"></span>
  </div>
</div>

<style>
.flash-timer-bar{
  background:linear-gradient(135deg,#8B2500,#c0392b);
  padding:12px 20px;margin-bottom:0;
}
.flash-timer-inner{
  max-width:1200px;margin:0 auto;
  display:flex;align-items:center;justify-content:center;
  gap:16px;flex-wrap:wrap;
}
.flash-fire{font-size:22px;animation:pulse 1s infinite alternate;}
@keyframes pulse{from{transform:scale(1);}to{transform:scale(1.2);}}
.flash-label{color:rgba(255,255,255,.9);font-size:14px;font-weight:600;}
.timer-blocks{display:flex;align-items:center;gap:6px;}
.timer-block{
  background:rgba(0,0,0,.25);border-radius:8px;
  padding:6px 12px;text-align:center;min-width:54px;
}
.timer-block span{display:block;font-size:22px;font-weight:800;color:#fff;line-height:1;font-family:monospace;}
.timer-block label{display:block;font-size:9px;color:rgba(255,255,255,.7);font-weight:700;letter-spacing:1px;margin-top:2px;}
.timer-sep{font-size:22px;font-weight:800;color:rgba(255,255,255,.6);}
.flash-urgency{color:#fde68a;font-size:12px;font-weight:700;display:none;}

/* Mobile */
@media(max-width:480px){
  .flash-label{display:none;}
  .timer-block span{font-size:18px;}
  .timer-block{padding:5px 8px;min-width:44px;}
}
</style>

<script>
(function() {
  const STORAGE_KEY = 'lamoda_flash_end';
  const DURATION    = 24 * 60 * 60 * 1000; // 24 hours in ms
  const RESET_THRESHOLD = 60 * 60 * 1000;  // Reset when under 1 hour

  function getEndTime() {
    let end = parseInt(localStorage.getItem(STORAGE_KEY) || '0', 10);
    const now = Date.now();
    // Set new end time if not set, expired, or under 1 hour (auto-reset)
    if (!end || now >= end || (end - now) <= RESET_THRESHOLD) {
      end = now + DURATION;
      localStorage.setItem(STORAGE_KEY, end);
    }
    return end;
  }

  function pad(n) { return String(n).padStart(2,'0'); }

  function tick() {
    const endTime = getEndTime();
    const now     = Date.now();
    let diff      = Math.max(0, endTime - now);

    const h = Math.floor(diff / 3600000);       diff %= 3600000;
    const m = Math.floor(diff / 60000);          diff %= 60000;
    const s = Math.floor(diff / 1000);

    document.getElementById('th').textContent = pad(h);
    document.getElementById('tm').textContent = pad(m);
    document.getElementById('ts').textContent = pad(s);

    // Urgency label when under 1 hour
    const urgEl = document.getElementById('flashUrgency');
    if (h === 0 && m < 60) {
      urgEl.style.display = 'inline';
      urgEl.textContent   = m < 10 ? '🔥 Hurry! Ending very soon!' : '🔥 Less than 1 hour left!';
    } else {
      urgEl.style.display = 'none';
    }
  }

  tick(); // Run immediately
  setInterval(tick, 1000); // Update every second
})();
</script>
<h2 class="section-title">Today's Flash Deals</h2>
<p class="section-subtitle">Limited time offers — grab yours now!</p>
<div class="scroll-row-wrap">
<div class="product-scroll">
<?php
$cursor = $search
    ? $products->find(['$or' => [
        ['name'        => ['$regex' => preg_quote($search,'/'), '$options'=>'i']],
        ['description' => ['$regex' => preg_quote($search,'/'), '$options'=>'i']],
      ]])
    : $products->find(['flash_sale' => 'yes']);

$count = 0;
foreach ($cursor as $row) {
    $count++;
    include __DIR__ . "/_product_card.php";
}
if ($count === 0) {
    echo '<p style="padding:20px;color:#999;">No flash deals found.</p>';
}
?>
</div>
</div>

<!-- CATEGORY SECTIONS -->
<?php foreach (['traditional','dresses','casual','accessories'] as $cat): ?>
<section>
    <h2 class="section-title"><?= ucfirst($cat) ?> Wear</h2>
    <div class="scroll-row-wrap">
    <div class="product-scroll">
    <?php
    $catCount = 0;
    foreach ($products->find(['category' => $cat]) as $row) {
        $catCount++;
        include __DIR__ . "/_product_card.php";
    }
    if ($catCount === 0) {
        echo '<p style="padding:20px;color:#999;">No products found.</p>';
    }
    ?>
    </div>
    </div>
</section>
<?php endforeach; ?>

<!-- RECENTLY VIEWED -->
<?php if (!empty($recentlyViewed)): ?>
<section>
    <h2 class="section-title">👁 Recently Viewed</h2>
    <div class="scroll-row-wrap">
    <div class="product-scroll">
    <?php foreach ($recentlyViewed as $row): include __DIR__ . "/_product_card.php"; endforeach; ?>
    </div>
    </div>
</section>
<?php endif; ?>

<footer>
    <h1>☆ La Moda ☆</h1>
    <p>Wear the Moment</p>
</footer>

</body>
</html>
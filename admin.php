<?php
ob_start();
session_start();
include __DIR__ . "/db.php";
include __DIR__ . "/auth.php";

// ── Admin credentials — CHANGE THE PASSWORD ──
// To generate your hash, run this once in php:
//   echo password_hash('your_password_here', PASSWORD_BCRYPT);
// Then paste the result below replacing the example hash.
define('ADMIN_USER', 'admin');
define('ADMIN_PASS', '$2y$10$xbmmQTKbkMfFpV2cMXhuZ.KKaZQNKzfiYrI4F5SxmoLbYz..aSgnO');

if (isset($_POST['admin_login'])) {
    if ($_POST['admin_username'] === ADMIN_USER &&
        password_verify($_POST['admin_password'], ADMIN_PASS)) {
        $_SESSION['admin'] = true;
    } else {
        $loginError = "Invalid username or password";
    }
}

if (isset($_GET['admin_logout'])) {
    unset($_SESSION['admin']);
    header("Location: admin.php");
    exit();
}

// ── Show login page if not authenticated ──
if (!isset($_SESSION['admin'])) {
    ob_end_clean();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Login | La Moda</title>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@700&family=Jost:wght@400;500&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{display:flex;align-items:center;justify-content:center;min-height:100vh;background:#1a1a2e;font-family:'Jost',sans-serif;}
.box{background:#fff;padding:48px 40px;border-radius:20px;width:340px;text-align:center;box-shadow:0 24px 64px rgba(0,0,0,0.4);}
.box h2{font-family:'Cormorant Garamond',serif;font-size:28px;margin-bottom:4px;color:#1e1e1e;}
.box p{color:#999;font-size:13px;margin-bottom:28px;}
.box input{width:100%;padding:11px 14px;margin:6px 0;border:1.5px solid #e0e0e0;border-radius:8px;font-size:14px;outline:none;font-family:'Jost',sans-serif;}
.box input:focus{border-color:#8B2500;}
.box button{width:100%;padding:13px;background:#8B2500;color:#fff;border:none;border-radius:8px;font-size:15px;font-weight:600;margin-top:14px;cursor:pointer;font-family:'Jost',sans-serif;}
.box button:hover{background:#5c1800;}
.err{color:#c0392b;font-size:13px;margin-top:10px;background:#fff0f0;padding:8px;border-radius:6px;}
</style>
</head>
<body>
<div class="box">
    <h2>La Moda</h2>
    <p>Admin Dashboard — Restricted Access</p>
    <?php if (!empty($loginError)): ?>
    <p class="err"><?= htmlspecialchars($loginError) ?></p>
    <?php endif; ?>
    <form method="POST">
        <input type="text"     name="admin_username" placeholder="Admin Username" required autocomplete="off">
        <input type="password" name="admin_password" placeholder="Password"       required>
        <input type="hidden"   name="admin_login"    value="1">
        <button type="submit">Login →</button>
    </form>
</div>
</body>
</html>
<?php
    exit();
}

// ── Fetch stats ──

$totalUsers   = $users->countDocuments([]);
$totalOrders  = $orders->countDocuments([]);
$totalVisits  = $visits->countDocuments([]);

// Total revenue
$revResult    = iterator_to_array($orders->aggregate([
    ['$group' => ['_id' => null, 'total' => ['$sum' => '$item_price']]]
]));
$totalRevenue = !empty($revResult) ? (int)$revResult[0]['total'] : 0;

// Orders per day — last 14 days
// Use milliseconds timestamp directly — MongoDB accepts this in aggregation
$since14ms    = (time() - 14 * 86400) * 1000;
$ordersPerDay = [];
foreach ($orders->aggregate([
    ['$match' => ['placed_at' => ['$gte' => ['$date' => ['$numberLong' => (string)$since14ms]]]]],
    ['$group' => ['_id' => ['$dateToString' => ['format' => '%Y-%m-%d', 'date' => '$placed_at']], 'count' => ['$sum' => 1]]],
    ['$sort'  => ['_id' => 1]],
]) as $row) {
    $ordersPerDay[(string)$row['_id']] = (int)$row['count'];
}

// Category sales
$catSales = [];
foreach ($orders->aggregate([
    ['$lookup' => ['from' => 'products', 'localField' => 'item_name', 'foreignField' => 'name', 'as' => 'prod']],
    ['$unwind' => ['path' => '$prod', 'preserveNullAndEmptyArrays' => true]],
    ['$group'  => ['_id' => ['$ifNull' => ['$prod.category', 'Other']], 'count' => ['$sum' => 1]]],
    ['$sort'   => ['count' => -1]],
]) as $row) {
    $catSales[(string)$row['_id']] = (int)$row['count'];
}

// Page visits breakdown
$pageVisits = [];
foreach ($visits->aggregate([
    ['$group' => ['_id' => '$page', 'count' => ['$sum' => 1]]],
    ['$sort'  => ['count' => -1]],
    ['$limit' => 8],
]) as $row) {
    $pageVisits[(string)$row['_id']] = (int)$row['count'];
}

// Most wishlisted products
$topWished = iterator_to_array($wishlist->aggregate([
    ['$group' => ['_id' => '$product_name', 'count' => ['$sum' => 1]]],
    ['$sort'  => ['count' => -1]],
    ['$limit' => 5],
]));

// Recent orders
$recentOrders = iterator_to_array(
    $orders->find([], ['sort' => ['placed_at' => -1], 'limit' => 10])
);

// Recent signups
$recentUsers = iterator_to_array(
    $users->find([], ['sort' => ['created_at' => -1], 'limit' => 10,
                      'projection' => ['password' => 0]])
);

// Helper: format MongoDB date safely — works with any date type MongoDB returns
function fmtDate($dt): string {
    if (!$dt) return '—';
    try {
        // MongoDB PHP library returns UTCDateTime objects
        // Call toDateTime() if the method exists (it will on UTCDateTime)
        if (is_object($dt) && method_exists($dt, 'toDateTime')) {
            return $dt->toDateTime()->format('d M Y');
        }
        // Fallback: cast to string gives milliseconds
        $ms = (string)$dt;
        if (is_numeric($ms)) {
            return date('d M Y', (int)$ms / 1000);
        }
        return '—';
    } catch (\Throwable $e) {
        return '—';
    }
}


// ── Additional stats for all sections ──
$totalProducts = $products->countDocuments([]);
$flashCount    = $products->countDocuments(['flash_sale' => 'yes']);
$wishCount     = $wishlist->countDocuments([]);

// Gender distribution
$genderData = [];
foreach ($users->aggregate([
    ['$group' => ['_id' => '$gender', 'count' => ['$sum' => 1]]]
]) as $row) {
    $genderData[(string)($row['_id'] ?: 'Not specified')] = (int)$row['count'];
}

// Orders by status
$placed    = $orders->countDocuments(['status' => 'placed']);
$shipped   = $orders->countDocuments(['status' => 'shipped']);
$transit   = $orders->countDocuments(['status' => 'in transit']);
$delivered = $orders->countDocuments(['status' => 'delivered']);

// Top 5 products by order count
$topProducts = iterator_to_array($orders->aggregate([
    ['$group' => ['_id' => '$item_name', 'count' => ['$sum' => 1], 'revenue' => ['$sum' => '$item_price']]],
    ['$sort'  => ['count' => -1]],
    ['$limit' => 5],
]));

// Weekly sales (last 7 days)
$weeklySales = [];
for ($i = 6; $i >= 0; $i--) {
    $day   = date('Y-m-d', strtotime("-$i days"));
    $weeklySales[$day] = $orders->countDocuments([
        'placed_at' => ['$regex' => '^' . $day]
    ]);
}

// All orders for orders page
$allOrders = iterator_to_array(
    $orders->find([], ['sort' => ['placed_at' => -1], 'limit' => 50])
);

// All customers
$allCustomers = iterator_to_array(
    $users->find([], ['sort' => ['created_at' => -1], 'projection' => ['password' => 0]])
);

// All products with details
$allProducts = iterator_to_array(
    $products->find([], ['sort' => ['category' => 1, 'name' => 1]])
);


// City/orders geo data
$cityOrders = [];
foreach ($orders->aggregate([
    ['$group' => ['_id' => '$city', 'count' => ['$sum' => 1]]],
    ['$sort'  => ['count' => -1]],
    ['$limit' => 12],
]) as $row) {
    if ($row['_id']) $cityOrders[(string)$row['_id']] = (int)$row['count'];
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard | La Moda</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<style>
/* ═══════ RESET & BASE ═══════ */
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
:root{
  --brand:#7c3d12;--brand-light:#fef3ec;--brand-mid:#d4642a;
  --text:#18181b;--text-mid:#52525b;--text-light:#a1a1aa;
  --border:#e4e4e7;--bg:#f8f8f7;--white:#ffffff;
  --green:#16a34a;--blue:#2563eb;--amber:#d97706;--purple:#7c3aed;
  --r:10px;--rs:6px;
  --shadow:0 1px 3px rgba(0,0,0,0.08),0 1px 2px rgba(0,0,0,0.05);
  --shadow-md:0 4px 12px rgba(0,0,0,0.08);
}
html{scroll-behavior:smooth;}
body{font-family:'Inter',sans-serif;background:var(--bg);color:var(--text);font-size:13px;line-height:1.5;}
a{text-decoration:none;color:inherit;}
button{cursor:pointer;font-family:inherit;}

/* ═══════ LAYOUT ═══════ */
.shell{display:flex;min-height:100vh;}

/* ── Sidebar ── */
.sidebar{
  width:200px;flex-shrink:0;
  background:var(--white);
  border-right:1px solid var(--border);
  display:flex;flex-direction:column;
  position:sticky;top:0;height:100vh;
  transform:translateX(0);
  transition:transform .28s cubic-bezier(.4,0,.2,1);
  z-index:200;
}
.sidebar.collapsed{transform:translateX(-200px);position:fixed;}
.sb-logo{
  padding:18px 16px 14px;
  border-bottom:1px solid var(--border);
  display:flex;align-items:center;gap:8px;
}
.sb-logo-icon{
  width:30px;height:30px;
  background:linear-gradient(135deg,var(--brand),var(--brand-mid));
  border-radius:8px;
  display:flex;align-items:center;justify-content:center;
  color:white;font-size:14px;font-weight:700;
  flex-shrink:0;
}
.sb-logo-text{font-weight:700;font-size:14px;letter-spacing:.3px;}
.sb-nav{flex:1;padding:10px 8px;overflow-y:auto;}
.sb-section{font-size:10px;font-weight:600;color:var(--text-light);letter-spacing:.8px;text-transform:uppercase;padding:10px 8px 4px;}
.sb-item{
  display:flex;align-items:center;gap:8px;
  padding:7px 10px;border-radius:var(--rs);
  font-size:12px;font-weight:500;color:var(--text-mid);
  transition:all .15s;margin-bottom:1px;
  border:none;background:none;width:100%;text-align:left;
}
.sb-item:hover{background:var(--brand-light);color:var(--brand);}
.sb-item.active{background:var(--brand-light);color:var(--brand);font-weight:600;}
.sb-item .si{font-size:14px;width:18px;text-align:center;flex-shrink:0;}
.sb-bottom{padding:10px 8px 14px;border-top:1px solid var(--border);}
.sb-user{
  display:flex;align-items:center;gap:8px;
  padding:8px 10px;border-radius:var(--rs);
  background:var(--bg);margin-bottom:6px;
}
.sb-avatar{
  width:28px;height:28px;border-radius:50%;
  background:linear-gradient(135deg,var(--brand),var(--brand-mid));
  display:flex;align-items:center;justify-content:center;
  color:white;font-size:11px;font-weight:700;flex-shrink:0;
}
.sb-uname{font-size:11px;font-weight:600;}
.sb-urole{font-size:10px;color:var(--text-light);}
.sb-logout{
  display:flex;align-items:center;gap:8px;
  padding:7px 10px;border-radius:var(--rs);
  font-size:12px;color:var(--text-light);
  transition:all .15s;border:none;background:none;width:100%;text-align:left;
}
.sb-logout:hover{background:#fef2f2;color:#dc2626;}

/* ── Main ── */
.main{flex:1;min-width:0;display:flex;flex-direction:column;}

/* ── Topbar ── */
.topbar{
  display:flex;align-items:center;justify-content:space-between;
  padding:0 24px;height:52px;
  background:var(--white);
  border-bottom:1px solid var(--border);
  position:sticky;top:0;z-index:100;
}
.tb-left{display:flex;align-items:center;gap:10px;}
.tb-hamburger{
  background:none;border:none;padding:5px;
  border-radius:var(--rs);font-size:16px;
  color:var(--text-mid);transition:background .15s;
}
.tb-hamburger:hover{background:var(--bg);}
.tb-title{font-size:14px;font-weight:600;color:var(--text);}
.tb-right{display:flex;align-items:center;gap:10px;}
.tb-date{font-size:11px;color:var(--text-light);}
.tb-badge{
  padding:4px 10px;border-radius:20px;
  font-size:11px;font-weight:600;
  background:var(--brand-light);color:var(--brand);
}
.tb-store{
  padding:5px 12px;border:1px solid var(--border);
  border-radius:var(--rs);font-size:11px;font-weight:500;
  color:var(--text-mid);background:var(--white);
  transition:all .15s;
}
.tb-store:hover{border-color:var(--brand);color:var(--brand);}

/* ── Content ── */
.content{padding:20px 24px;flex:1;}

/* ═══════ SECTION HEADER ═══════ */
.section-hd{margin-bottom:16px;}
.section-hd h1{
  font-family:'Playfair Display',serif;
  font-size:20px;font-weight:700;
  color:var(--text);
}
.section-hd p{font-size:12px;color:var(--text-light);margin-top:2px;}

/* ═══════ KPI ROW ═══════ */
.kpi-row{
  display:grid;
  grid-template-columns:repeat(6,1fr);
  gap:10px;margin-bottom:16px;
}
.kpi{
  background:var(--white);
  border:1px solid var(--border);
  border-radius:var(--r);
  padding:14px 14px 12px;
  transition:box-shadow .2s,transform .2s;
  position:relative;overflow:hidden;
}
.kpi::before{
  content:'';position:absolute;
  top:0;left:0;right:0;height:2px;
  border-radius:var(--r) var(--r) 0 0;
}
.kpi:nth-child(1)::before{background:var(--brand-mid);}
.kpi:nth-child(2)::before{background:var(--blue);}
.kpi:nth-child(3)::before{background:var(--green);}
.kpi:nth-child(4)::before{background:var(--amber);}
.kpi:nth-child(5)::before{background:var(--purple);}
.kpi:nth-child(6)::before{background:#0891b2;}
.kpi:hover{box-shadow:var(--shadow-md);transform:translateY(-1px);}
.kpi-top{display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;}
.kpi-lbl{font-size:10px;font-weight:600;color:var(--text-light);text-transform:uppercase;letter-spacing:.4px;}
.kpi-ico{font-size:16px;}
.kpi-val{font-size:20px;font-weight:700;color:var(--text);line-height:1;margin-bottom:4px;}
.kpi-sub{font-size:10px;color:var(--text-light);}
.kpi-tag{
  display:inline-block;font-size:10px;font-weight:600;
  padding:1px 6px;border-radius:20px;margin-top:3px;
}
.tag-up{background:#f0fdf4;color:#16a34a;}
.tag-neu{background:#f4f4f5;color:#71717a;}

/* ═══════ CARD ═══════ */
.card{
  background:var(--white);
  border:1px solid var(--border);
  border-radius:var(--r);
  padding:16px;
  box-shadow:var(--shadow);
}
.card-hd{
  display:flex;align-items:flex-start;
  justify-content:space-between;
  margin-bottom:14px;
}
.card-hd h3{font-size:12px;font-weight:600;color:var(--text);}
.card-hd p{font-size:10px;color:var(--text-light);margin-top:1px;}
.card-badge{
  padding:3px 8px;border-radius:20px;
  font-size:10px;font-weight:600;
  background:var(--brand-light);color:var(--brand);
  white-space:nowrap;
}

/* ═══════ GRID LAYOUTS ═══════ */
.grid-2{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;}
.grid-3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;margin-bottom:12px;}
.grid-32{display:grid;grid-template-columns:2fr 1fr;gap:12px;margin-bottom:12px;}
.grid-23{display:grid;grid-template-columns:1fr 2fr;gap:12px;margin-bottom:12px;}
.full{margin-bottom:12px;}

/* ═══════ TOP PRODUCTS ═══════ */
.top-prod-row{display:flex;gap:8px;margin-bottom:12px;}
.top-prod{
  flex:1;
  background:var(--white);
  border:1px solid var(--border);
  border-radius:var(--r);
  padding:12px 10px;
  text-align:center;
  transition:box-shadow .2s;
}
.top-prod:hover{box-shadow:var(--shadow-md);}
.tp-medal{font-size:18px;margin-bottom:4px;}
.tp-name{font-size:11px;font-weight:600;color:var(--text);line-height:1.3;margin-bottom:4px;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;line-clamp:2;-webkit-box-orient:vertical;}
.tp-count{font-size:18px;font-weight:700;color:var(--brand);}
.tp-lbl{font-size:10px;color:var(--text-light);}

/* ═══════ GEO MAP ═══════ */
.geo-wrap{position:relative;background:var(--white);border:1px solid var(--border);border-radius:var(--r);padding:16px;margin-bottom:12px;}
.geo-map{
  width:100%;height:220px;
  background:linear-gradient(135deg,#e8f4fd 0%,#dbeafe 100%);
  border-radius:var(--rs);
  position:relative;overflow:hidden;
}
.geo-svg{width:100%;height:100%;}
.geo-dot{
  position:absolute;
  border-radius:50%;
  background:var(--brand-mid);
  opacity:.8;
  transform:translate(-50%,-50%);
  animation:pulse 2s infinite;
}
@keyframes pulse{
  0%,100%{transform:translate(-50%,-50%) scale(1);}
  50%{transform:translate(-50%,-50%) scale(1.4);}
}
.geo-legend{margin-top:10px;}
.geo-legend-row{display:flex;flex-wrap:wrap;gap:6px;}
.geo-chip{
  display:flex;align-items:center;gap:5px;
  padding:3px 8px;background:var(--bg);
  border-radius:20px;font-size:10px;
  border:1px solid var(--border);
}
.geo-chip-dot{width:8px;height:8px;border-radius:50%;background:var(--brand-mid);}

/* ═══════ TABLE ═══════ */
.tbl-wrap{overflow-x:auto;}
.dtbl{width:100%;border-collapse:collapse;}
.dtbl th{
  padding:8px 12px;text-align:left;
  font-size:10px;font-weight:700;color:var(--text-light);
  text-transform:uppercase;letter-spacing:.4px;
  background:var(--bg);border-bottom:1px solid var(--border);
}
.dtbl td{
  padding:9px 12px;font-size:12px;color:var(--text-mid);
  border-bottom:1px solid #f4f4f5;vertical-align:middle;
}
.dtbl tr:last-child td{border-bottom:none;}
.dtbl tr:hover td{background:var(--bg);}
.dt-avatar{
  width:26px;height:26px;border-radius:50%;
  background:linear-gradient(135deg,var(--brand),var(--brand-mid));
  display:inline-flex;align-items:center;justify-content:center;
  font-size:10px;font-weight:700;color:white;
  margin-right:6px;vertical-align:middle;
}
.dt-bold{font-weight:600;color:var(--text);}

/* ═══════ PILLS ═══════ */
.pill{
  display:inline-block;padding:2px 8px;
  border-radius:20px;font-size:10px;font-weight:600;
  text-transform:capitalize;
}
.p-placed   {background:#eff6ff;color:#2563eb;}
.p-shipped  {background:#fefce8;color:#ca8a04;}
.p-transit  {background:#faf5ff;color:#7c3aed;}
.p-delivered{background:#f0fdf4;color:#16a34a;}
.p-cod      {background:#f4f4f5;color:#71717a;}
.p-upi      {background:#eff6ff;color:#2563eb;}
.p-card     {background:#fdf4ff;color:#a21caf;}
.p-emi      {background:#f0fdf4;color:#16a34a;}
.p-bank     {background:#ecfeff;color:#0891b2;}
.p-trad     {background:#fef3ec;color:var(--brand);}
.p-dress    {background:#f0fdf4;color:#16a34a;}
.p-casual   {background:#eff6ff;color:#2563eb;}
.p-access   {background:#fefce8;color:#ca8a04;}

/* ═══════ SPARKLINES ═══════ */
.spark-row{display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-bottom:12px;}
.spark{
  background:var(--white);border:1px solid var(--border);
  border-radius:var(--r);padding:12px;
}
.spark-val{font-size:18px;font-weight:700;color:var(--text);margin-bottom:2px;}
.spark-lbl{font-size:10px;color:var(--text-light);margin-bottom:6px;}
</style>
</head>
<body>
<div class="shell">

<!-- ══════ SIDEBAR ══════ -->
<aside class="sidebar" id="sidebar">
  <div class="sb-logo">
    <div class="sb-logo-icon">LM</div>
    <span class="sb-logo-text">La Moda</span>
  </div>
  <nav class="sb-nav">
    <p class="sb-section">Menu</p>
    <button class="sb-item active" onclick="navTo('sec-kpi')"><span class="si">📊</span> Dashboard</button>
    <button class="sb-item" onclick="navTo('sec-sales')"><span class="si">💰</span> Sales</button>
    <button class="sb-item" onclick="navTo('sec-products')"><span class="si">📦</span> Products</button>
    <button class="sb-item" onclick="navTo('sec-orders')"><span class="si">🛍️</span> Orders</button>
    <button class="sb-item" onclick="navTo('sec-customers')"><span class="si">👥</span> Customers</button>
    <button class="sb-item" onclick="navTo('sec-analytics')"><span class="si">📈</span> Analytics</button>
    <p class="sb-section">Admin</p>
    <a href="manage_products.php" class="sb-item"><span class="si">✏️</span> Manage Products</a>
    <a href="index.php" target="_blank" class="sb-item"><span class="si">🌐</span> View Store</a>
  </nav>
  <div class="sb-bottom">
    <div class="sb-user">
      <div class="sb-avatar">A</div>
      <div><div class="sb-uname">Admin</div><div class="sb-urole">Super Admin</div></div>
    </div>
    <a href="admin.php?admin_logout=1">
      <button class="sb-logout"><span>🚪</span> Logout</button>
    </a>
  </div>
</aside>

<!-- ══════ MAIN ══════ -->
<div class="main" id="mainArea">

  <!-- Topbar -->
  <header class="topbar">
    <div class="tb-left">
      <button class="tb-hamburger" id="hamburgerBtn">☰</button>
      <span class="tb-title">Dashboard</span>
    </div>
    <div class="tb-right">
      <span class="tb-date"><?= date('D, d M Y') ?></span>
      <span class="tb-badge">● Live</span>
      <a href="index.php" target="_blank"><button class="tb-store">View Store →</button></a>
    </div>
  </header>

  <div class="content">

    <!-- ═══ KPI ROW ═══ -->
    <div id="sec-kpi" class="section-hd">
      <h1>Overview</h1>
      <p>All key metrics at a glance</p>
    </div>

    <div class="kpi-row">
      <div class="kpi">
        <div class="kpi-top"><span class="kpi-lbl">Revenue</span><span class="kpi-ico">💰</span></div>
        <div class="kpi-val">₹<?= number_format($totalRevenue) ?></div>
        <div class="kpi-sub">Total earnings</div>
        <span class="kpi-tag tag-up">↑ All time</span>
      </div>
      <div class="kpi">
        <div class="kpi-top"><span class="kpi-lbl">Orders</span><span class="kpi-ico">🛍️</span></div>
        <div class="kpi-val"><?= number_format($totalOrders) ?></div>
        <div class="kpi-sub">Total placed</div>
        <span class="kpi-tag tag-up">↑ Growing</span>
      </div>
      <div class="kpi">
        <div class="kpi-top"><span class="kpi-lbl">Customers</span><span class="kpi-ico">👥</span></div>
        <div class="kpi-val"><?= number_format($totalUsers) ?></div>
        <div class="kpi-sub">Registered users</div>
        <span class="kpi-tag tag-neu">Total</span>
      </div>
      <div class="kpi">
        <div class="kpi-top"><span class="kpi-lbl">Products</span><span class="kpi-ico">📦</span></div>
        <div class="kpi-val"><?= number_format($totalProducts) ?></div>
        <div class="kpi-sub">In catalogue</div>
        <span class="kpi-tag tag-neu">Active</span>
      </div>
      <div class="kpi">
        <div class="kpi-top"><span class="kpi-lbl">Flash Sales</span><span class="kpi-ico">⚡</span></div>
        <div class="kpi-val"><?= number_format($flashCount) ?></div>
        <div class="kpi-sub">Active deals</div>
        <span class="kpi-tag tag-up">Live</span>
      </div>
      <div class="kpi">
        <div class="kpi-top"><span class="kpi-lbl">Page Visits</span><span class="kpi-ico">👁️</span></div>
        <div class="kpi-val"><?= number_format($totalVisits) ?></div>
        <div class="kpi-sub">All time views</div>
        <span class="kpi-tag tag-up">↑ Rising</span>
      </div>
    </div>

    <!-- ═══ SALES ROW 1: Category pie + Weekly line ═══ -->
    <div id="sec-sales" class="grid-2">
      <div class="card">
        <div class="card-hd">
          <div><h3>Sales by Category</h3><p>Order distribution</p></div>
          <span class="card-badge">All time</span>
        </div>
        <canvas id="catChart" height="160"></canvas>
      </div>
      <div class="card">
        <div class="card-hd">
          <div><h3>Weekly Sales</h3><p>Orders last 7 days</p></div>
          <span class="card-badge">7 days</span>
        </div>
        <canvas id="weeklyChart" height="160"></canvas>
      </div>
    </div>

    <!-- ═══ TOP PRODUCTS ═══ -->
    <div class="section-hd" style="margin-top:4px;">
      <h1>Highest Selling Products</h1>
    </div>
    <div class="top-prod-row">
      <?php
      $medals = ['🥇','🥈','🥉','4️⃣','5️⃣'];
      if (empty($topProducts)):
      ?>
      <div class="top-prod" style="flex:1;grid-column:span 5;">
        <p style="color:var(--text-light);font-size:12px;padding:20px;">No sales data yet</p>
      </div>
      <?php else: ?>
      <?php foreach ($topProducts as $ti => $tp): ?>
      <div class="top-prod">
        <div class="tp-medal"><?= $medals[$ti] ?? ($ti+1) ?></div>
        <div class="tp-name"><?= htmlspecialchars((string)($tp['_id'] ?? 'Unknown')) ?></div>
        <div class="tp-count"><?= (int)($tp['count'] ?? 0) ?></div>
        <div class="tp-lbl">orders</div>
      </div>
      <?php endforeach; endif; ?>
    </div>

    <!-- ═══ WATERFALL + TRANSACTION KPI ═══ -->
    <div class="grid-2">
      <div class="card">
        <div class="card-hd">
          <div><h3>Waterfall — Sales Trend</h3><p>Cumulative order growth</p></div>
        </div>
        <canvas id="waterfallChart" height="150"></canvas>
      </div>
      <div class="card">
        <div class="card-hd">
          <div><h3>Transaction KPI</h3><p>Weekly order counts</p></div>
          <span class="card-badge">This week</span>
        </div>
        <canvas id="transactChart" height="150"></canvas>
      </div>
    </div>

    <!-- ═══ GEO CHART ═══ -->
    <div class="geo-wrap">
      <div class="card-hd" style="margin-bottom:10px;">
        <div><h3 style="font-size:13px;font-weight:600;">📍 Order Geo Distribution</h3><p style="font-size:10px;color:var(--text-light);">Cities with most orders highlighted</p></div>
      </div>
      <div class="geo-map" id="geoMap">
        <!-- World map SVG simplified outline -->
        <svg class="geo-svg" viewBox="0 0 900 450" xmlns="http://www.w3.org/2000/svg">
          <rect width="900" height="450" fill="#dbeafe" rx="8"/>
          <!-- Simplified continent shapes -->
          <!-- North America -->
          <path d="M80,60 L180,50 L220,80 L200,120 L240,160 L200,200 L170,220 L140,200 L120,170 L80,160 L60,120 Z" fill="#bfdbfe" stroke="#93c5fd" stroke-width="1.5"/>
          <!-- South America -->
          <path d="M170,230 L220,220 L250,250 L260,310 L240,370 L200,380 L170,350 L155,300 L160,260 Z" fill="#bfdbfe" stroke="#93c5fd" stroke-width="1.5"/>
          <!-- Europe -->
          <path d="M400,50 L460,45 L490,65 L480,95 L450,110 L420,105 L395,85 Z" fill="#bfdbfe" stroke="#93c5fd" stroke-width="1.5"/>
          <!-- Africa -->
          <path d="M400,130 L460,120 L490,150 L500,220 L480,290 L450,320 L410,315 L390,280 L380,220 L385,160 Z" fill="#bfdbfe" stroke="#93c5fd" stroke-width="1.5"/>
          <!-- Asia (includes India) -->
          <path d="M490,50 L650,40 L720,70 L740,110 L720,150 L680,160 L640,140 L600,160 L570,200 L540,190 L520,150 L490,130 L475,90 Z" fill="#bfdbfe" stroke="#93c5fd" stroke-width="1.5"/>
          <!-- India highlight -->
          <path d="M580,150 L620,145 L640,165 L635,210 L610,230 L585,220 L570,195 Z" fill="#fcd34d" stroke="#f59e0b" stroke-width="1.5" opacity=".7"/>
          <!-- Australia -->
          <path d="M660,240 L740,230 L780,260 L775,310 L740,330 L690,325 L660,295 Z" fill="#bfdbfe" stroke="#93c5fd" stroke-width="1.5"/>
          <!-- India label -->
          <text x="605" y="205" font-size="9" fill="#92400e" font-weight="600" font-family="Inter,sans-serif">India</text>
        </svg>
      </div>
      <!-- Dots added by JS -->
      <div class="geo-legend" style="margin-top:10px;">
        <div class="geo-legend-row">
          <?php foreach (array_slice($cityOrders,0,8,true) as $city => $cnt): ?>
          <div class="geo-chip">
            <div class="geo-chip-dot" style="opacity:<?= min(1, 0.4 + ($cnt / max(array_values($cityOrders)+[1])) * 0.6) ?>;"></div>
            <span><?= htmlspecialchars($city) ?> (<?= $cnt ?>)</span>
          </div>
          <?php endforeach; ?>
          <?php if (empty($cityOrders)): ?><span style="font-size:11px;color:var(--text-light);">No order location data yet</span><?php endif; ?>
        </div>
      </div>
    </div>

    <!-- ═══ SPARKLINES ═══ -->
    <div class="spark-row">
      <div class="spark">
        <div class="spark-lbl">Revenue trend</div>
        <div class="spark-val">₹<?= number_format($totalRevenue) ?></div>
        <canvas id="spark1" height="36"></canvas>
      </div>
      <div class="spark">
        <div class="spark-lbl">Orders this week</div>
        <div class="spark-val"><?= array_sum(array_values($weeklySales)) ?></div>
        <canvas id="spark2" height="36"></canvas>
      </div>
      <div class="spark">
        <div class="spark-lbl">Avg order value</div>
        <div class="spark-val">₹<?= $totalOrders > 0 ? number_format($totalRevenue/$totalOrders,0) : 0 ?></div>
        <canvas id="spark3" height="36"></canvas>
      </div>
      <div class="spark">
        <div class="spark-lbl">Wishlist saves</div>
        <div class="spark-val"><?= $wishCount ?></div>
        <canvas id="spark4" height="36"></canvas>
      </div>
    </div>

    <!-- ═══ PRODUCTS ═══ -->
    <div id="sec-products" class="section-hd" style="margin-top:8px;">
      <h1>Products</h1><p>Full catalogue overview</p>
    </div>
    <div class="card full">
      <div class="card-hd">
        <div><h3>All Products (<?= count($allProducts) ?>)</h3></div>
        <a href="manage_products.php" style="font-size:11px;font-weight:600;color:var(--brand);">+ Add / Edit →</a>
      </div>
      <div class="tbl-wrap">
        <table class="dtbl">
          <thead><tr><th>Product</th><th>Category</th><th>Subcategory</th><th>Price</th><th>Stock</th><th>Flash</th></tr></thead>
          <tbody>
          <?php if (empty($allProducts)): ?>
          <tr><td colspan="6" style="text-align:center;padding:24px;color:var(--text-light);">No products yet</td></tr>
          <?php else: ?>
          <?php foreach ($allProducts as $p):
            $pCat = strtolower((string)($p['category']??''));
            $pStock = isset($p['stock']) ? (int)$p['stock'] : null;
          ?>
          <tr>
            <td><span class="dt-bold"><?= htmlspecialchars((string)($p['name']??'')) ?></span></td>
            <td><span class="pill p-<?= htmlspecialchars(substr($pCat,0,6)) ?>"><?= htmlspecialchars(ucfirst($pCat)) ?></span></td>
            <td style="color:var(--text-light);"><?= htmlspecialchars((string)($p['subcategory']??'—')) ?></td>
            <td class="dt-bold">₹<?= (int)($p['new_price']??0) ?></td>
            <td><?php
              if ($pStock===null) echo '<span style="color:var(--text-light);">—</span>';
              elseif ($pStock===0) echo '<span style="color:#dc2626;font-weight:700;">OUT</span>';
              elseif ($pStock<=5)  echo '<span style="color:#d97706;font-weight:600;">'.$pStock.' left</span>';
              else echo '<span style="color:#16a34a;">'.$pStock.'</span>';
            ?></td>
            <td><?= ($p['flash_sale']??'') === 'yes' ? '⚡' : '—' ?></td>
          </tr>
          <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- ═══ ORDERS ═══ -->
    <div id="sec-orders" class="section-hd" style="margin-top:8px;">
      <h1>Recent Orders</h1><p>Latest 15 orders</p>
    </div>
    <div class="card full">
      <div class="tbl-wrap">
        <table class="dtbl">
          <thead><tr><th>Customer</th><th>Product</th><th>Amount</th><th>Payment</th><th>City</th><th>Date</th><th>Status</th></tr></thead>
          <tbody>
          <?php if (empty($recentOrders)): ?>
          <tr><td colspan="7" style="text-align:center;padding:24px;color:var(--text-light);">No orders yet</td></tr>
          <?php else: ?>
          <?php foreach (array_slice($recentOrders,0,15) as $o):
            $cn  = htmlspecialchars((string)($o['full_name']??$o['username']??'Customer'));
            $st  = strtolower((string)($o['status']??'placed'));
            $pay = strtolower((string)($o['payment_method']??'cod'));
            $iname = (string)($o['item_name']??'—');
            if ($iname==='__cart__') $iname='Cart Order';
          ?>
          <tr>
            <td>
              <span class="dt-avatar"><?= strtoupper(substr((string)($o['full_name']??$o['username']??'C'),0,1)) ?></span>
              <span class="dt-bold"><?= $cn ?></span>
            </td>
            <td style="max-width:140px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars($iname) ?></td>
            <td class="dt-bold" style="color:#16a34a;">₹<?= number_format((int)($o['item_price']??0)) ?></td>
            <td><span class="pill p-<?= $pay ?>"><?= strtoupper($pay) ?></span></td>
            <td><?= htmlspecialchars((string)($o['city']??'—')) ?></td>
            <td style="color:var(--text-light);font-size:11px;"><?= htmlspecialchars(substr((string)($o['placed_at']??'—'),0,10)) ?></td>
            <td><span class="pill p-<?= $st ?>"><?= $st ?></span></td>
          </tr>
          <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- ═══ CUSTOMERS ═══ -->
    <div id="sec-customers" class="grid-23" style="margin-top:8px;">
      <div class="card">
        <div class="card-hd"><div><h3>Gender Distribution</h3><p>User demographics</p></div></div>
        <canvas id="genderChart" height="180"></canvas>
      </div>
      <div class="card">
        <div class="card-hd"><div><h3>Recent Customers (<?= count($allCustomers) ?> total)</h3></div></div>
        <div class="tbl-wrap">
          <table class="dtbl">
            <thead><tr><th>Name</th><th>Username</th><th>Phone</th><th>Gender</th><th>Joined</th></tr></thead>
            <tbody>
            <?php if (empty($allCustomers)): ?>
            <tr><td colspan="5" style="text-align:center;padding:20px;color:var(--text-light);">No customers yet</td></tr>
            <?php else: ?>
            <?php foreach (array_slice($allCustomers,0,8) as $u):
              $uname = htmlspecialchars((string)($u['name']??'User'));
            ?>
            <tr>
              <td><span class="dt-avatar"><?= strtoupper(substr($uname,0,1)) ?></span><span class="dt-bold"><?= $uname ?></span></td>
              <td style="color:var(--text-light);">@<?= htmlspecialchars((string)($u['username']??'—')) ?></td>
              <td><?= htmlspecialchars((string)($u['phone']??'—')) ?></td>
              <td><?= htmlspecialchars((string)($u['gender']??'—')) ?></td>
              <td style="font-size:11px;color:var(--text-light);"><?= fmtDate($u['created_at']??null) ?></td>
            </tr>
            <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ═══ ANALYTICS ═══ -->
    <div id="sec-analytics" class="section-hd" style="margin-top:8px;">
      <h1>Analytics</h1><p>Detailed performance charts</p>
    </div>
    <div class="grid-2">
      <div class="card">
        <div class="card-hd"><div><h3>Orders — Last 14 Days</h3><p>Daily trend</p></div></div>
        <canvas id="ordersChart" height="150"></canvas>
      </div>
      <div class="card">
        <div class="card-hd"><div><h3>Page Visits Breakdown</h3><p>Top pages</p></div></div>
        <canvas id="pageChart" height="150"></canvas>
      </div>
    </div>
    <div class="grid-2">
      <div class="card">
        <div class="card-hd"><div><h3>Most Wishlisted</h3><p>Top 5 favourites</p></div></div>
        <canvas id="wishChart" height="150"></canvas>
      </div>
      <div class="card">
        <div class="card-hd"><div><h3>Store Radar</h3><p>Performance overview</p></div></div>
        <canvas id="radarChart" height="150"></canvas>
      </div>
    </div>

  </div><!-- /content -->
</div><!-- /main -->
</div><!-- /shell -->

<script>
// ── Chart defaults ──
Chart.defaults.font.family = "'Inter', sans-serif";
Chart.defaults.font.size   = 11;
Chart.defaults.color       = '#71717a';
const pal  = ['#7c3d12','#d4642a','#f59e0b','#16a34a','#2563eb','#7c3aed','#0891b2','#db2777'];
const pal2 = ['#fef3ec','#fce7d8','#fdd9ba','#fcca99','#fbb577'];

const orderDays  = <?= json_encode(array_keys($ordersPerDay)) ?>;
const orderCts   = <?= json_encode(array_values($ordersPerDay)) ?>;
const catLbls    = <?= json_encode(array_keys($catSales)) ?>;
const catVals    = <?= json_encode(array_values($catSales)) ?>;
const pageLbls   = <?= json_encode(array_keys($pageVisits)) ?>;
const pageVals   = <?= json_encode(array_values($pageVisits)) ?>;
const wishLbls   = <?= json_encode(array_map(fn($r)=>(string)$r['_id'],$topWished)) ?>;
const wishVals   = <?= json_encode(array_map(fn($r)=>(int)$r['count'],$topWished)) ?>;
const wkDays     = <?= json_encode(array_keys($weeklySales)) ?>;
const wkVals     = <?= json_encode(array_values($weeklySales)) ?>;
const genderLbls = <?= json_encode(array_keys($genderData)) ?>;
const genderVals = <?= json_encode(array_values($genderData)) ?>;

const gridColor = '#f4f4f5';
function mkLine(id,lbl,data,color='#7c3d12',fill=true){
  const c=document.getElementById(id); if(!c)return;
  new Chart(c,{type:'line',data:{labels:lbl,datasets:[{data,borderColor:color,backgroundColor:color+'18',borderWidth:2,pointRadius:3,fill,tension:.4}]},options:{plugins:{legend:{display:false}},scales:{y:{beginAtZero:true,grid:{color:gridColor},ticks:{maxTicksLimit:4}},x:{grid:{display:false},ticks:{maxTicksLimit:6}}}}});
}
function mkBar(id,lbl,data,colors){
  const c=document.getElementById(id); if(!c)return;
  new Chart(c,{type:'bar',data:{labels:lbl,datasets:[{data,backgroundColor:colors||pal,borderRadius:4,borderWidth:0}]},options:{plugins:{legend:{display:false}},scales:{y:{beginAtZero:true,grid:{color:gridColor},ticks:{maxTicksLimit:4}},x:{grid:{display:false},ticks:{maxTicksLimit:8}}}}});
}
function mkDonut(id,lbl,data){
  const c=document.getElementById(id); if(!c)return;
  new Chart(c,{type:'doughnut',data:{labels:lbl,datasets:[{data,backgroundColor:pal,borderWidth:0}]},options:{cutout:'62%',plugins:{legend:{position:'bottom',labels:{padding:10,boxWidth:10,font:{size:10}}}}}});
}
function mkHBar(id,lbl,data){
  const c=document.getElementById(id); if(!c)return;
  new Chart(c,{type:'bar',data:{labels:lbl,datasets:[{data,backgroundColor:'#d4642a',borderRadius:4,borderWidth:0}]},options:{indexAxis:'y',plugins:{legend:{display:false}},scales:{x:{beginAtZero:true,grid:{color:gridColor},ticks:{maxTicksLimit:4}},y:{grid:{display:false},ticks:{font:{size:10}}}}}});
}
function mkSpark(id,data,color){
  const c=document.getElementById(id); if(!c)return;
  new Chart(c,{type:'line',data:{labels:data.map((_,i)=>i),datasets:[{data,borderColor:color||'#7c3d12',borderWidth:2,pointRadius:0,fill:false,tension:.4}]},options:{plugins:{legend:{display:false},tooltip:{enabled:false}},scales:{x:{display:false},y:{display:false}}}});
}

// ── Render charts ──
mkLine('ordersChart', orderDays, orderCts);
mkDonut('catChart', catLbls, catVals);
mkLine('weeklyChart', wkDays, wkVals, '#16a34a');
mkBar('pageChart', pageLbls, pageVals, pal);
mkHBar('wishChart', wishLbls, wishVals);
mkDonut('genderChart', genderLbls, genderVals);

// Waterfall (cumulative)
let cum=0; const wfData=wkVals.map(v=>{cum+=v;return cum;});
mkLine('waterfallChart', wkDays, wfData, '#f59e0b', true);

// Transaction KPI
mkBar('transactChart', wkDays, wkVals, wkVals.map((_,i)=>pal[i%pal.length]));

// Sparklines
mkSpark('spark1', wkVals, '#7c3d12');
mkSpark('spark2', wkVals, '#2563eb');
mkSpark('spark3', wkVals, '#16a34a');
mkSpark('spark4', wkVals, '#7c3aed');

// Radar
(()=>{
  const c=document.getElementById('radarChart'); if(!c)return;
  new Chart(c,{type:'radar',data:{labels:['Revenue','Orders','Customers','Products','Visits','Wishlist'],datasets:[{label:'Performance',data:[Math.min(100,Math.round(<?= $totalRevenue ?>/1000)),<?= $totalOrders ?>,<?= $totalUsers ?>,<?= $totalProducts ?>,Math.min(100,<?= $totalVisits ?>),<?= $wishCount ?>],backgroundColor:'rgba(124,61,18,.12)',borderColor:'#7c3d12',pointBackgroundColor:'#7c3d12',borderWidth:2,pointRadius:3}]},options:{plugins:{legend:{display:false}},scales:{r:{beginAtZero:true,grid:{color:gridColor},ticks:{display:false},pointLabels:{font:{size:10}}}}}});
})();

// ── Sidebar toggle ──
const sidebar  = document.getElementById('sidebar');
const mainArea = document.getElementById('mainArea');
let sideOpen   = true;
document.getElementById('hamburgerBtn').addEventListener('click', () => {
  sideOpen = !sideOpen;
  sidebar.style.width    = sideOpen ? '200px' : '0';
  sidebar.style.overflow = sideOpen ? ''       : 'hidden';
});

// ── Scroll to section (renamed to avoid native scrollTo conflict) ──
function navTo(id) {
  const el = document.getElementById(id);
  if (!el) return;
  el.scrollIntoView({ behavior: 'smooth', block: 'start' });
  document.querySelectorAll('.sb-item').forEach(b => b.classList.remove('active'));
  // Mark the clicked button active
  event.currentTarget.classList.add('active');
}
</script>

</body>
</html>
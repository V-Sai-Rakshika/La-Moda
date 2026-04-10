<?php
ob_start();
session_start();
include __DIR__ . "/db.php";
include __DIR__ . "/auth.php";

if (!isset($_SESSION['admin'])) { header("Location: admin.php"); exit(); }

$message = '';
$msgType = '';

// DELETE
if (isset($_GET['delete'])) {
    $delName = clean($_GET['delete'], 200);
    $products->deleteOne(['name' => $delName]);
    $message = "Product deleted successfully."; $msgType = 'success';
}

// EDIT: load product
$editProduct = null;
if (isset($_GET['edit'])) {
    $editName    = clean($_GET['edit'], 200);
    $editProduct = $products->findOne(['name' => $editName]);
}

// ADD or UPDATE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $pName  = clean($_POST['name']        ?? '', 200);
    $pDesc  = clean($_POST['description'] ?? '', 500);
    $pCat   = clean($_POST['category']    ?? '', 50);
    $pSub   = strtolower(clean($_POST['subcategory'] ?? '', 100));
    $pOld   = (int)($_POST['old_price']   ?? 0);
    $pNew   = (int)($_POST['new_price']   ?? 0);
    $pFlash = isset($_POST['flash_sale']) ? 'yes' : 'no';
    $pStock = (int)($_POST['stock']       ?? 0);
    $pSizes = clean($_POST['sizes']       ?? '', 200);

    // Sizes array
    $sizesArr = array_filter(array_map('trim', explode(',', $pSizes)));

    if (!$pName || !$pCat || !$pNew) {
        $message = "Name, Category and New Price are required."; $msgType = 'error';
    } else {
        // Handle image upload
        $imageName = clean($_POST['existing_image'] ?? '', 200);
        if (!empty($_FILES['image']['name'])) {
            $file = $_FILES['image'];
            $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg','jpeg','png','webp','gif'];
            if (!in_array($ext, $allowed)) {
                $message = "Invalid image format."; $msgType = 'error';
            } elseif ($file['size'] > 5 * 1024 * 1024) {
                $message = "Image too large (max 5MB)."; $msgType = 'error';
            } else {
                $newName = uniqid('prod_') . '.' . $ext;
                $dest    = __DIR__ . '/images/' . $newName;
                if (!is_dir(__DIR__ . '/images')) mkdir(__DIR__ . '/images', 0755, true);
                if (move_uploaded_file($file['tmp_name'], $dest)) $imageName = $newName;
                else { $message = "Image upload failed."; $msgType = 'error'; }
            }
        }

        if (!$message) {
            $doc = [
                'name'        => $pName,
                'description' => $pDesc,
                'category'    => $pCat,
                'subcategory' => $pSub,
                'old_price'   => $pOld,
                'new_price'   => $pNew,
                'flash_sale'  => $pFlash,
                'stock'       => $pStock,
                'sizes'       => array_values($sizesArr),
                'image'       => $imageName,
            ];

            if ($_POST['action'] === 'add') {
                $doc['avg_rating']   = 0;
                $doc['rating_count'] = 0;
                $doc['created_at']   = date('Y-m-d H:i:s');
                $products->insertOne($doc);
                $message = "Product added successfully!"; $msgType = 'success';
            } else {
                $products->updateOne(
                    ['name' => clean($_POST['original_name'] ?? $pName, 200)],
                    ['$set' => $doc]
                );
                $message = "Product updated successfully!"; $msgType = 'success';
                $editProduct = null;
            }
        }
    }
}

// Fetch all products
$allProducts = iterator_to_array($products->find([], ['sort' => ['created_at' => -1]]));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>La Moda Admin | Manage Products</title>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@700&family=Jost:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>👗</text></svg>">
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'Jost',sans-serif;background:#f4f5f7;min-height:100vh;color:#1e1e1e;}

/* ── TOP NAV ── */
.top-nav{
  background:#fff;border-bottom:1px solid #eee;
  padding:0 24px;height:60px;
  display:flex;align-items:center;justify-content:space-between;
  position:sticky;top:0;z-index:100;
  box-shadow:0 2px 8px rgba(0,0,0,.06);
}
.top-nav-logo{font-family:'Cormorant Garamond',serif;font-size:22px;color:#8B2500;font-weight:700;}
.top-nav-links{display:flex;align-items:center;gap:8px;}
.nav-link{padding:7px 14px;border-radius:8px;font-size:13px;font-weight:500;color:#666;text-decoration:none;transition:all .18s;}
.nav-link:hover,.nav-link.active{background:#fff5f2;color:#8B2500;}
.nav-link.back{border:1.5px solid #e0e0e0;color:#555;}

/* ── LAYOUT ── */
.main{max-width:1300px;margin:0 auto;padding:24px 20px 60px;}

/* ── PAGE HEADER ── */
.page-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;flex-wrap:wrap;gap:12px;}
.page-header h1{font-family:'Cormorant Garamond',serif;font-size:28px;color:#1e1e1e;}
.page-header p{font-size:13px;color:#999;margin-top:3px;}

/* ── ALERT ── */
.alert{padding:12px 16px;border-radius:10px;font-size:13px;font-weight:500;margin-bottom:20px;display:flex;align-items:center;gap:10px;}
.alert-success{background:#f0fdf4;border:1px solid #bbf7d0;color:#15803d;}
.alert-error  {background:#fff5f5;border:1px solid #fecaca;color:#dc2626;}

/* ── GRID ── */
.grid-2{display:grid;grid-template-columns:1fr 1fr;gap:24px;align-items:start;}
@media(max-width:900px){.grid-2{grid-template-columns:1fr;}}

/* ── CARD ── */
.card{background:#fff;border-radius:16px;box-shadow:0 2px 12px rgba(0,0,0,.06);overflow:hidden;}
.card-header{padding:16px 20px;border-bottom:1px solid #f0f0f0;display:flex;align-items:center;justify-content:space-between;}
.card-header h2{font-size:16px;font-weight:700;color:#1e1e1e;}
.card-header p{font-size:12px;color:#aaa;margin-top:2px;}
.card-body{padding:20px;}

/* ── FORM ── */
.form-group{margin-bottom:14px;}
.form-group label{display:block;font-size:11px;font-weight:600;color:#888;text-transform:uppercase;letter-spacing:.5px;margin-bottom:5px;}
.form-group input,.form-group select,.form-group textarea{
  width:100%;padding:10px 13px;border:1.5px solid #e8e8e8;border-radius:9px;
  font-size:13px;font-family:'Jost',sans-serif;outline:none;background:#fff;
  transition:border-color .18s;
}
.form-group input:focus,.form-group select:focus,.form-group textarea:focus{border-color:#8B2500;box-shadow:0 0 0 3px rgba(139,37,0,.07);}
.form-group textarea{resize:vertical;min-height:70px;}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:12px;}
@media(max-width:500px){.form-row{grid-template-columns:1fr;}}

/* Toggle switch */
.toggle-wrap{display:flex;align-items:center;gap:10px;padding:4px 0;}
.toggle-wrap label{font-size:13px;color:#444;font-weight:500;cursor:pointer;}
.toggle{position:relative;width:42px;height:24px;flex-shrink:0;}
.toggle input{opacity:0;width:0;height:0;}
.toggle-slider{position:absolute;inset:0;background:#e0e0e0;border-radius:24px;cursor:pointer;transition:.3s;}
.toggle-slider::before{content:'';position:absolute;width:18px;height:18px;left:3px;bottom:3px;background:#fff;border-radius:50%;transition:.3s;box-shadow:0 1px 3px rgba(0,0,0,.2);}
.toggle input:checked + .toggle-slider{background:#8B2500;}
.toggle input:checked + .toggle-slider::before{transform:translateX(18px);}

/* Image preview */
.img-preview-wrap{margin-top:10px;}
.img-preview{width:100%;max-height:180px;object-fit:cover;border-radius:10px;border:1px solid #eee;display:none;}
.img-preview.show{display:block;}
.img-upload-area{
  border:2px dashed #e0e0e0;border-radius:10px;padding:20px;text-align:center;
  cursor:pointer;transition:border-color .18s;position:relative;
}
.img-upload-area:hover{border-color:#8B2500;}
.img-upload-area input{position:absolute;inset:0;opacity:0;cursor:pointer;}
.img-upload-area p{font-size:13px;color:#aaa;margin-top:6px;}
.img-upload-icon{font-size:28px;}

/* Sizes grid */
.sizes-grid{display:grid;grid-template-columns:repeat(7,1fr);gap:6px;margin-top:4px;}
.size-chip{
  padding:7px 0;text-align:center;border:1.5px solid #e0e0e0;border-radius:7px;
  font-size:12px;font-weight:600;cursor:pointer;transition:all .18s;user-select:none;
}
.size-chip.on{border-color:#8B2500;background:#8B2500;color:#fff;}
.size-chip:hover:not(.on){border-color:#8B2500;color:#8B2500;}

/* Submit button */
.btn-primary{
  width:100%;padding:13px;background:#8B2500;color:#fff;border:none;
  border-radius:10px;font-size:14px;font-weight:700;cursor:pointer;
  font-family:'Jost',sans-serif;transition:background .2s;
}
.btn-primary:hover{background:#5c1800;}
.btn-outline{
  padding:8px 16px;background:#fff;color:#8B2500;border:1.5px solid #8B2500;
  border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;
  font-family:'Jost',sans-serif;transition:all .18s;text-decoration:none;display:inline-flex;align-items:center;gap:4px;
}
.btn-outline:hover{background:#8B2500;color:#fff;}
.btn-danger{padding:7px 13px;background:#fff5f5;color:#dc2626;border:1.5px solid #fecaca;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;font-family:'Jost',sans-serif;text-decoration:none;transition:all .18s;}
.btn-danger:hover{background:#dc2626;color:#fff;}

/* ── PRODUCT TABLE ── */
.table-header{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;}
.search-box{display:flex;align-items:center;gap:8px;background:#f5f5f5;border-radius:8px;padding:8px 12px;width:260px;}
.search-box input{border:none;background:none;outline:none;font-size:13px;font-family:'Jost',sans-serif;width:100%;}

.products-table{width:100%;border-collapse:collapse;font-size:13px;}
.products-table th{padding:10px 14px;text-align:left;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.5px;color:#999;border-bottom:1px solid #f0f0f0;white-space:nowrap;}
.products-table td{padding:12px 14px;border-bottom:1px solid #f8f8f8;vertical-align:middle;}
.products-table tr:hover td{background:#fafafa;}
.products-table tr:last-child td{border-bottom:none;}

.prod-img{width:46px;height:54px;object-fit:cover;border-radius:8px;border:1px solid #eee;}
.prod-name{font-weight:600;color:#1e1e1e;margin-bottom:2px;}
.prod-cat{font-size:11px;color:#aaa;text-transform:capitalize;}
.badge{display:inline-block;padding:3px 9px;border-radius:20px;font-size:11px;font-weight:600;}
.badge-flash{background:#fff5f2;color:#8B2500;border:1px solid #ffd6c4;}
.badge-normal{background:#f5f5f5;color:#aaa;}
.stock-ok  {color:#16a34a;font-weight:600;}
.stock-low {color:#f59e0b;font-weight:600;}
.stock-out {color:#dc2626;font-weight:600;}
.actions-cell{display:flex;gap:6px;align-items:center;}

/* Mobile table scroll */
.table-wrap{overflow-x:auto;}

/* ── MOBILE RESPONSIVE ── */
@media(max-width:768px){
  .top-nav-links .nav-link span{display:none;}
  .page-header h1{font-size:22px;}
  .main{padding:16px 14px 60px;}
}
@media(max-width:480px){
  .sizes-grid{grid-template-columns:repeat(4,1fr);}
  .card-body{padding:16px;}
}
</style>
</head>
<body>

<!-- Top Nav -->
<div class="top-nav">
  <div class="top-nav-logo">La Moda</div>
  <div class="top-nav-links">
    <a href="admin.php" class="nav-link"><span>📊 </span>Dashboard</a>
    <a href="manage_products.php" class="nav-link active"><span>🛍️ </span>Products</a>
    <a href="index.php" target="_blank" class="nav-link back">🌐 View Site</a>
    <a href="admin.php?admin_logout=1" class="nav-link" style="color:#dc2626;">Logout</a>
  </div>
</div>

<div class="main">

  <div class="page-header">
    <div>
      <h1>🛍️ Manage Products</h1>
      <p><?= count($allProducts) ?> products in your catalogue</p>
    </div>
  </div>

  <?php if ($message): ?>
  <div class="alert alert-<?= $msgType ?>">
    <?= $msgType === 'success' ? '✅' : '❌' ?> <?= htmlspecialchars($message) ?>
  </div>
  <?php endif; ?>

  <div class="grid-2">

    <!-- ── ADD / EDIT FORM ── -->
    <div class="card">
      <div class="card-header">
        <div>
          <h2><?= $editProduct ? '✏️ Edit Product' : '➕ Add New Product' ?></h2>
          <p><?= $editProduct ? 'Update product details' : 'Fill in the details below' ?></p>
        </div>
        <?php if ($editProduct): ?>
        <a href="manage_products.php" class="btn-outline">✕ Cancel Edit</a>
        <?php endif; ?>
      </div>
      <div class="card-body">
        <form method="POST" enctype="multipart/form-data" id="productForm">
          <input type="hidden" name="action" value="<?= $editProduct ? 'edit' : 'add' ?>">
          <input type="hidden" name="existing_image" value="<?= htmlspecialchars((string)($editProduct['image'] ?? '')) ?>">
          <input type="hidden" name="original_name"  value="<?= htmlspecialchars((string)($editProduct['name']  ?? '')) ?>">
          <input type="hidden" id="sizesHidden" name="sizes" value="<?= htmlspecialchars(implode(', ', is_array($editProduct['sizes'] ?? null) ? $editProduct['sizes'] : [])) ?>">

          <div class="form-group">
            <label>Product Name *</label>
            <input type="text" name="name" required maxlength="200" placeholder="e.g. Silk Saree in Red"
                   value="<?= htmlspecialchars((string)($editProduct['name'] ?? '')) ?>">
          </div>

          <div class="form-group">
            <label>Description</label>
            <textarea name="description" maxlength="500" placeholder="Short product description…"><?= htmlspecialchars((string)($editProduct['description'] ?? '')) ?></textarea>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>Category *</label>
              <select name="category" required>
                <option value="">Select category</option>
                <?php foreach (['traditional','dresses','casual','accessories'] as $cat): ?>
                <option value="<?= $cat ?>" <?= (($editProduct['category'] ?? '') === $cat) ? 'selected' : '' ?>><?= ucfirst($cat) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label>Subcategory</label>
              <input type="text" name="subcategory" maxlength="100" placeholder="e.g. saree, jeans, bag"
                     value="<?= htmlspecialchars((string)($editProduct['subcategory'] ?? '')) ?>">
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>MRP (Old Price) ₹</label>
              <input type="number" name="old_price" min="0" placeholder="e.g. 2500"
                     value="<?= (int)($editProduct['old_price'] ?? 0) ?>">
            </div>
            <div class="form-group">
              <label>Selling Price ₹ *</label>
              <input type="number" name="new_price" min="1" required placeholder="e.g. 1999"
                     value="<?= (int)($editProduct['new_price'] ?? 0) ?>">
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>Stock Qty</label>
              <input type="number" name="stock" min="0" placeholder="e.g. 20"
                     value="<?= (int)($editProduct['stock'] ?? 0) ?>">
            </div>
            <div class="form-group" style="display:flex;align-items:flex-end;padding-bottom:2px;">
              <div class="toggle-wrap">
                <label class="toggle">
                  <input type="checkbox" name="flash_sale" id="flashToggle"
                         <?= (($editProduct['flash_sale'] ?? 'no') === 'yes') ? 'checked' : '' ?>>
                  <span class="toggle-slider"></span>
                </label>
                <label for="flashToggle" style="font-size:13px;color:#444;">⚡ Flash Sale</label>
              </div>
            </div>
          </div>

          <!-- Sizes -->
          <div class="form-group" id="sizesGroup">
            <label>Available Sizes <span style="font-weight:400;color:#bbb;">(click to select)</span></label>
            <div class="sizes-grid" id="sizesGrid">
              <?php
              $currentSizes = is_array($editProduct['sizes'] ?? null) ? $editProduct['sizes'] : [];
              foreach (['XS','S','M','L','XL','XXL','XXXL'] as $sz):
              ?>
              <div class="size-chip<?= in_array($sz, $currentSizes) ? ' on' : '' ?>" data-size="<?= $sz ?>"><?= $sz ?></div>
              <?php endforeach; ?>
            </div>
            <p style="font-size:11px;color:#bbb;margin-top:6px;">Not required for accessories</p>
          </div>

          <!-- Image upload -->
          <div class="form-group">
            <label>Product Image</label>
            <?php if (!empty($editProduct['image'])): ?>
              <img src="images/<?= htmlspecialchars($editProduct['image']) ?>" class="img-preview show" id="imgPreview" alt="Current image">
            <?php else: ?>
              <img src="" class="img-preview" id="imgPreview" alt="">
            <?php endif; ?>
            <div class="img-upload-area" style="margin-top:8px;">
              <input type="file" name="image" id="imageInput" accept=".jpg,.jpeg,.png,.webp,.gif">
              <div class="img-upload-icon">📷</div>
              <p><?= $editProduct ? 'Upload new image (leave empty to keep current)' : 'Click or drag image here' ?></p>
              <p style="font-size:11px;color:#bbb;margin-top:4px;">JPG, PNG, WEBP, GIF — max 5MB</p>
            </div>
          </div>

          <button type="submit" class="btn-primary">
            <?= $editProduct ? '✅ Update Product' : '➕ Add Product' ?>
          </button>
        </form>
      </div>
    </div>

    <!-- ── PRODUCTS TABLE ── -->
    <div class="card">
      <div class="card-header">
        <div>
          <h2>📦 All Products</h2>
          <p>Click edit to modify a product</p>
        </div>
        <div class="search-box">
          <span>🔍</span>
          <input type="text" id="searchInput" placeholder="Search products…" oninput="filterProducts()">
        </div>
      </div>
      <div class="card-body" style="padding:0;">
        <div class="table-wrap">
          <table class="products-table" id="productsTable">
            <thead>
              <tr>
                <th>Product</th>
                <th>Price</th>
                <th>Stock</th>
                <th>Type</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($allProducts as $p):
                $pImg   = (string)($p['image'] ?? '');
                $imgSrc = $pImg ? (strpos($pImg,'http')===0 ? $pImg : "images/$pImg") : "https://placehold.co/46x54/f5f5f5/aaa?text=?";
                $stock  = (int)($p['stock'] ?? 0);
                $stockClass = $stock <= 0 ? 'stock-out' : ($stock <= 5 ? 'stock-low' : 'stock-ok');
                $stockLabel = $stock <= 0 ? 'Out of stock' : ($stock <= 5 ? "Only $stock left" : "$stock");
              ?>
              <tr class="prod-row">
                <td>
                  <div style="display:flex;align-items:center;gap:10px;">
                    <img src="<?= htmlspecialchars($imgSrc) ?>" class="prod-img"
                         onerror="this.src='https://placehold.co/46x54/f5f5f5/aaa?text=?'" alt="">
                    <div>
                      <div class="prod-name"><?= htmlspecialchars((string)($p['name'] ?? '')) ?></div>
                      <div class="prod-cat"><?= htmlspecialchars((string)($p['category'] ?? '')) ?> · <?= htmlspecialchars((string)($p['subcategory'] ?? '')) ?></div>
                    </div>
                  </div>
                </td>
                <td>
                  <div style="font-weight:700;color:#8B2500;">₹<?= (int)($p['new_price'] ?? 0) ?></div>
                  <?php if (!empty($p['old_price'])): ?>
                  <div style="font-size:11px;color:#bbb;text-decoration:line-through;">MRP ₹<?= (int)$p['old_price'] ?></div>
                  <?php endif; ?>
                </td>
                <td><span class="<?= $stockClass ?>"><?= $stockLabel ?></span></td>
                <td>
                  <?php if (($p['flash_sale'] ?? 'no') === 'yes'): ?>
                    <span class="badge badge-flash">⚡ Flash</span>
                  <?php else: ?>
                    <span class="badge badge-normal">Regular</span>
                  <?php endif; ?>
                </td>
                <td>
                  <div class="actions-cell">
                    <a href="manage_products.php?edit=<?= urlencode((string)($p['name'] ?? '')) ?>" class="btn-outline">✏️ Edit</a>
                    <a href="manage_products.php?delete=<?= urlencode((string)($p['name'] ?? '')) ?>"
                       class="btn-danger"
                       onclick="return confirm('Delete <?= htmlspecialchars(addslashes((string)($p['name'] ?? ''))) ?>? This cannot be undone.')">🗑️</a>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
          <?php if (empty($allProducts)): ?>
          <div style="text-align:center;padding:48px;color:#bbb;font-size:15px;">No products yet. Add your first product!</div>
          <?php endif; ?>
        </div>
      </div>
    </div>

  </div><!-- end grid -->
</div><!-- end main -->

<script>
// ── Size chips ──
const sizesHidden = document.getElementById('sizesHidden');
function updateSizesHidden() {
  const on = [...document.querySelectorAll('.size-chip.on')].map(c => c.dataset.size);
  sizesHidden.value = on.join(', ');
}
document.querySelectorAll('.size-chip').forEach(chip => {
  chip.addEventListener('click', () => {
    chip.classList.toggle('on');
    updateSizesHidden();
  });
});

// ── Image preview ──
document.getElementById('imageInput')?.addEventListener('change', function() {
  const file = this.files[0]; if (!file) return;
  const reader = new FileReader();
  reader.onload = e => {
    const img = document.getElementById('imgPreview');
    img.src = e.target.result; img.classList.add('show');
  };
  reader.readAsDataURL(file);
});

// ── Table search ──
function filterProducts() {
  const q = document.getElementById('searchInput').value.toLowerCase();
  document.querySelectorAll('.prod-row').forEach(row => {
    row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
  });
}

// ── Auto-scroll to form on edit ──
<?php if ($editProduct): ?>
document.getElementById('productForm').scrollIntoView({behavior:'smooth', block:'start'});
<?php endif; ?>

// ── Category change: show/hide sizes ──
document.querySelector('[name="category"]')?.addEventListener('change', function() {
  const sizesGroup = document.getElementById('sizesGroup');
  sizesGroup.style.opacity = this.value === 'accessories' ? '0.4' : '1';
});
</script>
</body>
</html>
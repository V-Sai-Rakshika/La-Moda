<?php
ob_start();
session_start();
include __DIR__ . "/db.php";
include __DIR__ . "/auth.php";

// ── Admin gate — same as admin.php ──
if (!isset($_SESSION['admin'])) {
    header("Location: admin.php");
    exit();
}

$message = '';
$msgType = '';

// ── DELETE product ──
if (isset($_GET['delete'])) {
    $delName = clean($_GET['delete'], 200);
    $products->deleteOne(['name' => $delName]);
    $message = "Product deleted successfully.";
    $msgType = 'success';
}

// ── EDIT: load product into form ──
$editProduct = null;
if (isset($_GET['edit'])) {
    $editName    = clean($_GET['edit'], 200);
    $editProduct = $products->findOne(['name' => $editName]);
}

// ── ADD or UPDATE product ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    $pName     = clean($_POST['name']        ?? '', 200);
    $pDesc     = clean($_POST['description'] ?? '', 500);
    $pCat      = clean($_POST['category']    ?? '', 50);
    $pSub      = strtolower(clean($_POST['subcategory'] ?? '', 100));
    $pOld      = (int)($_POST['old_price']   ?? 0);
    $pNew      = (int)($_POST['new_price']   ?? 0);
    $pFlash    = isset($_POST['flash_sale']) ? 'yes' : 'no';
    $pStock    = (int)($_POST['stock']       ?? 0);
    $pSizes    = clean($_POST['sizes']       ?? '', 200);

    if (!$pName || !$pCat || !$pNew) {
        $message = "Name, Category and New Price are required.";
        $msgType = 'error';
    } else {

        // ── Handle image upload ──
        $imageName = clean($_POST['existing_image'] ?? '', 200); // keep old image by default

        if (!empty($_FILES['image']['name'])) {
            $file     = $_FILES['image'];
            $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed  = ['jpg','jpeg','png','webp','gif'];

            if (!in_array($ext, $allowed)) {
                $message = "Invalid image format. Use JPG, PNG, WEBP or GIF.";
                $msgType = 'error';
            } elseif ($file['size'] > 5 * 1024 * 1024) {
                $message = "Image too large. Maximum size is 5MB.";
                $msgType = 'error';
            } else {
                // Safe filename: remove spaces, special chars
                $safeBase  = preg_replace('/[^a-zA-Z0-9_-]/', '_', pathinfo($file['name'], PATHINFO_FILENAME));
                $imageName = $safeBase . '_' . time() . '.' . $ext;
                $uploadDir = __DIR__ . '/images/';

                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

                if (!move_uploaded_file($file['tmp_name'], $uploadDir . $imageName)) {
                    $message = "Failed to upload image. Check images/ folder permissions.";
                    $msgType = 'error';
                    $imageName = clean($_POST['existing_image'] ?? '', 200);
                }
            }
        }

        if ($msgType !== 'error') {
            // Parse sizes into array
            $sizesArr = [];
            if ($pSizes) {
                $sizesArr = array_map('trim', explode(',', $pSizes));
                $sizesArr = array_filter($sizesArr);
                $sizesArr = array_values($sizesArr);
            }

            $doc = [
                'name'         => $pName,
                'description'  => $pDesc,
                'category'     => strtolower($pCat),
                'subcategory'  => $pSub,
                'old_price'    => $pOld,
                'new_price'    => $pNew,
                'flash_sale'   => $pFlash,
                'stock'        => $pStock,
                'image'        => $imageName,
                'avg_rating'   => 0,
                'rating_count' => 0,
            ];
            if (!empty($sizesArr)) $doc['sizes'] = $sizesArr;

            if ($_POST['action'] === 'add') {
                // Check duplicate name
                if ($products->findOne(['name' => $pName])) {
                    $message = "A product with this name already exists.";
                    $msgType = 'error';
                } else {
                    $products->insertOne($doc);
                    $message = "✅ Product '{$pName}' added successfully!";
                    $msgType = 'success';
                }
            } else {
                // Update — preserve avg_rating and rating_count
                $oldDoc = $products->findOne(['name' => $_POST['original_name']]);
                $doc['avg_rating']   = (float)($oldDoc['avg_rating']   ?? 0);
                $doc['rating_count'] = (int)($oldDoc['rating_count']   ?? 0);

                $products->replaceOne(['name' => clean($_POST['original_name'], 200)], $doc);
                $message = "✅ Product '{$pName}' updated successfully!";
                $msgType = 'success';
                $editProduct = null; // clear edit form after save
            }
        }
    }
}

// ── Fetch all products for the table ──
$allProducts = iterator_to_array(
    $products->find([], ['sort' => ['category' => 1, 'name' => 1]])
);

ob_end_clean();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Products | La Moda Admin</title>
<link rel="stylesheet" href="admin_shared.css">
</head>
<body>

<!-- ══════ SIDEBAR ══════ -->
<aside class="admin-sidebar">
    <div class="sidebar-brand">
        <div class="brand-icon">✦</div>
        <div>
            <div class="brand-name">La Moda</div>
            <div class="brand-sub">Admin Panel</div>
        </div>
    </div>
    <nav class="sidebar-nav">
        <p class="nav-group-label">Main</p>
        <a href="admin.php"           class="nav-link"><span class="ni">📊</span> Dashboard</a>
        <a href="manage_products.php" class="nav-link active"><span class="ni">📦</span> Products</a>
        <p class="nav-group-label">Store</p>
        <a href="index.php" target="_blank" class="nav-link"><span class="ni">🛍️</span> View Store</a>
        <a href="wishlist.php"              class="nav-link"><span class="ni">♡</span> Wishlists</a>
    </nav>
    <div class="sidebar-bottom">
        <div class="admin-profile">
            <div class="admin-avatar">A</div>
            <div>
                <div class="admin-name">Admin</div>
                <div class="admin-role">Super Admin</div>
            </div>
        </div>
        <a href="admin.php?admin_logout=1" class="logout-link"><span>🚪</span> Logout</a>
    </div>
</aside>

<!-- ══════ MAIN ══════ -->
<div class="admin-main">

    <header class="admin-header">
        <div class="header-left">
            <h2>Products</h2>
            <p>Manage your product catalogue</p>
        </div>
        <div class="header-right">
            <span class="header-date"><?= date('D, d M Y') ?></span>
        </div>
    </header>

    <div class="admin-content">

        <?php if ($message): ?>
        <div class="alert alert-<?= $msgType ?>">
            <?= $msgType === 'success' ? '✅' : '❌' ?>
            <?= htmlspecialchars($message) ?>
        </div>
        <?php endif; ?>

        <!-- Quick stats -->
        <div class="pm-stats">
            <div class="pm-stat">
                <div class="pm-stat-val"><?= count($allProducts) ?></div>
                <div class="pm-stat-lbl">Total Products</div>
            </div>
            <?php foreach (['traditional','dresses','casual','accessories'] as $c): ?>
            <div class="pm-stat">
                <div class="pm-stat-val">
                    <?= count(array_filter($allProducts, fn($p) => strtolower((string)($p['category']??'')) === $c)) ?>
                </div>
                <div class="pm-stat-lbl"><?= ucfirst($c) ?></div>
            </div>
            <?php endforeach; ?>
            <div class="pm-stat">
                <div class="pm-stat-val" style="color:#e8590c;">
                    <?= count(array_filter($allProducts, fn($p) => ($p['flash_sale']??'') === 'yes')) ?>
                </div>
                <div class="pm-stat-lbl">Flash Deals</div>
            </div>
        </div>

        <!-- Two column layout -->
        <div class="pm-grid">

            <!-- ── ADD / EDIT FORM ── -->
            <div class="form-panel">
                <div class="form-panel-title">
                    <?= $editProduct ? '✏️ Edit Product' : '➕ Add New Product' ?>
                </div>

                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action"
                           value="<?= $editProduct ? 'update' : 'add' ?>">
                    <input type="hidden" name="original_name"
                           value="<?= htmlspecialchars((string)($editProduct['name'] ?? '')) ?>">
                    <input type="hidden" name="existing_image"
                           value="<?= htmlspecialchars((string)($editProduct['image'] ?? '')) ?>">

                    <label class="form-label">Product Name *</label>
                    <input class="form-input" type="text" name="name" required maxlength="200"
                           placeholder="e.g. Floral Kurti Set"
                           value="<?= htmlspecialchars((string)($editProduct['name'] ?? '')) ?>">

                    <label class="form-label">Description</label>
                    <textarea class="form-textarea" name="description" rows="2" maxlength="500"
                        placeholder="Short description…"><?= htmlspecialchars((string)($editProduct['description'] ?? '')) ?></textarea>

                    <label class="form-label">Category *</label>
                    <select class="form-select" name="category" required>
                        <option value="">Select category</option>
                        <?php foreach (['traditional','dresses','casual','accessories'] as $c): ?>
                        <option value="<?= $c ?>"
                            <?= strtolower((string)($editProduct['category']??'')) === $c ? 'selected' : '' ?>>
                            <?= ucfirst($c) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>

                    <label class="form-label">Subcategory</label>
                    <input class="form-input" type="text" name="subcategory" maxlength="100"
                           placeholder="e.g. kurti set, jeans, watch, ring"
                           value="<?= htmlspecialchars((string)($editProduct['subcategory'] ?? '')) ?>">
                    <p class="form-helper">
                        Casual: shirt, t-shirt, top, jeans, trouser, skirt, bottom<br>
                        Accessories: jewellery, watch, bag, ring, necklace, earring, bracelet, handbag
                    </p>

                    <div class="form-grid-2" style="margin-top:4px;">
                        <div>
                            <label class="form-label">Old Price (₹)</label>
                            <input class="form-input" type="number" name="old_price" min="0"
                                   placeholder="1299"
                                   value="<?= (int)($editProduct['old_price'] ?? 0) ?>">
                        </div>
                        <div>
                            <label class="form-label">New Price (₹) *</label>
                            <input class="form-input" type="number" name="new_price" min="1" required
                                   placeholder="899"
                                   value="<?= (int)($editProduct['new_price'] ?? 0) ?>">
                        </div>
                    </div>

                    <div class="form-grid-2">
                        <div>
                            <label class="form-label">Stock Qty</label>
                            <input class="form-input" type="number" name="stock" min="0"
                                   placeholder="50"
                                   value="<?= (int)($editProduct['stock'] ?? 0) ?>">
                        </div>
                        <div>
                            <label class="form-label">Sizes (comma separated)</label>
                            <input class="form-input" type="text" name="sizes"
                                   placeholder="XS,S,M,L,XL"
                                   value="<?php
                                       $sz = $editProduct['sizes'] ?? [];
                                       if (is_object($sz)) $sz = iterator_to_array($sz);
                                       echo htmlspecialchars(implode(',', (array)$sz));
                                   ?>">
                        </div>
                    </div>

                    <label class="form-label">Product Image</label>
                    <?php if (!empty($editProduct['image'])): ?>
                    <img src="images/<?= htmlspecialchars((string)$editProduct['image']) ?>"
                         class="img-preview-box" id="imgPreview"
                         onerror="this.src='https://placehold.co/72x72/f5f5f5/aaa?text=?'">
                    <?php else: ?>
                    <img src="https://placehold.co/72x72/faf8f5/c4b5a5?text=Photo"
                         class="img-preview-box" id="imgPreview">
                    <?php endif; ?>
                    <input class="form-input" type="file" name="image" accept="image/*"
                           id="imgInput" style="margin-top:8px;padding:6px;">
                    <p class="form-helper">JPG/PNG/WEBP, max 5MB. Leave blank to keep existing.</p>

                    <label class="form-check-row">
                        <input type="checkbox" name="flash_sale" value="1"
                            <?= ($editProduct['flash_sale'] ?? '') === 'yes' ? 'checked' : '' ?>>
                        ⚡ Include in Flash Deals
                    </label>

                    <button type="submit" class="btn-primary">
                        <?= $editProduct ? '💾 Save Changes' : '➕ Add Product' ?>
                    </button>

                    <?php if ($editProduct): ?>
                    <a href="manage_products.php">
                        <button type="button" class="btn-secondary">✕ Cancel Edit</button>
                    </a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- ── PRODUCTS TABLE ── -->
            <div class="table-card">
                <div class="table-card-header">
                    <div>
                        <div class="table-card-title">All Products (<?= count($allProducts) ?>)</div>
                        <div class="table-card-sub">Click Edit to modify any product</div>
                    </div>
                </div>

                <div class="tbl-search-wrap">
                    <input type="text" class="tbl-search" id="tableSearch"
                           placeholder="🔍  Search by name, category, subcategory…">
                </div>

                <table class="admin-tbl" id="prodTable">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Subcategory</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Flash</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($allProducts)): ?>
                    <tr><td colspan="8" class="tbl-empty">No products yet. Add your first product using the form on the left.</td></tr>
                    <?php else: ?>
                    <?php foreach ($allProducts as $p):
                        $pCat    = strtolower((string)($p['category'] ?? ''));
                        $pStock  = isset($p['stock']) ? (int)$p['stock'] : null;
                        $pImg    = (string)($p['image'] ?? '');
                        $pImgSrc = $pImg
                            ? (strpos($pImg,'http')===0 ? $pImg : "images/".$pImg)
                            : "https://placehold.co/48x48/faf8f5/c4b5a5?text=?";
                    ?>
                    <tr class="prod-row">
                        <td>
                            <img src="<?= htmlspecialchars($pImgSrc) ?>"
                                 style="width:44px;height:44px;object-fit:cover;border-radius:8px;background:#faf8f5;"
                                 onerror="this.src='https://placehold.co/44x44/faf8f5/c4b5a5?text=?'">
                        </td>
                        <td>
                            <div style="font-weight:600;color:#1a1a1a;font-size:13px;">
                                <?= htmlspecialchars((string)($p['name'] ?? '')) ?>
                            </div>
                        </td>
                        <td>
                            <span class="cat-pill cat-<?= htmlspecialchars($pCat) ?>">
                                <?= htmlspecialchars(ucfirst($pCat)) ?>
                            </span>
                        </td>
                        <td style="color:#9ca3af;font-size:12px;">
                            <?= htmlspecialchars((string)($p['subcategory'] ?? '—')) ?>
                        </td>
                        <td>
                            <?php if ((int)($p['old_price']??0) > 0): ?>
                            <div style="text-decoration:line-through;color:#c4b5a5;font-size:11px;">
                                ₹<?= (int)$p['old_price'] ?>
                            </div>
                            <?php endif; ?>
                            <div style="font-weight:700;color:#1a1a1a;">₹<?= (int)($p['new_price'] ?? 0) ?></div>
                        </td>
                        <td>
                            <?php if ($pStock === null): ?>
                            <span style="color:#c4b5a5;">—</span>
                            <?php elseif ($pStock === 0): ?>
                            <span class="stock-out">OUT</span>
                            <?php elseif ($pStock <= 5): ?>
                            <span class="stock-low"><?= $pStock ?> left</span>
                            <?php else: ?>
                            <span class="stock-ok"><?= $pStock ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (($p['flash_sale']??'') === 'yes'): ?>
                            <span class="flash-on">⚡</span>
                            <?php else: ?>
                            <span style="color:#c4b5a5;">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div style="display:flex;gap:6px;">
                                <a href="?edit=<?= urlencode((string)($p['name']??'')) ?>"
                                   class="action-btn-edit">Edit</a>
                                <a href="?delete=<?= urlencode((string)($p['name']??'')) ?>"
                                   class="action-btn-del"
                                   onclick="return confirm('Delete this product? This cannot be undone.')">
                                   Delete
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div><!-- /pm-grid -->
    </div><!-- /admin-content -->
</div><!-- /admin-main -->

<script>
document.getElementById("imgInput")?.addEventListener("change", function() {
    const file = this.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = e => document.getElementById("imgPreview").src = e.target.result;
    reader.readAsDataURL(file);
});

document.getElementById("tableSearch")?.addEventListener("input", function() {
    const q = this.value.toLowerCase();
    document.querySelectorAll(".prod-row").forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(q) ? "" : "none";
    });
});

<?php if ($editProduct): ?>
window.scrollTo({ top: 0, behavior: 'smooth' });
<?php endif; ?>
</script>

</body>
</html>
<style>
body { background: #f0f2f8 !important; background-image: none !important; }

/* ── Page layout ── */
.mp-wrap { max-width: 1200px; margin: 0 auto; padding: 30px 24px; }
.mp-title {
    font-family: 'Cormorant Garamond', serif;
    font-size: 28px; font-weight: 700;
    color: #1e1e1e; margin-bottom: 24px;
}

/* ── Message bar ── */
.msg {
    padding: 12px 18px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 500;
    margin-bottom: 20px;
}
.msg.success { background: #e8f5e9; color: #2e7d32; border-left: 4px solid #2e7d32; }
.msg.error   { background: #fff0f0; color: #c0392b; border-left: 4px solid #c0392b; }

/* ── Two column layout ── */
.mp-grid { display: grid; grid-template-columns: 400px 1fr; gap: 28px; align-items: flex-start; }

/* ── Form card ── */
.form-card {
    background: white;
    border-radius: 14px;
    padding: 28px 24px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.07);
    position: sticky;
    top: 80px;
}
.form-card h3 {
    font-family: 'Cormorant Garamond', serif;
    font-size: 20px; margin-bottom: 18px;
    color: #1e1e1e;
    padding-bottom: 10px;
    border-bottom: 1px solid #f0f0f0;
}
.form-card label {
    display: block;
    font-size: 12px;
    font-weight: 600;
    color: #666;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    margin: 12px 0 4px;
}
.form-card input[type="text"],
.form-card input[type="number"],
.form-card input[type="file"],
.form-card select,
.form-card textarea {
    width: 100%;
    padding: 9px 12px;
    border: 1.5px solid #e8e8e8;
    border-radius: 8px;
    font-family: 'Jost', sans-serif;
    font-size: 13px;
    outline: none;
    transition: border-color 0.2s;
    box-sizing: border-box;
    background: white;
}
.form-card input:focus,
.form-card select:focus,
.form-card textarea:focus { border-color: #8B2500; }

.form-row-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }

.flash-row {
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 12px 0;
    font-size: 13px;
    color: #444;
}
.flash-row input[type="checkbox"] {
    width: 16px; height: 16px;
    accent-color: #8B2500;
}

.img-preview {
    width: 80px; height: 80px;
    object-fit: cover;
    border-radius: 8px;
    border: 1px solid #eee;
    margin-top: 8px;
    display: block;
}

.btn-add {
    width: 100%;
    padding: 12px;
    background: #8B2500;
    color: white;
    border: none;
    border-radius: 8px;
    font-family: 'Jost', sans-serif;
    font-size: 14px;
    font-weight: 700;
    margin-top: 16px;
    cursor: pointer;
    transition: background 0.2s;
}
.btn-add:hover { background: #5c1800; }
.btn-cancel {
    width: 100%;
    padding: 10px;
    background: #f5f5f5;
    color: #555;
    border: none;
    border-radius: 8px;
    font-family: 'Jost', sans-serif;
    font-size: 13px;
    margin-top: 8px;
    cursor: pointer;
}
.btn-cancel:hover { background: #e8e8e8; }

/* ── Products table ── */
.table-card {
    background: white;
    border-radius: 14px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.07);
    overflow: hidden;
}
.table-card h3 {
    font-family: 'Cormorant Garamond', serif;
    font-size: 20px;
    padding: 20px 24px 14px;
    border-bottom: 1px solid #f0f0f0;
    color: #1e1e1e;
}

/* Search bar in table */
.table-search {
    padding: 12px 24px;
    border-bottom: 1px solid #f0f0f0;
}
.table-search input {
    width: 100%;
    padding: 8px 14px;
    border: 1.5px solid #e8e8e8;
    border-radius: 20px;
    font-family: 'Jost', sans-serif;
    font-size: 13px;
    outline: none;
}
.table-search input:focus { border-color: #8B2500; }

.prod-table { width: 100%; border-collapse: collapse; }
.prod-table th {
    background: #fdf8f8;
    padding: 11px 14px;
    text-align: left;
    font-size: 11px;
    font-weight: 700;
    color: #666;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-bottom: 1px solid #f0f0f0;
}
.prod-table td {
    padding: 10px 14px;
    font-size: 13px;
    border-bottom: 1px solid #f8f8f8;
    vertical-align: middle;
}
.prod-table tr:last-child td { border-bottom: none; }
.prod-table tr:hover td { background: #fdf8f8; }

.prod-img {
    width: 48px; height: 48px;
    object-fit: cover;
    border-radius: 6px;
    background: #f5f5f5;
}
.cat-badge {
    display: inline-block;
    padding: 2px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
    text-transform: capitalize;
}
.cat-traditional { background: #fef0f0; color: #8B2500; }
.cat-dresses      { background: #f0fff0; color: #2e7d32; }
.cat-casual       { background: #e8f4fd; color: #1565c0; }
.cat-accessories  { background: #fef9e7; color: #f57f17; }

.stock-low  { color: #e67e22; font-weight: 600; }
.stock-out  { color: #c0392b; font-weight: 700; }
.stock-ok   { color: #27ae60; }

.flash-yes { color: #c0392b; font-weight: 700; }

.action-btns { display: flex; gap: 8px; }
.btn-edit {
    padding: 5px 14px;
    background: #e8f4fd;
    color: #1565c0;
    border: none;
    border-radius: 5px;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
}
.btn-edit:hover { background: #bbdefb; }
.btn-delete {
    padding: 5px 14px;
    background: #fff0f0;
    color: #c0392b;
    border: none;
    border-radius: 5px;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
}
.btn-delete:hover { background: #ffcdd2; }

.empty-table { text-align: center; padding: 40px; color: #aaa; }

/* Stats row */
.stat-row {
    display: flex;
    gap: 16px;
    margin-bottom: 24px;
    flex-wrap: wrap;
}
.mini-stat {
    background: white;
    border-radius: 10px;
    padding: 14px 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.06);
    text-align: center;
    min-width: 120px;
    border-top: 3px solid #8B2500;
}
.mini-stat .n { font-family: 'Cormorant Garamond', serif; font-size: 28px; font-weight: 700; color: #8B2500; }
.mini-stat .l { font-size: 11px; color: #888; margin-top: 2px; }
</style>
</head>
<body>

<!-- Admin Navbar -->
<div class="admin-nav">
    <span class="logo">☆ La Moda — Admin</span>
    <div style="display:flex;gap:20px;align-items:center;font-size:13px;">
        <a href="admin.php">📊 Dashboard</a>
        <a href="manage_products.php" style="color:white;font-weight:700;">📦 Products</a>
        <a href="index.php" target="_blank">← View Site</a>
        <a href="admin.php?admin_logout=1" style="color:rgba(255,255,255,0.7);">Logout</a>
    </div>
</div>

<div class="mp-wrap">
    <h1 class="mp-title">📦 Manage Products</h1>

    <?php if ($message): ?>
    <div class="msg <?= $msgType ?>"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <!-- Quick stats -->
    <div class="stat-row">
        <div class="mini-stat">
            <div class="n"><?= count($allProducts) ?></div>
            <div class="l">Total Products</div>
        </div>
        <?php
        $cats = ['traditional','dresses','casual','accessories'];
        foreach ($cats as $c):
            $cnt = count(array_filter($allProducts, fn($p) => strtolower((string)($p['category']??'')) === $c));
        ?>
        <div class="mini-stat">
            <div class="n"><?= $cnt ?></div>
            <div class="l"><?= ucfirst($c) ?></div>
        </div>
        <?php endforeach; ?>
        <?php
        $flashCnt = count(array_filter($allProducts, fn($p) => ($p['flash_sale']??'') === 'yes'));
        $outCnt   = count(array_filter($allProducts, fn($p) => (int)($p['stock']??999) === 0));
        ?>
        <div class="mini-stat">
            <div class="n" style="color:#c0392b;"><?= $flashCnt ?></div>
            <div class="l">Flash Deals</div>
        </div>
        <div class="mini-stat">
            <div class="n" style="color:#e67e22;"><?= $outCnt ?></div>
            <div class="l">Out of Stock</div>
        </div>
    </div>

    <div class="mp-grid">

        <!-- ── ADD / EDIT FORM ── -->
        <div class="form-card">
            <h3><?= $editProduct ? '✏️ Edit Product' : '➕ Add New Product' ?></h3>

            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action"
                       value="<?= $editProduct ? 'update' : 'add' ?>">
                <input type="hidden" name="original_name"
                       value="<?= htmlspecialchars((string)($editProduct['name'] ?? '')) ?>">
                <input type="hidden" name="existing_image"
                       value="<?= htmlspecialchars((string)($editProduct['image'] ?? '')) ?>">

                <label>Product Name *</label>
                <input type="text" name="name" required maxlength="200"
                       placeholder="e.g. Floral Kurti Set"
                       value="<?= htmlspecialchars((string)($editProduct['name'] ?? '')) ?>">

                <label>Description</label>
                <textarea name="description" rows="2" maxlength="500"
                    placeholder="Short description of the product…"><?= htmlspecialchars((string)($editProduct['description'] ?? '')) ?></textarea>

                <label>Category *</label>
                <select name="category" required>
                    <option value="">Select category</option>
                    <?php foreach (['traditional','dresses','casual','accessories'] as $c): ?>
                    <option value="<?= $c ?>"
                        <?= strtolower((string)($editProduct['category']??'')) === $c ? 'selected' : '' ?>>
                        <?= ucfirst($c) ?>
                    </option>
                    <?php endforeach; ?>
                </select>

                <label>Subcategory</label>
                <input type="text" name="subcategory" maxlength="100"
                       placeholder="e.g. kurti set, jeans, watch, ring"
                       value="<?= htmlspecialchars((string)($editProduct['subcategory'] ?? '')) ?>">
                <small style="color:#aaa;font-size:11px;">
                    Casual: shirt, t-shirt, top, jeans, trouser, skirt, bottom<br>
                    Accessories: jewellery, watch, bag, ring, necklace, earring, bracelet, handbag
                </small>

                <div class="form-row-2" style="margin-top:8px;">
                    <div>
                        <label>Old Price (₹)</label>
                        <input type="number" name="old_price" min="0"
                               placeholder="e.g. 1299"
                               value="<?= (int)($editProduct['old_price'] ?? 0) ?>">
                    </div>
                    <div>
                        <label>New Price (₹) *</label>
                        <input type="number" name="new_price" min="1" required
                               placeholder="e.g. 899"
                               value="<?= (int)($editProduct['new_price'] ?? 0) ?>">
                    </div>
                </div>

                <div class="form-row-2">
                    <div>
                        <label>Stock Quantity</label>
                        <input type="number" name="stock" min="0"
                               placeholder="e.g. 50"
                               value="<?= (int)($editProduct['stock'] ?? 0) ?>">
                    </div>
                    <div>
                        <label>Sizes (comma separated)</label>
                        <input type="text" name="sizes"
                               placeholder="XS,S,M,L,XL"
                               value="<?php
                                   $sz = $editProduct['sizes'] ?? [];
                                   if (is_object($sz)) $sz = iterator_to_array($sz);
                                   echo htmlspecialchars(implode(',', (array)$sz));
                               ?>">
                    </div>
                </div>

                <label>Product Image</label>
                <?php if (!empty($editProduct['image'])): ?>
                <img src="images/<?= htmlspecialchars((string)$editProduct['image']) ?>"
                     class="img-preview" id="imgPreview"
                     onerror="this.src='https://placehold.co/80x80/f5f5f5/aaa?text=?'">
                <?php else: ?>
                <img src="https://placehold.co/80x80/f5f5f5/aaa?text=No+Image"
                     class="img-preview" id="imgPreview">
                <?php endif; ?>
                <input type="file" name="image" accept="image/*" id="imgInput"
                       style="margin-top:6px;">
                <small style="color:#aaa;font-size:11px;">
                    JPG/PNG/WEBP, max 5MB. Leave blank to keep existing image.
                </small>

                <label class="flash-row" style="text-transform:none;letter-spacing:0;font-size:13px;font-weight:500;">
                    <input type="checkbox" name="flash_sale" value="1"
                        <?= ($editProduct['flash_sale'] ?? '') === 'yes' ? 'checked' : '' ?>>
                    ⚡ Include in Flash Deals
                </label>

                <button type="submit" class="btn-add">
                    <?= $editProduct ? '💾 Save Changes' : '➕ Add Product' ?>
                </button>

                <?php if ($editProduct): ?>
                <a href="manage_products.php">
                    <button type="button" class="btn-cancel">✕ Cancel Edit</button>
                </a>
                <?php endif; ?>
            </form>
        </div>

        <!-- ── PRODUCTS TABLE ── -->
        <div class="table-card">
            <h3>All Products (<?= count($allProducts) ?>)</h3>

            <div class="table-search">
                <input type="text" id="tableSearch" placeholder="🔍  Search products…">
            </div>

            <table class="prod-table" id="prodTable">
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Subcategory</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Flash</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($allProducts)): ?>
                <tr>
                    <td colspan="8" class="empty-table">
                        No products yet. Add your first product using the form.
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($allProducts as $p):
                    $pCat   = strtolower((string)($p['category'] ?? ''));
                    $pStock = isset($p['stock']) ? (int)$p['stock'] : null;
                    $pImg   = (string)($p['image'] ?? '');
                    $pImgSrc = $pImg
                        ? (strpos($pImg,'http')===0 ? $pImg : "images/" . $pImg)
                        : "https://placehold.co/48x48/f5f5f5/aaa?text=?";
                ?>
                <tr class="prod-row">
                    <td>
                        <img src="<?= htmlspecialchars($pImgSrc) ?>"
                             class="prod-img"
                             onerror="this.src='https://placehold.co/48x48/f5f5f5/aaa?text=?'">
                    </td>
                    <td><strong><?= htmlspecialchars((string)($p['name'] ?? '')) ?></strong></td>
                    <td>
                        <span class="cat-badge cat-<?= htmlspecialchars($pCat) ?>">
                            <?= htmlspecialchars(ucfirst($pCat)) ?>
                        </span>
                    </td>
                    <td><?= htmlspecialchars((string)($p['subcategory'] ?? '—')) ?></td>
                    <td>
                        <?php if ((int)($p['old_price']??0) > 0): ?>
                        <span style="text-decoration:line-through;color:#bbb;font-size:11px;">
                            ₹<?= (int)$p['old_price'] ?>
                        </span><br>
                        <?php endif; ?>
                        <strong>₹<?= (int)($p['new_price'] ?? 0) ?></strong>
                    </td>
                    <td>
                        <?php if ($pStock === null): ?>
                        <span style="color:#aaa;">—</span>
                        <?php elseif ($pStock === 0): ?>
                        <span class="stock-out">OUT</span>
                        <?php elseif ($pStock <= 5): ?>
                        <span class="stock-low"><?= $pStock ?> left</span>
                        <?php else: ?>
                        <span class="stock-ok"><?= $pStock ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (($p['flash_sale']??'') === 'yes'): ?>
                        <span class="flash-yes">⚡ Yes</span>
                        <?php else: ?>
                        <span style="color:#aaa;">No</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="action-btns">
                            <a href="?edit=<?= urlencode((string)($p['name']??'')) ?>"
                               class="btn-edit">Edit</a>
                            <a href="?delete=<?= urlencode((string)($p['name']??'')) ?>"
                               class="btn-delete"
                               onclick="return confirm('Delete \'<?= addslashes((string)($p['name']??'')) ?>\'? This cannot be undone.')">
                               Delete
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>
</div>

<script>
// Live image preview when file is selected
document.getElementById("imgInput")?.addEventListener("change", function() {
    const file = this.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = e => {
        document.getElementById("imgPreview").src = e.target.result;
    };
    reader.readAsDataURL(file);
});

// Live table search
document.getElementById("tableSearch")?.addEventListener("input", function() {
    const q = this.value.toLowerCase();
    document.querySelectorAll(".prod-row").forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(q) ? "" : "none";
    });
});

// Scroll to form when editing
<?php if ($editProduct): ?>
window.scrollTo({ top: 0, behavior: 'smooth' });
<?php endif; ?>
</script>

</body>
</html>
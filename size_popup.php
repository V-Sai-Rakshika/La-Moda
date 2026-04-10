<?php
/**
 * size_popup.php
 * Include ONCE per page (before </body>) on any page that uses product cards.
 * Provides: size selection popup + size chart popup
 * Triggered when user clicks Add to Cart or Buy Now on a card that needs size.
 */
?>

<!-- ═══════════════════════════════════════════════════════
     SIZE SELECTION POPUP
═══════════════════════════════════════════════════════ -->
<div id="sizePopupOverlay"
     style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.52);z-index:9500;
            align-items:center;justify-content:center;padding:16px;">
<div id="sizePopupBox"
     style="background:#fff;border-radius:16px;padding:28px 24px 24px;
            width:440px;max-width:100%;position:relative;box-shadow:0 8px 40px rgba(0,0,0,.15);">

  <button onclick="closeSizePopup()"
          style="position:absolute;top:12px;right:14px;background:none;border:none;
                 font-size:22px;cursor:pointer;color:#999;line-height:1;">✕</button>

  <h2 style="font-family:var(--font-display,serif);font-size:20px;margin-bottom:4px;">Select Size</h2>
  <p id="sizePopupProductName"
     style="font-size:12px;color:#aaa;margin-bottom:18px;font-style:italic;"></p>

  <div id="sizeChipsPopup"
       style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:16px;"></div>

  <p id="sizePopupErr"
     style="display:none;font-size:12px;color:#dc2626;font-weight:600;margin-bottom:12px;">
    ⚠ Please select a size to continue.
  </p>

  <div style="display:flex;gap:10px;align-items:center;">
    <button id="sizePopupConfirmBtn"
            onclick="confirmSizePopup()"
            style="flex:1;padding:12px;background:#8B2500;color:#fff;border:none;border-radius:8px;
                   font-size:14px;font-weight:700;cursor:pointer;font-family:inherit;transition:background .2s;">
      Add to Cart ✓
    </button>
    <button onclick="openSizeChart()"
            style="padding:10px 16px;border:1.5px solid #8B2500;color:#8B2500;background:#fff;
                   border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;font-family:inherit;
                   white-space:nowrap;">
      Size Chart
    </button>
  </div>
</div>
</div>

<!-- ═══════════════════════════════════════════════════════
     SIZE CHART POPUP
═══════════════════════════════════════════════════════ -->
<div id="sizeChartOverlay"
     style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:9600;
            align-items:center;justify-content:center;padding:16px;overflow-y:auto;">
<div style="background:#fff;border-radius:16px;padding:28px 24px 24px;
            width:560px;max-width:100%;position:relative;box-shadow:0 8px 40px rgba(0,0,0,.2);margin:auto;">

  <button onclick="closeSizeChart()"
          style="position:absolute;top:12px;right:14px;background:none;border:none;
                 font-size:22px;cursor:pointer;color:#999;line-height:1;">✕</button>

  <h2 style="font-family:var(--font-display,serif);font-size:20px;margin-bottom:16px;">📏 Size Chart</h2>

  <!-- Women's Clothing -->
  <p style="font-size:12px;font-weight:700;color:#8B2500;text-transform:uppercase;letter-spacing:.4px;margin-bottom:8px;">Women's Clothing</p>
  <div style="overflow-x:auto;margin-bottom:18px;">
  <table style="width:100%;border-collapse:collapse;font-size:12px;">
    <thead>
      <tr style="background:#fff5f2;">
        <th style="padding:8px 12px;text-align:left;border:1px solid #f0e0dc;">Size</th>
        <th style="padding:8px 12px;text-align:center;border:1px solid #f0e0dc;">Bust (cm)</th>
        <th style="padding:8px 12px;text-align:center;border:1px solid #f0e0dc;">Waist (cm)</th>
        <th style="padding:8px 12px;text-align:center;border:1px solid #f0e0dc;">Hip (cm)</th>
        <th style="padding:8px 12px;text-align:center;border:1px solid #f0e0dc;">UK Size</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ([
        ['XS',  '78–80',  '60–62',  '84–86',  '6'],
        ['S',   '82–86',  '64–68',  '88–92',  '8'],
        ['M',   '88–92',  '70–74',  '94–98',  '10-12'],
        ['L',   '94–98',  '76–80',  '100–104','14'],
        ['XL',  '100–104','82–86',  '106–110','16'],
        ['XXL', '106–110','88–92',  '112–116','18'],
        ['XXXL','112–118','94–100', '118–124','20'],
      ] as [$sz,$bust,$waist,$hip,$uk]): ?>
      <tr style="border-bottom:1px solid #f5f5f5;">
        <td style="padding:7px 12px;font-weight:700;border:1px solid #f0e0dc;color:#8B2500;"><?= $sz ?></td>
        <td style="padding:7px 12px;text-align:center;border:1px solid #f0e0dc;"><?= $bust ?></td>
        <td style="padding:7px 12px;text-align:center;border:1px solid #f0e0dc;"><?= $waist ?></td>
        <td style="padding:7px 12px;text-align:center;border:1px solid #f0e0dc;"><?= $hip ?></td>
        <td style="padding:7px 12px;text-align:center;border:1px solid #f0e0dc;"><?= $uk ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  </div>

  <!-- Men's Clothing -->
  <p style="font-size:12px;font-weight:700;color:#8B2500;text-transform:uppercase;letter-spacing:.4px;margin-bottom:8px;">Men's Clothing</p>
  <div style="overflow-x:auto;margin-bottom:18px;">
  <table style="width:100%;border-collapse:collapse;font-size:12px;">
    <thead>
      <tr style="background:#fff5f2;">
        <th style="padding:8px 12px;text-align:left;border:1px solid #f0e0dc;">Size</th>
        <th style="padding:8px 12px;text-align:center;border:1px solid #f0e0dc;">Chest (cm)</th>
        <th style="padding:8px 12px;text-align:center;border:1px solid #f0e0dc;">Waist (cm)</th>
        <th style="padding:8px 12px;text-align:center;border:1px solid #f0e0dc;">UK Size</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ([
        ['S',    '86–90',  '70–74',  '36'],
        ['M',    '92–96',  '76–80',  '38-40'],
        ['L',    '98–102', '82–86',  '42'],
        ['XL',   '104–108','88–92',  '44'],
        ['XXL',  '110–114','94–98',  '46'],
        ['XXXL', '116–120','100–104','48'],
      ] as [$sz,$chest,$waist,$uk]): ?>
      <tr>
        <td style="padding:7px 12px;font-weight:700;border:1px solid #f0e0dc;color:#8B2500;"><?= $sz ?></td>
        <td style="padding:7px 12px;text-align:center;border:1px solid #f0e0dc;"><?= $chest ?></td>
        <td style="padding:7px 12px;text-align:center;border:1px solid #f0e0dc;"><?= $waist ?></td>
        <td style="padding:7px 12px;text-align:center;border:1px solid #f0e0dc;"><?= $uk ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  </div>

  <p style="font-size:11px;color:#aaa;margin-bottom:16px;">
    💡 Tip: If between sizes, we recommend sizing up for a comfortable fit.
  </p>
  <button onclick="closeSizeChart()"
          style="width:100%;padding:11px;background:#8B2500;color:#fff;border:none;
                 border-radius:8px;font-size:13px;font-weight:700;cursor:pointer;font-family:inherit;">
    ← Back to Size Selection
  </button>
</div>
</div>

<script>
/* ══════════════════════════════════════════════════════════════
   Size Popup state
══════════════════════════════════════════════════════════════ */
let _spSelectedSize  = '';
let _spPendingAction = null;  // { type: 'cart'|'buynow', btn: DOMElement }

function openSizePopup(btn, action) {
    _spSelectedSize  = '';
    _spPendingAction = { type: action, btn: btn };
    document.getElementById('sizePopupErr').style.display = 'none';

    const name = btn.dataset.name || '';
    document.getElementById('sizePopupProductName').textContent = name;

    // Update confirm button label
    const confirmBtn = document.getElementById('sizePopupConfirmBtn');
    confirmBtn.textContent = action === 'buynow' ? 'Buy Now →' : 'Add to Cart ✓';

    // Build size chips from product data  
    // Try to get sizes from page (if product page) or from card dataset
    const card    = btn.closest('.card') || btn.closest('.product-card');
    const sswDiv  = card ? (card.querySelector('[id^="ssw-"]') || card.querySelector('.size-selector')) : null;
    let   sizes   = [];

    if (sswDiv) {
        sswDiv.querySelectorAll('.sz-chip').forEach(c => sizes.push(c.dataset.sz));
    }
    // Fallback: try data attribute on button
    if (!sizes.length && btn.dataset.sizes) {
        sizes = btn.dataset.sizes.split(',').map(s => s.trim()).filter(Boolean);
    }
    // Fallback: default clothing sizes
    if (!sizes.length) {
        sizes = ['XS','S','M','L','XL','XXL','XXXL'];
    }

    const chipsDiv = document.getElementById('sizeChipsPopup');
    chipsDiv.innerHTML = sizes.map(sz => `
        <button onclick="selectPopupSize('${escHtml(sz)}')"
                id="szpop-${escHtml(sz)}"
                style="padding:8px 16px;border:1.5px solid #e0e0e0;border-radius:8px;
                       font-size:13px;font-weight:600;cursor:pointer;background:#fff;
                       transition:all .2s;font-family:inherit;">
            ${escHtml(sz)}
        </button>`).join('');

    // Show popup
    const ov = document.getElementById('sizePopupOverlay');
    ov.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function selectPopupSize(sz) {
    _spSelectedSize = sz;
    document.getElementById('sizePopupErr').style.display = 'none';
    // Highlight selected
    document.querySelectorAll('#sizeChipsPopup button').forEach(b => {
        const isThis = b.id === 'szpop-' + sz;
        b.style.background   = isThis ? '#8B2500' : '#fff';
        b.style.borderColor  = isThis ? '#8B2500' : '#e0e0e0';
        b.style.color        = isThis ? '#fff'    : '';
    });
}

function closeSizePopup() {
    document.getElementById('sizePopupOverlay').style.display = 'none';
    document.body.style.overflow = '';
    _spPendingAction = null;
    _spSelectedSize  = '';
}

function confirmSizePopup() {
    if (!_spSelectedSize) {
        document.getElementById('sizePopupErr').style.display = 'block';
        return;
    }
    if (!_spPendingAction) { closeSizePopup(); return; }

    const btn    = _spPendingAction.btn;
    const action = _spPendingAction.type;
    closeSizePopup();

    // Write size onto the button
    btn.dataset.size = _spSelectedSize;

    // Also sync onto the card's other button
    const card    = btn.closest('.card') || btn.closest('.product-card');
    if (card) {
        const addBtn = card.querySelector('.add-btn');
        const buyBtn = card.querySelector('.buy-now-btn');
        if (addBtn) addBtn.dataset.size = _spSelectedSize;
        if (buyBtn) buyBtn.dataset.size = _spSelectedSize;
        // Also update the size-selector chips (if present)
        const sswDiv = card.querySelector('[id^="ssw-"]') || card.querySelector('.size-selector');
        if (sswDiv) {
            sswDiv.querySelectorAll('.sz-chip').forEach(c => {
                const isThis = c.dataset.sz === _spSelectedSize;
                c.style.background  = isThis ? '#16a34a' : '#fff';
                c.style.borderColor = isThis ? '#16a34a' : '#d1d5db';
                c.style.color       = isThis ? '#fff'    : '';
                if (isThis && c.dataset.sz !== undefined) {
                    // trigger the chip's click logic
                    c.click();
                }
            });
        }
    }

    if (action === 'cart') {
        if (typeof addToCart === 'function') addToCart(btn);
    } else if (action === 'buynow') {
        if (typeof openBuyNow === 'function') openBuyNow(btn.dataset.name, _spSelectedSize, btn.dataset.price || 0);
    }
}

function openSizeChart() {
    document.getElementById('sizeChartOverlay').style.display = 'flex';
}
function closeSizeChart() {
    document.getElementById('sizeChartOverlay').style.display = 'none';
}

function escHtml(s) {
    return String(s).replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

/* ══════════════════════════════════════════════════════════════
   Intercept add-btn and buy-now-btn clicks BEFORE navbar handler
   to show size popup if size is needed
══════════════════════════════════════════════════════════════ */
document.addEventListener('click', function(e) {
    // Add to cart
    if (e.target.classList.contains('add-btn') && !e.target.disabled) {
        if (e.target.dataset.needsSize === '1' && !e.target.dataset.size) {
            e.stopImmediatePropagation();
            if (typeof isLoggedIn !== 'undefined' && !isLoggedIn) {
                if (typeof openLogin === 'function') openLogin();
                return;
            }
            openSizePopup(e.target, 'cart');
            return;
        }
    }
    // Buy now
    const buyBtn = e.target.closest('.buy-now-btn');
    if (buyBtn && !buyBtn.disabled) {
        if (buyBtn.dataset.needsSize === '1' && !buyBtn.dataset.size) {
            e.stopImmediatePropagation();
            if (typeof isLoggedIn !== 'undefined' && !isLoggedIn) {
                if (typeof openLogin === 'function') openLogin();
                return;
            }
            openSizePopup(buyBtn, 'buynow');
            return;
        }
    }
}, true); // capture phase — runs before all other handlers

// Close on backdrop click
document.getElementById('sizePopupOverlay')?.addEventListener('click', function(e) {
    if (e.target === this) closeSizePopup();
});
document.getElementById('sizeChartOverlay')?.addEventListener('click', function(e) {
    if (e.target === this) closeSizeChart();
});
</script>
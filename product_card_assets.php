<?php
/**
 * _product_card_assets.php
 * Include ONCE per page (at bottom of body) after _product_card.php is used.
 * Handles: size selection, size-required enforcement, stock display.
 */
?>
<style>
/* ── Size selector ── */
.size-label{font-size:10px;font-weight:600;color:#888;text-transform:uppercase;letter-spacing:.4px;margin-bottom:4px;}
.size-chips{display:flex;flex-wrap:wrap;gap:4px;margin-bottom:6px;}
.sz-chip{
  padding:4px 9px;border:1.5px solid #e0e0e0;border-radius:6px;
  font-size:11px;font-weight:600;cursor:pointer;transition:all .15s;user-select:none;color:#444;
}
.sz-chip:hover{border-color:#8B2500;color:#8B2500;}
.sz-chip.on{border-color:#8B2500;background:#8B2500;color:#fff;}
.size-err{font-size:10px;color:#dc2626;display:none;margin-top:2px;font-weight:600;}
.size-err.show{display:block;}

/* ── Stock badges ── */
.stock-out{color:#dc2626;font-size:11px;font-weight:700;}
.stock-low{color:#f59e0b;font-size:11px;font-weight:700;}

/* ── Discount badge ── */
.discount-badge{
  position:absolute;top:10px;left:10px;
  background:#dc2626;color:#fff;
  padding:3px 8px;border-radius:6px;
  font-size:11px;font-weight:700;z-index:2;
}

/* ── Size-required pulse on add attempt ── */
@keyframes sz-shake{0%,100%{transform:translateX(0)}25%{transform:translateX(-4px)}75%{transform:translateX(4px)}}
.sz-shake{animation:sz-shake .3s ease;}
</style>

<script>
/* ────────────────────────────────────────────────
   Size chip selection
──────────────────────────────────────────────── */
document.addEventListener('click', function(e) {
  const chip = e.target.closest('.sz-chip');
  if (!chip) return;
  const selector = chip.closest('.size-selector');
  if (!selector) return;

  // Toggle selection
  selector.querySelectorAll('.sz-chip').forEach(c => c.classList.remove('on'));
  chip.classList.add('on');
  selector.querySelector('.size-err')?.classList.remove('show');

  const pname = selector.dataset.product;
  const sz    = chip.dataset.sz;

  // Sync data-size onto Add to Cart btn
  const cc = document.getElementById('cc-' + encodeURIComponent(pname));
  if (cc) {
    const addBtn = cc.querySelector('.add-btn'); if (addBtn) addBtn.dataset.size = sz;
    const qtyBox = cc.querySelector('.qty-box'); if (qtyBox) qtyBox.dataset.size = sz;
  }
  // Sync data-size onto Buy Now btn
  document.querySelectorAll('.buy-now-btn').forEach(b => {
    if (b.dataset.name === pname) b.dataset.size = sz;
  });
});

/* ────────────────────────────────────────────────
   Intercept Add-to-Cart: enforce size for clothing
──────────────────────────────────────────────── */
// Override the global click handler's add-btn path
// We patch into the existing navbar.js flow by checking data-needs-size
document.addEventListener('click', function(e) {
  if (!e.target.classList.contains('add-btn')) return;
  const btn = e.target;
  if (btn.dataset.needsSize !== '1') return; // accessories pass through
  if (!btn.dataset.size) {
    e.stopImmediatePropagation(); // block the real addToCart call
    // Find the size selector for this product
    const pname   = btn.dataset.name;
    const sel = document.querySelector(`.size-selector[data-product="${CSS.escape(pname)}"]`);
    if (sel) {
      const errEl = sel.querySelector('.size-err');
      if (errEl) errEl.classList.add('show');
      sel.classList.add('sz-shake');
      setTimeout(() => sel.classList.remove('sz-shake'), 400);
      sel.scrollIntoView({ behavior: 'smooth', block: 'center' });
    } else {
      if (typeof showToast === 'function') showToast('Please select a size first', 'error');
    }
    return;
  }
}, true); // capture phase so we run BEFORE the navbar handler

/* ────────────────────────────────────────────────
   Intercept Buy Now: enforce size for clothing
──────────────────────────────────────────────── */
document.addEventListener('click', function(e) {
  const buyBtn = e.target.closest('.buy-now-btn');
  if (!buyBtn) return;
  if (buyBtn.dataset.needsSize !== '1') return;
  if (!buyBtn.dataset.size) {
    e.stopImmediatePropagation();
    const pname = buyBtn.dataset.name;
    const sel   = document.querySelector(`.size-selector[data-product="${CSS.escape(pname)}"]`);
    if (sel) {
      const errEl = sel.querySelector('.size-err');
      if (errEl) errEl.classList.add('show');
      sel.classList.add('sz-shake');
      setTimeout(() => sel.classList.remove('sz-shake'), 400);
      sel.scrollIntoView({ behavior: 'smooth', block: 'center' });
    } else {
      if (typeof showToast === 'function') showToast('Please select a size first', 'error');
    }
    return;
  }
}, true); // capture phase
</script>
<?php
/**
 * size_modal.php
 * Include ONCE per page right after: <?php include 'navbar.php'; ?>
 * Uses your existing .modal and .modal-box CSS — zero new styles added.
 */
?>

<!-- SIZE PICK MODAL -->
<div id="sizePickModal" class="modal">
    <div class="modal-box">
        <button class="modal-close" onclick="closeSizePick()">✕</button>
        <h2>Select Size</h2>
        <p class="modal-sub" id="sizePickProductName" style="color:var(--brand);font-weight:600;font-size:14px;margin-bottom:6px;"></p>
        <p class="modal-sub">Pick a size to continue</p>

        <div id="sizePickChips" style="display:flex;flex-wrap:wrap;gap:8px;justify-content:center;margin:16px 0;"></div>

        <p id="sizePickErr" style="font-size:12px;color:#dc2626;display:none;margin-bottom:10px;">⚠ Please select a size first</p>

        <a href="#" onclick="openSizeChart();return false;"
           style="display:inline-block;font-size:12px;color:var(--brand);text-decoration:underline;margin-bottom:16px;">
            📏 View Size Chart
        </a>

        <button id="sizePickConfirmBtn" onclick="confirmSizePick()" class="form-submit-btn">
            Continue
        </button>
    </div>
</div>

<!-- SIZE CHART MODAL -->
<div id="sizeChartModal" class="modal">
    <div class="modal-box modal-box--wide" style="text-align:left;">
        <button class="modal-close" onclick="closeSizeChart()">✕</button>
        <h2 style="text-align:center;">📏 Size Chart</h2>
        <p class="modal-sub" style="text-align:center;">All measurements in centimetres (cm)</p>
        <div style="overflow-x:auto;margin-top:14px;">
            <table style="width:100%;border-collapse:collapse;font-size:13px;">
                <thead>
                    <tr style="background:var(--brand);color:#fff;">
                        <th style="padding:9px 12px;text-align:left;border-radius:6px 0 0 0;">Size</th>
                        <th style="padding:9px 12px;text-align:center;">Chest</th>
                        <th style="padding:9px 12px;text-align:center;">Waist</th>
                        <th style="padding:9px 12px;text-align:center;">Hip</th>
                        <th style="padding:9px 12px;text-align:center;border-radius:0 6px 0 0;">Length</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ([
                        ['XS',  '76–80',  '60–64',  '84–88',  '57–59'],
                        ['S',   '80–86',  '64–70',  '88–94',  '59–61'],
                        ['M',   '86–92',  '70–76',  '94–100', '61–63'],
                        ['L',   '92–98',  '76–82',  '100–106','63–65'],
                        ['XL',  '98–104', '82–88',  '106–112','65–67'],
                        ['XXL', '104–112','88–96',  '112–120','67–69'],
                        ['XXXL','112–120','96–104', '120–128','69–71'],
                    ] as $i => [$sz, $ch, $wa, $hi, $le]): ?>
                    <tr style="background:<?= $i % 2 === 0 ? '#fff' : '#faf9f8' ?>;">
                        <td style="padding:8px 12px;font-weight:700;color:var(--brand);"><?= $sz ?></td>
                        <td style="padding:8px 12px;text-align:center;color:#555;"><?= $ch ?></td>
                        <td style="padding:8px 12px;text-align:center;color:#555;"><?= $wa ?></td>
                        <td style="padding:8px 12px;text-align:center;color:#555;"><?= $hi ?></td>
                        <td style="padding:8px 12px;text-align:center;color:#555;"><?= $le ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p style="font-size:11px;color:#aaa;margin-top:12px;line-height:1.7;">
            <b>Chest</b> — measure around fullest part &nbsp;|&nbsp;
            <b>Waist</b> — natural waistline &nbsp;|&nbsp;
            <b>Hip</b> — fullest part of hips &nbsp;|&nbsp;
            <b>Length</b> — shoulder to hem
        </p>
        <button onclick="closeSizeChart()" class="modal-choice-btn" style="margin-top:16px;">
            ← Back to Size Selection
        </button>
    </div>
</div>

<script>
/* ══════════════════════════════════════════
   Size popup state
══════════════════════════════════════════ */
var _szBtn    = null;
var _szAction = 'cart';
var _szChosen = '';

function openSizePick(btn, action) {
    _szBtn    = btn;
    _szAction = action || 'cart';
    _szChosen = '';

    var name  = btn.dataset.name || '';
    var sizes = [];
    try { sizes = JSON.parse(btn.dataset.sizes || '[]'); } catch(e) {}

    document.getElementById('sizePickProductName').textContent = name;
    document.getElementById('sizePickErr').style.display       = 'none';
    document.getElementById('sizePickConfirmBtn').textContent  =
        action === 'buynow' ? 'Buy Now →' : 'Add to Cart';

    var wrap = document.getElementById('sizePickChips');
    wrap.innerHTML = sizes.map(function(sz) {
        return '<button type="button" class="sz-pick-chip" onclick="chooseSzChip(this,\'' + sz + '\')">' + sz + '</button>';
    }).join('');

    document.getElementById('sizePickModal').style.display = 'block';
    document.body.style.overflow = 'hidden';
}

function chooseSzChip(el, sz) {
    _szChosen = sz;
    document.querySelectorAll('#sizePickChips .sz-pick-chip').forEach(function(c) {
        c.style.background   = '#fff';
        c.style.borderColor  = 'var(--border)';
        c.style.color        = 'var(--text)';
    });
    el.style.background  = 'var(--brand)';
    el.style.borderColor = 'var(--brand)';
    el.style.color       = '#fff';
    document.getElementById('sizePickErr').style.display = 'none';
}

function confirmSizePick() {
    if (!_szChosen) {
        document.getElementById('sizePickErr').style.display = 'block';
        return;
    }
    if (_szBtn) _szBtn.dataset.size = _szChosen;
    closeSizePick();

    if (_szAction === 'buynow' && _szBtn) {
        if (typeof openBuyNow === 'function') {
            openBuyNow(_szBtn.dataset.name, _szChosen, _szBtn.dataset.price || 0);
        }
    } else if (_szBtn) {
        _szBtn.disabled = true;
        if (typeof addToCart === 'function') addToCart(_szBtn);
    }
}

function closeSizePick() {
    document.getElementById('sizePickModal').style.display = 'none';
    document.body.style.overflow = '';
}

function openSizeChart() {
    document.getElementById('sizeChartModal').style.display = 'block';
}

function closeSizeChart() {
    document.getElementById('sizeChartModal').style.display = 'none';
}

/* Close on backdrop click */
document.getElementById('sizePickModal').addEventListener('click', function(e) {
    if (e.target === this) closeSizePick();
});
document.getElementById('sizeChartModal').addEventListener('click', function(e) {
    if (e.target === this) closeSizeChart();
});
</script>

<style>
/* Size pick chips — match your existing sz-chip style */
.sz-pick-chip {
    padding: 8px 18px;
    border: 1.5px solid var(--border);
    border-radius: var(--radius-sm);
    background: var(--white);
    font-size: 13px;
    font-weight: 600;
    color: var(--text);
    cursor: pointer;
    font-family: var(--font-body);
    transition: all var(--t);
    min-width: 52px;
}
.sz-pick-chip:hover {
    border-color: var(--brand);
    color: var(--brand);
}
</style>
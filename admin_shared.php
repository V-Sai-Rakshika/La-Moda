/* ═══════════════════════════════════════════════════
   admin_shared.css — La Moda Admin Panel
   Shared by admin.php AND manage_products.php
   ═══════════════════════════════════════════════════ */

@import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap');

*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
html { scroll-behavior: smooth; }

body {
    font-family: 'Plus Jakarta Sans', sans-serif;
    background: #faf8f5;
    color: #1a1a1a;
    min-height: 100vh;
}
a { text-decoration: none; color: inherit; }
button { cursor: pointer; font-family: inherit; }
img { display: block; max-width: 100%; }

/* ══════════════════════════════════
   SIDEBAR
══════════════════════════════════ */
.admin-sidebar {
    position: fixed;
    top: 0; left: 0;
    width: 220px;
    height: 100vh;
    background: #fff;
    border-right: 1px solid #f0ede8;
    display: flex;
    flex-direction: column;
    z-index: 200;
    overflow-y: auto;
}

.sidebar-brand {
    padding: 22px 20px 18px;
    border-bottom: 1px solid #f0ede8;
    display: flex;
    align-items: center;
    gap: 10px;
}
.brand-icon {
    width: 36px; height: 36px;
    background: linear-gradient(135deg, #e8590c, #f7a04c);
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 18px;
    flex-shrink: 0;
}
.brand-name {
    font-size: 16px; font-weight: 700;
    color: #1a1a1a; letter-spacing: 0.5px;
}
.brand-sub { font-size: 10px; color: #9ca3af; }

.sidebar-nav { flex: 1; padding: 12px 10px; }

.nav-group-label {
    font-size: 10px; font-weight: 700;
    color: #c4b5a5; text-transform: uppercase;
    letter-spacing: 1px;
    padding: 12px 12px 4px;
}

.nav-link {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 12px;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 500;
    color: #6b7280;
    transition: all 0.18s;
    margin-bottom: 1px;
}
.nav-link:hover { background: #faf8f5; color: #1a1a1a; }
.nav-link.active {
    background: linear-gradient(135deg, rgba(232,89,12,0.12), rgba(247,160,76,0.08));
    color: #e8590c;
    font-weight: 600;
}
.nav-link .ni { font-size: 16px; width: 20px; text-align: center; flex-shrink: 0; }

.sidebar-bottom {
    padding: 12px 10px 16px;
    border-top: 1px solid #f0ede8;
}
.admin-profile {
    display: flex; align-items: center; gap: 10px;
    padding: 10px 12px;
    border-radius: 10px;
    background: #faf8f5;
    margin-bottom: 8px;
}
.admin-avatar {
    width: 34px; height: 34px;
    background: linear-gradient(135deg,#e8590c,#f7a04c);
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 13px; font-weight: 700; color: white;
    flex-shrink: 0;
}
.admin-name { font-size: 12px; font-weight: 600; color: #1a1a1a; }
.admin-role { font-size: 10px; color: #9ca3af; }
.logout-link {
    display: flex; align-items: center; gap: 8px;
    padding: 9px 12px;
    border-radius: 10px;
    font-size: 12px; color: #9ca3af;
    transition: all 0.18s;
}
.logout-link:hover { background: #fff0eb; color: #e8590c; }

/* ══════════════════════════════════
   MAIN CONTENT AREA
══════════════════════════════════ */
.admin-main {
    margin-left: 220px;
    min-height: 100vh;
    display: flex;
    flex-direction: column;
}

/* Top Header */
.admin-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 28px;
    background: #fff;
    border-bottom: 1px solid #f0ede8;
    position: sticky;
    top: 0;
    z-index: 100;
}
.header-left h2 {
    font-size: 18px; font-weight: 700; color: #1a1a1a;
}
.header-left p { font-size: 12px; color: #9ca3af; margin-top: 1px; }

.header-right { display: flex; align-items: center; gap: 12px; }

.header-search {
    display: flex;
    align-items: center;
    gap: 8px;
    background: #faf8f5;
    border: 1px solid #f0ede8;
    border-radius: 10px;
    padding: 8px 14px;
    font-size: 13px; color: #9ca3af;
}
.header-search input {
    background: none; border: none; outline: none;
    font-size: 13px; color: #1a1a1a;
    font-family: inherit; width: 160px;
}

.header-date {
    font-size: 12px; color: #9ca3af;
    background: #faf8f5;
    border: 1px solid #f0ede8;
    border-radius: 8px;
    padding: 7px 14px;
}
.header-btn {
    padding: 8px 18px;
    background: linear-gradient(135deg,#e8590c,#f7a04c);
    color: white;
    border: none;
    border-radius: 10px;
    font-size: 13px; font-weight: 600;
    transition: opacity 0.2s;
}
.header-btn:hover { opacity: 0.88; }

/* ══════════════════════════════════
   PAGE CONTENT
══════════════════════════════════ */
.admin-content { padding: 24px 28px; flex: 1; }

/* ══════════════════════════════════
   KPI CARDS
══════════════════════════════════ */
.kpi-row {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
    margin-bottom: 24px;
}
.kpi-card {
    background: #fff;
    border: 1px solid #f0ede8;
    border-radius: 14px;
    padding: 20px 22px;
    position: relative;
    overflow: hidden;
    transition: box-shadow 0.2s, transform 0.2s;
}
.kpi-card:hover { box-shadow: 0 8px 24px rgba(0,0,0,0.07); transform: translateY(-2px); }

.kpi-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    margin-bottom: 14px;
}
.kpi-label-sm {
    font-size: 12px; font-weight: 500; color: #9ca3af;
    text-transform: uppercase; letter-spacing: 0.5px;
}
.kpi-icon-wrap {
    width: 38px; height: 38px;
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 18px;
    flex-shrink: 0;
}
.kpi-icon-orange { background: #fff4ec; }
.kpi-icon-blue   { background: #eff6ff; }
.kpi-icon-green  { background: #f0fdf4; }
.kpi-icon-purple { background: #faf5ff; }

.kpi-value {
    font-size: 28px; font-weight: 700;
    color: #1a1a1a; line-height: 1;
    margin-bottom: 8px;
}
.kpi-footer { display: flex; align-items: center; gap: 6px; }
.kpi-badge-up   { font-size: 11px; font-weight: 700; color: #22c55e; background: #f0fdf4; padding: 2px 8px; border-radius: 20px; }
.kpi-badge-down { font-size: 11px; font-weight: 700; color: #ef4444; background: #fef2f2; padding: 2px 8px; border-radius: 20px; }
.kpi-foot-label { font-size: 11px; color: #9ca3af; }

/* ══════════════════════════════════
   CHARTS
══════════════════════════════════ */
.chart-row {
    display: grid;
    gap: 16px;
    margin-bottom: 24px;
}
.chart-row-2 { grid-template-columns: 2fr 1fr; }
.chart-row-equal { grid-template-columns: 1fr 1fr; }

.chart-card {
    background: #fff;
    border: 1px solid #f0ede8;
    border-radius: 14px;
    padding: 20px 22px;
}
.chart-card-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    margin-bottom: 18px;
}
.chart-card-title { font-size: 14px; font-weight: 700; color: #1a1a1a; }
.chart-card-sub   { font-size: 11px; color: #9ca3af; margin-top: 2px; }

.chart-pill {
    padding: 5px 12px;
    background: linear-gradient(135deg,#e8590c,#f7a04c);
    color: white; border: none;
    border-radius: 20px;
    font-size: 11px; font-weight: 600;
}
.chart-pill-ghost {
    padding: 5px 12px;
    background: #faf8f5;
    border: 1px solid #f0ede8;
    border-radius: 20px;
    font-size: 11px; color: #9ca3af;
}

/* Funnel / conversion row */
.funnel-row {
    display: flex;
    gap: 0;
    margin-top: 10px;
}
.funnel-step {
    flex: 1;
    text-align: center;
    padding: 14px 8px;
    border-right: 1px solid #f0ede8;
    position: relative;
}
.funnel-step:last-child { border-right: none; }
.funnel-val {
    font-size: 18px; font-weight: 700; color: #1a1a1a;
    margin-bottom: 2px;
}
.funnel-lbl { font-size: 10px; color: #9ca3af; }
.funnel-delta {
    font-size: 10px; font-weight: 700;
    margin-top: 3px;
}
.delta-up   { color: #22c55e; }
.delta-down { color: #ef4444; }

/* Category legend list */
.cat-legend { margin-top: 12px; }
.cat-legend-item {
    display: flex; align-items: center;
    justify-content: space-between;
    padding: 7px 0;
    border-bottom: 1px solid #f9f7f4;
    font-size: 12px;
}
.cat-legend-item:last-child { border-bottom: none; }
.cat-dot {
    width: 10px; height: 10px;
    border-radius: 50%;
    margin-right: 8px;
    flex-shrink: 0;
}
.cat-legend-name { display: flex; align-items: center; color: #6b7280; flex: 1; }
.cat-legend-val  { font-weight: 700; color: #1a1a1a; }

/* Traffic sources */
.traffic-item {
    display: flex; align-items: center;
    gap: 10px; padding: 8px 0;
    border-bottom: 1px solid #f9f7f4;
    font-size: 12px;
}
.traffic-item:last-child { border-bottom: none; }
.traffic-bar-wrap { flex: 1; height: 6px; background: #f0ede8; border-radius: 3px; overflow: hidden; }
.traffic-bar      { height: 100%; border-radius: 3px; background: linear-gradient(90deg,#e8590c,#f7a04c); }
.traffic-name { color: #6b7280; width: 110px; flex-shrink: 0; }
.traffic-pct  { font-weight: 700; color: #1a1a1a; width: 34px; text-align: right; flex-shrink: 0; }

/* Monthly target gauge */
.target-card { text-align: center; }
.target-pct  { font-size: 36px; font-weight: 700; color: #1a1a1a; margin: 8px 0 4px; }
.target-msg  { font-size: 12px; color: #9ca3af; }
.target-row  { display: flex; gap: 10px; margin-top: 16px; }
.target-box  {
    flex: 1; padding: 12px; border-radius: 10px;
    background: #faf8f5; text-align: left;
}
.target-box-label { font-size: 10px; color: #9ca3af; }
.target-box-val   { font-size: 15px; font-weight: 700; color: #1a1a1a; margin-top: 2px; }

/* ══════════════════════════════════
   TABLES
══════════════════════════════════ */
.table-card {
    background: #fff;
    border: 1px solid #f0ede8;
    border-radius: 14px;
    overflow: hidden;
    margin-bottom: 20px;
}
.table-card-header {
    display: flex; align-items: center;
    justify-content: space-between;
    padding: 18px 22px 14px;
    border-bottom: 1px solid #f0ede8;
}
.table-card-title { font-size: 14px; font-weight: 700; color: #1a1a1a; }
.table-card-sub   { font-size: 11px; color: #9ca3af; margin-top: 2px; }
.table-link { font-size: 12px; color: #e8590c; font-weight: 600; }
.table-link:hover { text-decoration: underline; }

.admin-tbl { width: 100%; border-collapse: collapse; }
.admin-tbl th {
    padding: 10px 22px;
    text-align: left;
    font-size: 11px; font-weight: 700;
    color: #9ca3af; text-transform: uppercase; letter-spacing: 0.5px;
    background: #faf8f5;
    border-bottom: 1px solid #f0ede8;
}
.admin-tbl td {
    padding: 13px 22px;
    font-size: 13px; color: #374151;
    border-bottom: 1px solid #f9f7f4;
    vertical-align: middle;
}
.admin-tbl tr:last-child td { border-bottom: none; }
.admin-tbl tr:hover td { background: #fdfcfb; }

.tbl-avatar {
    width: 30px; height: 30px;
    border-radius: 50%;
    background: linear-gradient(135deg,#e8590c,#f7a04c);
    display: inline-flex; align-items: center; justify-content: center;
    font-size: 11px; font-weight: 700; color: white;
    margin-right: 8px; vertical-align: middle; flex-shrink: 0;
}
.tbl-name { font-weight: 600; color: #1a1a1a; }
.tbl-sub  { font-size: 11px; color: #9ca3af; }
.tbl-amount { font-weight: 700; color: #22c55e; }

/* Status pills */
.pill {
    display: inline-block; padding: 3px 10px;
    border-radius: 20px; font-size: 11px; font-weight: 600;
    text-transform: capitalize;
}
.pill-placed    { background: #eff6ff; color: #3b82f6; }
.pill-shipped   { background: #fef9ec; color: #f59e0b; }
.pill-delivered { background: #f0fdf4; color: #22c55e; }
.pill-pending   { background: #f9fafb; color: #9ca3af; }
.pill-cod       { background: #f0ede8; color: #9ca3af; }
.pill-upi       { background: #eff6ff; color: #3b82f6; }
.pill-qr        { background: #faf5ff; color: #a855f7; }

/* Action buttons */
.action-btn-edit {
    padding: 5px 12px;
    background: #eff6ff; color: #3b82f6;
    border: none; border-radius: 6px;
    font-size: 11px; font-weight: 600;
    cursor: pointer; transition: background 0.2s;
}
.action-btn-edit:hover { background: #dbeafe; }
.action-btn-del {
    padding: 5px 12px;
    background: #fef2f2; color: #ef4444;
    border: none; border-radius: 6px;
    font-size: 11px; font-weight: 600;
    cursor: pointer; transition: background 0.2s;
}
.action-btn-del:hover { background: #fee2e2; }

/* Empty row */
.tbl-empty { text-align: center; padding: 36px; color: #c4b5a5; font-size: 13px; }

/* ══════════════════════════════════
   PRODUCT MANAGER FORM
══════════════════════════════════ */
.pm-grid {
    display: grid;
    grid-template-columns: 380px 1fr;
    gap: 20px;
    align-items: flex-start;
}

.form-panel {
    background: #fff;
    border: 1px solid #f0ede8;
    border-radius: 14px;
    padding: 22px;
    position: sticky;
    top: 80px;
}
.form-panel-title {
    font-size: 15px; font-weight: 700; color: #1a1a1a;
    margin-bottom: 18px; padding-bottom: 14px;
    border-bottom: 1px solid #f0ede8;
    display: flex; align-items: center; gap: 8px;
}

.form-label {
    display: block;
    font-size: 11px; font-weight: 700;
    color: #9ca3af; text-transform: uppercase;
    letter-spacing: 0.5px; margin: 14px 0 5px;
}
.form-input,
.form-select,
.form-textarea {
    width: 100%;
    padding: 9px 12px;
    border: 1.5px solid #f0ede8;
    border-radius: 8px;
    font-family: inherit;
    font-size: 13px;
    color: #1a1a1a;
    background: #fff;
    outline: none;
    transition: border-color 0.18s, box-shadow 0.18s;
    box-sizing: border-box;
}
.form-input:focus,
.form-select:focus,
.form-textarea:focus {
    border-color: #e8590c;
    box-shadow: 0 0 0 3px rgba(232,89,12,0.08);
}
.form-helper {
    font-size: 10px; color: #c4b5a5;
    margin-top: 3px; line-height: 1.4;
}

.form-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }

.form-check-row {
    display: flex; align-items: center; gap: 8px;
    padding: 10px 0;
    font-size: 13px; color: #374151;
    cursor: pointer;
}
.form-check-row input[type="checkbox"] {
    width: 16px; height: 16px;
    accent-color: #e8590c;
}

.img-preview-box {
    width: 72px; height: 72px;
    border-radius: 10px;
    border: 1.5px dashed #f0ede8;
    object-fit: cover;
    background: #faf8f5;
    margin-top: 8px;
}

.btn-primary {
    width: 100%;
    padding: 11px;
    background: linear-gradient(135deg,#e8590c,#f7a04c);
    color: white; border: none;
    border-radius: 10px;
    font-size: 14px; font-weight: 700;
    margin-top: 16px;
    transition: opacity 0.2s;
}
.btn-primary:hover { opacity: 0.88; }
.btn-secondary {
    width: 100%;
    padding: 10px;
    background: #faf8f5;
    color: #6b7280; border: none;
    border-radius: 10px;
    font-size: 13px; margin-top: 8px;
    transition: background 0.18s;
}
.btn-secondary:hover { background: #f0ede8; }

/* Alert messages */
.alert {
    padding: 12px 16px; border-radius: 10px;
    font-size: 13px; font-weight: 500;
    margin-bottom: 18px;
    display: flex; align-items: center; gap: 8px;
}
.alert-success { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }
.alert-error   { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }

/* Product table search */
.tbl-search-wrap { padding: 14px 22px; border-bottom: 1px solid #f0ede8; }
.tbl-search {
    width: 100%; padding: 8px 14px;
    border: 1.5px solid #f0ede8;
    border-radius: 8px;
    font-size: 13px; font-family: inherit;
    outline: none; background: #faf8f5;
    transition: border-color 0.18s;
}
.tbl-search:focus { border-color: #e8590c; background: #fff; }

/* Category badges */
.cat-pill {
    display: inline-block; padding: 2px 10px;
    border-radius: 20px; font-size: 11px; font-weight: 600;
    text-transform: capitalize;
}
.cat-traditional { background: #fef2f2; color: #dc2626; }
.cat-dresses     { background: #f0fdf4; color: #16a34a; }
.cat-casual      { background: #eff6ff; color: #3b82f6; }
.cat-accessories { background: #fef9ec; color: #d97706; }

.stock-ok   { color: #22c55e; font-weight: 600; }
.stock-low  { color: #f59e0b; font-weight: 600; }
.stock-out  { color: #ef4444; font-weight: 700; }
.flash-on   { color: #e8590c; font-weight: 700; }

/* Quick stats in PM page */
.pm-stats {
    display: grid;
    grid-template-columns: repeat(6, 1fr);
    gap: 12px;
    margin-bottom: 20px;
}
.pm-stat {
    background: #fff;
    border: 1px solid #f0ede8;
    border-radius: 12px;
    padding: 14px 16px;
    text-align: center;
}
.pm-stat-val {
    font-size: 22px; font-weight: 700;
    color: #e8590c; font-family: inherit;
}
.pm-stat-lbl { font-size: 10px; color: #9ca3af; margin-top: 2px; }
<?php
// navbar.php — shared across ALL pages
$_isLoggedIn = is_logged_in();
$_userName   = $_isLoggedIn ? htmlspecialchars(current_user()['name']) : '';
$_cartCount  = count($_SESSION['cart']    ?? []);
$_wishCount  = count($_SESSION['wishlist'] ?? []);
?>
<nav class="navbar-top">
    <a href="index.php" class="navbar-logo">La Moda</a>

    <div class="search-wrap" id="searchWrap">
        <form method="GET" action="index.php" id="searchForm" autocomplete="off">
            <input type="text" name="search" id="searchInput" placeholder="Search for fashion…">
            <button type="submit" aria-label="Search">🔍</button>
        </form>
        <div class="search-suggestions" id="searchSuggestions"></div>
    </div>

    <div class="navbar-actions">
        <?php if ($_isLoggedIn): ?>
            <span class="nav-username">👤 <?= $_userName ?></span>
            <a href="cart.php">🛒 Cart (<span class="cart-count-badge"><?= $_cartCount ?></span>)</a>
            <a href="wishlist.php">♡ Wishlist (<span class="wish-count-badge"><?= $_wishCount ?></span>)</a>
            <a href="logout.php">Logout</a>
        <?php else: ?>
            <a href="cart.php">🛒 Cart (<span class="cart-count-badge"><?= $_cartCount ?></span>)</a>
            <button class="nav-login-btn" onclick="openLogin()">Login</button>
        <?php endif; ?>
    </div>
</nav>

<div class="navbar-bottom">
    <button class="hamburger-btn" onclick="openCatPanel()" aria-label="Browse Categories">
        <span></span><span></span><span></span>
    </button>
    <span class="navbar-bottom-label">Browse Categories</span>
</div>

<!-- Category side panel -->
<div class="cat-overlay" id="catOverlay" onclick="closeCatPanel()"></div>
<div class="cat-panel" id="catPanel">
    <button class="cat-close" onclick="closeCatPanel()">✕</button>
    <h3>Categories</h3>
    <a href="traditional.php">👘 Traditional</a>
    <a href="dresses.php">👗 Dresses</a>
    <a href="casual.php">👕 Casual</a>
    <a href="accessories.php">👜 Accessories</a>
    <a href="cart.php" class="cat-special">🛒 My Cart</a>
    <a href="wishlist.php" class="cat-special">♡ Wishlist</a>
    <?php if ($_isLoggedIn): ?>
    <a href="logout.php" class="cat-logout">Logout</a>
    <?php endif; ?>
</div>

<!-- AUTH MODALS -->
<div id="loginModal" class="modal">
    <div class="modal-box">
        <button class="modal-close" onclick="closeAll()">✕</button>
        <h2>Welcome 🤗</h2>
        <p class="modal-sub">New to La Moda or returning?</p>
        <button class="modal-choice-btn primary" onclick="showSignup()">Create Account</button>
        <button class="modal-choice-btn" onclick="showLoginForm()">Already have an account</button>
    </div>
</div>

<div id="signupModal" class="modal">
    <div class="modal-box">
        <button class="modal-close" onclick="closeAll()">✕</button>
        <h2>Create Account</h2>
        <form id="signupForm">
            <?= csrf_field() ?>
            <input type="text"     name="name"     placeholder="Full Name"        required maxlength="100">
            <select name="gender" required>
                <option value="">Select Gender</option>
                <option>Female</option><option>Male</option><option>Other</option>
            </select>
            <input type="tel"      name="phone"    placeholder="Phone Number"     maxlength="20">
            <input type="text"     name="username" placeholder="Username"         required maxlength="50">
            <input type="password" name="password" id="signupPassword" placeholder="Password" required maxlength="100">

            <!-- Password strength checklist -->
            <div class="pwd-rules" id="pwdRules">
                <div class="pwd-rule" id="rule-len">    <span class="rule-icon">○</span> At least 8 characters</div>
                <div class="pwd-rule" id="rule-lower">  <span class="rule-icon">○</span> At least 1 lowercase letter (a–z)</div>
                <div class="pwd-rule" id="rule-upper">  <span class="rule-icon">○</span> At least 1 uppercase letter (A–Z)</div>
                <div class="pwd-rule" id="rule-num">    <span class="rule-icon">○</span> At least 1 number (0–9)</div>
                <div class="pwd-rule" id="rule-special"><span class="rule-icon">○</span> At least 1 special character (!@#$…)</div>
            </div>

            <input type="password" name="confirm"  placeholder="Confirm Password" required maxlength="100">
            <button type="submit" class="form-submit-btn">Sign Up</button>
        </form>
        <p class="modal-foot">Already a user? <a onclick="showLoginForm()">Login</a></p>
    </div>
</div>

<div id="loginFormModal" class="modal">
    <div class="modal-box">
        <button class="modal-close" onclick="closeAll()">✕</button>
        <h2>Login</h2>
        <form id="loginForm">
            <?= csrf_field() ?>
            <input type="text"     name="username" placeholder="Username" required maxlength="50">
            <input type="password" name="password" placeholder="Password" required maxlength="100">
            <button type="submit" class="form-submit-btn">Login</button>
        </form>
        <p class="modal-foot">New user? <a onclick="showSignup()">Sign Up</a></p>
    </div>
</div>

<!-- BUY NOW MODAL — Step 1: Address -->
<div id="buyNowModal" class="modal">
    <div class="modal-box modal-box--wide">
        <button class="modal-close" onclick="closeAll()">✕</button>
        <h2>Buy Now 🛍️</h2>
        <p class="modal-sub">Step 1 of 2 — Enter your delivery address</p>
        <form id="buyNowForm" novalidate>
            <?= csrf_field() ?>
            <input type="hidden" name="item_name" id="buyItemName">
            <p class="form-section-label">Delivery Address</p>
            <input list="countryList" name="country" id="bn_country" placeholder="Country *" required autocomplete="off" maxlength="100">
            <datalist id="countryList">
                <option value="India"><option value="Indonesia"><option value="Iran"><option value="Iraq">
                <option value="Ireland"><option value="Israel"><option value="Italy">
                <option value="United States"><option value="United Kingdom"><option value="United Arab Emirates">
                <option value="Australia"><option value="Austria"><option value="Argentina">
                <option value="Bangladesh"><option value="Belgium"><option value="Brazil">
                <option value="Canada"><option value="Chile"><option value="China"><option value="Colombia">
                <option value="Denmark"><option value="Egypt"><option value="Finland">
                <option value="France"><option value="Germany"><option value="Ghana">
                <option value="Greece"><option value="Hungary"><option value="Japan">
                <option value="Jordan"><option value="Kenya"><option value="Kuwait">
                <option value="Malaysia"><option value="Mexico"><option value="Morocco">
                <option value="Nepal"><option value="Netherlands"><option value="New Zealand">
                <option value="Nigeria"><option value="Norway"><option value="Oman">
                <option value="Pakistan"><option value="Peru"><option value="Philippines">
                <option value="Poland"><option value="Portugal"><option value="Qatar">
                <option value="Romania"><option value="Russia"><option value="Saudi Arabia">
                <option value="Singapore"><option value="South Africa"><option value="South Korea">
                <option value="Spain"><option value="Sri Lanka"><option value="Sweden">
                <option value="Switzerland"><option value="Thailand"><option value="Turkey">
                <option value="Ukraine"><option value="Venezuela"><option value="Vietnam">
            </datalist>
            <input list="stateList" name="state" id="bn_state" placeholder="State / Province *" required autocomplete="off" maxlength="100">
            <datalist id="stateList">
                <option value="Andhra Pradesh"><option value="Arunachal Pradesh">
                <option value="Assam"><option value="Bihar"><option value="Chhattisgarh">
                <option value="Goa"><option value="Gujarat"><option value="Haryana">
                <option value="Himachal Pradesh"><option value="Jharkhand"><option value="Karnataka">
                <option value="Kerala"><option value="Madhya Pradesh"><option value="Maharashtra">
                <option value="Manipur"><option value="Meghalaya"><option value="Mizoram">
                <option value="Nagaland"><option value="Odisha"><option value="Punjab">
                <option value="Rajasthan"><option value="Sikkim"><option value="Tamil Nadu">
                <option value="Telangana"><option value="Tripura"><option value="Uttar Pradesh">
                <option value="Uttarakhand"><option value="West Bengal">
                <option value="Delhi"><option value="Jammu and Kashmir"><option value="Ladakh">
                <option value="Puducherry"><option value="Chandigarh">
            </datalist>
            <input type="text" name="full_name" id="bn_name"    placeholder="Full Name *"       required maxlength="100">
            <input type="tel"  name="mobile"    id="bn_mobile"  placeholder="Mobile Number *"   required maxlength="10">
            <p class="form-section-label">Address</p>
            <input type="text" name="flat"     id="bn_flat"     placeholder="Flat, House No., Building *" required maxlength="200">
            <input type="text" name="area"     id="bn_area"     placeholder="Area, Street, Sector *"      required maxlength="200">
            <input type="text" name="landmark" id="bn_landmark" placeholder="Landmark *"                  required maxlength="200">
            <div class="form-row">
                <input type="text" name="pincode" id="bn_pincode" placeholder="Pincode * (6 digits)" required maxlength="6">
                <input type="text" name="city"    id="bn_city"    placeholder="Town / City *"         required maxlength="100">
            </div>
            <label class="checkbox-row">
                <input type="checkbox" name="default_address" value="1">
                Make this my Default Address
            </label>
            <p class="form-section-label">Delivery Instructions <span class="optional-label">(optional)</span></p>
            <textarea name="delivery_instructions" maxlength="500" rows="2"
                placeholder="Any special instructions…"></textarea>
            <!-- Proceed to Pay button — opens payment popup -->
            <button type="button" id="buyNowProceedBtn"
                style="width:100%;padding:11px;background:var(--brand);color:white;border:none;border-radius:8px;font-size:14px;font-weight:700;margin-top:14px;cursor:pointer;font-family:var(--font-body);">
                Proceed to Pay →
            </button>
        </form>
    </div>
</div>

<!-- BUY NOW PAYMENT POPUP — Step 2 -->
<div id="buyNowPayPopup" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:3500;overflow-y:auto;">
    <div style="background:white;width:460px;max-width:95vw;padding:28px 26px;border-radius:14px;margin:60px auto;position:relative;">
        <button onclick="document.getElementById('buyNowPayPopup').style.display='none'"
            style="position:absolute;top:10px;right:14px;background:none;border:none;font-size:20px;cursor:pointer;color:#888;">✕</button>
        <h3 style="font-family:var(--font-display);font-size:20px;margin-bottom:4px;">Select Payment Method</h3>
        <p style="font-size:12px;color:#aaa;margin-bottom:16px;">Step 2 of 2 — Choose how you want to pay</p>

        <input type="hidden" id="buyPayMethod" value="">

        <!-- Payment options -->
        <div style="display:flex;flex-direction:column;gap:10px;margin-bottom:18px;">

            <label style="display:flex;align-items:center;gap:12px;padding:12px 14px;border:1.5px solid #e8e8e8;border-radius:10px;cursor:pointer;transition:border-color 0.18s;" class="pay-opt-label">
                <input type="radio" name="buyPayOption" value="cod" style="accent-color:var(--brand);width:16px;height:16px;">
                <span style="font-size:20px;">💵</span>
                <div>
                    <div style="font-size:14px;font-weight:600;">Cash on Delivery</div>
                    <div style="font-size:11px;color:#aaa;">Pay when order is delivered</div>
                </div>
            </label>

            <label style="display:flex;align-items:center;gap:12px;padding:12px 14px;border:1.5px solid #e8e8e8;border-radius:10px;cursor:pointer;transition:border-color 0.18s;" class="pay-opt-label">
                <input type="radio" name="buyPayOption" value="upi" style="accent-color:var(--brand);width:16px;height:16px;">
                <span style="font-size:20px;">📱</span>
                <div>
                    <div style="font-size:14px;font-weight:600;">UPI</div>
                    <div style="font-size:11px;color:#aaa;">PhonePe, GPay, Paytm, BHIM</div>
                </div>
            </label>

            <label style="display:flex;align-items:center;gap:12px;padding:12px 14px;border:1.5px solid #e8e8e8;border-radius:10px;cursor:pointer;transition:border-color 0.18s;" class="pay-opt-label">
                <input type="radio" name="buyPayOption" value="card" style="accent-color:var(--brand);width:16px;height:16px;">
                <span style="font-size:20px;">💳</span>
                <div>
                    <div style="font-size:14px;font-weight:600;">Credit / Debit Card</div>
                    <div style="font-size:11px;color:#aaa;">Visa, Mastercard, RuPay</div>
                </div>
            </label>

            <label style="display:flex;align-items:center;gap:12px;padding:12px 14px;border:1.5px solid #e8e8e8;border-radius:10px;cursor:pointer;transition:border-color 0.18s;" class="pay-opt-label">
                <input type="radio" name="buyPayOption" value="emi" style="accent-color:var(--brand);width:16px;height:16px;">
                <span style="font-size:20px;">📅</span>
                <div>
                    <div style="font-size:14px;font-weight:600;">EMI</div>
                    <div style="font-size:11px;color:#aaa;">3, 6, 9 or 12 month options</div>
                </div>
            </label>

            <label style="display:flex;align-items:center;gap:12px;padding:12px 14px;border:1.5px solid #e8e8e8;border-radius:10px;cursor:pointer;transition:border-color 0.18s;" class="pay-opt-label">
                <input type="radio" name="buyPayOption" value="bank" style="accent-color:var(--brand);width:16px;height:16px;">
                <span style="font-size:20px;">🏦</span>
                <div>
                    <div style="font-size:14px;font-weight:600;">Net Banking</div>
                    <div style="font-size:11px;color:#aaa;">SBI, HDFC, ICICI, Axis, Kotak</div>
                </div>
            </label>
        </div>

        <p id="buyPayError" style="color:#c0392b;font-size:12px;margin-bottom:10px;display:none;">⚠ Please select a payment method</p>

        <button id="buyNowConfirmBtn"
            style="width:100%;padding:12px;background:var(--brand);color:white;border:none;border-radius:8px;font-size:14px;font-weight:700;cursor:pointer;font-family:var(--font-body);">
            Confirm & Place Order 🎉
        </button>
        <button onclick="document.getElementById('buyNowPayPopup').style.display='none';document.getElementById('buyNowModal').style.display='block';"
            style="width:100%;padding:10px;background:#f5f5f5;color:#666;border:none;border-radius:8px;font-size:13px;margin-top:8px;cursor:pointer;font-family:var(--font-body);">
            ← Back to Address
        </button>
    </div>
</div>

<!-- ORDER SUCCESS -->
<div id="orderSuccessModal" class="modal">
    <div class="modal-box modal-box--center">
        <button class="modal-close" onclick="closeAll()">✕</button>
        <div class="success-icon">🎉</div>
        <h2>Order Placed!</h2>
        <p class="modal-sub">Your order is confirmed.<br>We'll deliver it soon! 💗</p>
        <div id="bnCouponBox" style="display:none;margin:12px 0;padding:12px;background:#fff5f2;border:1.5px dashed var(--brand);border-radius:10px;text-align:center;">
            <p style="font-size:12px;color:#8B2500;font-weight:600;margin-bottom:4px;">🎁 You won a coupon!</p>
            <div id="bnCouponCode" style="font-size:20px;font-weight:700;color:#8B2500;letter-spacing:2px;"></div>
            <p id="bnCouponDisc" style="font-size:11px;color:#888;margin-top:3px;"></p>
            <p style="font-size:10px;color:#aaa;">Valid on your next order only</p>
        </div>
        <button class="modal-btn" onclick="closeAll()">Continue Shopping</button>
    </div>
</div>

<div id="toast" class="toast"></div>

<script>
const CSRF       = <?= json_encode(csrf_token()) ?>;
const isLoggedIn = <?= is_logged_in() ? 'true' : 'false' ?>;
const cartData   = <?= json_encode(array_values($_SESSION['cart']    ?? [])) ?>;
const wishData   = <?= json_encode(array_values($_SESSION['wishlist'] ?? [])) ?>;

// ── Counts ──
function updateCartCount() { document.querySelectorAll(".cart-count-badge").forEach(e => e.textContent = cartData.length); }
function updateWishCount() { document.querySelectorAll(".wish-count-badge").forEach(e => e.textContent = wishData.length); }

// ── Init qty boxes ──
document.querySelectorAll(".add-btn").forEach(btn => {
    const item = cartData.find(i => i.name === btn.dataset.name);
    if (item) btn.closest(".cart-controls").innerHTML = buildQtyBox(btn.dataset, item.qty);
});

// ── Unified click handler ──
document.addEventListener("click", function(e) {
    const heart = e.target.closest(".heart-btn");
    if (heart) { if (!isLoggedIn) { openLogin(); return; } toggleWishlist(heart); return; }

    if (e.target.classList.contains("add-btn")) {
        if (!isLoggedIn) { openLogin(); return; }
        e.target.disabled = true; addToCart(e.target); return;
    }
    if (e.target.classList.contains("plus"))  { e.target.disabled = true; updateQty(e.target.closest(".qty-box"), "plus");  return; }
    if (e.target.classList.contains("minus")) { e.target.disabled = true; updateQty(e.target.closest(".qty-box"), "minus"); return; }

    const buyBtn = e.target.closest(".buy-now-btn");
    if (buyBtn) {
        if (!isLoggedIn) { openLogin(); return; }
        document.getElementById("buyNowForm").reset();
        document.getElementById("buyItemName").value = buyBtn.dataset.name || '';
        document.querySelector("#buyNowForm [name='csrf_token']").value = CSRF;
        // Reset field styles
        ['bn_country','bn_state','bn_name','bn_mobile','bn_flat','bn_area','bn_landmark','bn_pincode','bn_city'].forEach(id => {
            const el = document.getElementById(id);
            if (el) { el.style.borderColor=''; el.style.background=''; }
        });
        document.querySelectorAll(".modal").forEach(m => m.style.display="none");
        document.getElementById("buyNowModal").style.display = "block";
        return;
    }
    if (e.target.classList.contains("modal")) closeAll();
});

// ── Wishlist ──
function toggleWishlist(btn) {
    btn.disabled = true;
    const name = btn.dataset.name;
    const isIn = btn.classList.contains("active");
    const d = new FormData();
    d.append("csrf_token", CSRF); d.append("name", name); d.append("action", isIn ? "remove" : "add");
    fetch("toggle_wishlist.php", { method: "POST", body: d })
        .then(r => r.json())
        .then(res => {
            if (res.error) { showToast(res.error,'error'); btn.disabled=false; return; }
            btn.classList.toggle("active");
            btn.textContent = isIn ? "🤍" : "❤️";
            if (isIn) { const i = wishData.indexOf(name); if(i>-1) wishData.splice(i,1); showToast("Removed from wishlist"); }
            else { wishData.push(name); showToast("Added to wishlist 💗"); }
            updateWishCount(); btn.disabled = false;
        })
        .catch(() => { showToast("Network error",'error'); btn.disabled=false; });
}

// ── Add to cart ──
function addToCart(btn) {
    const d = new FormData();
    d.append("csrf_token", CSRF); d.append("name", btn.dataset.name);
    fetch("add_to_cart.php", { method:"POST", body:d })
        .then(r => r.json())
        .then(res => {
            if (res.error) { showToast(res.error,'error'); btn.disabled=false; return; }
            let item = cartData.find(i => i.name === btn.dataset.name);
            if (item) item.qty++;
            else cartData.push({ name: btn.dataset.name, qty:1, price: res.price, image: res.image });
            updateCartCount();
            btn.closest(".cart-controls").innerHTML = buildQtyBox({ name: btn.dataset.name, price: res.price, image: res.image }, 1);
            showToast("Added to your cart 💗");
        })
        .catch(() => { showToast("Network error",'error'); btn.disabled=false; });
}

// ── Update qty ──
function updateQty(box, action) {
    const name = box.dataset.name;
    const d = new FormData();
    d.append("csrf_token",CSRF); d.append("name",name); d.append("action",action);
    fetch("update_cart.php", { method:"POST", body:d })
        .then(r => r.json())
        .then(res => {
            let item = cartData.find(i => i.name === name);
            if (res.removed) {
                cartData.splice(cartData.indexOf(item),1);
                box.innerHTML = `<button class="add-btn" data-name="${escH(box.dataset.name)}">Add to Cart</button>`;
            } else {
                if (item) item.qty = res.qty;
                box.querySelector("span").textContent = res.qty;
                box.querySelectorAll("button").forEach(b => b.disabled=false);
                if (action==="plus") showToast("Added to your cart 💗");
            }
            updateCartCount();
        })
        .catch(() => { showToast("Network error",'error'); box.querySelectorAll("button").forEach(b=>b.disabled=false); });
}

function buildQtyBox(ds, qty) {
    return `<div class="qty-box" data-name="${escH(ds.name)}" data-price="${ds.price||''}" data-image="${escH(ds.image||'')}">
        <button class="qty-btn minus">−</button>
        <span>${qty}</span>
        <button class="qty-btn plus">+</button>
    </div>`;
}
function escH(s) { return String(s).replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

// ── Toast ──
function showToast(msg, type) {
    const t = document.getElementById("toast");
    if (!t) return;
    t.textContent = msg;
    t.className = 'toast show' + (type==='error' ? ' toast-error' : '');
    clearTimeout(t._t);
    t._t = setTimeout(() => t.classList.remove("show"), 3000);
}

// ── Modals ──
function closeAll() { document.querySelectorAll(".modal").forEach(m => m.style.display="none"); }
function openLogin()     { closeAll(); document.getElementById("loginModal").style.display="block"; }
function showSignup()    { closeAll(); document.getElementById("signupModal").style.display="block"; }
function showLoginForm() { closeAll(); document.getElementById("loginFormModal").style.display="block"; }
function openCatPanel()  { document.getElementById("catPanel").classList.add("open"); document.getElementById("catOverlay").classList.add("open"); }
function closeCatPanel() { document.getElementById("catPanel").classList.remove("open"); document.getElementById("catOverlay").classList.remove("open"); }

// ── Signup ──
document.getElementById("signupForm")?.addEventListener("submit", function(e) {
    e.preventDefault();
    if (this.password.value !== this.confirm.value) { showToast("Passwords don't match",'error'); return; }
    // Check all password rules pass
    if (!checkPasswordRules(this.password.value)) { showToast("Password does not meet requirements",'error'); return; }
    const btn = this.querySelector("button[type='submit']");
    btn.disabled=true; btn.textContent="Creating account…";
    fetch("save_user.php",{method:"POST",body:new FormData(this)}).then(r=>r.json())
        .then(res=>{ if(res.error){showToast(res.error,'error');btn.disabled=false;btn.textContent="Sign Up";return;} closeAll();location.reload(); })
        .catch(()=>{ showToast("Network error",'error');btn.disabled=false;btn.textContent="Sign Up"; });
});

// ── Password strength rules ──
function checkPasswordRules(pw) {
    const rules = {
        'rule-len':     pw.length >= 8,
        'rule-lower':   /[a-z]/.test(pw),
        'rule-upper':   /[A-Z]/.test(pw),
        'rule-num':     /[0-9]/.test(pw),
        'rule-special': /[^a-zA-Z0-9]/.test(pw),
    };
    let allPass = true;
    Object.entries(rules).forEach(([id, pass]) => {
        const el   = document.getElementById(id);
        const icon = el?.querySelector('.rule-icon');
        if (el)   el.classList.toggle('rule-pass', pass);
        if (icon) icon.textContent = pass ? '✅' : '○';
        if (!pass) allPass = false;
    });
    return allPass;
}

document.getElementById("signupPassword")?.addEventListener("input", function() {
    checkPasswordRules(this.value);
});

// ── Login ──
document.getElementById("loginForm")?.addEventListener("submit", function(e) {
    e.preventDefault();
    const btn = this.querySelector("button[type='submit']");
    btn.disabled=true; btn.textContent="Logging in…";
    fetch("login_user.php",{method:"POST",body:new FormData(this)}).then(r=>r.json())
        .then(res=>{ if(res.error){showToast(res.error,'error');btn.disabled=false;btn.textContent="Login";return;} closeAll();location.reload(); })
        .catch(()=>{ showToast("Network error",'error');btn.disabled=false;btn.textContent="Login"; });
});

// ── Buy Now: Step 1 — validate address then open payment popup ──
document.getElementById("buyNowProceedBtn")?.addEventListener("click", function() {
    const required = [
        {id:'bn_country', label:'Country'},
        {id:'bn_name',    label:'Full Name'},
        {id:'bn_mobile',  label:'Mobile'},
        {id:'bn_flat',    label:'Flat/House'},
        {id:'bn_area',    label:'Area'},
        {id:'bn_landmark',label:'Landmark'},
        {id:'bn_pincode', label:'Pincode'},
        {id:'bn_city',    label:'City'},
    ];
    let firstBad = null;
    required.forEach(({id}) => {
        const el = document.getElementById(id);
        if (!el) return;
        const bad = !el.value.trim();
        el.style.borderColor = bad ? '#c0392b' : '';
        el.style.background  = bad ? '#fff8f8' : '';
        if (bad && !firstBad) firstBad = el;
    });
    if (firstBad) { firstBad.focus(); showToast("Please fill all required fields",'error'); return; }
    if (!/^[A-Za-z\s]{2,}$/.test(document.getElementById("bn_name").value.trim()))   { document.getElementById("bn_name").focus();    showToast("Name: letters only","error"); return; }
    if (!/^[6-9][0-9]{9}$/.test(document.getElementById("bn_mobile").value.trim()))  { document.getElementById("bn_mobile").focus();  showToast("Enter valid 10-digit mobile","error"); return; }
    if (!/^[1-9][0-9]{5}$/.test(document.getElementById("bn_pincode").value.trim())) { document.getElementById("bn_pincode").focus(); showToast("Enter valid 6-digit pincode","error"); return; }
    // Check address fields — only letters, numbers, hyphen
    for (const id of ['bn_flat','bn_area']) {
        const v = document.getElementById(id)?.value.trim();
        if (v && !/^[A-Za-z0-9\s\-]+$/.test(v)) {
            document.getElementById(id)?.focus();
            showToast("Address fields: letters, numbers and hyphen (-) only","error"); return;
        }
    }

    document.getElementById("buyNowModal").style.display = "none";
    document.getElementById("buyNowPayPopup").style.display = "block";
    document.querySelectorAll('input[name="buyPayOption"]').forEach(r => r.checked = false);
    document.querySelectorAll(".pay-opt-label").forEach(l => l.style.borderColor = "#e8e8e8");
    document.getElementById("buyPayError").style.display = "none";
});

// Highlight payment option on select
document.querySelectorAll('input[name="buyPayOption"]').forEach(radio => {
    radio.addEventListener("change", () => {
        document.querySelectorAll(".pay-opt-label").forEach(l => l.style.borderColor = "#e8e8e8");
        radio.closest(".pay-opt-label").style.borderColor = "var(--brand)";
        document.getElementById("buyPayError").style.display = "none";
    });
});

// ── Buy Now: Step 2 — place order ──
document.getElementById("buyNowConfirmBtn")?.addEventListener("click", function() {
    const selected = document.querySelector('input[name="buyPayOption"]:checked');
    if (!selected) { document.getElementById("buyPayError").style.display = "block"; return; }

    this.disabled = true; this.textContent = "Placing order…";
    const fd = new FormData(document.getElementById("buyNowForm"));
    fd.set("payment_method", selected.value);

    fetch("place_order.php", { method:"POST", body: fd })
        .then(r => { if (!r.ok) throw new Error(r.status); return r.json(); })
        .then(res => {
            if (res.error) { showToast(res.error,'error'); this.disabled=false; this.textContent="Confirm & Place Order 🎉"; return; }
            document.getElementById("buyNowPayPopup").style.display = "none";
            // Show coupon if awarded
            if (res.coupon) {
                const cb = document.getElementById("bnCouponBox");
                const cc = document.getElementById("bnCouponCode");
                const cd = document.getElementById("bnCouponDisc");
                if (cb) cb.style.display = "block";
                if (cc) cc.textContent = res.coupon.code;
                if (cd) cd.textContent = res.coupon.discount + "% off on your next order";
            }
            document.getElementById("orderSuccessModal").style.display = "block";
            // Buy Now does NOT touch cart
        })
        .catch(() => { showToast("Network error. Try again.",'error'); this.disabled=false; this.textContent="Confirm & Place Order 🎉"; });
});

// ── LIVE SEARCH SUGGESTIONS ──
(function() {
    const input = document.getElementById("searchInput");
    const box   = document.getElementById("searchSuggestions");
    if (!input || !box) return;

    let timer;
    input.addEventListener("input", () => {
        const q = input.value.trim();
        clearTimeout(timer);
        if (q.length < 1) { box.classList.remove("open"); box.innerHTML=""; return; }
        timer = setTimeout(() => {
            fetch("search_suggest.php?q=" + encodeURIComponent(q))
                .then(r => r.json())
                .then(items => {
                    if (!items.length) { box.classList.remove("open"); return; }
                    box.innerHTML = items.map(it =>
                        `<div class="suggest-item" data-name="${escH(it.name)}">
                            <span>${escH(it.name)}</span>
                            <span class="suggest-cat">${escH(it.category)}</span>
                        </div>`
                    ).join("");
                    box.classList.add("open");
                })
                .catch(()=>{});
        }, 200);
    });

    box.addEventListener("click", e => {
        const item = e.target.closest(".suggest-item");
        if (!item) return;
        input.value = item.dataset.name;
        box.classList.remove("open");
        document.getElementById("searchForm").submit();
    });

    document.addEventListener("click", e => {
        if (!document.getElementById("searchWrap").contains(e.target)) {
            box.classList.remove("open");
        }
    });
})();

// ── Page visit tracking (fire & forget) ──
(function() {
    const d = new FormData();
    d.append("page", window.location.pathname.split("/").pop() || "index.php");
    fetch("track_visit.php", { method:"POST", body:d }).catch(()=>{});
})();
</script>
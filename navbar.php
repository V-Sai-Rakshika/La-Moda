<?php
// navbar.php — shared across ALL pages
// Sync cart + wishlist from MongoDB for logged-in users (cross-tab sync)
$_isLoggedIn = is_logged_in();
$_userName   = $_isLoggedIn ? htmlspecialchars(current_user()['name']) : '';

if ($_isLoggedIn) {
    $__navUser = $users->findOne(['username' => current_user()['username']]);
    if (!empty($__navUser['cart']) && is_array($__navUser['cart'])) {
        $_SESSION['cart'] = $__navUser['cart'];
    }
    if (!empty($__navUser['wishlist_items']) && is_array($__navUser['wishlist_items'])) {
        $_SESSION['wishlist'] = $__navUser['wishlist_items'];
    }
}

$_cartCount = count($_SESSION['cart']    ?? []);
$_wishCount = count($_SESSION['wishlist'] ?? []);

// Current page for active link
$_currentPage = basename($_SERVER['PHP_SELF']);
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
    <a href="traditional.php"  class="<?= $_currentPage==='traditional.php'  ? 'cat-active' : '' ?>">👘 Traditional Wear</a>
    <a href="dresses.php"      class="<?= $_currentPage==='dresses.php'      ? 'cat-active' : '' ?>">👗 Dresses</a>
    <a href="casual.php"       class="<?= $_currentPage==='casual.php'       ? 'cat-active' : '' ?>">👕 Casual Wear</a>
    <a href="accessories.php"  class="<?= $_currentPage==='accessories.php'  ? 'cat-active' : '' ?>">👜 Accessories</a>
    <div class="cat-divider"></div>
    <a href="cart.php"    class="cat-special <?= $_currentPage==='cart.php'     ? 'cat-active' : '' ?>">🛒 My Cart <span class="cat-badge"><?= $_cartCount ?></span></a>
    <a href="wishlist.php" class="cat-special <?= $_currentPage==='wishlist.php' ? 'cat-active' : '' ?>">♡ Wishlist <span class="cat-badge"><?= $_wishCount ?></span></a>
    <?php if ($_isLoggedIn): ?>
    <div class="cat-divider"></div>
    <a href="my_orders.php"  class="<?= $_currentPage==='my_orders.php'  ? 'cat-active' : '' ?>">📦 My Orders</a>
    <a href="my_coupons.php" class="<?= $_currentPage==='my_coupons.php' ? 'cat-active' : '' ?>">🎟️ My Coupons</a>
    <div class="cat-divider"></div>
    <a href="logout.php" class="cat-logout">🚪 Logout</a>
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

<!-- BUY NOW MODAL — unified with cart checkout style -->
<div id="buyNowModal" class="modal">
    <div class="modal-box modal-box--wide">
        <button class="modal-close" onclick="closeAll()">✕</button>

        <!-- Steps -->
        <div class="bn-steps">
            <div class="bn-step active" id="bnStep1dot"><div class="bn-step-dot">1</div><span>Address</span></div>
            <div class="bn-step-line"></div>
            <div class="bn-step" id="bnStep2dot"><div class="bn-step-dot">2</div><span>Payment</span></div>
        </div>

        <!-- STEP 1: Address -->
        <div id="bnAddrPanel">
            <h2>Delivery Address</h2>
            <p class="modal-sub">Where should we deliver your order?</p>

            <!-- Saved addresses -->
            <div id="bnSavedAddrsWrap" style="display:none;">
                <p style="font-size:12px;font-weight:600;color:#555;margin-bottom:8px;">Your saved addresses</p>
                <div id="bnSavedCards" style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:12px;"></div>
                <div style="display:flex;gap:8px;margin-bottom:14px;flex-wrap:wrap;">
                    <button class="addr-btn sel" id="bnBtnUseSaved" onclick="bnSetMode('saved')">Use selected</button>
                    <button class="addr-btn" id="bnBtnNewAddr" onclick="bnSetMode('new')">+ New address</button>
                </div>
            </div>

            <form id="buyNowForm" novalidate>
                <?= csrf_field() ?>
                <input type="hidden" name="order_type"      value="buynow">
                <input type="hidden" name="bn_product_name" id="buyItemName">
                <input type="hidden" name="bn_size"         id="buyItemSize">
                <input type="hidden" name="bn_qty"          id="buyItemQty" value="1">

                <div class="ff">
                    <label>Country *</label>
                    <input list="bnCountryList" name="country" id="bn_country"
                           placeholder="Select or type country" maxlength="100" autocomplete="off">
                    <datalist id="bnCountryList">
                        <option value="— Type your country below —">
                        <option value="India"><option value="United States"><option value="United Kingdom">
                        <option value="Australia"><option value="Canada"><option value="UAE">
                        <option value="Singapore"><option value="Germany"><option value="France">
                        <option value="Japan"><option value="China"><option value="Brazil">
                        <option value="South Africa"><option value="New Zealand">
                    </datalist>
                    <p class="emsg" id="bn_e_country">⚠ Country is required</p>
                </div>

                <div class="ff">
                    <label>Full Name *</label>
                    <input type="text" name="full_name" id="bn_name" placeholder="e.g. Priya Sharma" maxlength="100">
                    <p class="emsg" id="bn_e_name">⚠ Letters and spaces only</p>
                </div>

                <div class="ff">
                    <label>Mobile Number *</label>
                    <input type="tel" name="mobile" id="bn_mobile" placeholder="10-digit number" maxlength="10" inputmode="numeric" pattern="[0-9]*">
                    <p class="emsg" id="bn_e_mobile">⚠ Valid 10-digit number required</p>
                </div>

                <div class="ff">
                    <label>Email Address</label>
                    <input type="email" name="email" id="bn_email" placeholder="you@example.com" maxlength="150">
                    <p class="hint">For order confirmation</p>
                </div>

                <div class="ff">
                    <label>Flat / House No. *</label>
                    <input type="text" name="flat" id="bn_flat" placeholder="e.g. Flat 4B, Sunrise Apartments" maxlength="200">
                    <p class="emsg" id="bn_e_flat">⚠ Required</p>
                </div>

                <div class="ff">
                    <label>Area / Street *</label>
                    <input type="text" name="area" id="bn_area" placeholder="e.g. T. Nagar" maxlength="200">
                    <p class="emsg" id="bn_e_area">⚠ Required</p>
                </div>

                <div class="ff">
                    <label>Landmark *</label>
                    <input type="text" name="landmark" id="bn_landmark" placeholder="e.g. Near SBI Bank" maxlength="200">
                    <p class="emsg" id="bn_e_landmark">⚠ Required</p>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                    <div class="ff">
                        <label>Pincode *</label>
                        <input type="text" name="pincode" id="bn_pincode" placeholder="6-digit" maxlength="6" inputmode="numeric" pattern="[0-9]*">
                        <p class="emsg" id="bn_e_pincode">⚠ Valid 6-digit pincode</p>
                        <p id="bn_pincode_state_msg" style="font-size:11px;margin-top:3px;display:none;"></p>
                    </div>
                    <div class="ff">
                        <label>City *</label>
                        <input type="text" name="city" id="bn_city" placeholder="e.g. Chennai" maxlength="100">
                        <p class="emsg" id="bn_e_city">⚠ Required</p>
                    </div>
                </div>

                <div class="ff">
                    <label>State / Province</label>
                    <input type="text" name="state" id="bn_state" placeholder="e.g. Tamil Nadu" maxlength="100">
                </div>

                <div class="ff">
                    <label>Coupon Code <span style="font-weight:400;color:#bbb;">(optional)</span></label>
                    <div style="display:flex;gap:8px;">
                        <input type="text" id="bn_coupon" placeholder="Enter coupon code" maxlength="20"
                               style="flex:1;padding:9px 12px;border:1.5px solid #e0e0e0;border-radius:8px;font-size:13px;font-family:inherit;outline:none;">
                        <button type="button" id="bnApplyCouponBtn"
                            style="padding:9px 16px;background:#8B2500;color:#fff;border:none;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;">
                            Apply
                        </button>
                    </div>
                    <p id="bnCouponMsg" style="font-size:12px;margin-top:4px;"></p>
                </div>

                <label style="display:flex;align-items:center;gap:8px;font-size:13px;margin:8px 0;cursor:pointer;">
                    <input type="checkbox" name="save_address" value="1" style="accent-color:#8B2500;">
                    Save this address
                </label>
                <label style="display:flex;align-items:center;gap:8px;font-size:13px;margin-bottom:10px;cursor:pointer;" id="bnDefaultChkWrap">
                    <input type="checkbox" name="is_default" value="1" id="bnDefaultChk" style="accent-color:#8B2500;">
                    Set as Default Address
                </label>

                <div class="ff">
                    <label>Delivery Instructions <span style="font-weight:400;color:#bbb;">(optional)</span></label>
                    <textarea name="delivery_instructions" rows="2" maxlength="500"
                              placeholder="Any special instructions…" style="resize:vertical;width:100%;padding:9px 12px;border:1.5px solid #e0e0e0;border-radius:8px;font-size:13px;font-family:inherit;outline:none;box-sizing:border-box;"></textarea>
                </div>
            </form>

            <input type="hidden" id="bnSelectedAddrIdx" value="-1">
            <input type="hidden" id="bnAddrMode" value="new">
            <button class="sbtn" id="bnProceedToPayBtn" style="width:100%;padding:12px;background:#8B2500;color:#fff;border:none;border-radius:8px;font-size:14px;font-weight:700;cursor:pointer;font-family:inherit;margin-top:8px;">
                Proceed to Pay →
            </button>
        </div>

        <!-- STEP 2: Payment -->
        <div id="bnPayPanel" style="display:none;">
            <h2>Choose Payment</h2>
            <p class="modal-sub">Step 2 of 2</p>
            <div id="bnPayAmtLine" style="font-size:14px;font-weight:600;color:#8B2500;margin-bottom:4px;">
                Amount: ₹<span id="bnPayAmt">0</span>
                <span id="bnCouponSaving" style="font-size:12px;color:#16a34a;margin-left:8px;display:none;"></span>
            </div>
            <div id="bnDeliveryLine" style="font-size:12px;color:#888;margin-bottom:14px;">
                Subtotal ₹<span id="bnSubAmt">0</span>
                + Delivery ₹<span id="bnDelAmt">0</span>
            </div>

            <div style="display:flex;flex-direction:column;gap:10px;margin-bottom:18px;">
                <?php foreach ([
                    ['cod',  '💵', 'Cash on Delivery',     'Pay when order arrives'],
                    ['upi',  '📱', 'UPI',                   'PhonePe, GPay, Paytm, BHIM'],
                    ['card', '💳', 'Credit / Debit Card',   'Visa, Mastercard, RuPay'],
                    ['emi',  '📅', 'EMI',                   '3, 6, 9 or 12 month options'],
                    ['bank', '🏦', 'Net Banking',           'SBI, HDFC, ICICI, Axis, Kotak'],
                ] as [$val, $icon, $label, $sub]): ?>
                <label style="display:flex;align-items:center;gap:12px;padding:12px 14px;border:1.5px solid #e8e8e8;border-radius:10px;cursor:pointer;transition:border-color .18s;" class="pay-opt-label">
                    <input type="radio" name="bnPayOption" value="<?= $val ?>" style="accent-color:#8B2500;width:16px;height:16px;">
                    <span style="font-size:20px;"><?= $icon ?></span>
                    <div>
                        <div style="font-size:14px;font-weight:600;"><?= $label ?></div>
                        <div style="font-size:11px;color:#aaa;"><?= $sub ?></div>
                    </div>
                </label>
                <?php endforeach; ?>
            </div>

            <!-- Card fields (shown when card is selected) -->
            <div id="bnCardPanel" style="display:none;margin-bottom:12px;">
                <input id="bnCardNum"  type="text" placeholder="Card Number (16 digits)" maxlength="19"
                    style="width:100%;padding:9px 12px;border:1.5px solid #e0e0e0;border-radius:8px;font-size:13px;font-family:inherit;outline:none;box-sizing:border-box;margin-bottom:8px;">
                <input id="bnCardName" type="text" placeholder="Name on Card" maxlength="60"
                    style="width:100%;padding:9px 12px;border:1.5px solid #e0e0e0;border-radius:8px;font-size:13px;font-family:inherit;outline:none;box-sizing:border-box;margin-bottom:8px;">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
                    <input id="bnCardExp" type="text" placeholder="MM / YY" maxlength="7"
                        style="padding:9px 12px;border:1.5px solid #e0e0e0;border-radius:8px;font-size:13px;font-family:inherit;outline:none;box-sizing:border-box;">
                    <input id="bnCardCvv" type="text" placeholder="CVV" maxlength="3"
                        style="padding:9px 12px;border:1.5px solid #e0e0e0;border-radius:8px;font-size:13px;font-family:inherit;outline:none;box-sizing:border-box;">
                </div>
                <p style="font-size:11px;color:#aaa;margin-top:6px;">🔒 256-bit encryption — demo only</p>
            </div>

            <!-- EMI panel (shown when emi is selected) -->
            <div id="bnEmiPanel" style="display:none;margin-bottom:12px;">
                <p style="font-size:12px;color:#555;margin-bottom:8px;">Select your EMI plan:</p>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:10px;" id="bnEmiGrid">
                    <?php foreach ([3,6,9,12] as $bnT):
                        $bnInt = $bnT <= 6 ? 'No Cost EMI' : '1.5% p.m.';
                    ?>
                    <button type="button" class="bn-emi-opt" data-t="<?= $bnT ?>"
                        style="padding:10px;border:1.5px solid #e0e0e0;border-radius:8px;text-align:center;cursor:pointer;font-family:inherit;background:#fff;transition:all .18s;">
                        <strong style="display:block;font-size:14px;"><?= $bnT ?> months</strong>
                        <span class="bn-emi-amt" style="display:block;font-size:13px;font-weight:600;color:#1e1e1e;">₹—/mo</span>
                        <span style="font-size:10px;color:#8B2500;"><?= $bnInt ?></span>
                    </button>
                    <?php endforeach; ?>
                </div>
                <p style="font-size:12px;font-weight:600;color:#555;margin-bottom:6px;">Select bank:</p>
                <?php foreach (['HDFC Bank','ICICI Bank','SBI Card','Axis Bank','Kotak Bank'] as $bnBk): ?>
                <label style="display:flex;align-items:center;gap:8px;font-size:13px;padding:5px 0;cursor:pointer;border-bottom:1px solid #f5f5f5;">
                    <input type="radio" name="bnEmiBank" value="<?= $bnBk ?>" style="accent-color:#8B2500;"> <?= $bnBk ?>
                </label>
                <?php endforeach; ?>
                <div id="bnEmiBankDetails" style="display:none;margin-top:10px;padding:14px;background:#fef9ec;border:1px solid #fde68a;border-radius:10px;">
                    <p style="font-size:12px;font-weight:700;color:#555;margin-bottom:8px;">Bank Account Details</p>
                    <input type="text" id="bnEmiAccNum" placeholder="Account Number" maxlength="18" inputmode="numeric"
                        style="width:100%;padding:9px 12px;border:1.5px solid #e0e0e0;border-radius:8px;font-size:13px;font-family:inherit;outline:none;box-sizing:border-box;margin-bottom:8px;">
                    <input type="text" id="bnEmiIfsc" placeholder="IFSC Code (e.g. SBIN0001234)" maxlength="11"
                        style="width:100%;padding:9px 12px;border:1.5px solid #e0e0e0;border-radius:8px;font-size:13px;font-family:inherit;outline:none;box-sizing:border-box;text-transform:uppercase;margin-bottom:8px;">
                    <input type="text" id="bnEmiAccName" placeholder="Account Holder Name" maxlength="80"
                        style="width:100%;padding:9px 12px;border:1.5px solid #e0e0e0;border-radius:8px;font-size:13px;font-family:inherit;outline:none;box-sizing:border-box;margin-bottom:4px;">
                    <p style="font-size:10px;color:#aaa;">🔒 Demo mode — no real transaction</p>
                </div>
            </div>

            <!-- Net Banking panel (shown when bank is selected) -->
            <div id="bnBankPanel" style="display:none;margin-bottom:12px;">
                <p style="font-size:12px;color:#555;margin-bottom:8px;">Select your bank:</p>
                <?php foreach ([
                    ['sbi',   '#1a3d7c', '🏛️', 'State Bank of India'],
                    ['hdfc',  '#004c8c', '🏦', 'HDFC Bank'],
                    ['icici', '#b02121', '🏦', 'ICICI Bank'],
                    ['axis',  '#97144d', '🏦', 'Axis Bank'],
                    ['kotak', '#ed1c24', '🏦', 'Kotak Mahindra Bank'],
                ] as [$bk, $bColor, $bIcon, $bName]): ?>
                <div class="bn-bank-opt" data-b="<?= $bk ?>"
                    style="display:flex;align-items:center;gap:12px;padding:10px 12px;border:1.5px solid #e0e0e0;border-radius:8px;margin-bottom:6px;cursor:pointer;transition:all .18s;">
                    <div style="width:32px;height:32px;border-radius:6px;background:<?= $bColor ?>;display:flex;align-items:center;justify-content:center;font-size:16px;flex-shrink:0;"><?= $bIcon ?></div>
                    <strong style="font-size:13px;"><?= $bName ?></strong>
                </div>
                <?php endforeach; ?>
                <div id="bnBankDetailsBox" style="display:none;margin-top:12px;padding:14px;background:#fef9ec;border:1px solid #fde68a;border-radius:10px;">
                    <p style="font-size:12px;font-weight:700;color:#555;margin-bottom:10px;">Bank Account Details</p>
                    <div style="margin-bottom:8px;">
                        <label style="display:block;font-size:11px;font-weight:600;color:#666;text-transform:uppercase;margin-bottom:4px;">Account Number *</label>
                        <input type="text" id="bnBankAccNum" placeholder="Enter account number" maxlength="18"
                            style="width:100%;padding:9px 12px;border:1.5px solid #e0e0e0;border-radius:8px;font-size:13px;font-family:inherit;outline:none;box-sizing:border-box;">
                    </div>
                    <div style="margin-bottom:8px;">
                        <label style="display:block;font-size:11px;font-weight:600;color:#666;text-transform:uppercase;margin-bottom:4px;">IFSC Code *</label>
                        <input type="text" id="bnBankIfsc" placeholder="e.g. SBIN0001234" maxlength="11"
                            style="width:100%;padding:9px 12px;border:1.5px solid #e0e0e0;border-radius:8px;font-size:13px;font-family:inherit;outline:none;box-sizing:border-box;text-transform:uppercase;">
                    </div>
                    <div>
                        <label style="display:block;font-size:11px;font-weight:600;color:#666;text-transform:uppercase;margin-bottom:4px;">Account Holder Name *</label>
                        <input type="text" id="bnBankAccName" placeholder="Name as per bank records" maxlength="80"
                            style="width:100%;padding:9px 12px;border:1.5px solid #e0e0e0;border-radius:8px;font-size:13px;font-family:inherit;outline:none;box-sizing:border-box;">
                    </div>
                    <p style="font-size:10px;color:#aaa;margin-top:8px;">🔒 Demo mode — no real transaction</p>
                </div>
            </div>

            <p id="bnPayError" style="color:#c0392b;font-size:12px;margin-bottom:10px;display:none;">⚠ Please select a payment method</p>
            <button id="bnConfirmBtn"
                style="width:100%;padding:12px;background:#8B2500;color:#fff;border:none;border-radius:8px;font-size:14px;font-weight:700;cursor:pointer;font-family:inherit;">
                Confirm &amp; Place Order 🎉
            </button>
            <button onclick="bnShowAddr()"
                style="width:100%;padding:10px;background:#f5f5f5;color:#666;border:none;border-radius:8px;font-size:13px;margin-top:8px;cursor:pointer;font-family:inherit;">
                ← Back to Address
            </button>
        </div>
    </div>
</div>

<!-- BN COUPON POPUP -->
<div id="bnCouponPopup" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,.52);z-index:9100;overflow-y:auto;padding:20px 12px 60px;">
<div style="background:#fff;width:420px;max-width:100%;margin:0 auto;border-radius:14px;padding:28px 24px 24px;position:relative;">
    <button onclick="document.getElementById('bnCouponPopup').style.display='none'" style="position:absolute;top:11px;right:14px;background:none;border:none;font-size:22px;cursor:pointer;color:#999;line-height:1;">✕</button>
    <h2 style="font-family:var(--font-display,serif);font-size:20px;margin-bottom:4px;">🎟️ Do you have a coupon?</h2>
    <p style="font-size:12px;color:#aaa;margin-bottom:16px;">Apply a coupon for a discount on this order</p>
    <div id="bnMyCouponsList" style="margin-bottom:14px;display:none;">
        <p style="font-size:11px;font-weight:600;color:#888;text-transform:uppercase;letter-spacing:.4px;margin-bottom:8px;">Your available coupons</p>
        <div id="bnMyCouponsItems"></div>
    </div>
    <div style="display:flex;gap:8px;margin-bottom:4px;">
        <input type="text" id="bnPopCouponInput" placeholder="Enter coupon code" maxlength="20"
               style="flex:1;padding:10px 12px;border:1.5px solid #e0e0e0;border-radius:8px;font-size:13px;font-family:inherit;outline:none;text-transform:uppercase;">
        <button id="bnPopApplyCouponBtn"
            style="padding:10px 18px;background:#8B2500;color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:700;cursor:pointer;font-family:inherit;white-space:nowrap;">Apply</button>
    </div>
    <p id="bnPopCouponMsg" style="font-size:12px;margin-bottom:14px;min-height:18px;"></p>
    <button id="bnPopContinueBtn"
        style="width:100%;padding:13px;background:#8B2500;color:#fff;border:none;border-radius:8px;font-size:14px;font-weight:700;cursor:pointer;font-family:inherit;margin-top:4px;">
        Continue to Payment →
    </button>
    <p id="bnSkipCoupon" style="font-size:11px;color:#bbb;text-align:center;margin-top:10px;cursor:pointer;">
        Skip — I don't have a coupon
    </p>
</div>
</div>

<!-- ORDER SUCCESS -->
<div id="orderSuccessModal" class="modal">
    <div class="modal-box modal-box--center">
        <button class="modal-close" onclick="closeAll();window.location='index.php'">✕</button>
        <div style="font-size:52px;margin-bottom:10px;">🎉</div>
        <h2>Order Placed!</h2>
        <p class="modal-sub">Your order is confirmed.<br>We'll deliver it soon 💗</p>
        <div id="bnCouponBox" style="display:none;margin:12px 0;padding:12px;background:#fff5f2;border:1.5px dashed #8B2500;border-radius:10px;text-align:center;">
            <p style="font-size:12px;color:#8B2500;font-weight:600;margin-bottom:4px;">🎁 You won a coupon!</p>
            <div id="bnCouponCode" style="font-size:22px;font-weight:800;color:#8B2500;letter-spacing:2px;font-family:monospace;"></div>
            <p id="bnCouponDisc" style="font-size:11px;color:#888;margin-top:3px;"></p>
            <p style="font-size:10px;color:#aaa;">Valid for 30 days on your next order</p>
        </div>
        <button class="modal-btn" onclick="closeAll();window.location='index.php'">Continue Shopping →</button>
    </div>
</div>

<div id="toast" class="toast"></div>

<style>
.cat-divider { height: 1px; background: #f0f0f0; margin: 8px 0; }
.cat-badge   { background: #8B2500; color: #fff; border-radius: 20px; font-size: 10px; padding: 1px 7px; margin-left: 4px; }
.cat-active  { color: #8B2500 !important; font-weight: 700; background: #fff5f2; }
.bn-steps     { display:flex;align-items:center;gap:6px;margin-bottom:16px; }
.bn-step      { display:flex;align-items:center;gap:6px;font-size:12px;font-weight:500;color:#bbb; }
.bn-step.active { color:#8B2500;font-weight:700; }
.bn-step.done   { color:#16a34a; }
.bn-step-dot  { width:22px;height:22px;border-radius:50%;border:2px solid currentColor;display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:700; }
.bn-step-line { flex:1;height:1px;background:#e0e0e0; }
.ff           { margin-bottom:10px; }
.ff label     { display:block;font-size:11px;font-weight:600;color:#666;text-transform:uppercase;letter-spacing:.4px;margin-bottom:4px; }
.ff input,.ff select,.ff textarea { width:100%;padding:9px 12px;border:1.5px solid #e0e0e0;border-radius:8px;font-size:13px;font-family:inherit;outline:none;box-sizing:border-box;transition:border-color .18s; }
.ff input:focus,.ff select:focus,.ff textarea:focus { border-color:#8B2500; }
.ff .hint     { font-size:11px;color:#bbb;margin-top:3px; }
.ff .emsg     { font-size:11px;color:#dc2626;margin-top:3px;display:none; }
.ff .emsg.show { display:block; }
.ff input.bad  { border-color:#dc2626!important;background:#fff8f8; }
.ff input.good { border-color:#16a34a!important; }
.sbtn          { width:100%;padding:12px;background:#8B2500;color:#fff;border:none;border-radius:8px;font-size:14px;font-weight:700;cursor:pointer;font-family:inherit;margin-top:12px;transition:background .2s; }
.sbtn:hover:not(:disabled) { background:#5c1800; }
.addr-btn      { padding:8px 16px;border:1.5px solid #e0e0e0;border-radius:20px;font-size:12px;font-weight:500;background:#fff;cursor:pointer;font-family:inherit;transition:all .18s; }
.addr-btn.sel  { border-color:#8B2500;background:#8B2500;color:#fff; }
.addr-card     { flex:1;min-width:200px;padding:12px 14px;border:1.5px solid #e0e0e0;border-radius:10px;cursor:pointer;transition:all .18s;font-size:12px; }
.addr-card:hover,.addr-card.sel { border-color:#8B2500;background:#fff5f2; }
.addr-card strong { display:block;font-size:13px;margin-bottom:4px; }
</style>

<script>
const CSRF       = <?= json_encode(csrf_token()) ?>;
const isLoggedIn = <?= $_isLoggedIn ? 'true' : 'false' ?>;
const cartData   = <?= json_encode(array_values($_SESSION['cart']    ?? [])) ?>;
const wishData   = <?= json_encode(array_values($_SESSION['wishlist'] ?? [])) ?>;

const savedAddresses = <?= json_encode(
    $_isLoggedIn
        ? (is_array(($__navUser['addresses'] ?? null)) ? array_values($__navUser['addresses']) : [])
        : []
) ?>;

function updateCartCount() {
    document.querySelectorAll('.cart-count-badge').forEach(e => e.textContent = cartData.length);
    document.querySelectorAll('.cat-badge').forEach(e => {
        if (e.closest('a[href="cart.php"]') || e.closest('.cat-special')) e.textContent = cartData.length;
    });
}
function updateWishCount() { document.querySelectorAll('.wish-count-badge').forEach(e => e.textContent = wishData.length); }

function showToast(msg, type) {
    const t = document.getElementById('toast');
    if (!t) return;
    t.textContent = msg;
    t.className = 'toast show' + (type === 'error' ? ' toast-error' : '');
    clearTimeout(t._t);
    t._t = setTimeout(() => t.className = 'toast', 3000);
}

function closeAll() { document.querySelectorAll('.modal').forEach(m => m.style.display = 'none'); document.body.style.overflow = ''; }
function openLogin()     { closeAll(); document.getElementById('loginModal').style.display     = 'block'; document.body.style.overflow = 'hidden'; }
function showSignup()    { closeAll(); document.getElementById('signupModal').style.display    = 'block'; document.body.style.overflow = 'hidden'; }
function showLoginForm() { closeAll(); document.getElementById('loginFormModal').style.display = 'block'; document.body.style.overflow = 'hidden'; }
function openCatPanel()  { document.getElementById('catPanel').classList.add('open');    document.getElementById('catOverlay').classList.add('open'); }
function closeCatPanel() { document.getElementById('catPanel').classList.remove('open'); document.getElementById('catOverlay').classList.remove('open'); }

// ── Wishlist toggle ──────────────────────────────────────────────────────────
function toggleWishlist(btn) {
    btn.disabled = true;
    const name = btn.dataset.name;
    const isIn = btn.classList.contains('active');
    const d = new FormData();
    d.append('csrf_token', CSRF); d.append('name', name); d.append('action', isIn ? 'remove' : 'add');
    fetch('toggle_wishlist.php', { method: 'POST', body: d })
        .then(r => r.json())
        .then(res => {
            if (res.error) { showToast(res.error, 'error'); btn.disabled = false; return; }
            btn.classList.toggle('active');
            btn.textContent = isIn ? '🤍' : '❤️';
            if (isIn) { const i = wishData.indexOf(name); if (i > -1) wishData.splice(i, 1); showToast('Removed from wishlist'); }
            else       { wishData.push(name); showToast('Added to wishlist 💗'); }
            updateWishCount(); btn.disabled = false;
        })
        .catch(() => { showToast('Network error', 'error'); btn.disabled = false; });
}

// ── Add to cart ──────────────────────────────────────────────────────────────
function addToCart(btn) {
    const d = new FormData();
    d.append('csrf_token', CSRF);
    d.append('name', btn.dataset.name);
    d.append('size', btn.dataset.size || '');
    fetch('add_to_cart.php', { method: 'POST', body: d })
        .then(r => r.json())
        .then(res => {
            if (res.error) { showToast(res.error, 'error'); btn.disabled = false; return; }
            let item = cartData.find(i => i.name === btn.dataset.name);
            if (item) item.qty++;
            else cartData.push({ 
    name: btn.dataset.name, 
    qty: 1, 
    price: Number(res.price),  // ✅ FORCE NUMBER
    image: res.image 
});
            updateCartCount();
            const cc = btn.closest('.cart-controls');
            if (cc) cc.innerHTML = buildQtyBox({ name: btn.dataset.name, price: res.price, image: res.image }, 1);
            showToast('Added to cart 💗');
        })
        .catch(() => { showToast('Network error', 'error'); btn.disabled = false; });
}

// ── Update qty ───────────────────────────────────────────────────────────────
function updateQty(box, action) {
    const name = box.dataset.name;
    const size = box.dataset.size || '';
    const d = new FormData();
    d.append('csrf_token', CSRF); d.append('name', name); d.append('size', size); d.append('action', action);
    fetch('update_cart.php', { method: 'POST', body: d })
        .then(r => r.json())
        .then(res => {
            let item = cartData.find(i => i.name === name);
            if (res.removed) {
                if (item) cartData.splice(cartData.indexOf(item), 1);
                box.innerHTML = `<button class="add-btn" data-name="${escH(name)}">Add to Cart</button>`;
            } else {
                if (item) item.qty = res.qty;
                box.querySelector('span').textContent = res.qty;
                box.querySelectorAll('button').forEach(b => b.disabled = false);
            }
            updateCartCount();
        })
        .catch(() => { showToast('Network error', 'error'); box.querySelectorAll('button').forEach(b => b.disabled = false); });
}

function buildQtyBox(ds, qty) {
    return `<div class="qty-box" data-name="${escH(ds.name)}" data-price="${ds.price||''}" data-image="${escH(ds.image||'')}">
        <button class="qty-btn minus">−</button><span>${qty}</span><button class="qty-btn plus">+</button></div>`;
}
function escH(s) { return String(s).replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

// ── Size guard ───────────────────────────────────────────────────────────────
function sizeGuard(btn, action) {
    if (btn.dataset.needsSize !== '1') return true;
    if ((btn.dataset.size || '').trim() !== '') return true;
    // Open size picker popup
    openSizePick(btn, action || 'cart');
    return false;
}

// ── Size picker popup (inline — no extra file needed) ────────────────────
function openSizePick(btn, action) {
    window._szBtn    = btn;
    window._szAction = action || 'cart';
    window._szChosen = '';

    var name  = btn.dataset.name || '';
    var sizes = [];
    try { sizes = JSON.parse(btn.dataset.sizes || '[]'); } catch(e) {}

    document.getElementById('_szProductName').textContent    = name;
    document.getElementById('_szErr').style.display          = 'none';
    document.getElementById('_szConfirmBtn').textContent     =
        action === 'buynow' ? 'Buy Now →' : 'Add to Cart';

    var wrap = document.getElementById('_szChips');
    wrap.innerHTML = sizes.map(function(sz) {
        return '<button type="button" class="_szChip" onclick="_chooseSz(this,\'' + sz + '\')" style="padding:8px 18px;border:1.5px solid var(--border);border-radius:var(--radius-sm);background:var(--white);font-size:13px;font-weight:600;color:var(--text);cursor:pointer;font-family:var(--font-body);transition:all .15s;">' + sz + '</button>';
    }).join('');

    document.getElementById('_szModal').style.display = 'block';
    document.body.style.overflow = 'hidden';
}
function _chooseSz(el, sz) {
    window._szChosen = sz;
    document.querySelectorAll('._szChip').forEach(function(c) {
        c.style.background  = 'var(--white)';
        c.style.borderColor = 'var(--border)';
        c.style.color       = 'var(--text)';
    });
    el.style.background  = 'var(--brand)';
    el.style.borderColor = 'var(--brand)';
    el.style.color       = '#fff';
    document.getElementById('_szErr').style.display = 'none';
}
function _confirmSz() {
    if (!window._szChosen) {
        document.getElementById('_szErr').style.display = 'block';
        return;
    }
    var btn = window._szBtn;
    if (btn) btn.dataset.size = window._szChosen;
    _closeSzModal();
    if (window._szAction === 'buynow' && btn) {
        openBuyNow(btn.dataset.name, window._szChosen, btn.dataset.price || 0);
    } else if (btn) {
        btn.disabled = true;
        addToCart(btn);
    }
}
function _closeSzModal() {
    document.getElementById('_szModal').style.display = 'none';
    document.body.style.overflow = '';
}
function _openSzChart() {
    document.getElementById('_szChartModal').style.display = 'block';
}
function _closeSzChart() {
    document.getElementById('_szChartModal').style.display = 'none';
}

// ── Global click handler ─────────────────────────────────────────────────────
document.addEventListener('click', function(e) {
    const heart = e.target.closest('.heart-btn');
    if (heart) { if (!isLoggedIn) { openLogin(); return; } toggleWishlist(heart); return; }

    if (e.target.classList.contains('add-btn')) {
        if (!isLoggedIn) { openLogin(); return; }
        if (!sizeGuard(e.target, 'cart')) return;
        e.target.disabled = true;
        addToCart(e.target);
        return;
    }

    if (e.target.classList.contains('plus'))  { e.target.disabled = true; updateQty(e.target.closest('.qty-box'), 'plus');  return; }
    if (e.target.classList.contains('minus')) { e.target.disabled = true; updateQty(e.target.closest('.qty-box'), 'minus'); return; }

    const buyBtn = e.target.closest('.buy-now-btn');
    if (buyBtn) {
        if (!isLoggedIn) { openLogin(); return; }
        if (!sizeGuard(buyBtn, 'buynow')) return;
        openBuyNow(buyBtn.dataset.name, buyBtn.dataset.size || '', buyBtn.dataset.price || 0);
        return;
    }

    if (e.target.classList.contains('modal') || e.target.id === 'buyNowModal') closeAll();
});

// ── Buy Now ──────────────────────────────────────────────────────────────────
let bnCouponPct = 0, bnCouponApplied = '', bnBaseTotal = 0, bnBaseDelivery = 0;
let bnAddrModeVal = savedAddresses.length > 0 ? 'saved' : 'new';
let bnSelectedIdx = savedAddresses.length > 0 ? 0 : -1;
let bnSelectedEmiTenure = null, bnSelectedBank = null;

function openBuyNow(name, size, price) {
    document.getElementById('buyItemName').value = name;
    document.getElementById('buyItemSize').value = size;
    bnCouponPct = 0; bnCouponApplied = ''; bnSelectedEmiTenure = null; bnSelectedBank = null;

    const knownItem = cartData.find(i => i.name === name);

const cleanPrice = Number(price);
const cleanCartPrice = knownItem ? Number(knownItem.price) : 0;

bnBaseTotal = !isNaN(cleanPrice) && cleanPrice > 0
    ? cleanPrice
    : (!isNaN(cleanCartPrice) ? cleanCartPrice : 0);

    const setPrice = (p) => {
        bnBaseTotal = p;
        if (p <= 0)        bnBaseDelivery = 0;
        else if (p > 1000) bnBaseDelivery = 0;
        else if (p >= 500) bnBaseDelivery = 40;
        else               bnBaseDelivery = 50;
        document.getElementById('bnPayAmt').textContent = p + bnBaseDelivery;
        const sa = document.getElementById('bnSubAmt'); if(sa) sa.textContent = p;
        const da = document.getElementById('bnDelAmt'); if(da) da.textContent = bnBaseDelivery;
    };
    if (bnBaseTotal > 0) { setPrice(bnBaseTotal); }
    else {
        document.getElementById('bnPayAmt').textContent = '…';
        fetch('get_price.php?name=' + encodeURIComponent(name))
            .then(r => r.json()).then(res => { const price = Number(res.price);
if (!isNaN(price)) {
    setPrice(price);
} else {
    console.error("❌ Invalid price from API:", res.price);
} }).catch(() => {});
    }

    document.getElementById('buyNowForm').reset();
    document.getElementById('buyNowForm').querySelector('[name="csrf_token"]').value = CSRF;

    const wrap  = document.getElementById('bnSavedAddrsWrap');
    const cards = document.getElementById('bnSavedCards');
    if (savedAddresses.length > 0) {
        wrap.style.display = 'block';
        cards.innerHTML = savedAddresses.map((a, i) => `
            <div class="addr-card${i === 0 ? ' sel' : ''}" data-idx="${i}" onclick="bnSelectAddr(${i})">
                <strong>${escH(a.full_name || 'Address '+(i+1))}</strong>
                ${escH(a.flat||'')}, ${escH(a.area||'')}<br>
                ${escH(a.city||'')} - ${escH(a.pincode||'')}<br>
                📱 ${escH(a.mobile||'')}
                ${a.is_default ? '<br><span style="color:#8B2500;font-size:10px;font-weight:700;">★ Default</span>' : ''}
            </div>`).join('');
        bnSetMode('saved');
    } else {
        wrap.style.display = 'none';
        bnSetMode('new');
    }

    bnShowAddr();
    closeAll();
    document.getElementById('buyNowModal').style.display = 'block';
    document.body.style.overflow = 'hidden';
}

function bnSelectAddr(idx) {
    bnSelectedIdx = idx;
    document.querySelectorAll('#bnSavedCards .addr-card').forEach(c => c.classList.remove('sel'));
    document.querySelector(`#bnSavedCards .addr-card[data-idx="${idx}"]`)?.classList.add('sel');
    bnAddrModeVal = 'saved';
    bnSetMode('saved');
}

function bnSetMode(mode) {
    bnAddrModeVal = mode;
    const form   = document.getElementById('buyNowForm');
    const fields = form.querySelectorAll('input:not([type=hidden]):not([type=checkbox]), select, textarea');
    if (mode === 'saved') {
        fields.forEach(f => { f.style.display = 'none'; const lbl = f.closest('.ff'); if(lbl) lbl.style.display = 'none'; });
        document.getElementById('bnBtnUseSaved')?.classList.add('sel');
        document.getElementById('bnBtnNewAddr')?.classList.remove('sel');
    } else {
        fields.forEach(f => { f.style.display = ''; const lbl = f.closest('.ff'); if(lbl) lbl.style.display = ''; });
        document.getElementById('bnBtnUseSaved')?.classList.remove('sel');
        document.getElementById('bnBtnNewAddr')?.classList.add('sel');
    }
}

function bnShowAddr() {
    document.getElementById('bnAddrPanel').style.display = 'block';
    document.getElementById('bnPayPanel').style.display  = 'none';
    document.getElementById('bnStep1dot').classList.add('active');
    document.getElementById('bnStep1dot').classList.remove('done');
    document.getElementById('bnStep2dot').classList.remove('active', 'done');
}

function bnShowPay() {
    document.getElementById('bnAddrPanel').style.display = 'none';
    document.getElementById('bnPayPanel').style.display  = 'block';
    document.getElementById('bnStep1dot').classList.remove('active');
    document.getElementById('bnStep1dot').classList.add('done');
    document.getElementById('bnStep2dot').classList.add('active');
    const sub = bnBaseTotal;
    let delivery = 0;
    if (sub > 0 && sub <= 1000) delivery = sub >= 500 ? 40 : 50;
    bnBaseDelivery = delivery;
    let fullAmt = sub + delivery;
    if (bnCouponPct > 0) fullAmt = Math.round(fullAmt * (1 - bnCouponPct / 100));
    document.getElementById('bnPayAmt').textContent = fullAmt;
    const sa = document.getElementById('bnSubAmt'); if(sa) sa.textContent = sub;
    const da = document.getElementById('bnDelAmt'); if(da) da.textContent = delivery;
    const dl = document.getElementById('bnDeliveryLine'); if(dl) dl.style.display = sub > 0 ? 'block' : 'none';
    const cs = document.getElementById('bnCouponSaving');
    if (cs) { if (bnCouponPct > 0) { cs.style.display = 'inline'; cs.textContent = '(' + bnCouponPct + '% off)'; } else cs.style.display = 'none'; }
    bnUpdateEmiAmounts(fullAmt);
}

document.getElementById('bnApplyCouponBtn')?.addEventListener('click', function() {
    const code = document.getElementById('bn_coupon').value.trim();
    const msg  = document.getElementById('bnCouponMsg');
    if (!code) { msg.style.color = '#dc2626'; msg.textContent = 'Enter a coupon code'; return; }
    const fd = new FormData(); fd.append('csrf_token', CSRF); fd.append('code', code);
    fetch('apply_coupon.php', { method: 'POST', body: fd }).then(r => r.json()).then(res => {
        if (res.error) { msg.style.color = '#dc2626'; msg.textContent = '❌ ' + res.error; bnCouponPct = 0; bnCouponApplied = ''; }
        else { bnCouponPct = res.discount; bnCouponApplied = code; msg.style.color = '#16a34a'; msg.textContent = '✅ ' + res.discount + '% discount applied!'; }
    }).catch(() => { msg.style.color = '#dc2626'; msg.textContent = 'Could not verify coupon'; });
});

const bnRules = {
    bn_country:  v => v.trim().length >= 2,
    bn_name:     v => /^[A-Za-z\s]{2,}$/.test(v.trim()),
    bn_mobile:   v => /^[6-9][0-9]{9}$/.test(v.trim()),
    bn_flat:     v => v.trim().length >= 2,
    bn_area:     v => v.trim().length >= 2,
    bn_landmark: v => v.trim().length >= 2,
    bn_pincode:  v => /^[1-9][0-9]{5}$/.test(v.trim()),
    bn_city:     v => v.trim().length >= 2,
};
function bnValidate() {
    let ok = true, first = null;
    Object.entries(bnRules).forEach(([id, rule]) => {
        const el = document.getElementById(id); if (!el || el.closest('.ff')?.style.display === 'none') return;
        const em = document.getElementById('bn_e_' + id.replace('bn_', ''));
        const pass = rule(el.value);
        el.classList.toggle('bad', !pass); el.classList.toggle('good', pass && el.value.trim().length > 0);
        if (em) em.classList.toggle('show', !pass);
        if (!pass) { ok = false; if (!first) first = el; }
    });
    if (first) { first.scrollIntoView({ behavior: 'smooth', block: 'center' }); first.focus(); }
    return ok;
}

document.getElementById('bnProceedToPayBtn')?.addEventListener('click', function() {
    if (bnAddrModeVal === 'new' && !bnValidate()) { showToast('Please fill all required fields', 'error'); return; }
    openBnCouponPopup();
});

function openBnCouponPopup() {
    document.getElementById('bnPopCouponInput').value = bnCouponApplied;
    const msg = document.getElementById('bnPopCouponMsg');
    msg.textContent = bnCouponApplied ? '✅ ' + bnCouponPct + '% discount applied!' : '';
    msg.style.color = '#16a34a';
    fetch('get_user_coupons.php').then(r => r.json()).then(res => {
        const list = document.getElementById('bnMyCouponsItems');
        const wrap = document.getElementById('bnMyCouponsList');
        if (res.coupons && res.coupons.length > 0) {
            wrap.style.display = 'block';
            list.innerHTML = res.coupons.map(c => `
                <button class="coupon-chip${bnCouponApplied === c.code ? ' sel' : ''}" onclick="bnSelectCouponChip('${c.code}',${c.discount})">
                    <span class="cc">${c.code}</span>
                    <span class="cd">${c.discount}% off</span>
                    <span class="ck">${bnCouponApplied === c.code ? '✅' : '→'}</span>
                </button>`).join('');
        } else { wrap.style.display = 'none'; }
    }).catch(() => {});
    document.getElementById('bnCouponPopup').style.display = 'block';
    document.body.style.overflow = 'hidden';
}

function bnSelectCouponChip(code, discount) {
    document.getElementById('bnPopCouponInput').value = code;
    bnApplyCouponInPopup(code);
}

function bnApplyCouponInPopup(code) {
    code = code || document.getElementById('bnPopCouponInput').value.trim().toUpperCase();
    const msg = document.getElementById('bnPopCouponMsg');
    if (!code) { msg.style.color = '#dc2626'; msg.textContent = 'Enter a coupon code'; return; }
    const fd = new FormData(); fd.append('csrf_token', CSRF); fd.append('code', code);
    fetch('apply_coupon.php', { method: 'POST', body: fd }).then(r => r.json()).then(res => {
        if (res.error) { msg.style.color = '#dc2626'; msg.textContent = '❌ ' + res.error; bnCouponPct = 0; bnCouponApplied = ''; }
        else {
            bnCouponPct = res.discount; bnCouponApplied = code;
            msg.style.color = '#16a34a'; msg.textContent = '✅ ' + res.discount + '% discount applied!';
            document.querySelectorAll('#bnMyCouponsItems .coupon-chip').forEach(c => {
                const isThis = c.querySelector('.cc')?.textContent === code;
                c.classList.toggle('sel', isThis); c.querySelector('.ck').textContent = isThis ? '✅' : '→';
            });
        }
    }).catch(() => { msg.style.color = '#dc2626'; msg.textContent = 'Could not verify coupon'; });
}

document.getElementById('bnPopApplyCouponBtn')?.addEventListener('click', function() {
    bnApplyCouponInPopup(document.getElementById('bnPopCouponInput').value.trim().toUpperCase());
});
document.getElementById('bnPopCouponInput')?.addEventListener('input', function() { this.value = this.value.toUpperCase(); });
document.getElementById('bnPopContinueBtn')?.addEventListener('click', function() {
    document.getElementById('bnCouponPopup').style.display = 'none'; bnShowPay();
});
document.getElementById('bnSkipCoupon')?.addEventListener('click', function() {
    bnCouponPct = 0; bnCouponApplied = '';
    document.getElementById('bnCouponPopup').style.display = 'none'; bnShowPay();
});

document.querySelectorAll('input[name="bnPayOption"]').forEach(radio => {
    radio.addEventListener('change', () => {
        document.querySelectorAll('.pay-opt-label').forEach(l => l.style.borderColor = '#e8e8e8');
        radio.closest('.pay-opt-label').style.borderColor = '#8B2500';
        document.getElementById('bnPayError').style.display = 'none';
        const m = radio.value;
        document.getElementById('bnCardPanel').style.display = m === 'card' ? 'block' : 'none';
        document.getElementById('bnEmiPanel').style.display  = m === 'emi'  ? 'block' : 'none';
        document.getElementById('bnBankPanel').style.display = m === 'bank' ? 'block' : 'none';
    });
});

document.getElementById('bnCardNum')?.addEventListener('input', function() {
    const d = this.value.replace(/\D/g, '').substring(0, 16);
    this.value = d.match(/.{1,4}/g)?.join(' ') || d;
});
document.getElementById('bnCardExp')?.addEventListener('input', function() {
    const d = this.value.replace(/\D/g, '');
    this.value = d.length >= 2 ? d.substring(0, 2) + ' / ' + d.substring(2, 4) : d;
});
document.getElementById('bnCardCvv')?.addEventListener('input', function() {
    this.value = this.value.replace(/\D/g, '').substring(0, 3);
});

document.addEventListener('click', function(e) {
    const emiBtn = e.target.closest('.bn-emi-opt');
    if (emiBtn && document.getElementById('bnEmiPanel')?.contains(emiBtn)) {
        document.querySelectorAll('#bnEmiGrid .bn-emi-opt').forEach(x => { x.style.borderColor = '#e0e0e0'; x.style.background = '#fff'; });
        emiBtn.style.borderColor = '#8B2500'; emiBtn.style.background = '#fff5f2';
        bnSelectedEmiTenure = parseInt(emiBtn.dataset.t);
    }
    const bankBtn = e.target.closest('.bn-bank-opt');
    if (bankBtn && document.getElementById('bnBankPanel')?.contains(bankBtn)) {
        document.querySelectorAll('#bnBankPanel .bn-bank-opt').forEach(x => { x.style.borderColor = '#e0e0e0'; x.style.background = '#fff'; });
        bankBtn.style.borderColor = '#8B2500'; bankBtn.style.background = '#fff5f2';
        bnSelectedBank = bankBtn.dataset.b;
        document.getElementById('bnBankDetailsBox').style.display = 'block';
    }
});

document.querySelectorAll('input[name="bnEmiBank"]').forEach(r => {
    r.addEventListener('change', () => { document.getElementById('bnEmiBankDetails').style.display = 'block'; });
});
['bnBankIfsc', 'bnEmiIfsc'].forEach(id => {
    document.getElementById(id)?.addEventListener('input', function() { this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, ''); });
});
['bnBankAccNum', 'bnEmiAccNum'].forEach(id => {
    document.getElementById(id)?.addEventListener('input', function() { this.value = this.value.replace(/[^0-9]/g, ''); });
});

function bnUpdateEmiAmounts(total) {
    document.querySelectorAll('#bnEmiGrid .bn-emi-opt').forEach(btn => {
        const t = parseInt(btn.dataset.t) || 1;
        const mo = total > 0 ? Math.ceil(total / t) : 0;
        const amtSpan = btn.querySelector('.bn-emi-amt');
        if (amtSpan) amtSpan.textContent = mo > 0 ? '₹' + mo + '/mo' : '₹—/mo';
    });
}

document.getElementById('bnConfirmBtn')?.addEventListener('click', function() {
    const selected = document.querySelector('input[name="bnPayOption"]:checked');
    if (!selected) { document.getElementById('bnPayError').style.display = 'block'; return; }
    const method = selected.value;
    this.disabled = true; this.textContent = 'Placing order…';
    const btn = this;

    const buildFd = () => {
        const fd = new FormData(document.getElementById('buyNowForm'));
        fd.set('payment_method', method);
        fd.set('coupon_code', bnCouponApplied);
        if (bnAddrModeVal === 'saved' && bnSelectedIdx >= 0) {
            fd.set('using_saved', '1'); fd.set('use_saved_addr', bnSelectedIdx);
        }
        return fd;
    };

    const doPlaceOrder = (fd) => {
        fetch('place_order.php', { method: 'POST', body: fd })
            .then(r => { if (!r.ok) throw new Error(r.status); return r.json(); })
            .then(res => {
                if (res.error) { showToast(res.error, 'error'); btn.disabled = false; btn.textContent = 'Confirm & Place Order 🎉'; return; }
                if (res.coupon) {
                    document.getElementById('bnCouponBox').style.display = 'block';
                    document.getElementById('bnCouponCode').textContent  = res.coupon.code;
                    document.getElementById('bnCouponDisc').textContent  = res.coupon.discount + '% off your next order';
                }
                document.getElementById('buyNowModal').style.display      = 'none';
                document.getElementById('orderSuccessModal').style.display = 'block';
            })
            .catch(err => { showToast('Network error — ' + err.message, 'error'); btn.disabled = false; btn.textContent = 'Confirm & Place Order 🎉'; });
    };

    if (method === 'upi') {
        btn.textContent = 'Opening payment…';
        const upiAmt    = parseInt(document.getElementById('bnPayAmt').textContent) || 0;
        const upiName   = bnAddrModeVal === 'saved' ? '' : (document.getElementById('bn_name')?.value   || '');
        const upiMobile = bnAddrModeVal === 'saved' ? '' : (document.getElementById('bn_mobile')?.value || '');
        const upiEmail  = bnAddrModeVal === 'saved' ? '' : (document.getElementById('bn_email')?.value  || '');
        function bnUpiErr(msg) { showToast('❌ ' + msg, 'error'); btn.disabled = false; btn.textContent = 'Confirm & Place Order 🎉'; }
        const cfFd = new FormData();
        cfFd.append('csrf_token', CSRF); cfFd.append('amount', upiAmt);
        cfFd.append('name', upiName); cfFd.append('mobile', upiMobile); cfFd.append('email', upiEmail);
        fetch('cashfree_payment.php', { method: 'POST', body: cfFd })
            .then(r => r.json())
            .then(res => {
                if (res.error) { bnUpiErr(res.error); return; }
                const sessionId = res.payment_session_id, cfOrderId = res.order_id || '';
                const pFd = new FormData(document.getElementById('buyNowForm'));
                pFd.set('cashfree_order_id', cfOrderId); pFd.set('coupon_code', bnCouponApplied);
                if (bnAddrModeVal === 'saved' && bnSelectedIdx >= 0) { pFd.set('using_saved', '1'); pFd.set('use_saved_addr', bnSelectedIdx); }
                fetch('save_pending_order.php', { method: 'POST', body: pFd })
                    .then(r => r.json())
                    .then(pr => {
                        if (pr.error) { bnUpiErr('Could not save order: ' + pr.error); return; }
                        const script = document.createElement('script');
                        script.src = 'https://sdk.cashfree.com/js/v3/cashfree.js';
                        script.onload = () => { const cf = Cashfree({ mode: res.test_mode ? 'sandbox' : 'production' }); cf.checkout({ paymentSessionId: sessionId, redirectTarget: '_self' }); };
                        script.onerror = () => { bnUpiErr('Could not load payment SDK'); };
                        document.head.appendChild(script);
                    })
                    .catch(() => { bnUpiErr('Network error saving order'); });
            })
            .catch(err => { bnUpiErr('Network error: ' + err.message); });
        return;
    }

    if (method === 'card') {
        const num  = document.getElementById('bnCardNum')?.value.replace(/\s/g, '') || '';
        const name = document.getElementById('bnCardName')?.value.trim() || '';
        const exp  = document.getElementById('bnCardExp')?.value.trim() || '';
        const cvv  = document.getElementById('bnCardCvv')?.value.trim() || '';
        if (num.length < 16)                        { showToast('Enter valid 16-digit card number', 'error'); btn.disabled = false; btn.textContent = 'Confirm & Place Order 🎉'; return; }
        if (!name)                                   { showToast('Enter name on card', 'error');               btn.disabled = false; btn.textContent = 'Confirm & Place Order 🎉'; return; }
        if (!/^\d{2} \/ \d{2}$/.test(exp))           { showToast('Enter valid expiry MM / YY', 'error');      btn.disabled = false; btn.textContent = 'Confirm & Place Order 🎉'; return; }
        if (cvv.length < 3)                          { showToast('Enter 3-digit CVV', 'error');                btn.disabled = false; btn.textContent = 'Confirm & Place Order 🎉'; return; }
    }
    if (method === 'emi') {
        if (!bnSelectedEmiTenure) { showToast('Select an EMI tenure', 'error'); btn.disabled = false; btn.textContent = 'Confirm & Place Order 🎉'; return; }
        if (!document.querySelector('input[name="bnEmiBank"]:checked')) { showToast('Select your bank for EMI', 'error'); btn.disabled = false; btn.textContent = 'Confirm & Place Order 🎉'; return; }
        const emiAcc = (document.getElementById('bnEmiAccNum')?.value  || '').trim();
        const emiIfc = (document.getElementById('bnEmiIfsc')?.value    || '').trim();
        const emiAnm = (document.getElementById('bnEmiAccName')?.value || '').trim();
        if (!emiAcc) { showToast('Enter account number for EMI', 'error'); btn.disabled = false; btn.textContent = 'Confirm & Place Order 🎉'; return; }
        if (!/^[A-Z]{4}0[A-Z0-9]{6}$/.test(emiIfc)) { showToast('Enter valid IFSC code', 'error'); btn.disabled = false; btn.textContent = 'Confirm & Place Order 🎉'; return; }
        if (!emiAnm) { showToast('Enter account holder name', 'error'); btn.disabled = false; btn.textContent = 'Confirm & Place Order 🎉'; return; }
    }
    if (method === 'bank') {
        if (!bnSelectedBank) { showToast('Select your bank', 'error'); btn.disabled = false; btn.textContent = 'Confirm & Place Order 🎉'; return; }
        const bnAcc  = (document.getElementById('bnBankAccNum')?.value  || '').trim();
        const bnIfc  = (document.getElementById('bnBankIfsc')?.value    || '').trim();
        const bnAnm  = (document.getElementById('bnBankAccName')?.value || '').trim();
        if (!bnAcc)  { showToast('Enter account number', 'error');       btn.disabled = false; btn.textContent = 'Confirm & Place Order 🎉'; return; }
        if (!/^[A-Z]{4}0[A-Z0-9]{6}$/.test(bnIfc)) { showToast('Enter valid IFSC', 'error'); btn.disabled = false; btn.textContent = 'Confirm & Place Order 🎉'; return; }
        if (!bnAnm)  { showToast('Enter account holder name', 'error');  btn.disabled = false; btn.textContent = 'Confirm & Place Order 🎉'; return; }
    }

    doPlaceOrder(buildFd());
});

// ── Signup ───────────────────────────────────────────────────────────────────
document.getElementById('signupForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    if (this.password.value !== this.confirm.value) { showToast("Passwords don't match", 'error'); return; }
    if (!checkPasswordRules(this.password.value)) { showToast('Password does not meet requirements', 'error'); return; }
    const btn = this.querySelector("button[type='submit']");
    btn.disabled = true; btn.textContent = 'Creating account…';
    fetch('save_user.php', { method: 'POST', body: new FormData(this) }).then(r => r.json())
        .then(res => { if (res.error) { showToast(res.error, 'error'); btn.disabled = false; btn.textContent = 'Sign Up'; return; } closeAll(); location.reload(); })
        .catch(() => { showToast('Network error', 'error'); btn.disabled = false; btn.textContent = 'Sign Up'; });
});

function checkPasswordRules(pw) {
    const rules = { 'rule-len': pw.length >= 8, 'rule-lower': /[a-z]/.test(pw), 'rule-upper': /[A-Z]/.test(pw), 'rule-num': /[0-9]/.test(pw), 'rule-special': /[^a-zA-Z0-9]/.test(pw) };
    let allPass = true;
    Object.entries(rules).forEach(([id, pass]) => {
        const el = document.getElementById(id); const icon = el?.querySelector('.rule-icon');
        if (el) el.classList.toggle('rule-pass', pass);
        if (icon) icon.textContent = pass ? '✅' : '○';
        if (!pass) allPass = false;
    });
    return allPass;
}
document.getElementById('signupPassword')?.addEventListener('input', function() { checkPasswordRules(this.value); });

// ── Login ────────────────────────────────────────────────────────────────────
document.getElementById('loginForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const btn = this.querySelector("button[type='submit']");
    btn.disabled = true; btn.textContent = 'Logging in…';
    fetch('login_user.php', { method: 'POST', body: new FormData(this) }).then(r => r.json())
        .then(res => { if (res.error) { showToast(res.error, 'error'); btn.disabled = false; btn.textContent = 'Login'; return; } closeAll(); location.reload(); })
        .catch(() => { showToast('Network error', 'error'); btn.disabled = false; btn.textContent = 'Login'; });
});

// ── Search suggestions ───────────────────────────────────────────────────────
(function() {
    const input = document.getElementById('searchInput');
    const box   = document.getElementById('searchSuggestions');
    if (!input || !box) return;
    let timer;
    input.addEventListener('input', () => {
        const q = input.value.trim();
        clearTimeout(timer);
        if (q.length < 1) { box.classList.remove('open'); box.innerHTML = ''; return; }
        timer = setTimeout(() => {
            fetch('search_suggest.php?q=' + encodeURIComponent(q))
                .then(r => r.json())
                .then(items => {
                    if (!items.length) { box.classList.remove('open'); return; }
                    box.innerHTML = items.map(it => `<div class="suggest-item" data-name="${escH(it.name)}"><span>${escH(it.name)}</span><span class="suggest-cat">${escH(it.category)}</span></div>`).join('');
                    box.classList.add('open');
                }).catch(() => {});
        }, 200);
    });
    box.addEventListener('click', e => {
        const item = e.target.closest('.suggest-item'); if (!item) return;
        input.value = item.dataset.name; box.classList.remove('open');
        document.getElementById('searchForm').submit();
    });
    document.addEventListener('click', e => { if (!document.getElementById('searchWrap').contains(e.target)) box.classList.remove('open'); });
})();

// ── Page visit tracking ──────────────────────────────────────────────────────
(function() {
    const d = new FormData();
    d.append('page', window.location.pathname.split('/').pop() || 'index.php');
    fetch('track_visit.php', { method: 'POST', body: d }).catch(() => {});
})();

</script>

<!-- ═══ SIZE PICKER MODAL (uses existing .modal .modal-box CSS) ═══ -->
<div id="_szModal" class="modal">
  <div class="modal-box">
    <button class="modal-close" onclick="_closeSzModal()">✕</button>
    <h2>Select Size</h2>
    <p class="modal-sub" id="_szProductName" style="color:var(--brand);font-weight:600;font-size:14px;margin-bottom:6px;"></p>
    <p class="modal-sub">Pick a size to continue</p>
    <div id="_szChips" style="display:flex;flex-wrap:wrap;gap:8px;justify-content:center;margin:16px 0;"></div>
    <p id="_szErr" style="font-size:12px;color:#dc2626;display:none;margin-bottom:10px;">⚠ Please select a size first</p>
    <a href="#" onclick="_openSzChart();return false;" style="display:inline-block;font-size:12px;color:var(--brand);text-decoration:underline;margin-bottom:16px;">📏 View Size Chart</a>
    <button id="_szConfirmBtn" onclick="_confirmSz()" class="form-submit-btn">Add to Cart</button>
  </div>
</div>

<!-- ═══ SIZE CHART MODAL ═══ -->
<div id="_szChartModal" class="modal">
  <div class="modal-box modal-box--wide" style="text-align:left;">
    <button class="modal-close" onclick="_closeSzChart()">✕</button>
    <h2 style="text-align:center;">📏 Size Chart</h2>
    <p class="modal-sub" style="text-align:center;">Measurements in centimetres (cm)</p>
    <div style="overflow-x:auto;margin-top:14px;">
      <table style="width:100%;border-collapse:collapse;font-size:13px;">
        <thead>
          <tr style="background:var(--brand);color:#fff;">
            <th style="padding:9px 12px;text-align:left;">Size</th>
            <th style="padding:9px 12px;text-align:center;">Chest</th>
            <th style="padding:9px 12px;text-align:center;">Waist</th>
            <th style="padding:9px 12px;text-align:center;">Hip</th>
            <th style="padding:9px 12px;text-align:center;">Length</th>
          </tr>
        </thead>
        <tbody>
          <tr style="background:#fff;"><td style="padding:8px 12px;font-weight:700;color:var(--brand);">XS</td><td style="padding:8px 12px;text-align:center;color:#555;">76–80</td><td style="padding:8px 12px;text-align:center;color:#555;">60–64</td><td style="padding:8px 12px;text-align:center;color:#555;">84–88</td><td style="padding:8px 12px;text-align:center;color:#555;">57–59</td></tr>
          <tr style="background:#faf9f8;"><td style="padding:8px 12px;font-weight:700;color:var(--brand);">S</td><td style="padding:8px 12px;text-align:center;color:#555;">80–86</td><td style="padding:8px 12px;text-align:center;color:#555;">64–70</td><td style="padding:8px 12px;text-align:center;color:#555;">88–94</td><td style="padding:8px 12px;text-align:center;color:#555;">59–61</td></tr>
          <tr style="background:#fff;"><td style="padding:8px 12px;font-weight:700;color:var(--brand);">M</td><td style="padding:8px 12px;text-align:center;color:#555;">86–92</td><td style="padding:8px 12px;text-align:center;color:#555;">70–76</td><td style="padding:8px 12px;text-align:center;color:#555;">94–100</td><td style="padding:8px 12px;text-align:center;color:#555;">61–63</td></tr>
          <tr style="background:#faf9f8;"><td style="padding:8px 12px;font-weight:700;color:var(--brand);">L</td><td style="padding:8px 12px;text-align:center;color:#555;">92–98</td><td style="padding:8px 12px;text-align:center;color:#555;">76–82</td><td style="padding:8px 12px;text-align:center;color:#555;">100–106</td><td style="padding:8px 12px;text-align:center;color:#555;">63–65</td></tr>
          <tr style="background:#fff;"><td style="padding:8px 12px;font-weight:700;color:var(--brand);">XL</td><td style="padding:8px 12px;text-align:center;color:#555;">98–104</td><td style="padding:8px 12px;text-align:center;color:#555;">82–88</td><td style="padding:8px 12px;text-align:center;color:#555;">106–112</td><td style="padding:8px 12px;text-align:center;color:#555;">65–67</td></tr>
          <tr style="background:#faf9f8;"><td style="padding:8px 12px;font-weight:700;color:var(--brand);">XXL</td><td style="padding:8px 12px;text-align:center;color:#555;">104–112</td><td style="padding:8px 12px;text-align:center;color:#555;">88–96</td><td style="padding:8px 12px;text-align:center;color:#555;">112–120</td><td style="padding:8px 12px;text-align:center;color:#555;">67–69</td></tr>
          <tr style="background:#fff;"><td style="padding:8px 12px;font-weight:700;color:var(--brand);">XXXL</td><td style="padding:8px 12px;text-align:center;color:#555;">112–120</td><td style="padding:8px 12px;text-align:center;color:#555;">96–104</td><td style="padding:8px 12px;text-align:center;color:#555;">120–128</td><td style="padding:8px 12px;text-align:center;color:#555;">69–71</td></tr>
        </tbody>
      </table>
    </div>
    <p style="font-size:11px;color:#aaa;margin-top:12px;line-height:1.7;"><b>Chest</b> — fullest part &nbsp;|&nbsp; <b>Waist</b> — natural waistline &nbsp;|&nbsp; <b>Hip</b> — fullest part &nbsp;|&nbsp; <b>Length</b> — shoulder to hem</p>
    <button onclick="_closeSzChart()" class="modal-choice-btn" style="margin-top:16px;">← Back to Size Selection</button>
  </div>
</div>

<!-- close backdrop clicks -->
<script>
document.getElementById('_szModal').addEventListener('click', function(e){ if(e.target===this) _closeSzModal(); });
document.getElementById('_szChartModal').addEventListener('click', function(e){ if(e.target===this) _closeSzChart(); });
</script>
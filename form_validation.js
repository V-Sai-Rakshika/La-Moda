/**
 * form_validation.js — La Moda
 * Full validation: phone, pincode, IFSC, state, country (India only),
 * card, address, real-time feedback.
 */

/* ══════════════════════════════════════════════
   INDIA DATA
══════════════════════════════════════════════ */
const INDIA_STATES = [
  'andhra pradesh','arunachal pradesh','assam','bihar','chhattisgarh',
  'goa','gujarat','haryana','himachal pradesh','jharkhand','karnataka',
  'kerala','madhya pradesh','maharashtra','manipur','meghalaya','mizoram',
  'nagaland','odisha','punjab','rajasthan','sikkim','tamil nadu','telangana',
  'tripura','uttar pradesh','uttarakhand','west bengal',
  // UTs
  'andaman and nicobar islands','chandigarh','dadra and nagar haveli and daman and diu',
  'delhi','jammu and kashmir','ladakh','lakshadweep','puducherry'
];

const PINCODE_STATE = {
  '11':'Delhi','12':'Haryana','13':'Haryana','14':'Punjab','15':'Punjab',
  '16':'Punjab','17':'Himachal Pradesh','18':'Jammu and Kashmir',
  '19':'Jammu and Kashmir','20':'Uttar Pradesh','21':'Uttar Pradesh',
  '22':'Uttar Pradesh','23':'Uttar Pradesh','24':'Uttar Pradesh',
  '25':'Uttar Pradesh','26':'Uttar Pradesh','27':'Uttar Pradesh',
  '28':'Uttar Pradesh','30':'Rajasthan','31':'Rajasthan','32':'Rajasthan',
  '33':'Rajasthan','34':'Rajasthan','36':'Gujarat','37':'Gujarat',
  '38':'Gujarat','39':'Gujarat','40':'Maharashtra','41':'Maharashtra',
  '42':'Maharashtra','43':'Maharashtra','44':'Maharashtra',
  '45':'Madhya Pradesh','46':'Madhya Pradesh','47':'Madhya Pradesh',
  '48':'Madhya Pradesh','49':'Chhattisgarh','50':'Telangana',
  '51':'Andhra Pradesh','52':'Andhra Pradesh','53':'Andhra Pradesh',
  '56':'Karnataka','57':'Karnataka','58':'Karnataka','59':'Karnataka',
  '60':'Tamil Nadu','61':'Tamil Nadu','62':'Tamil Nadu','63':'Tamil Nadu',
  '64':'Tamil Nadu','67':'Kerala','68':'Kerala','69':'Kerala',
  '70':'West Bengal','71':'West Bengal','72':'West Bengal',
  '73':'West Bengal','74':'West Bengal','75':'Odisha','76':'Odisha',
  '77':'Odisha','78':'Assam','79':'Arunachal Pradesh',
  '80':'Bihar','81':'Bihar','82':'Jharkhand','83':'Jharkhand',
  '84':'Bihar','85':'Bihar','56':'Karnataka'
};

function pincodeToState(pin) {
  return PINCODE_STATE[pin.substring(0,2)] || null;
}
function normState(s) {
  return s.toLowerCase().replace(/\s+/g,' ').trim();
}
function isValidIndianState(s) {
  return INDIA_STATES.includes(normState(s));
}
function isIndia(v) {
  return v.trim().toLowerCase() === 'india';
}

/* ══════════════════════════════════════════════
   INPUT RESTRICTORS (real-time)
══════════════════════════════════════════════ */
function numericOnly(el) {
  if (!el) return;
  el.setAttribute('inputmode','numeric');
  el.addEventListener('keypress', e => {
    if (!/[0-9]/.test(e.key) && !['Backspace','Delete','Tab','ArrowLeft','ArrowRight'].includes(e.key))
      e.preventDefault();
  });
  el.addEventListener('input', function() {
    const p = this.selectionStart;
    const v = this.value.replace(/[^0-9]/g,'');
    if (this.value !== v) { this.value = v; try { this.setSelectionRange(p,p); } catch(e){} }
  });
}

function lettersOnly(el) {
  if (!el) return;
  el.addEventListener('keypress', e => {
    if (!/[A-Za-z\s]/.test(e.key) && !['Backspace','Delete','Tab','ArrowLeft','ArrowRight'].includes(e.key))
      e.preventDefault();
  });
  el.addEventListener('input', function() {
    const p = this.selectionStart;
    const v = this.value.replace(/[^A-Za-z\s]/g,'');
    if (this.value !== v) { this.value = v; try { this.setSelectionRange(p,p); } catch(e){} }
  });
}

function ifscOnly(el) {
  if (!el) return;
  el.setAttribute('autocapitalize','characters');
  el.addEventListener('input', function() {
    this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g,'');
  });
}

/* ══════════════════════════════════════════════
   FIELD VALIDATORS
══════════════════════════════════════════════ */
const V = {
  phone:   v => /^[6-9][0-9]{9}$/.test(v.trim()),
  pincode: v => /^[1-9][0-9]{5}$/.test(v.trim()),
  name:    v => /^[A-Za-z\s]{2,}$/.test(v.trim()),
  ifsc:    v => /^[A-Z]{4}0[A-Z0-9]{6}$/.test(v.trim().toUpperCase()),
  accNum:  v => /^[0-9]{8,18}$/.test(v.trim()),
  accName: v => /^[A-Za-z\s]{3,}$/.test(v.trim()),
  country: v => isIndia(v),
  state:   v => isValidIndianState(v),
  notEmpty:v => v.trim().length >= 2,
  email:   v => v.trim() === '' || /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v.trim()),
};

/* ══════════════════════════════════════════════
   INLINE ERROR / SUCCESS DISPLAY
══════════════════════════════════════════════ */
function setFieldState(el, ok, msgEl, errMsg, okMsg) {
  if (!el) return;
  el.style.borderColor = ok ? '#16a34a' : '#dc2626';
  el.style.background  = ok ? '' : '#fff8f8';
  if (msgEl) {
    msgEl.textContent  = ok ? (okMsg||'') : errMsg;
    msgEl.style.color  = ok ? '#16a34a' : '#dc2626';
    msgEl.style.display= (ok && !okMsg) || (!ok && !errMsg) ? 'none' : 'block';
  }
}
function clearField(el, msgEl) {
  if (!el) return;
  el.style.borderColor = '';
  el.style.background  = '';
  if (msgEl) { msgEl.textContent=''; msgEl.style.display='none'; }
}

/* ══════════════════════════════════════════════
   PINCODE → STATE AUTOFILL + VALIDATION
══════════════════════════════════════════════ */
function setupPincodeValidation(pincodeId, stateId, countryId, msgId) {
  const pin = document.getElementById(pincodeId);
  const sta = document.getElementById(stateId);
  const ctr = document.getElementById(countryId);
  const msg = document.getElementById(msgId);
  if (!pin) return;

  numericOnly(pin);

  pin.addEventListener('input', function() {
    const v = this.value.trim();
    if (!msg) return;

    // Only validate for India
    const country = ctr ? ctr.value.trim().toLowerCase() : 'india';
    if (country && country !== 'india') { msg.style.display='none'; return; }

    if (v.length < 6) { clearField(null, msg); return; }

    if (!V.pincode(v)) {
      setFieldState(pin, false, msg, '⚠ Invalid pincode format');
      return;
    }

    const expectedState = pincodeToState(v);
    if (!expectedState) {
      setFieldState(pin, false, msg, '⚠ Pincode not recognised — verify it');
      return;
    }

    // Auto-fill state if empty
    if (sta && !sta.value.trim()) {
      sta.value = expectedState;
    }

    // Cross-check state
    if (sta && sta.value.trim()) {
      const typed = normState(sta.value);
      const exp   = normState(expectedState);
      if (typed && typed !== exp && !exp.includes(typed) && !typed.includes(exp)) {
        setFieldState(pin, false, msg, `⚠ This pincode is in ${expectedState}, not "${sta.value}"`);
        return;
      }
    }

    setFieldState(pin, true, msg, '', `✅ ${expectedState}`);
  });

  if (sta) {
    sta.addEventListener('blur', () => pin.dispatchEvent(new Event('input')));
  }
}

/* ══════════════════════════════════════════════
   COUNTRY VALIDATION (India only)
══════════════════════════════════════════════ */
function setupCountryValidation(countryId, msgId) {
  const el  = document.getElementById(countryId);
  const msg = document.getElementById(msgId);
  if (!el) return;

  el.addEventListener('blur', function() {
    if (!this.value.trim()) { clearField(this, msg); return; }
    if (!isIndia(this.value)) {
      setFieldState(this, false, msg, '❌ This website currently operates only in India.');
    } else {
      setFieldState(this, true, msg, '', '✅ India');
    }
  });
}

/* ══════════════════════════════════════════════
   STATE VALIDATION
══════════════════════════════════════════════ */
function setupStateValidation(stateId, countryId, msgId) {
  const el  = document.getElementById(stateId);
  const ctr = document.getElementById(countryId);
  const msg = document.getElementById(msgId);
  if (!el) return;

  el.addEventListener('blur', function() {
    if (!this.value.trim()) { clearField(this, msg); return; }
    const country = ctr ? ctr.value.trim().toLowerCase() : 'india';
    if (country !== 'india') { clearField(this, msg); return; }
    if (!isValidIndianState(this.value)) {
      setFieldState(this, false, msg, `⚠ "${this.value}" is not a valid Indian state/UT`);
    } else {
      clearField(this, msg);
    }
  });
}

/* ══════════════════════════════════════════════
   PHONE VALIDATION
══════════════════════════════════════════════ */
function setupPhoneValidation(phoneId, msgId) {
  const el  = document.getElementById(phoneId);
  const msg = document.getElementById(msgId);
  if (!el) return;
  numericOnly(el);
  el.addEventListener('input', function() {
    if (this.value.length === 0) { clearField(this, msg); return; }
    if (this.value.length === 10) {
      setFieldState(this, V.phone(this.value), msg,
        '⚠ Must start with 6-9 and be 10 digits', '');
    }
  });
  el.addEventListener('blur', function() {
    if (!this.value) { clearField(this, msg); return; }
    setFieldState(this, V.phone(this.value), msg,
      '⚠ Valid 10-digit mobile number required (starts with 6–9)');
  });
}

/* ══════════════════════════════════════════════
   APPLY TO ALL KNOWN FORM FIELDS
══════════════════════════════════════════════ */
document.addEventListener('DOMContentLoaded', function() {

  // ── Cart checkout form ──────────────────────
  setupPhoneValidation('f_mobile', null);
  setupCountryValidation('f_country', 'f_country_msg');
  setupStateValidation('f_state', 'f_country', 'f_state_msg');
  setupPincodeValidation('f_pincode', 'f_state', 'f_country', 'pincode_state_msg');
  lettersOnly(document.getElementById('f_name'));
  ifscOnly(document.getElementById('bankIfsc'));
  ifscOnly(document.getElementById('emiIfsc'));
  numericOnly(document.getElementById('bankAccNum'));
  numericOnly(document.getElementById('emiAccNum'));
  lettersOnly(document.getElementById('bankAccName'));
  lettersOnly(document.getElementById('emiAccName'));
  lettersOnly(document.getElementById('cardName'));
  numericOnly(document.getElementById('f_pincode'));

  // ── Buy Now form ────────────────────────────
  setupPhoneValidation('bn_mobile', null);
  setupCountryValidation('bn_country', 'bn_country_msg');
  setupStateValidation('bn_state', 'bn_country', 'bn_state_msg');
  setupPincodeValidation('bn_pincode', 'bn_state', 'bn_country', 'bn_pincode_state_msg');
  lettersOnly(document.getElementById('bn_name'));
  ifscOnly(document.getElementById('bnBankIfsc'));
  ifscOnly(document.getElementById('bnEmiIfsc'));
  numericOnly(document.getElementById('bnBankAccNum'));
  numericOnly(document.getElementById('bnEmiAccNum'));
  lettersOnly(document.getElementById('bnCardName'));
  numericOnly(document.getElementById('bn_pincode'));

  // ── Signup form ─────────────────────────────
  const signupPhone = document.querySelector('#signupForm [name="phone"]');
  if (signupPhone) numericOnly(signupPhone);

  // ── Country validation messages (add spans if missing) ──
  ensureMsgSpan('f_country', 'f_country_msg');
  ensureMsgSpan('f_state',   'f_state_msg');
  ensureMsgSpan('bn_country','bn_country_msg');
  ensureMsgSpan('bn_state',  'bn_state_msg');
});

function ensureMsgSpan(fieldId, msgId) {
  if (document.getElementById(msgId)) return;
  const el = document.getElementById(fieldId);
  if (!el) return;
  const span = document.createElement('p');
  span.id = msgId;
  span.style.cssText = 'font-size:11px;margin-top:3px;display:none;';
  el.parentNode.insertBefore(span, el.nextSibling?.nextSibling || null);
}

/* ══════════════════════════════════════════════
   BACKEND-CONSISTENT VALIDATORS (used by PHP mirrors)
   Exported for use in page-specific JS
══════════════════════════════════════════════ */
window.LaModa = window.LaModa || {};
window.LaModa.V = V;
window.LaModa.isIndia = isIndia;
window.LaModa.isValidIndianState = isValidIndianState;
window.LaModa.pincodeToState = pincodeToState;
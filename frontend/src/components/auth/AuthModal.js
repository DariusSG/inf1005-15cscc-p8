//uhhh api call should be at ../api/auth.pphp


function openAuthModal() {
  openModal('authModal');
  switchAuthTab('login', document.querySelector('.auth-tab'));
}

function switchAuthTab(t, btn) {
  document.querySelectorAll('.auth-tab').forEach(x => x.classList.remove('active'));
  btn.classList.add('active');
  document.getElementById('loginForm').style.display    = t === 'login'    ? 'block' : 'none';
  document.getElementById('registerForm').style.display = t === 'register' ? 'block' : 'none';
  document.getElementById('otpSection').classList.remove('active');
}

async function handleLogin() {
  const email = document.getElementById('loginEmail').value.trim().toLowerCase();
  const pw    = document.getElementById('loginPw').value;
  const err   = document.getElementById('loginErr');
  err.classList.remove('visible');

  if (!isSITEmail(email)) {
    err.textContent = 'Only SIT emails.'; err.classList.add('visible'); return;
  }

  try {
    const res  = await fetch('../api/auth.php', {
      method:  'POST',
      headers: { 'Content-Type': 'application/json' },
      body:    JSON.stringify({ action: 'login', email, password: pw }),
    });
    const data = await res.json();

    if (data.success) {
      authStore.user = data.user;
      closeModal('authModal');
      updateAuth();
      toast('Welcome, ' + data.user.name + '!', 'success');
    } else {
      err.textContent = data.message; err.classList.add('visible');
    }
  } catch (e) {
    toast('Network error', 'error');
  }
}

// ── Register ─────────────────────────────────────────────────────
async function handleRegister() {
  const name  = document.getElementById('regName').value.trim();
  const email = document.getElementById('regEmail').value.trim().toLowerCase();
  const pw    = document.getElementById('regPw').value;
  const cpw   = document.getElementById('regCpw').value;
  const err   = document.getElementById('regErr');
  err.classList.remove('visible');

  if (!name)                    { err.textContent = 'Name required.';         err.classList.add('visible'); return; }
  if (!isSITEmail(email))       { err.textContent = 'Only SIT emails.';       err.classList.add('visible'); return; }
  if (!isValidPassword(pw))     { err.textContent = 'Min 6 chars.';           err.classList.add('visible'); return; }
  if (!passwordsMatch(pw, cpw)) { err.textContent = "Passwords don't match."; err.classList.add('visible'); return; }

  try {
    const res  = await fetch('../api/auth.php', {
      method:  'POST',
      headers: { 'Content-Type': 'application/json' },
      body:    JSON.stringify({ action: 'register', name, email, password: pw }),
    });
    const data = await res.json();

    if (data.success) {
      authStore._pending = { name, email };
      document.getElementById('registerForm').style.display = 'none';
      document.getElementById('otpSection').classList.add('active');
      document.getElementById('otpEmail').textContent = email;
      document.querySelectorAll('.otp-d').forEach(d => d.value = '');
      document.querySelector('.otp-d').focus();
      toast('OTP sent to ' + email, 'info');
    } else {
      err.textContent = data.message; err.classList.add('visible');
    }
  } catch (e) {
    toast('Network error', 'error');
  }
}

function otpNext(el) {
  if (el.value && el.nextElementSibling) el.nextElementSibling.focus();
}
function otpBack(e, el) {
  if (e.key === 'Backspace' && !el.value && el.previousElementSibling)
    el.previousElementSibling.focus();
}

async function verifyOtp() {
  const digits = Array.from(document.querySelectorAll('.otp-d')).map(x => x.value).join('');
  const err    = document.getElementById('otpErr');
  err.classList.remove('visible');

  if (digits.length < 6) {
    err.textContent = 'Enter 6 digits.'; err.classList.add('visible'); return;
  }

  try {
    const res  = await fetch('../api/auth.php', {
      method:  'POST',
      headers: { 'Content-Type': 'application/json' },
      body:    JSON.stringify({ action: 'verify_otp', email: authStore._pending.email, otp: digits }),
    });
    const data = await res.json();

    if (data.success) {
      authStore.user     = data.user;
      authStore._pending = null;
      closeModal('authModal');
      updateAuth();
      toast('Welcome, ' + data.user.name + '!', 'success');
    } else {
      err.textContent = data.message; err.classList.add('visible');
    }
  } catch (e) {
    toast('Network error', 'error');
  }
}

async function resendOtp() {
  try {
    await fetch('../api/auth.php', {
      method:  'POST',
      headers: { 'Content-Type': 'application/json' },
      body:    JSON.stringify({ action: 'resend_otp', email: authStore._pending.email }),
    });
    toast('OTP resent', 'info');
  } catch (e) {
    toast('Network error', 'error');
  }
}

async function logout() {
  try {
    await fetch('../api/auth.php', {
      method:  'POST',
      headers: { 'Content-Type': 'application/json' },
      body:    JSON.stringify({ action: 'logout' }),
    });
  } catch (e) { /* session cleared server-side regardless */ }

  authStore.user = null;
  closeUserMenu();
  updateAuth();
  navigate('welcome');
  toast('Signed out', 'info');
}

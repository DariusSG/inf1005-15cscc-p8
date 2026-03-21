/**
 * AUTH MODAL INTEGRATION
 * ──────────────────────
 * Drop this into wherever your auth modal is initialised
 * (e.g. main.js, authModal.js, or your app bootstrap).
 *
 * It replaces the old registerPending / verifyOtp / resendOtp wiring.
 */

import { api }              from '../services/api.js';
import { initRegisterFlow } from './registerFlow.js';

// ── 1. HTML changes needed in your auth modal ─────────────────────────────
//
// Replace the old <div class="otp-section"> block with:
//
//   <div class="auth-tabs">
//     <button class="auth-tab active" data-tab="login">Sign in</button>
//     <button class="auth-tab"        data-tab="register">Register</button>
//   </div>
//
//   <!-- LOGIN pane (unchanged) -->
//   <div id="auth-login-pane" class="auth-form-inner"> … </div>
//
//   <!-- REGISTER pane — registerFlow renders into this element -->
//   <div id="auth-register-pane" class="auth-register-pane"></div>
//
// ── 2. JS wiring ─────────────────────────────────────────────────────────

export function initAuthModal({ onLoginSuccess }) {
  const overlay      = document.querySelector('.auth-modal');
  const loginPane    = document.getElementById('auth-login-pane');
  const registerPane = document.getElementById('auth-register-pane');
  const tabs         = document.querySelectorAll('.auth-tab');

  // Helper: show a toast (wire up to your existing showToast)
  function showToast(msg, type = 'info') {
    const box = document.querySelector('.toast-box');
    if (!box) return;
    const t = document.createElement('div');
    t.className = `toast ${type}`;
    t.textContent = msg;
    box.appendChild(t);
    setTimeout(() => t.remove(), 3800);
  }

  // ── Tab switching ──────────────────────────────────────────────────────
  function switchTab(tab) {
    tabs.forEach(t => t.classList.toggle('active', t.dataset.tab === tab));
    loginPane.style.display    = tab === 'login'    ? 'block' : 'none';
    registerPane.style.display = tab === 'register' ? 'block' : 'none';

    if (tab === 'register') {
      // (Re-)init the register flow every time the tab is opened.
      // initRegisterFlow is idempotent — safe to call multiple times.
      initRegisterFlow(
        registerPane,
        api,
        () => {
          // onSuccess: close modal and refresh session
          overlay.classList.remove('active');
          onLoginSuccess();
        },
        showToast
      );
    }
  }

  tabs.forEach(t => t.addEventListener('click', () => switchTab(t.dataset.tab)));

  // ── Auto-open on invite link ───────────────────────────────────────────
  // If the user arrives at the app with ?token= in the URL (clicked the
  // email link), open the auth modal directly on the Register tab.
  const hasToken = new URLSearchParams(window.location.search).has('token');
  if (hasToken) {
    overlay.classList.add('active');
    switchTab('register');
  }

  // ── Login form (existing logic, kept as-is) ────────────────────────────
  const loginForm   = loginPane?.querySelector('#login-form');
  const loginEmail  = loginPane?.querySelector('#login-email');
  const loginPw     = loginPane?.querySelector('#login-password');
  const loginBtn    = loginPane?.querySelector('#login-btn');
  const loginErr    = loginPane?.querySelector('#login-err');

  loginBtn?.addEventListener('click', async () => {
    loginErr && (loginErr.textContent = '');
    loginBtn.disabled = true;

    try {
      const result = await api.login({
        email:    loginEmail.value.trim(),
        password: loginPw.value,
      });

      if (!result.success) throw new Error(result.message || 'Login failed');
      overlay.classList.remove('active');
      onLoginSuccess();
    } catch (e) {
      if (loginErr) { loginErr.textContent = e.message; loginErr.classList.add('visible'); }
    } finally {
      loginBtn.disabled = false;
    }
  });
}
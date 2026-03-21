/**
 * Registration flow manager.
 *
 * Handles two distinct UI states:
 *   "request"  — user enters SIT email, we send the invite link
 *   "complete" — user arrived via ?token= link, fills name + password
 *
 * Usage:
 *   import { initRegisterFlow } from './registerFlow.js';
 *   initRegisterFlow(api, onSuccess, showToast);
 *
 * The module self-detects which state to render based on the URL.
 */

const SIT_DOMAIN = "@sit.singaporetech.edu.sg";

/** Returns the ?token= value from the current URL, or null. */
function getTokenFromUrl() {
  return new URLSearchParams(window.location.search).get("token");
}

/** Swap the browser URL without reloading (removes ?token= after use). */
function clearTokenFromUrl() {
  const url = new URL(window.location.href);
  url.searchParams.delete("token");
  window.history.replaceState({}, "", url.toString());
}

// ─────────────────────────────────────────────────────────────────────────────
// HTML TEMPLATES
// ─────────────────────────────────────────────────────────────────────────────

function requestTemplate() {
  return /* html */ `
    <div class="reg-step" id="reg-request">
      <div class="reg-icon">✉️</div>

      <h2 class="reg-title">Create your account</h2>
      <p class="reg-sub">
        Enter your SIT student email — we'll send you a secure link to finish
        setting up your account.
      </p>

      <div class="form-group" style="margin-top:20px;">
        <label for="reg-email">SIT Email</label>
        <div class="reg-email-wrap">
          <input
            id="reg-email"
            type="email"
            placeholder="yourname.2024@sit.singaporetech.edu.sg"
            autocomplete="email"
            spellcheck="false"
          />
          <span class="reg-domain-badge">@sit.singaporetech.edu.sg</span>
        </div>
        <div class="form-error" id="reg-email-err"></div>
      </div>

      <button class="btn btn-primary reg-submit" id="reg-request-btn" style="width:100%;margin-top:6px;">
        <span class="btn-label">Send invite link</span>
        <span class="btn-spinner" hidden>⏳</span>
      </button>

      <p class="reg-note">
        Only <strong>${SIT_DOMAIN}</strong> addresses are accepted.
        Your details are never shared.
      </p>
    </div>

    <!-- Sent confirmation state (hidden initially) -->
    <div class="reg-step" id="reg-sent" hidden>
      <div class="reg-icon">📬</div>
      <h2 class="reg-title">Check your inbox</h2>
      <p class="reg-sub">
        We've sent a registration link to <strong id="reg-sent-email"></strong>.
        Click the link in the email to continue — it expires in 24 hours.
      </p>
      <div class="reg-sent-details">
        <div class="reg-sent-row">
          <span>📁</span>
          <span>Check your <strong>spam/junk</strong> folder if it doesn't arrive within a minute.</span>
        </div>
        <div class="reg-sent-row">
          <span>🔁</span>
          <span>
            Wrong email?
            <button class="link-btn" id="reg-retry-btn">Start over</button>
          </span>
        </div>
      </div>
    </div>
  `;
}

function completeTemplate(maskedEmail) {
  return /* html */ `
    <div class="reg-step" id="reg-complete">
      <div class="reg-icon">🎓</div>

      <h2 class="reg-title">Finish setting up</h2>
      <p class="reg-sub">
        Registering as <strong class="reg-masked-email">${maskedEmail}</strong>
      </p>

      <div class="form-group" style="margin-top:20px;">
        <label for="reg-name">Full name</label>
        <input
          id="reg-name"
          type="text"
          placeholder="Jane Doe"
          autocomplete="name"
          maxlength="80"
        />
        <div class="form-error" id="reg-name-err"></div>
      </div>

      <div class="form-group">
        <label for="reg-password">Password</label>
        <div class="reg-pw-wrap">
          <input
            id="reg-password"
            type="password"
            placeholder="At least 8 characters"
            autocomplete="new-password"
          />
          <button class="reg-pw-toggle" id="reg-pw-toggle" type="button" aria-label="Toggle password visibility">👁</button>
        </div>
        <div class="pw-strength" id="pw-strength">
          <div class="pw-bar" id="pw-bar"></div>
        </div>
        <div class="form-hint" id="pw-hint"></div>
        <div class="form-error" id="reg-pw-err"></div>
      </div>

      <div class="form-group">
        <label for="reg-confirm">Confirm password</label>
        <div class="reg-pw-wrap">
          <input
            id="reg-confirm"
            type="password"
            placeholder="Repeat password"
            autocomplete="new-password"
          />
        </div>
        <div class="form-error" id="reg-confirm-err"></div>
      </div>

      <button class="btn btn-primary reg-submit" id="reg-complete-btn" style="width:100%;margin-top:6px;" disabled>
        <span class="btn-label">Create account →</span>
        <span class="btn-spinner" hidden>⏳</span>
      </button>
    </div>

    <!-- Invalid / expired token state -->
    <div class="reg-step" id="reg-token-invalid" hidden>
      <div class="reg-icon">⚠️</div>
      <h2 class="reg-title">Link expired or invalid</h2>
      <p class="reg-sub">
        This registration link has already been used or has expired.
        Links are valid for 24 hours.
      </p>
      <button class="btn btn-secondary" id="reg-newlink-btn" style="width:100%;margin-top:16px;">
        Request a new link
      </button>
    </div>
  `;
}

// ─────────────────────────────────────────────────────────────────────────────
// STYLES (injected once)
// ─────────────────────────────────────────────────────────────────────────────

function injectStyles() {
  if (document.getElementById("reg-flow-styles")) return;
  const style = document.createElement("style");
  style.id = "reg-flow-styles";
  style.textContent = /* css */ `
    /* ── Container ───────────────────────────────────────── */
    .reg-flow-wrap {
      padding: 24px 26px 28px;
    }

    /* ── Step shared ─────────────────────────────────────── */
    .reg-step { animation: fadeUp 0.28s ease forwards; }

    .reg-icon {
      font-size: 2rem;
      margin-bottom: 10px;
      display: block;
    }

    .reg-title {
      font-size: 1.18rem;
      font-weight: 700;
      letter-spacing: -0.3px;
      margin-bottom: 6px;
      color: var(--text-primary);
    }

    .reg-sub {
      font-size: 0.86rem;
      color: var(--text-secondary);
      line-height: 1.55;
      margin-bottom: 4px;
    }

    .reg-sub strong { color: var(--text-primary); }

    .reg-note {
      font-size: 0.74rem;
      color: var(--text-muted);
      margin-top: 14px;
      line-height: 1.5;
      text-align: center;
    }

    .reg-note strong { color: var(--text-secondary); }

    /* ── Email input + domain badge ──────────────────────── */
    .reg-email-wrap {
      position: relative;
    }

    .reg-email-wrap input {
      padding-right: 12px;
    }

    .reg-domain-badge {
      display: none; /* shown dynamically when user starts typing */
      position: absolute;
      right: 10px;
      top: 50%;
      transform: translateY(-50%);
      font-size: 0.7rem;
      font-family: var(--mono);
      color: var(--accent);
      background: var(--accent-subtle);
      padding: 2px 7px;
      border-radius: 4px;
      pointer-events: none;
      white-space: nowrap;
    }

    .reg-domain-badge.visible { display: block; }

    /* ── Sent confirmation ───────────────────────────────── */
    .reg-sent-details {
      margin-top: 20px;
      background: var(--bg-card);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      padding: 14px 16px;
      display: flex;
      flex-direction: column;
      gap: 10px;
    }

    .reg-sent-row {
      display: flex;
      gap: 10px;
      font-size: 0.83rem;
      color: var(--text-secondary);
      line-height: 1.45;
      align-items: flex-start;
    }

    .reg-sent-row strong { color: var(--text-primary); }

    /* ── Password field ──────────────────────────────────── */
    .reg-pw-wrap {
      position: relative;
    }

    .reg-pw-wrap input {
      padding-right: 40px;
    }

    .reg-pw-toggle {
      position: absolute;
      right: 10px;
      top: 50%;
      transform: translateY(-50%);
      background: none;
      border: none;
      cursor: pointer;
      font-size: 0.9rem;
      opacity: 0.5;
      transition: opacity var(--transition);
      line-height: 1;
      padding: 2px;
    }

    .reg-pw-toggle:hover { opacity: 1; }

    /* ── Password strength bar ───────────────────────────── */
    .pw-strength {
      height: 3px;
      background: var(--bg-elevated);
      border-radius: 2px;
      margin-top: 6px;
      overflow: hidden;
    }

    .pw-bar {
      height: 100%;
      width: 0%;
      border-radius: 2px;
      transition: width 0.3s ease, background 0.3s ease;
    }

    .pw-bar.weak   { background: var(--red);    width: 25%; }
    .pw-bar.fair   { background: var(--orange); width: 55%; }
    .pw-bar.good   { background: var(--blue);   width: 75%; }
    .pw-bar.strong { background: var(--green);  width: 100%; }

    /* ── Masked email highlight ──────────────────────────── */
    .reg-masked-email {
      font-family: var(--mono);
      font-size: 0.85em;
      color: var(--accent);
      background: var(--accent-subtle);
      padding: 1px 7px;
      border-radius: 4px;
    }

    /* ── Link button (inline text) ───────────────────────── */
    .link-btn {
      background: none;
      border: none;
      color: var(--accent);
      cursor: pointer;
      font-size: inherit;
      font-family: inherit;
      padding: 0;
      text-decoration: underline;
      text-underline-offset: 2px;
    }

    .link-btn:hover { color: var(--accent-hover); }

    /* ── Disabled submit ─────────────────────────────────── */
    .reg-submit:disabled {
      opacity: 0.45;
      cursor: not-allowed;
      pointer-events: none;
    }

    /* ── Token checking state ────────────────────────────── */
    .reg-checking {
      text-align: center;
      padding: 40px 20px;
      color: var(--text-muted);
      font-size: 0.9rem;
    }

    .reg-checking-spinner {
      font-size: 1.6rem;
      display: block;
      margin-bottom: 12px;
      animation: float 1.5s ease-in-out infinite;
    }
  `;
  document.head.appendChild(style);
}

// ─────────────────────────────────────────────────────────────────────────────
// HELPERS
// ─────────────────────────────────────────────────────────────────────────────

function showErr(id, msg) {
  const el = document.getElementById(id);
  if (!el) return;
  el.textContent = msg;
  el.classList.toggle("visible", Boolean(msg));
}

function setLoading(btnId, loading) {
  const btn = document.getElementById(btnId);
  if (!btn) return;
  btn.querySelector(".btn-label").hidden = loading;
  btn.querySelector(".btn-spinner").hidden = !loading;
  btn.disabled = loading;
}

/** Password strength — returns { level, label } */
function measureStrength(pw) {
  if (!pw) return { level: "", label: "" };
  let score = 0;
  if (pw.length >= 8)  score++;
  if (pw.length >= 12) score++;
  if (/[A-Z]/.test(pw)) score++;
  if (/[0-9]/.test(pw)) score++;
  if (/[^A-Za-z0-9]/.test(pw)) score++;

  if (score <= 1) return { level: "weak",   label: "Weak" };
  if (score <= 2) return { level: "fair",   label: "Fair" };
  if (score <= 3) return { level: "good",   label: "Good" };
  return              { level: "strong", label: "Strong" };
}

// ─────────────────────────────────────────────────────────────────────────────
// STEP 1 — REQUEST FLOW
// ─────────────────────────────────────────────────────────────────────────────

function bindRequestStep(container, api, showToast) {
  const emailInput   = container.querySelector("#reg-email");
  const submitBtn    = container.querySelector("#reg-request-btn");
  const domainBadge  = container.querySelector(".reg-domain-badge");
  const requestStep  = container.querySelector("#reg-request");
  const sentStep     = container.querySelector("#reg-sent");
  const retryBtn     = container.querySelector("#reg-retry-btn");

  // Show domain badge while typing
  emailInput.addEventListener("input", () => {
    const val = emailInput.value.trim();
    domainBadge.classList.toggle("visible", val.length > 0);
    showErr("reg-email-err", "");
  });

  // Submit handler
  submitBtn.addEventListener("click", async () => {
    const email = emailInput.value.trim().toLowerCase();

    // Client-side domain guard (mirrors backend)
    if (!email) {
      showErr("reg-email-err", "Please enter your SIT email.");
      emailInput.focus();
      return;
    }

    if (!email.endsWith(SIT_DOMAIN)) {
      showErr(
        "reg-email-err",
        `Only ${SIT_DOMAIN} addresses are accepted.`
      );
      emailInput.focus();
      return;
    }

    setLoading("reg-request-btn", true);
    const result = await api.requestRegistration({ email });
    setLoading("reg-request-btn", false);

    if (!result.success) {
      showErr("reg-email-err", result.message || "Something went wrong. Try again.");
      return;
    }

    // Show sent state
    requestStep.hidden = true;
    sentStep.hidden = false;
    container.querySelector("#reg-sent-email").textContent = email;
  });

  // Retry — go back to email input
  retryBtn?.addEventListener("click", () => {
    sentStep.hidden = true;
    requestStep.hidden = false;
    emailInput.value = "";
    emailInput.focus();
    domainBadge.classList.remove("visible");
  });

  // Allow Enter key on email field
  emailInput.addEventListener("keydown", (e) => {
    if (e.key === "Enter") submitBtn.click();
  });
}

// ─────────────────────────────────────────────────────────────────────────────
// STEP 2 — COMPLETE FLOW
// ─────────────────────────────────────────────────────────────────────────────

function bindCompleteStep(container, api, token, onSuccess, showToast) {
  const nameInput    = container.querySelector("#reg-name");
  const pwInput      = container.querySelector("#reg-password");
  const confirmInput = container.querySelector("#reg-confirm");
  const submitBtn    = container.querySelector("#reg-complete-btn");
  const pwToggle     = container.querySelector("#reg-pw-toggle");
  const pwBar        = container.querySelector("#pw-bar");
  const pwHint       = container.querySelector("#pw-hint");

  // Enable submit only when both fields have values
  function checkReady() {
    const ok =
      nameInput.value.trim().length >= 2 &&
      pwInput.value.length >= 8 &&
      confirmInput.value.length >= 1;
    submitBtn.disabled = !ok;
  }

  // Password strength meter
  pwInput.addEventListener("input", () => {
    const { level, label } = measureStrength(pwInput.value);
    pwBar.className = "pw-bar " + level;
    pwHint.textContent = label ? `Strength: ${label}` : "";
    showErr("reg-pw-err", "");
    checkReady();
  });

  nameInput.addEventListener("input",    () => { showErr("reg-name-err", ""); checkReady(); });
  confirmInput.addEventListener("input", () => { showErr("reg-confirm-err", ""); checkReady(); });

  // Toggle password visibility
  pwToggle?.addEventListener("click", () => {
    const isText = pwInput.type === "text";
    pwInput.type = isText ? "password" : "text";
    pwToggle.textContent = isText ? "👁" : "🙈";
  });

  // Submit
  submitBtn.addEventListener("click", async () => {
    const name     = nameInput.value.trim();
    const password = pwInput.value;
    const confirm  = confirmInput.value;

    let valid = true;

    if (name.length < 2) {
      showErr("reg-name-err", "Name must be at least 2 characters.");
      valid = false;
    }

    if (password.length < 8) {
      showErr("reg-pw-err", "Password must be at least 8 characters.");
      valid = false;
    }

    if (password !== confirm) {
      showErr("reg-confirm-err", "Passwords don't match.");
      valid = false;
    }

    if (!valid) return;

    setLoading("reg-complete-btn", true);
    const result = await api.completeRegistration({ token, name, password });
    setLoading("reg-complete-btn", false);

    if (!result.success) {
      showErr("reg-pw-err", result.message || "Something went wrong. Try again.");
      return;
    }

    clearTokenFromUrl();
    showToast("Account created! Welcome to inSITe 🎉", "success");
    onSuccess();
  });

  // Enter key on confirm field
  confirmInput.addEventListener("keydown", (e) => {
    if (e.key === "Enter" && !submitBtn.disabled) submitBtn.click();
  });

  nameInput.focus();
}

// ─────────────────────────────────────────────────────────────────────────────
// PUBLIC ENTRY POINT
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Initialise the registration flow inside a container element.
 *
 * @param {HTMLElement} container  — element to render into (e.g. .auth-form-inner)
 * @param {object}      api        — the api module
 * @param {Function}    onSuccess  — called after account creation (e.g. close modal, reload session)
 * @param {Function}    showToast  — showToast(message, type)
 */
export async function initRegisterFlow(container, api, onSuccess, showToast) {
  injectStyles();

  const token = getTokenFromUrl();

  if (token) {
    // ── Arriving via invite link ──────────────────────────────────────────
    // Show a brief loading state while we verify the token
    container.innerHTML = /* html */ `
      <div class="reg-checking">
        <span class="reg-checking-spinner">🔐</span>
        Verifying your invite link…
      </div>
    `;

    const check = await api.checkVerifyToken({ token });

    container.innerHTML = `<div class="reg-flow-wrap">${completeTemplate(
      check.success ? check.email : "your SIT email"
    )}</div>`;

    const completeStep      = container.querySelector("#reg-complete");
    const tokenInvalidStep  = container.querySelector("#reg-token-invalid");
    const newLinkBtn        = container.querySelector("#reg-newlink-btn");

    if (!check.success) {
      // Token invalid / expired — show error state
      completeStep.hidden     = true;
      tokenInvalidStep.hidden = false;

      newLinkBtn?.addEventListener("click", () => {
        clearTokenFromUrl();
        // Re-init in request mode
        initRegisterFlow(container, api, onSuccess, showToast);
      });
      return;
    }

    // Token valid — bind the completion form
    bindCompleteStep(container, api, token, onSuccess, showToast);

  } else {
    // ── Normal register tab — email request ───────────────────────────────
    container.innerHTML = `<div class="reg-flow-wrap">${requestTemplate()}</div>`;
    bindRequestStep(container, api, showToast);
  }
}
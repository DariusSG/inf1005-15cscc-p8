/**
 * sanitise.js
 * Frontend input sanitisation utilities.
 *
 * Note: These are client-side guards for UX and basic hygiene.
 * The authoritative sanitisation must always be done server-side.
 *
 * React JSX already escapes all rendered values, so these helpers
 * focus on cleaning data BEFORE it is sent to the API.
 */

// Max lengths per field type (characters)
const LIMITS = {
  name:        100,
  email:       254,   // RFC 5321 max
  password:   128,
  title:       200,
  content:    5000,
  bio:         500,
  short:       300,
  code:         20,
};

/**
 * Trim whitespace and enforce a max length.
 * @param {string} value
 * @param {'name'|'email'|'password'|'title'|'content'|'bio'|'short'|'code'} type
 * @returns {string}
 */
export function sanitiseField(value, type = 'short') {
  if (typeof value !== 'string') return '';
  const limit = LIMITS[type] ?? 300;
  return value.trim().slice(0, limit);
}

/**
 * Validate and sanitise a SIT email address.
 * Returns the cleaned email or null if invalid.
 * @param {string} email
 * @returns {string|null}
 */
export function sanitiseEmail(email) {
  const clean = sanitiseField(email, 'email').toLowerCase();
  // Must be a SIT email
  if (!clean.endsWith('@sit.singaporetech.edu.sg')) return null;
  // Basic email shape check
  const emailRegex = /^[a-z0-9._%+\-]+@sit\.singaporetech\.edu\.sg$/;
  if (!emailRegex.test(clean)) return null;
  return clean;
}

/**
 * Validate password — min 6 chars, max 128.
 * Returns error string or null if valid.
 * @param {string} password
 * @returns {string|null}
 */
export function validatePassword(password) {
  if (!password || password.length < 6) return 'Password must be at least 6 characters.';
  if (password.length > 128) return 'Password is too long.';
  return null;
}

/**
 * Sanitise a free-text field and validate it is non-empty.
 * Returns { value, error }.
 * @param {string} value
 * @param {string} fieldName  — shown in error message
 * @param {'name'|'title'|'content'|'bio'|'short'|'code'} type
 * @returns {{ value: string, error: string|null }}
 */
export function requireField(value, fieldName, type = 'short') {
  const clean = sanitiseField(value, type);
  if (!clean) return { value: clean, error: `${fieldName} is required.` };
  return { value: clean, error: null };
}

/**
 * Sanitise an array of module codes.
 * Filters to valid-looking codes (e.g. ICT1001) and caps at maxCount.
 * @param {string[]} codes
 * @param {number} maxCount
 * @returns {string[]}
 */
export function sanitiseModuleCodes(codes, maxCount = 3) {
  const codeRegex = /^[A-Z]{2,4}\d{4}$/;
  return codes
    .map((c) => sanitiseField(c, 'code').toUpperCase())
    .filter((c) => codeRegex.test(c))
    .slice(0, maxCount);
}

/**
 * Sanitise a bounty amount — must be one of the allowed values.
 * @param {number} amount
 * @returns {number}
 */
export function sanitiseBounty(amount) {
  const allowed = [5, 10, 20, 30, 50];
  const n = Number(amount);
  return allowed.includes(n) ? n : 10;
}

/**
 * Sanitise a rating value — must be integer 1–5.
 * @param {number} rating
 * @returns {number|null}
 */
export function sanitiseRating(rating) {
  const n = Math.round(Number(rating));
  if (n < 1 || n > 5) return null;
  return n;
}

/**
 * Sanitise a slider value (workload, difficulty, usefulness) — integer 1–10.
 * @param {number} value
 * @returns {number}
 */
export function sanitiseSlider(value) {
  const n = Math.round(Number(value));
  if (n < 1) return 1;
  if (n > 10) return 10;
  return n;
}

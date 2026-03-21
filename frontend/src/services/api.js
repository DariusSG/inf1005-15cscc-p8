const BASE = "/api/v1";

const authHeaders = () => {
  const token = localStorage.getItem("access_token");
  return {
    "Content-Type": "application/json",
    ...(token ? { Authorization: `Bearer ${token}` } : {}),
  };
};

// Automatically store a rotated access token returned in X-New-Access-Token header
const handleResponse = async (res) => {
  const newToken = res.headers.get("X-New-Access-Token");
  if (newToken) localStorage.setItem("access_token", newToken);

  const body = await res.json();
  if (!res.ok) throw new Error(body.error || "Request failed");
  return body;
};

export const api = {
  // ── Auth ───────────────────────────────────────────────────────────────

  async getSession() {
    // 🔌 BACKEND_CALL: GET /api/v1/auth/me
    try {
      return await handleResponse(
        await fetch(`${BASE}/auth/me`, { headers: authHeaders() })
      );
    } catch {
      return null;
    }
  },

  async login({ email, password }) {
    // 🔌 BACKEND_CALL: POST /api/v1/auth/login
    const data = await handleResponse(
      await fetch(`${BASE}/auth/login`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ email, password }),
      })
    );
    localStorage.setItem("access_token", data.access_token);
    return { success: true, user: data.user ?? { email } };
  },

  async logout() {
    // 🔌 BACKEND_CALL: POST /api/v1/auth/logout
    const token = localStorage.getItem("access_token");
    await fetch(`${BASE}/auth/logout`, {
      method: "POST",
      headers: authHeaders(),
      body: JSON.stringify({ access_token: token }),
    });
    localStorage.removeItem("access_token");
    return { success: true };
  },

  // ── Registration — 2-step email-invite flow ────────────────────────────

  /**
   * Step 1 — user submits their SIT email.
   * Backend validates @sit.singaporetech.edu.sg domain and sends invite link.
   * 🔌 BACKEND_CALL: POST /api/v1/auth/register/request { email }
   */
  async requestRegistration({ email }) {
    try {
      await handleResponse(
        await fetch(`${BASE}/auth/register/request`, {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ email }),
        })
      );
      return { success: true };
    } catch (e) {
      return { success: false, message: e.message };
    }
  },

  /**
   * Step 1b — called on page load when ?token= is present in the URL.
   * Returns a masked email so the form can show "Registering as joh***@sit.…"
   * without exposing the full address in JS state.
   * 🔌 BACKEND_CALL: GET /api/v1/auth/register/verify?token=
   */
  async checkVerifyToken({ token }) {
    try {
      const data = await handleResponse(
        await fetch(
          `${BASE}/auth/register/verify?token=${encodeURIComponent(token)}`
        )
      );
      return { success: true, email: data.email };
    } catch (e) {
      return { success: false, message: e.message };
    }
  },

  /**
   * Step 2 — user fills in name + password after clicking the invite link.
   * 🔌 BACKEND_CALL: POST /api/v1/auth/register/complete { token, name, password }
   */
  async completeRegistration({ token, name, password }) {
    try {
      const data = await handleResponse(
        await fetch(`${BASE}/auth/register/complete`, {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ token, name, password }),
        })
      );
      localStorage.setItem("access_token", data.access_token);
      return { success: true };
    } catch (e) {
      return { success: false, message: e.message };
    }
  },

  // ── Modules ────────────────────────────────────────────────────────────

  async getModules() {
    // 🔌 BACKEND_CALL: GET /api/v1/modules
    return handleResponse(
      await fetch(`${BASE}/modules`, { headers: authHeaders() })
    );
  },

  async getModuleByCode(code) {
    // 🔌 BACKEND_CALL: GET /api/v1/modules/{code}
    return handleResponse(
      await fetch(`${BASE}/modules/${code}`, { headers: authHeaders() })
    );
  },

  // ── Reviews ────────────────────────────────────────────────────────────

  async submitReview(payload) {
    // 🔌 BACKEND_CALL: POST /api/v1/reviews  |  POST /api/v1/reviews/{id} (edit)
    const isEdit = !!payload.editingId;
    const url = isEdit
      ? `${BASE}/reviews/${payload.editingId}`
      : `${BASE}/reviews`;
    try {
      const review = await handleResponse(
        await fetch(url, {
          method: "POST",
          headers: authHeaders(),
          body: JSON.stringify({
            module_code: payload.moduleCode,
            rating: payload.rating,
            title: payload.title,
            content: payload.content,
            workload: payload.workload,
            difficulty: payload.difficulty,
            usefulness: payload.usefulness,
          }),
        })
      );
      return { success: true, review, mode: isEdit ? "edit" : "create" };
    } catch (e) {
      return { success: false, message: e.message };
    }
  },

  async toggleVote({ moduleCode, reviewId, type }) {
    // 🔌 BACKEND_CALL: POST /api/v1/reviews/{id}/vote
    try {
      const review = await handleResponse(
        await fetch(`${BASE}/reviews/${reviewId}/vote`, {
          method: "POST",
          headers: authHeaders(),
          body: JSON.stringify({ type }),
        })
      );
      return { success: true, review };
    } catch (e) {
      return { success: false, message: e.message };
    }
  },

  async toggleReport({ moduleCode, reviewId }) {
    // 🔌 BACKEND_CALL: POST /api/v1/reviews/{id}/report
    try {
      const result = await handleResponse(
        await fetch(`${BASE}/reviews/${reviewId}/report`, {
          method: "POST",
          headers: authHeaders(),
          body: JSON.stringify({ reason: "User reported review" }),
        })
      );
      return { success: true, ...result };
    } catch (e) {
      return { success: false, message: e.message };
    }
  },

  async addComment({ moduleCode, reviewId, text }) {
    // 🔌 BACKEND_CALL: POST /api/v1/reviews/{id}/comments
    try {
      const review = await handleResponse(
        await fetch(`${BASE}/reviews/${reviewId}/comments`, {
          method: "POST",
          headers: authHeaders(),
          body: JSON.stringify({ text }),
        })
      );
      return { success: true, review };
    } catch (e) {
      return { success: false, message: e.message };
    }
  },

  // ── Tutors ─────────────────────────────────────────────────────────────

  async getTutors(search = "") {
    // 🔌 BACKEND_CALL: GET /api/v1/tutors?search=
    const qs = search ? `?search=${encodeURIComponent(search)}` : "";
    return handleResponse(
      await fetch(`${BASE}/tutors${qs}`, { headers: authHeaders() })
    );
  },

  async createTutorListing(payload) {
    // 🔌 BACKEND_CALL: POST /api/v1/tutors
    try {
      const tutor = await handleResponse(
        await fetch(`${BASE}/tutors`, {
          method: "POST",
          headers: authHeaders(),
          body: JSON.stringify(payload),
        })
      );
      return { success: true, tutor };
    } catch (e) {
      return { success: false, message: e.message };
    }
  },

  // ── Study Groups ───────────────────────────────────────────────────────

  async getStudyGroups(search = "") {
    // 🔌 BACKEND_CALL: GET /api/v1/study-groups?search=
    const qs = search ? `?search=${encodeURIComponent(search)}` : "";
    return handleResponse(
      await fetch(`${BASE}/study-groups${qs}`, { headers: authHeaders() })
    );
  },

  async createStudyGroup(payload) {
    // 🔌 BACKEND_CALL: POST /api/v1/study-groups
    try {
      const group = await handleResponse(
        await fetch(`${BASE}/study-groups`, {
          method: "POST",
          headers: authHeaders(),
          body: JSON.stringify(payload),
        })
      );
      return { success: true, group };
    } catch (e) {
      return { success: false, message: e.message };
    }
  },

  // ── Help Requests ──────────────────────────────────────────────────────

  async getHelpRequests(search = "") {
    // 🔌 BACKEND_CALL: GET /api/v1/help-requests?search=
    const qs = search ? `?search=${encodeURIComponent(search)}` : "";
    return handleResponse(
      await fetch(`${BASE}/help-requests${qs}`, { headers: authHeaders() })
    );
  },

  async createHelpRequest(payload) {
    // 🔌 BACKEND_CALL: POST /api/v1/help-requests
    try {
      const req = await handleResponse(
        await fetch(`${BASE}/help-requests`, {
          method: "POST",
          headers: authHeaders(),
          body: JSON.stringify(payload),
        })
      );
      return { success: true, request: req };
    } catch (e) {
      return { success: false, message: e.message };
    }
  },

  // ── Admin ──────────────────────────────────────────────────────────────

  async getReportedReviews() {
    // 🔌 BACKEND_CALL: GET /api/v1/admin/reported-reviews
    return handleResponse(
      await fetch(`${BASE}/admin/reported-reviews`, { headers: authHeaders() })
    );
  },
};
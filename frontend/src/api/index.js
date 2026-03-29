import client from './client';

// ── Modules ──────────────────────────────────────────────
export const getModules = () =>
  client.get('/modules').then((r) => r.data);

export const getModule = (code) =>
  client.get(`/modules/${code}`).then((r) => r.data);

// ── Reviews ───────────────────────────────────────────────
export const getReviews = (params) =>
  client.get('/reviews', { params }).then((r) => r.data);

export const postReview = (data) =>
  client.post('/reviews', data).then((r) => r.data);

export const putReview = (id, data) =>
  client.put(`/reviews/${id}`, data).then((r) => r.data);

export const postReviewVote = (id, direction) =>
  client.post(`/reviews/${id}/vote`, { direction }).then((r) => r.data);

export const postReviewReport = (id) =>
  client.post(`/reviews/${id}/report`).then((r) => r.data);

export const postReviewComment = (id, content) =>
  client.post(`/reviews/${id}/comments`, { content }).then((r) => r.data);

// ── Tutors ────────────────────────────────────────────────
export const getTutors = (params) =>
  client.get('/tutors', { params }).then((r) => r.data);

export const postTutor = (data) =>
  client.post('/tutors', data).then((r) => r.data);

// ── Help Requests ─────────────────────────────────────────
export const getHelpRequests = (params) =>
  client.get('/help-requests', { params }).then((r) => r.data);

export const getHelpRequest = (id) =>
  client.get(`/help-requests/${id}`).then((r) => r.data);

export const postHelpRequest = (data) =>
  client.post('/help-requests', data).then((r) => r.data);

export const postHelpRespond = (id, content) =>
  client.post(`/help-requests/${id}/respond`, { content }).then((r) => r.data);

export const postHelpSolve = (id) =>
  client.post(`/help-requests/${id}/solve`).then((r) => r.data);

// ── Study Groups ──────────────────────────────────────────
export const getStudyGroups = (params) =>
  client.get('/study-groups', { params }).then((r) => r.data);

export const postStudyGroup = (data) =>
  client.post('/study-groups', data).then((r) => r.data);

// ── Admin ─────────────────────────────────────────────────
export const getReportedReviews = (params) =>
  client.get('/admin/reported-reviews', { params }).then((r) => r.data);

// ── Users ─────────────────────────────────────────────────
export const getUsers = (params) =>
  client.get('/users', { params }).then((r) => r.data);

export const getUser = (id) =>
  client.get(`/users/${id}`).then((r) => r.data);

// ════════════════════════════════════════════════════════════════════
//  tutors/ContactModal.js — Tutor contact info modal (read-only)
// ════════════════════════════════════════════════════════════════════

function openContactModal(tid) {
  const t = store.tutors.find(x => x.id === tid);
  if (!t) return;
  const ini = userInitials(t.name);

  document.getElementById('contactHead').innerHTML = `
    <h3>Tutor Information</h3>
    <p style="color:var(--text-secondary);font-size:.88rem">Contact details for ${t.name}</p>`;

  document.getElementById('contactBody').innerHTML = `
    <div style="display:flex;gap:14px;align-items:center;margin-bottom:16px">
      <div class="avatar avatar-lg avatar-green">${ini}</div>
      <div>
        <div style="font-weight:600;font-size:1.05rem">${t.name}</div>
        <div style="font-size:.82rem;color:var(--text-secondary)">${t.bio}</div>
      </div>
    </div>
    <div class="info-row">
      <span class="info-label">Modules</span>
      <span class="info-value">${t.modules.join(', ')}</span>
    </div>
    <div class="info-row">
      <span class="info-label">Rate</span>
      <span class="info-value" style="color:var(--green)">${t.rate}</span>
    </div>
    <div class="info-row">
      <span class="info-label">Contact Email</span>
      <span class="info-value"><a href="mailto:${t.contactEmail}">${t.contactEmail}</a></span>
    </div>
    <p style="font-size:.78rem;color:var(--text-muted);margin-top:16px;text-align:center">
      Reach out via the email above to arrange a session.
    </p>`;

  openModal('contactModal');
}

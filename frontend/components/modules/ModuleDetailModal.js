// ════════════════════════════════════════════════════════════════════
//  modules/ModuleDetailModal.js — Module detail modal
// ════════════════════════════════════════════════════════════════════

function openDetail(code) {
  const m = store.modules[code];
  if (!m) return;

  const avg = avgRating(m);
  const aW  = avgMetric(m, 'workload');
  const aD  = avgMetric(m, 'difficulty');
  const aU  = avgMetric(m, 'usefulness');
  const canWrite = authStore.user && authStore.user.role !== 'admin';

  document.getElementById('detailHead').innerHTML = `
    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:16px">
      <div>
        <span class="fac-tag ${m.faculty}">${m.faculty}</span>
        <div style="font-family:var(--mono);font-size:.82rem;color:var(--text-muted);margin:5px 0 3px">${m.code}</div>
        <h2 style="font-size:1.3rem;font-weight:700">${m.name}</h2>
      </div>
      <div style="text-align:right">
        <div style="font-size:1.8rem;font-family:var(--mono);font-weight:700;color:var(--orange)">${avg > 0 ? avg : '—'}</div>
        <div style="font-size:.76rem;color:var(--text-muted)">${m.reviews.length} reviews</div>
      </div>
    </div>`;

  const reviewsHtml = m.reviews.length
    ? m.reviews.map(r => renderReviewCard(r, code)).join('')
    : '<div class="empty"><div class="e-icon">📝</div><h3>No reviews yet</h3></div>';

  const prereqsHtml = m.prereqs.length
    ? m.prereqs.map(p => `<span class="tag-prereq">${p}</span>`).join('')
    : '<span style="color:var(--text-muted)">None</span>';

  document.getElementById('detailBody').innerHTML = `
    <div class="detail-stats">
      <div class="stat-box"><div class="sv" style="color:var(--orange)">${aW}</div><div class="sl">Workload</div></div>
      <div class="stat-box"><div class="sv" style="color:var(--red)">${aD}</div><div class="sl">Difficulty</div></div>
      <div class="stat-box"><div class="sv" style="color:var(--green)">${aU}</div><div class="sl">Usefulness</div></div>
    </div>
    <div class="detail-block">
      <h4>Description</h4>
      <p style="font-size:.88rem;color:var(--text-secondary)">${m.desc}</p>
    </div>
    <div class="detail-block">
      <h4>Prerequisites</h4>${prereqsHtml}
    </div>
    <div class="detail-block">
      <h4>Semesters</h4>
      ${m.semesters.map(s => `<span class="tag-sem">Sem ${s}</span>`).join('')}
    </div>
    <div class="detail-block">
      <div class="reviews-head">
        <h4 style="margin:0">Reviews (${m.reviews.length})</h4>
        ${canWrite ? `<button class="btn btn-primary btn-sm" onclick="openWriteReview('${code}')">+ Write Review</button>` : ''}
      </div>
      ${reviewsHtml}
    </div>`;

  openModal('detailModal');
}

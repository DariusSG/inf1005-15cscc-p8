// ════════════════════════════════════════════════════════════════════
//  modules/ReviewCard.js — Review card HTML + vote / report / comment
//  API: ../api/reviews.php  actions: vote, report, delete, add_comment
// ════════════════════════════════════════════════════════════════════

function renderReviewCard(r, mc) {
  const u      = authStore.user;
  const own    = u && u.email === r.email;
  const isAdm  = u && u.role === 'admin';
  const myVote = u && r.voters ? r.voters[u.email] || null : null;

  const commentsHtml = r.comments.map(c => `
    <div class="cmt">
      <div class="avatar avatar-sm" style="background:var(--bg-elevated);color:var(--text-muted)">${c.a[0]}</div>
      <div>
        <span class="cmt-author">${c.a}</span>
        <span class="cmt-text">${c.t}</span>
        <div class="cmt-time">${c.time}</div>
      </div>
    </div>`).join('');

  const commentInput = u ? `
    <div class="cmt-input-row">
      <input type="text" placeholder="Add comment..." id="cmt-in-${r.id}"
             onkeydown="if(event.key==='Enter')addComment('${mc}',${r.id})">
      <button class="btn btn-primary btn-sm" onclick="addComment('${mc}',${r.id})">Post</button>
    </div>` : '';

  return `
    <div class="rev-card">
      <div class="rev-top">
        <div class="rev-author">
          <div class="avatar avatar-sm avatar-accent">${r.author[0]}</div>
          <div>
            <div style="font-weight:600;font-size:.88rem">${r.author}</div>
            <div style="font-size:.72rem;color:var(--text-muted)">${r.date}</div>
          </div>
        </div>
        <div class="stars" style="font-size:.88rem">${starsHtml(r.rating)}</div>
      </div>
      <div class="rev-title">${r.title}</div>
      <div class="rev-content">${r.content}</div>
      <div class="rev-metrics">
        <span class="rev-metric">Workload: <span>${r.workload}/10</span></span>
        <span class="rev-metric">Difficulty: <span>${r.difficulty}/10</span></span>
        <span class="rev-metric">Usefulness: <span>${r.usefulness}/10</span></span>
      </div>
      <div class="rev-actions">
        <button class="vote-btn${myVote === 'up'   ? ' voted' : ''}"
                onclick="voteReview('${mc}',${r.id},'up')">▲ ${r.upvotes}</button>
        <button class="vote-btn${myVote === 'down' ? ' voted' : ''}"
                onclick="voteReview('${mc}',${r.id},'down')">▼ ${r.downvotes}</button>
        <button class="btn-ghost btn-sm" onclick="toggleComments(${r.id})">💬 ${r.comments.length}</button>
        ${own   ? `<button class="btn-ghost btn-sm" onclick="editReview('${mc}',${r.id})">✏️ Edit</button>` : ''}
        ${!own && u ? `<button class="btn-ghost btn-sm" onclick="reportReview('${mc}',${r.id})">🚩</button>` : ''}
        ${isAdm ? `<button class="btn-ghost btn-sm" style="color:var(--red)"
                           onclick="adminRemoveRev('${mc}',${r.id})">🗑️</button>` : ''}
      </div>
      <div class="comments-area" id="cmts-${r.id}" style="display:none">
        ${commentsHtml}
        ${commentInput}
      </div>
    </div>`;
}

function toggleComments(id) {
  const el = document.getElementById('cmts-' + id);
  el.style.display = el.style.display === 'none' ? 'block' : 'none';
}

// ── Vote ─────────────────────────────────────────────────────────
async function voteReview(mc, rid, dir) {
  if (!authStore.user) return toast('Sign in to vote', 'error');

  try {
    const res  = await fetch('../api/reviews.php', {
      method:  'POST',
      headers: { 'Content-Type': 'application/json' },
      body:    JSON.stringify({ action: 'vote', review_id: rid, direction: dir }),
    });
    const data = await res.json();

    if (data.success) {
      // Update local store with returned counts + voter map
      const r = store.modules[mc].reviews.find(x => x.id === rid);
      if (r) {
        r.upvotes   = data.upvotes;
        r.downvotes = data.downvotes;
        r.voters    = data.voters;
      }
      openDetail(mc);
    } else {
      toast(data.message, 'error');
    }
  } catch (e) {
    toast('Network error', 'error');
  }
}

// ── Report ───────────────────────────────────────────────────────
async function reportReview(mc, rid) {
  if (!authStore.user) return;

  try {
    const res  = await fetch('../api/reviews.php', {
      method:  'POST',
      headers: { 'Content-Type': 'application/json' },
      body:    JSON.stringify({ action: 'report', review_id: rid }),
    });
    const data = await res.json();

    if (data.success) {
      toast('Reported — admin will review', 'info');
    } else {
      toast(data.message, 'info');   // e.g. "Already reported"
    }
  } catch (e) {
    toast('Network error', 'error');
  }
}

// ── Add comment ──────────────────────────────────────────────────
async function addComment(mc, rid) {
  const inp = document.getElementById('cmt-in-' + rid);
  const txt = inp.value.trim();
  if (!txt) return;

  try {
    const res  = await fetch('../api/reviews.php', {
      method:  'POST',
      headers: { 'Content-Type': 'application/json' },
      body:    JSON.stringify({ action: 'add_comment', review_id: rid, text: txt }),
    });
    const data = await res.json();

    if (data.success) {
      const r = store.modules[mc].reviews.find(x => x.id === rid);
      if (r) r.comments.push(data.comment);
      openDetail(mc);
      setTimeout(() => {
        const el = document.getElementById('cmts-' + rid);
        if (el) el.style.display = 'block';
      }, 50);
      toast('Comment added!', 'success');
    } else {
      toast(data.message, 'error');
    }
  } catch (e) {
    toast('Network error', 'error');
  }
}

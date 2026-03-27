//reviews.php, tutors.php, help -> delete, add comment for reviews
function renderDashboard() {
    const el = document.getElementById('dashContent');
    if (!authStore.user) {
        el.innerHTML = '<div class="empty"><div class="e-icon">🔒</div><h3>Sign in to view dashboard</h3></div>';
        return;
    }

    // user's reviews
    const myRevs = [];
    Object.entries(store.modules).forEach(([mc, m]) =>
        m.reviews.forEach(r => {
            if (r.email === authStore.user.email) myRevs.push({ ...r, mc });
        })
    );
    const revsHtml = myRevs.length
        ? myRevs.map(r => `
        <div class="dash-item">
          <div class="di-info">
            <div class="di-title">${'★'.repeat(r.rating)} ${r.title}</div>
            <div class="di-sub">${r.mc} · ${r.date}</div>
          </div>
          <div class="di-actions">
            <button class="btn btn-secondary btn-sm" onclick="editReview('${r.mc}',${r.id})">Edit</button>
            <button class="btn btn-danger btn-sm"    onclick="dashDeleteReview('${r.mc}',${r.id})">Delete</button>
          </div>
        </div>`).join('')
        : '<p style="color:var(--text-muted);font-size:.86rem">No reviews yet.</p>';

    //user's comments
    const myCmts = [];
    const sn = shortName(authStore.user.name);
    Object.entries(store.modules).forEach(([mc, m]) =>
        m.reviews.forEach(r =>
            r.comments.forEach((c, ci) => {
                if (c.a === sn) myCmts.push({ mc, rid: r.id, ci, revTitle: r.title, text: c.t });
            })
        )
    );
    const cmtsHtml = myCmts.length
        ? myCmts.map(c => `
        <div class="dash-item">
          <div class="di-info">
            <div class="di-title">"${c.text}"</div>
            <div class="di-sub">on "${c.revTitle}" · ${c.mc}</div>
          </div>
          <div class="di-actions">
            <button class="btn btn-danger btn-sm"
                    onclick="dashDeleteComment('${c.mc}',${c.rid},${c.ci})">Delete</button>
          </div>
        </div>`).join('')
        : '<p style="color:var(--text-muted);font-size:.86rem">No comments yet.</p>';

    // user's tut post
    const myTut = store.tutors.filter(t => t.userEmail === authStore.user.email);
    const tutHtml = myTut.length
        ? myTut.map(t => `
        <div class="dash-item">
          <div class="di-info">
            <div class="di-title">${t.modules.join(', ')}</div>
            <div class="di-sub">${t.rate}</div>
          </div>
          <div class="di-actions">
            <button class="btn btn-danger btn-sm" onclick="dashDeleteTutor(${t.id})">Remove</button>
          </div>
        </div>`).join('')
        : '<p style="color:var(--text-muted);font-size:.86rem">No tutor listings.</p>';

    //user's help req
    const myHelp = store.helpReqs.filter(h => h.userEmail === authStore.user.email);
    const helpHtml = myHelp.length
        ? myHelp.map(h => `
        <div class="dash-item">
          <div class="di-info">
            <div class="di-title">
              ${h.title}
              ${h.status === 'solved' ? '<span class="solved-tag" style="font-size:.65rem">Solved</span>' : ''}
            </div>
            <div class="di-sub">${h.module} · ${h.urgency}${h.hasBounty ? ' · $' + h.bountyAmount : ''}</div>
          </div>
          <div class="di-actions">
            ${h.status === 'open'
            ? `<button class="btn btn-success btn-sm"
                         onclick="solveHelp(${h.id}).then(()=>renderDashboard())">Solve</button>`
            : `<button class="btn btn-secondary btn-sm"
                         onclick="reopenHelp(${h.id}).then(()=>renderDashboard())">Reopen</button>`}
            <button class="btn btn-danger btn-sm" onclick="dashDeleteHelp(${h.id})">Delete</button>
          </div>
        </div>`).join('')
        : '<p style="color:var(--text-muted);font-size:.86rem">No help requests.</p>';

    el.innerHTML = `
    <div class="dash-section">
      <h3>📝 My Reviews <span class="count">${myRevs.length}</span></h3>${revsHtml}
    </div>
    <div class="dash-section">
      <h3>💬 My Comments <span class="count">${myCmts.length}</span></h3>${cmtsHtml}
    </div>
    <div class="dash-section">
      <h3>🎓 My Tutor Listings <span class="count">${myTut.length}/5</span></h3>${tutHtml}
    </div>
    <div class="dash-section">
      <h3>🆘 My Help Requests
        <span class="count">${myHelp.filter(h => h.status === 'open').length}/5 open</span>
      </h3>${helpHtml}
    </div>`;
}

async function dashDeleteReview(mc, rid) {
    try {
        const res  = await fetch('../api/reviews.php', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ action: 'delete', id: rid }),
        });
        const data = await res.json();

        if (data.success) {
            store.modules[mc].reviews = store.modules[mc].reviews.filter(r => r.id !== rid);
            store.reported = store.reported.filter(r => r.reviewId !== rid);
            renderDashboard();
            toast('Review deleted', 'success');
        } else {
            toast(data.message, 'error');
        }
    } catch (e) {
        toast('Network error', 'error');
    }
}

async function dashDeleteComment(mc, rid, ci) {
    try {
        const res  = await fetch('../api/reviews.php', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ action: 'delete_comment', review_id: rid, comment_index: ci }),
        });
        const data = await res.json();

        if (data.success) {
            const r = store.modules[mc].reviews.find(x => x.id === rid);
            if (r) r.comments.splice(ci, 1);
            renderDashboard();
            toast('Comment deleted', 'success');
        } else {
            toast(data.message, 'error');
        }
    } catch (e) {
        toast('Network error', 'error');
    }
}

async function dashDeleteTutor(tid) {
    try {
        const res  = await fetch('../api/tutors.php', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ action: 'delete', id: tid }),
        });
        const data = await res.json();

        if (data.success) {
            store.tutors = store.tutors.filter(t => t.id !== tid);
            renderDashboard();
            toast('Listing removed', 'success');
        } else {
            toast(data.message, 'error');
        }
    } catch (e) {
        toast('Network error', 'error');
    }
}

async function dashDeleteHelp(hid) {
    try {
        const res  = await fetch('../api/help.php', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ action: 'delete', id: hid }),
        });
        const data = await res.json();

        if (data.success) {
            store.helpReqs = store.helpReqs.filter(h => h.id !== hid);
            renderDashboard();
            toast('Request deleted', 'success');
        } else {
            toast(data.message, 'error');
        }
    } catch (e) {
        toast('Network error', 'error');
    }
}

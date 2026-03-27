
async function renderAdmin() {
    const el = document.getElementById('adminContent');
    if (!authStore.user || authStore.user.role !== 'admin') {
        el.innerHTML = '<div class="empty"><div class="e-icon">🔒</div><h3>Admin access required</h3></div>';
        return;
    }

    // Fetch latest reports from server
    try {
        const res  = await fetch('../api/reviews.php?action=reports');
        const data = await res.json();
        if (data.success) store.reported = data.reports;
    } catch (e) { /* fall back to local store.reported */ }

    const rows = store.reported.length
        ? store.reported.map(r => `
        <tr>
          <td style="font-family:var(--mono);font-size:.8rem">${r.mc}</td>
          <td>${r.title}</td>
          <td><span class="report-badge">🚩 ${r.count}</span></td>
          <td>
            <button class="btn btn-danger btn-sm"
                    onclick="adminRemoveRev('${r.mc}',${r.reviewId})">Remove</button>
            <button class="btn btn-secondary btn-sm"
                    onclick="dismissReport(${r.reviewId})">Dismiss</button>
          </td>
        </tr>`).join('')
        : '<tr><td colspan="4" style="text-align:center;color:var(--text-muted);padding:20px">No reports</td></tr>';

    el.innerHTML = `
    <div class="admin-box">
      <h3>📦 Create Module</h3>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
        <div class="form-group">
          <label>Code</label>
          <input type="text" id="aCode" placeholder="ICT3001">
        </div>
        <div class="form-group">
          <label>Faculty</label>
          <select id="aFac">
            <option>ICT</option><option>Business</option>
            <option>Engineering</option><option>HSS</option>
          </select>
        </div>
        <div class="form-group" style="grid-column:1/-1">
          <label>Name</label>
          <input type="text" id="aName" placeholder="Module name">
        </div>
        <div class="form-group">
          <label>Credits</label>
          <input type="number" id="aCred" value="5">
        </div>
        <div class="form-group">
          <label>Semesters</label>
          <input type="text" id="aSem" placeholder="1,2">
        </div>
        <div class="form-group" style="grid-column:1/-1">
          <label>Description</label>
          <textarea id="aDesc" rows="2"></textarea>
        </div>
        <div class="form-group" style="grid-column:1/-1">
          <label>Prerequisites</label>
          <input type="text" id="aPrereq" placeholder="ICT1001,ICT1002">
        </div>
      </div>
      <button class="btn btn-primary" onclick="adminCreateMod()" style="margin-top:6px">Create</button>
    </div>
    <div class="admin-box">
      <h3>🚩 Reported Reviews</h3>
      <table class="adm-table">
        <thead><tr><th>Module</th><th>Review</th><th>Reports</th><th>Action</th></tr></thead>
        <tbody>${rows}</tbody>
      </table>
    </div>`;
}

async function adminCreateMod() {
    const code = document.getElementById('aCode').value.trim().toUpperCase();
    const name = document.getElementById('aName').value.trim();
    if (!code || !name) return toast('Code and name required', 'error');

    try {
        const res  = await fetch('../api/modules.php', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({
                action:    'create',
                code, name,
                faculty:   document.getElementById('aFac').value,
                credits:   +document.getElementById('aCred').value || 5,
                desc:      document.getElementById('aDesc').value.trim(),
                prereqs:   document.getElementById('aPrereq').value.split(',').map(s => s.trim()).filter(Boolean),
                semesters: document.getElementById('aSem').value.split(',').map(s => +s.trim()).filter(Boolean),
            }),
        });
        const data = await res.json();

        if (data.success) {
            store.modules[code] = { ...data.module, reviews: [] };
            toast(code + ' created!', 'success');
            ['aCode','aName','aDesc','aPrereq','aSem'].forEach(id => document.getElementById(id).value = '');
        } else {
            toast(data.message, 'error');
        }
    } catch (e) {
        toast('Network error', 'error');
    }
}

async function adminRemoveRev(mc, rid) {
    try {
        const res  = await fetch('../api/reviews.php', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ action: 'admin_remove', id: rid }),
        });
        const data = await res.json();

        if (data.success) {
            store.modules[mc].reviews = store.modules[mc].reviews.filter(r => r.id !== rid);
            store.reported = store.reported.filter(r => r.reviewId !== rid);
            toast('Review removed', 'success');
            renderAdmin();
        } else {
            toast(data.message, 'error');
        }
    } catch (e) {
        toast('Network error', 'error');
    }
}

//remove report
async function dismissReport(rid) {
    try {
        const res  = await fetch('../api/reviews.php', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ action: 'dismiss_report', review_id: rid }),
        });
        const data = await res.json();

        if (data.success) {
            store.reported = store.reported.filter(r => r.reviewId !== rid);
            renderAdmin();
            toast('Report dismissed', 'info');
        } else {
            toast(data.message, 'error');
        }
    } catch (e) {
        toast('Network error', 'error');
    }
}

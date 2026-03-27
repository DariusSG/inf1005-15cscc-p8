//api call can be done through ../api/help.php
function renderHelpCard(h) {
  const u      = authStore.user;
  const solved = h.status === 'solved';
  const own    = u && u.email === h.userEmail;

  const ownerActions = own && !solved
    ? `<button class="btn btn-success btn-sm" onclick="solveHelp(${h.id})">✓ Solved</button>`
    : own && solved
      ? `<button class="btn btn-secondary btn-sm" onclick="reopenHelp(${h.id})">↩ Reopen</button>`
      : '';

  const viewBtn = !own
    ? `<button class="btn btn-secondary btn-sm" onclick="openHelpDetailModal(${h.id})">View Details</button>`
    : '';

  return `
    <div class="help-card ${solved ? 'solved' : ''}">
      <div class="help-head">
        <div>
          <div class="help-title">
            ${h.title}
            ${solved ? '<span class="solved-tag">✓ Solved</span>' : ''}
          </div>
          <div class="help-mod">
            ${h.module}
            ${h.hasBounty ? `<span class="bounty-tag">💰 $${h.bountyAmount}</span>` : ''}
          </div>
        </div>
        <span class="urgency ${h.urgency}">${h.urgency}</span>
      </div>
      <div class="help-desc">${h.desc}</div>
      <div class="help-footer">
        <span class="help-meta">
          ${h.contactEmail.split('@')[0]} · ${h.responses} response${h.responses !== 1 ? 's' : ''}
        </span>
        <div class="help-actions">
          ${ownerActions}
          ${viewBtn}
        </div>
      </div>
    </div>`;
}

async function loadHelp() {
  try {
    const res  = await fetch('../api/help.php');
    const data = await res.json();
    if (data.success) store.helpReqs = data.helpReqs;
  } catch (e) {
    toast('Could not load help requests', 'error');
  }
}

function renderHelp(list) {
  const arr = list || store.helpReqs;
  document.getElementById('helpList').innerHTML =
    arr.length
      ? arr.map(h => renderHelpCard(h)).join('')
      : '<div class="empty"><div class="e-icon">🆘</div><h3>No help requests found</h3></div>';
}

function filterHelp() {
  const q        = document.getElementById('helpSearch').value.toLowerCase();
  const filtered = store.helpReqs.filter(h =>
    h.title.toLowerCase().includes(q) ||
    h.module.toLowerCase().includes(q)
  );
  renderHelp(filtered);
}

async function solveHelp(id) {
  try {
    const res  = await fetch('../api/help.php', {
      method:  'POST',
      headers: { 'Content-Type': 'application/json' },
      body:    JSON.stringify({ action: 'solve', id }),
    });
    const data = await res.json();

    if (data.success) {
      const h = store.helpReqs.find(x => x.id === id);
      if (h) h.status = 'solved';
      renderHelp();
      toast('Marked solved!', 'success');
    } else {
      toast(data.message, 'error');
    }
  } catch (e) {
    toast('Network error', 'error');
  }
}

async function reopenHelp(id) {
  try {
    const res  = await fetch('../api/help.php', {
      method:  'POST',
      headers: { 'Content-Type': 'application/json' },
      body:    JSON.stringify({ action: 'reopen', id }),
    });
    const data = await res.json();

    if (data.success) {
      const h = store.helpReqs.find(x => x.id === id);
      if (h) h.status = 'open';
      renderHelp();
      toast('Reopened', 'info');
    } else {
      toast(data.message, 'error');
    }
  } catch (e) {
    toast('Network error', 'error');
  }
}

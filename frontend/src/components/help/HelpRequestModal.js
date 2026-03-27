//api call for this one should be ../api/help.php (create action i think)
function openHelpModal() {
  if (!authStore.user) return toast('Sign in first', 'error');

  const openReqs = store.helpReqs.filter(h =>
    h.userEmail === authStore.user.email && h.status === 'open'
  );
  if (openReqs.length >= 5) return toast('Max 5 open requests', 'error');

  document.getElementById('helpModule').innerHTML = modsArray(store.modules)
    .map(m => `<option value="${m.code}">${m.code} — ${m.name}</option>`)
    .join('');
  document.getElementById('helpEmailHandle').value = authStore.user.email.split('@')[0];
  document.getElementById('helpTitle').value   = '';
  document.getElementById('helpDesc').value    = '';
  document.getElementById('helpUrgency').value = 'medium';

  uiStore.helpBountyOn = false;
  document.getElementById('bountyToggle').classList.remove('on');
  document.getElementById('bountyLabel').textContent = 'No bounty';
  document.getElementById('bountyAmountSection').classList.remove('visible');
  document.getElementById('helpErr').classList.remove('visible');

  openModal('helpModal');
}

function toggleBounty() {
  uiStore.helpBountyOn = !uiStore.helpBountyOn;
  document.getElementById('bountyToggle').classList.toggle('on', uiStore.helpBountyOn);
  document.getElementById('bountyLabel').textContent =
    uiStore.helpBountyOn ? 'Bounty enabled' : 'No bounty';
  document.getElementById('bountyAmountSection').classList.toggle('visible', uiStore.helpBountyOn);
}

async function submitHelpRequest() {
  const title  = document.getElementById('helpTitle').value.trim();
  const mod    = document.getElementById('helpModule').value;
  const desc   = document.getElementById('helpDesc').value.trim();
  const urg    = document.getElementById('helpUrgency').value;
  const handle = document.getElementById('helpEmailHandle').value.trim();
  const err    = document.getElementById('helpErr');
  err.classList.remove('visible');

  if (!title)  { err.textContent = 'Title required.';       err.classList.add('visible'); return; }
  if (!desc)   { err.textContent = 'Description required.'; err.classList.add('visible'); return; }
  if (!handle) { err.textContent = 'Email required.';       err.classList.add('visible'); return; }

  try {
    const res  = await fetch('../api/help.php', {
      method:  'POST',
      headers: { 'Content-Type': 'application/json' },
      body:    JSON.stringify({
        action:        'create',
        title, module: mod, desc, urgency: urg,
        contact_email: handle + '@sit.singaporetech.edu.sg',
        has_bounty:    uiStore.helpBountyOn,
        bounty_amount: uiStore.helpBountyOn
          ? +document.getElementById('bountyAmount').value
          : 0,
      }),
    });
    const data = await res.json();

    if (data.success) {
      store.helpReqs.unshift(data.helpReq);
      closeModal('helpModal');
      renderHelp();
      toast('Help request posted!', 'success');
    } else {
      err.textContent = data.message; err.classList.add('visible');
    }
  } catch (e) {
    toast('Network error', 'error');
  }
}

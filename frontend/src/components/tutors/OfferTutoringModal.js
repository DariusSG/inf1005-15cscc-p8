//api call tutors.php w create action

function openOfferTutorModal() {
  if (!authStore.user) return toast('Sign in first', 'error');

  const myListings = store.tutors.filter(t => t.userEmail === authStore.user.email);
  if (myListings.length >= 5) return toast('Max 5 tutor listings', 'error');

  document.getElementById('offerMods').innerHTML = modsArray(store.modules)
    .map(m => `<option value="${m.code}">${m.code} — ${m.name}</option>`)
    .join('');
  document.getElementById('offerEmail').value = authStore.user.email.split('@')[0];
  document.getElementById('offerBio').value   = '';
  document.getElementById('offerErr').classList.remove('visible');
  openModal('tutorOfferModal');
}

async function submitTutorOffer() {
  const sel    = document.getElementById('offerMods');
  const mods   = Array.from(sel.selectedOptions).map(o => o.value).slice(0, 3);
  const rate   = document.getElementById('offerRate').value;
  const bio    = document.getElementById('offerBio').value.trim();
  const handle = document.getElementById('offerEmail').value.trim();
  const err    = document.getElementById('offerErr');
  err.classList.remove('visible');

  if (!mods.length) { err.textContent = 'Select at least one module.'; err.classList.add('visible'); return; }
  if (!bio)         { err.textContent = 'Bio required.';               err.classList.add('visible'); return; }
  if (!handle)      { err.textContent = 'Email required.';             err.classList.add('visible'); return; }

  try {
    const res  = await fetch('../api/tutors.php', {
      method:  'POST',
      headers: { 'Content-Type': 'application/json' },
      body:    JSON.stringify({
        action:       'create',
        modules:      mods,
        rate,
        bio,
        contact_email: handle + '@sit.singaporetech.edu.sg',
      }),
    });
    const data = await res.json();

    if (data.success) {
      store.tutors.unshift(data.tutor);
      closeModal('tutorOfferModal');
      renderTutors();
      toast('Listing published!', 'success');
    } else {
      err.textContent = data.message; err.classList.add('visible');
    }
  } catch (e) {
    toast('Network error', 'error');
  }
}

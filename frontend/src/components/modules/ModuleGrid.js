//api under modules.php
//fetch module
async function loadModules() {
  try {
    const res  = await fetch('../api/modules.php');
    const data = await res.json();

    if (data.success) {
      // rebuild store.modules keyed by code, preserving reviews array
      store.modules = {};
      data.modules.forEach(m => {
        store.modules[m.code] = { ...m, reviews: m.reviews || [] };
      });
    }
  } catch (e) {
    toast('Could not load modules', 'error');
  }
}

function filterModules() {
  const q = (document.getElementById('modSearch')?.value || '').toLowerCase();
  const mods = modsArray(store.modules).filter(m =>
    (uiStore.faculty === 'All' || m.faculty === uiStore.faculty) &&
    (!q || m.code.toLowerCase().includes(q) ||
           m.name.toLowerCase().includes(q) ||
           m.desc.toLowerCase().includes(q))
  );
  renderModuleGrid(mods);
}

function renderModuleGrid(mods) {
  const g = document.getElementById('modGrid');
  if (!mods.length) {
    g.innerHTML = '<div class="empty" style="grid-column:1/-1"><div class="e-icon">📭</div><h3>No modules found</h3></div>';
    return;
  }
  g.innerHTML = mods.map((m, i) => renderModuleCard(m, i)).join('');
}

function setFaculty(f, btn) {
  uiStore.faculty = f;
  document.querySelectorAll('.pill').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  filterModules();
}

function updateHomeStats() {
  const mods = modsArray(store.modules);
  const totalReviews = mods.reduce((s, m) => s + m.reviews.length, 0);
  const allRatings   = mods.flatMap(m => m.reviews.map(r => r.rating));
  const avg = allRatings.length
    ? (allRatings.reduce((s, r) => s + r, 0) / allRatings.length).toFixed(1)
    : '—';

  document.getElementById('hcMod').textContent = mods.length;
  document.getElementById('hcTut').textContent = store.tutors.length;
  document.getElementById('hcHlp').textContent = store.helpReqs.filter(h => h.status === 'open').length;
  document.getElementById('wsM').textContent   = mods.length;
  document.getElementById('wsR').textContent   = totalReviews;
  document.getElementById('wsA').textContent   = avg;
}

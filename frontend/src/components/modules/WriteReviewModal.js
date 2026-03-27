//api call under reviews.php with create and update
function openWriteReview(code) {
  if (!authStore.user) return toast('Sign in first', 'error');
  uiStore.reviewMod    = code;
  uiStore.editingId    = null;
  uiStore.reviewRating = 0;

  document.getElementById('writeModalTitle').textContent = 'Write a Review';
  document.getElementById('writeModLabel').textContent   =
    store.modules[code].code + ' — ' + store.modules[code].name;
  document.getElementById('wTitle').value   = '';
  document.getElementById('wContent').value = '';
  ['wkSlider','dfSlider','usSlider'].forEach(id => document.getElementById(id).value = 5);
  ['wkVal','dfVal','usVal'].forEach(id => document.getElementById(id).textContent = '5');
  resetStars();

  closeModal('detailModal');
  openModal('writeModal');
}

function editReview(mc, rid) {
  const r = store.modules[mc].reviews.find(x => x.id === rid);
  if (!r) return;
  uiStore.reviewMod    = mc;
  uiStore.editingId    = rid;
  uiStore.reviewRating = r.rating;

  document.getElementById('writeModalTitle').textContent = 'Edit Review';
  document.getElementById('writeModLabel').textContent   = mc + ' — ' + store.modules[mc].name;
  document.getElementById('wTitle').value   = r.title;
  document.getElementById('wContent').value = r.content;
  document.getElementById('wkSlider').value = r.workload;   document.getElementById('wkVal').textContent = r.workload;
  document.getElementById('dfSlider').value = r.difficulty; document.getElementById('dfVal').textContent = r.difficulty;
  document.getElementById('usSlider').value = r.usefulness; document.getElementById('usVal').textContent = r.usefulness;
  pickStar(r.rating);

  closeModal('detailModal');
  openModal('writeModal');
}

function pickStar(v) {
  uiStore.reviewRating = v;
  document.querySelectorAll('#starPicker .star').forEach(s =>
    s.classList.toggle('filled', +s.dataset.v <= v)
  );
}
function hoverStar(v) {
  document.querySelectorAll('#starPicker .star').forEach(s =>
    s.classList.toggle('filled', +s.dataset.v <= v)
  );
}
function unhoverStar() { pickStar(uiStore.reviewRating); }
function resetStars() {
  uiStore.reviewRating = 0;
  document.querySelectorAll('#starPicker .star').forEach(s => s.classList.remove('filled'));
}

// ── Submit (create or update) ────────────────────────────────────
async function submitReview() {
  const title   = document.getElementById('wTitle').value.trim();
  const content = document.getElementById('wContent').value.trim();
  if (!uiStore.reviewRating) return toast('Select a rating', 'error');
  if (!title)                return toast('Enter a title', 'error');
  if (!content)              return toast('Write your review', 'error');

  const payload = {
    action:      uiStore.editingId ? 'update' : 'create',
    id:          uiStore.editingId,
    module_code: uiStore.reviewMod,
    rating:      uiStore.reviewRating,
    title, content,
    workload:    +document.getElementById('wkSlider').value,
    difficulty:  +document.getElementById('dfSlider').value,
    usefulness:  +document.getElementById('usSlider').value,
  };

  try {
    const res  = await fetch('../api/reviews.php', {
      method:  'POST',
      headers: { 'Content-Type': 'application/json' },
      body:    JSON.stringify(payload),
    });
    const data = await res.json();

    if (data.success) {
      const m = store.modules[uiStore.reviewMod];
      if (uiStore.editingId) {
        // Replace updated review in local store
        const idx = m.reviews.findIndex(x => x.id === uiStore.editingId);
        if (idx !== -1) m.reviews[idx] = { ...m.reviews[idx], ...data.review };
        toast('Updated!', 'success');
      } else {
        // PHP returns the new review with its real DB id
        m.reviews.unshift(data.review);
        toast('Review submitted!', 'success');
      }
      closeModal('writeModal');
      openDetail(uiStore.reviewMod);
    } else {
      toast(data.message, 'error');
    }
  } catch (e) {
    toast('Network error', 'error');
  }
}

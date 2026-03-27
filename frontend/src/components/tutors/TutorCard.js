//call tutors.php
function renderTutorCard(t) {
  const ini     = userInitials(t.name);
  const modTags = t.modules
    .map(m => `<span class="tag-prereq" style="font-size:.72rem">${m}</span>`)
    .join(' ');
  return `
    <div class="tutor-card">
      <div class="avatar avatar-lg avatar-green">${ini}</div>
      <div class="tutor-info">
        <div class="tname">${t.name}</div>
        <div class="tmods">Teaches: ${modTags}</div>
        <div class="tbio">${t.bio}</div>
        <span class="trate">${t.rate}</span>
      </div>
      <div style="align-self:center">
        <button class="btn btn-secondary btn-sm" onclick="openContactModal(${t.id})">View Info</button>
      </div>
    </div>`;
}

async function loadTutors() {
  try {
    const res  = await fetch('../api/tutors.php');
    const data = await res.json();
    if (data.success) store.tutors = data.tutors;
  } catch (e) {
    toast('Could not load tutors', 'error');
  }
}

function renderTutors(list) {
  const arr = list || store.tutors;
  document.getElementById('tutorList').innerHTML =
    arr.length
      ? arr.map(t => renderTutorCard(t)).join('')
      : '<div class="empty"><div class="e-icon">🎓</div><h3>No tutors found</h3></div>';
}

function filterTutors() {
  const q        = document.getElementById('tutorSearch').value.toLowerCase();
  const filtered = store.tutors.filter(t =>
    t.name.toLowerCase().includes(q) ||
    t.modules.some(m => m.toLowerCase().includes(q))
  );
  renderTutors(filtered);
}

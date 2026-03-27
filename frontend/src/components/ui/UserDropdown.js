let userMenuOpen = false;

function toggleUserMenu() {
  userMenuOpen = !userMenuOpen;
  const dd = document.getElementById('userDropdown');
  const tr = document.getElementById('userMenuTrigger');
  if (dd) dd.classList.toggle('visible', userMenuOpen);
  if (tr) tr.classList.toggle('open', userMenuOpen);
}

function closeUserMenu() {
  userMenuOpen = false;
  const dd = document.getElementById('userDropdown');
  const tr = document.getElementById('userMenuTrigger');
  if (dd) dd.classList.remove('visible');
  if (tr) tr.classList.remove('open');
}

// Close when clicking outside
document.addEventListener('click', e => {
  if (userMenuOpen && !e.target.closest('.user-menu-wrap')) closeUserMenu();
});

function updateAuth() {
  const el   = document.getElementById('topbarAuth');
  const adm  = document.getElementById('adminSideLink');
  const dash = document.getElementById('dashSideLink');
  const u    = authStore.user;

  if (u) {
    const ini = userInitials(u.name);
    el.innerHTML = `
      <div class="user-menu-wrap">
        <div class="user-menu-trigger" id="userMenuTrigger" onclick="toggleUserMenu()">
          <div class="avatar avatar-accent">${ini}</div>
          <span>${u.name}</span>
          <span class="chevron">▼</span>
        </div>
        <div class="user-dropdown" id="userDropdown">
          <div class="ud-header">
            <div class="ud-name">${u.name}</div>
            <div class="ud-email">${u.email}</div>
            ${u.role === 'admin' ? '<span class="ud-role">Admin</span>' : ''}
          </div>
          <button class="ud-item" onclick="closeUserMenu();navigate('dashboard')">
            <span class="ud-icon">👤</span> Dashboard
          </button>
          ${u.role === 'admin' ? `
          <button class="ud-item" onclick="closeUserMenu();navigate('admin')">
            <span class="ud-icon">⚙️</span> Admin Panel
          </button>` : ''}
          <div class="ud-divider"></div>
          <button class="ud-item danger" onclick="logout()">
            <span class="ud-icon">🚪</span> Sign Out
          </button>
        </div>
      </div>`;
    adm.style.display  = u.role === 'admin' ? 'flex' : 'none';
    dash.style.display = 'flex';
  } else {
    el.innerHTML = `<button class="btn btn-primary btn-sm" onclick="openAuthModal()">Sign In</button>`;
    adm.style.display  = 'none';
    dash.style.display = 'none';
  }
}

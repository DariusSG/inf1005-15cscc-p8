
async function navigate(page) {
    uiStore.page = page;

    // Toggle welcome page (uses flex, not block)
    document.getElementById('page-welcome').style.display =
        page === 'welcome' ? 'flex' : 'none';

    // Toggle all other page sections
    document.querySelectorAll('.page-section').forEach(s =>
        s.classList.remove('active')
    );
    if (page !== 'welcome') {
        const el = document.getElementById('page-' + page);
        if (el) el.classList.add('active');
    }

    // Update sidebar active state
    document.querySelectorAll('.sidebar-link').forEach(l =>
        l.classList.toggle('active', l.dataset.page === page)
    );

    // Fetch fresh data then render
    if (page === 'modules')   { await loadModules();  filterModules(); }
    if (page === 'tutors')    { await loadTutors();   renderTutors(); }
    if (page === 'help')      { await loadHelp();     renderHelp(); }
    if (page === 'dashboard') renderDashboard();
    if (page === 'admin')     renderAdmin();
    if (page === 'welcome')   updateHomeStats();

    document.getElementById('contentArea').scrollTo({ top: 0, behavior: 'smooth' });
}

function handleGlobalSearch() {
    const q = document.getElementById('globalSearch').value.trim();
    if (q && uiStore.page !== 'modules') navigate('modules');
    document.getElementById('modSearch').value = q;
    filterModules();
}

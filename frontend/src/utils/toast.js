
function toast(msg, type = 'info') {
    const box = document.getElementById('toastBox');
    const t = document.createElement('div');
    t.className = 'toast ' + type;
    const icons = { success: '✓', error: '✕', info: 'ℹ' };
    t.innerHTML = `<span>${icons[type] || 'ℹ'}</span> ${msg}`;
    box.appendChild(t);
    setTimeout(() => {
        t.style.opacity = '0';
        t.style.transform = 'translateX(16px)';
        t.style.transition = '0.25s';
        setTimeout(() => t.remove(), 250);
    }, 3000);
}

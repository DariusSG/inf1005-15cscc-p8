function avgRating(module) {
    if (!module.reviews.length) return 0;
    const sum = module.reviews.reduce((s, r) => s + r.rating, 0);
    return (sum / module.reviews.length).toFixed(1);
}

function avgMetric(module, key) {
    if (!module.reviews.length) return '—';
    const sum = module.reviews.reduce((s, r) => s + r[key], 0);
    return (sum / module.reviews.length).toFixed(1);
}

function starsHtml(rating) {
    return '★'.repeat(rating) + '☆'.repeat(5 - rating);
}

function userInitials(name) {
    return name.split(' ').map(n => n[0]).join('');
}

function shortName(name) {
    const parts = name.split(' ');
    return parts[0] + ' ' + parts[parts.length - 1][0] + '.';
}

function modsArray(modules) {
    return Object.values(modules);
}

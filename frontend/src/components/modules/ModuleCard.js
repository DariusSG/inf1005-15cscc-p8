function renderModuleCard(m, index) {
  const avg = avgRating(m);
  return `
    <div class="mod-card" style="animation:fadeUp .35s ${index * 0.04}s both"
         onclick="openDetail('${m.code}')">
      <span class="fac-tag ${m.faculty}">${m.faculty}</span>
      <div class="code">${m.code}</div>
      <div class="name">${m.name}</div>
      <div class="desc">${m.desc}</div>
      <div class="meta">
        <span class="rating">${avg > 0 ? '★ ' + avg : 'No ratings'}</span>
        <span>${m.reviews.length} review${m.reviews.length !== 1 ? 's' : ''}</span>
        <span class="cu">${m.credits} CU</span>
      </div>
    </div>`;
}

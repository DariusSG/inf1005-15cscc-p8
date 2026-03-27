function openHelpDetailModal(hid) {
  const h = store.helpReqs.find(x => x.id === hid);
  if (!h) return;
  const m = store.modules[h.module];

  document.getElementById('helpDetailHead').innerHTML = `
    <h3>${h.title}</h3>
    <p style="color:var(--text-secondary);font-size:.88rem">Help request details</p>`;

  document.getElementById('helpDetailBody').innerHTML = `
    <div class="info-row">
      <span class="info-label">Module</span>
      <span class="info-value">${h.module}${m ? ' — ' + m.name : ''}</span>
    </div>
    <div class="info-row">
      <span class="info-label">Urgency</span>
      <span class="info-value"><span class="urgency ${h.urgency}">${h.urgency}</span></span>
    </div>
    <div class="info-row">
      <span class="info-label">Bounty</span>
      <span class="info-value">
        ${h.hasBounty ? `<span class="bounty-tag">💰 $${h.bountyAmount}</span>` : 'None'}
      </span>
    </div>
    <div class="info-row">
      <span class="info-label">Status</span>
      <span class="info-value">
        ${h.status === 'solved'
          ? '<span class="solved-tag">Solved</span>'
          : '<span style="color:var(--blue)">Open</span>'}
      </span>
    </div>
    <div class="info-row">
      <span class="info-label">Responses</span>
      <span class="info-value">${h.responses}</span>
    </div>
    <div class="info-row">
      <span class="info-label">Contact</span>
      <span class="info-value"><a href="mailto:${h.contactEmail}">${h.contactEmail}</a></span>
    </div>
    <div class="info-row" style="border-bottom:none">
      <span class="info-label">Description</span>
    </div>
    <p style="font-size:.88rem;color:var(--text-secondary);padding:4px 0 16px">${h.desc}</p>
    <p style="font-size:.78rem;color:var(--text-muted);text-align:center">
      Reach out via email if you can help.
    </p>`;

  openModal('helpDetailModal');
}

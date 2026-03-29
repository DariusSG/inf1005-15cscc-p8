export function Loading({ text = 'Loading...' }) {
  return (
    <div className="loading">
      <div className="spin" />
      <div>{text}</div>
    </div>
  );
}

export function Empty({ icon = '📭', title = 'Nothing here', sub = '' }) {
  return (
    <div className="empty">
      <div className="e-icon">{icon}</div>
      <h3>{title}</h3>
      {sub && <p style={{ fontSize: '0.82rem' }}>{sub}</p>}
    </div>
  );
}

export function FacTag({ faculty }) {
  return <span className={`fac-tag ${faculty}`}>{faculty}</span>;
}

export function Stars({ rating, size = '0.88rem' }) {
  return (
    <span className="stars" style={{ fontSize: size }}>
      {'★'.repeat(rating)}{'☆'.repeat(5 - rating)}
    </span>
  );
}

export function Avatar({ name = '?', variant = 'accent', size = '' }) {
  const initials = name.split(' ').map((n) => n[0]).join('').slice(0, 2).toUpperCase();
  return (
    <div className={`avatar avatar-${variant} ${size ? 'avatar-' + size : ''}`}>
      {initials}
    </div>
  );
}

export function UrgencyBadge({ urgency }) {
  return <span className={`urgency ${urgency}`}>{urgency}</span>;
}

export function BountyTag({ amount }) {
  return <span className="bounty-tag">💰 ${amount}</span>;
}

export function SolvedTag() {
  return <span className="solved-tag">✓ Solved</span>;
}

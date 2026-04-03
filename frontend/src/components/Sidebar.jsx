import { NavLink } from 'react-router';
import { useAuth } from '../context/AuthContext';

const links = [
  { to: '/', label: 'Home', icon: '🏠', end: true },
  { to: '/modules', label: 'Modules', icon: '📚' },
  { to: '/tutors', label: 'Tutor Finder', icon: '🎓' },
  { to: '/study-groups', label: 'Study Groups', icon: '👥' },
  { to: '/help', label: 'Help Finder', icon: '🙋' },
];

export default function Sidebar() {
  const { user, isAdmin } = useAuth();

  return (
    <aside className="sidebar">
      <NavLink to="/" className="sidebar-brand" aria-label="SITMods home">
        <span>SIT
            <em>Mods</em>
        </span>
      </NavLink>
      <nav className="sidebar-nav">
        {links.map(({ to, label, icon, end }) => (
          <NavLink
            key={to}
            to={to}
            end={end}
            className={({ isActive }) => `sidebar-link${isActive ? ' active' : ''}`}
          >
            <span className="icon">{icon}</span>
            <span className="lbl">{label}</span>
          </NavLink>
        ))}
        {user && (
          <>
            <div className="sidebar-divider" />
            <NavLink
              to="/dashboard"
              className={({ isActive }) => `sidebar-link${isActive ? ' active' : ''}`}
            >
              <span className="icon">📊</span>
              <span className="lbl">Dashboard</span>
            </NavLink>
            {isAdmin && (
              <NavLink
                to="/admin"
                className={({ isActive }) => `sidebar-link${isActive ? ' active' : ''}`}
              >
                <span className="icon">⚙️</span>
                <span className="lbl">Admin</span>
              </NavLink>
            )}
          </>
        )}
      </nav>
      <div className="sidebar-footer">SITizens v67.0<br />&copy; Darius, Yu Xuan, Ray, Gibson, Kai Le</div>
    </aside>
  );
}

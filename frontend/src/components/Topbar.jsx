import { useState, useRef, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';
import { Avatar } from './Shared';

export default function Topbar({ onSearch, onSignIn }) {
  const { user, isAdmin, logout } = useAuth();
  const [open, setOpen] = useState(false);
  const [search, setSearch] = useState('');
  const wrapRef = useRef(null);
  const navigate = useNavigate();

  useEffect(() => {
    const handler = (e) => {
      if (open && wrapRef.current && !wrapRef.current.contains(e.target)) setOpen(false);
    };
    document.addEventListener('mousedown', handler);
    return () => document.removeEventListener('mousedown', handler);
  }, [open]);

  const handleSearch = (e) => {
    setSearch(e.target.value);
    if (onSearch) onSearch(e.target.value);
  };

  const handleSearchKey = (e) => {
    if (e.key === 'Enter' && search.trim()) {
      navigate(`/modules?q=${encodeURIComponent(search.trim())}`);
    }
  };

  const go = (path) => { setOpen(false); navigate(path); };

  return (
    <header className="topbar">
      <div className="topbar-search">
        <span className="ts-icon">🔍</span>
        <input
          type="text"
          placeholder="Search modules, tutors..."
          value={search}
          onChange={handleSearch}
          onKeyDown={handleSearchKey}
        />
      </div>

      {user ? (
        <div className="user-menu-wrap" ref={wrapRef}>
          <div
            className={`user-menu-trigger${open ? ' open' : ''}`}
            onClick={() => setOpen((o) => !o)}
          >
            <Avatar name={user.name} variant="accent" />
            <span>{user.name}</span>
            <span className="chevron">▼</span>
          </div>
          {open && (
            <div className="user-dropdown visible">
              <div className="ud-header">
                <div className="ud-name">{user.name}</div>
                <div className="ud-email">{user.email}</div>
                {isAdmin && <span className="ud-role">Admin</span>}
              </div>
              <button className="ud-item" onClick={() => go('/dashboard')}>
                <span className="ud-icon">👤</span> Dashboard
              </button>
              {isAdmin && (
                <button className="ud-item" onClick={() => go('/admin')}>
                  <span className="ud-icon">⚙️</span> Admin Panel
                </button>
              )}
              <div className="ud-divider" />
              <button className="ud-item danger" onClick={() => { setOpen(false); logout(); }}>
                <span className="ud-icon">🚪</span> Sign Out
              </button>
            </div>
          )}
        </div>
      ) : (
        <button className="btn btn-primary btn-sm" onClick={onSignIn}>Sign In</button>
      )}
    </header>
  );
}

import { useState, useRef, useEffect } from 'react';
import { useNavigate } from 'react-router';
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
        <span className="ts-icon" aria-hidden="true"></span>
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
          <button
            className={`user-menu-trigger${open ? ' open' : ''}`}
            onClick={() => setOpen((o) => !o)}
            aria-expanded={open}
            aria-haspopup="true"
            aria-label={`User menu for ${user.name}`}
          >
            <Avatar name={user.name} variant="accent" />
            <span>{user.name}</span>
            <span className="chevron" aria-hidden="true"></span>
          </button>
          {open && (
            <div className="user-dropdown visible" role="menu">
              <div className="ud-header">
                <div className="ud-name">{user.name}</div>
                <div className="ud-email">{user.email}</div>
                {isAdmin && <span className="ud-role">Admin</span>}
              </div>
              <button className="ud-item" onClick={() => go('/dashboard')} role="menuitem">
                <span className="ud-icon" aria-hidden="true"></span> Dashboard
              </button>
              {isAdmin && (
                <button className="ud-item" onClick={() => go('/admin')} role="menuitem">
                  <span className="ud-icon" aria-hidden="true"></span> Admin Panel
                </button>
              )}
              <div className="ud-divider" />
              <button className="ud-item danger" onClick={() => { setOpen(false); logout(); }} role="menuitem">
                <span className="ud-icon" aria-hidden="true"></span> Sign Out
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

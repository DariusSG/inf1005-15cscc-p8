import { useEffect } from 'react';

export default function Modal({ onClose, className = '', children }) {
  useEffect(() => {
    document.body.style.overflow = 'hidden';
    const onKey = (e) => { if (e.key === 'Escape') onClose(); };
    window.addEventListener('keydown', onKey);
    return () => {
      document.body.style.overflow = '';
      window.removeEventListener('keydown', onKey);
    };
  }, [onClose]);

  return (
    <div
      className="modal-overlay"
      onMouseDown={(e) => { if (e.target === e.currentTarget) onClose(); }}
    >
      <div className={`modal ${className}`}>
        <button className="modal-close" onClick={onClose}>✕</button>
        {children}
      </div>
    </div>
  );
}

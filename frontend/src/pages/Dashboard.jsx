import { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { getReviews, getTutors, getHelpRequests, postHelpSolve } from '../api/index';
import { useAuth } from '../context/AuthContext';
import { Loading, Empty, SolvedTag } from '../components/Shared';
import toast from 'react-hot-toast';

export default function Dashboard() {
  const { user } = useAuth();
  const navigate = useNavigate();
  const [myReviews, setMyReviews] = useState([]);
  const [myTutors, setMyTutors] = useState([]);
  const [myHelp, setMyHelp] = useState([]);
  const [loading, setLoading] = useState(true);

  const load = () => {
    if (!user) return;
    setLoading(true);
    Promise.allSettled([
      getReviews({ userId: user.id }),
      getTutors({ userId: user.id }),
      getHelpRequests({ userId: user.id }),
    ]).then(([revRes, tutRes, helpRes]) => {
      setMyReviews(revRes.status === 'fulfilled' ? (revRes.value?.data || revRes.value || []) : []);
      setMyTutors(tutRes.status === 'fulfilled' ? (tutRes.value?.data || tutRes.value || []) : []);
      setMyHelp(helpRes.status === 'fulfilled' ? (helpRes.value?.data || helpRes.value || []) : []);
    }).finally(() => setLoading(false));
  };

  useEffect(() => { load(); }, [user]);

  const handleSolve = async (id) => {
    try { await postHelpSolve(id); load(); toast.success('Marked solved!'); }
    catch { toast.error('Failed'); }
  };

  if (!user) {
    return (
      <div className="page-section">
        <Empty icon="🔒" title="Sign in to view dashboard" />
      </div>
    );
  }

  if (loading) return <div className="page-section"><Loading /></div>;

  const openHelp = myHelp.filter((h) => h.status === 'open');

  return (
    <div className="page-section">
      <div className="section-header">
        <h2>My Dashboard</h2>
        <p className="sub">Manage your reviews, tutoring offerings, and help requests</p>
      </div>

      {/* Reviews */}
      <div className="dash-section">
        <h3>📝 My Reviews <span className="count">{myReviews.length}</span></h3>
        {myReviews.length === 0 ? (
          <p style={{ color: 'var(--text-muted)', fontSize: '0.86rem' }}>No reviews yet.</p>
        ) : myReviews.map((r) => (
          <div className="dash-item" key={r.id}>
            <div className="di-info">
              <div className="di-title">{'★'.repeat(r.rating)} {r.title}</div>
              <div className="di-sub">{r.moduleCode || r.module} · {r.createdAt?.split('T')[0] || r.date}</div>
            </div>
            <div className="di-actions">
              <button
                className="btn btn-secondary btn-sm"
                onClick={() => navigate(`/modules/${r.moduleCode || r.module}`)}
              >
                View
              </button>
            </div>
          </div>
        ))}
      </div>

      {/* Tutor listings */}
      <div className="dash-section">
        <h3>🎓 My Tutor Listings <span className="count">{myTutors.length}/5</span></h3>
        {myTutors.length === 0 ? (
          <p style={{ color: 'var(--text-muted)', fontSize: '0.86rem' }}>No tutor listings.</p>
        ) : myTutors.map((t) => (
          <div className="dash-item" key={t.id}>
            <div className="di-info">
              <div className="di-title">{(t.modules || []).join(', ')}</div>
              <div className="di-sub">{t.rate}</div>
            </div>
            <div className="di-actions">
              <button className="btn btn-secondary btn-sm" onClick={() => navigate('/tutors')}>View</button>
            </div>
          </div>
        ))}
      </div>

      {/* Help requests */}
      <div className="dash-section">
        <h3>🆘 My Help Requests <span className="count">{openHelp.length}/5 open</span></h3>
        {myHelp.length === 0 ? (
          <p style={{ color: 'var(--text-muted)', fontSize: '0.86rem' }}>No help requests.</p>
        ) : myHelp.map((h) => (
          <div className="dash-item" key={h.id}>
            <div className="di-info">
              <div className="di-title">
                {h.title}{' '}
                {h.status === 'solved' && <SolvedTag />}
              </div>
              <div className="di-sub">
                {h.module || h.moduleCode} · {h.urgency}
                {h.hasBounty ? ` · $${h.bountyAmount}` : ''}
              </div>
            </div>
            <div className="di-actions">
              {h.status === 'open' && (
                <button className="btn btn-success btn-sm" onClick={() => handleSolve(h.id)}>
                  ✓ Solve
                </button>
              )}
            </div>
          </div>
        ))}
      </div>
    </div>
  );
}

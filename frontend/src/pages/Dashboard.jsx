import { useState, useEffect } from 'react';
import { useNavigate } from 'react-router';
import { getReviews, getTutors, getHelpRequests, postHelpSolve } from '../api/index';
import { postChangePassword } from '../api/auth';
import { useAuth } from '../context/AuthContext';
import { validatePassword } from '../utils/sanitise';
import { Loading, Empty, SolvedTag } from '../components/Shared';
import toast from 'react-hot-toast';

function ChangePasswordSection() {
    const [currentPw, setCurrentPw] = useState('');
    const [newPw, setNewPw] = useState('');
    const [confirmPw, setConfirmPw] = useState('');
    const [loading, setLoading] = useState(false);
    const [open, setOpen] = useState(false);
    const [error, setError] = useState('');
    const [success, setSuccess] = useState(false);

    const reset = () => {
        setCurrentPw(''); setNewPw(''); setConfirmPw('');
        setError(''); setSuccess(false);
    };

    const handleSubmit = async () => {
        setError(''); setSuccess(false);
        if (!currentPw) { setError('Enter your current password.'); return; }
        const pwErr = validatePassword(newPw);
        if (pwErr) { setError(pwErr); return; }
        if (newPw !== confirmPw) { setError("New passwords don't match."); return; }
        if (currentPw === newPw) { setError('New password must differ from current.'); return; }
        setLoading(true);
        try {
            await postChangePassword(currentPw, newPw);
            setSuccess(true);
            reset();
            toast.success('Password changed!');
            setOpen(false);
        } catch (e) {
            setError(e.response?.data?.message || 'Failed to change password. Check your current password.');
        } finally { setLoading(false); }
    };

    return (
        <div className="dash-section">
            <h2
                style={{ cursor: 'pointer', userSelect: 'none' }}
                onClick={() => { setOpen((o) => !o); reset(); }}
            >
                 Change Password
                <span style={{ marginLeft: 'auto', fontSize: '0.72rem', color: 'var(--text-muted)', fontWeight: 400 }}>
          {open ? '▲ collapse' : '▼ expand'}
        </span>
            </h2>

            {open && (
                <div style={{ maxWidth: 400 }}>
                    <div className="form-group">
                        <label htmlFor="cp-current">Current Password</label>
                        <input
                            id="cp-current"
                            type="password"
                            placeholder="Your current password"
                            value={currentPw}
                            onChange={(e) => setCurrentPw(e.target.value)}
                            autoComplete="current-password"
                            maxLength={128}
                        />
                    </div>
                    <div className="form-group">
                        <label htmlFor="cp-new">New Password</label>
                        <input
                            id="cp-new"
                            type="password"
                            placeholder="Min 6 characters"
                            value={newPw}
                            onChange={(e) => setNewPw(e.target.value)}
                            autoComplete="new-password"
                            maxLength={128}
                        />
                    </div>
                    <div className="form-group">
                        <label htmlFor="cp-confirm">Confirm New Password</label>
                        <input
                            id="cp-confirm"
                            type="password"
                            placeholder="Confirm new password"
                            value={confirmPw}
                            onChange={(e) => setConfirmPw(e.target.value)}
                            autoComplete="new-password"
                            maxLength={128}
                        />
                    </div>
                    {error && (
                        <div className="form-error" style={{ marginBottom: 10 }}>{error}</div>
                    )}
                    <div style={{ display: 'flex', gap: 8 }}>
                        <button
                            className="btn btn-primary btn-sm"
                            onClick={handleSubmit}
                            disabled={loading}
                        >
                            {loading ? 'Saving...' : 'Update Password'}
                        </button>
                        <button
                            className="btn btn-ghost btn-sm"
                            onClick={() => { setOpen(false); reset(); }}
                        >
                            Cancel
                        </button>
                    </div>
                </div>
            )}
        </div>
    );
}

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
            getReviews({ user_id: user.id }),
            getTutors({ user_id: user.id }),
            getHelpRequests({ user_id: user.id }),
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
                <Empty icon="" title="Sign in to view dashboard" />
            </div>
        );
    }

    if (loading) return <div className="page-section"><Loading /></div>;

    const openHelp = myHelp.filter((h) => h.status === 'open');

    return (
        <div className="page-section">
            <div className="section-header">
                <h1>My Dashboard</h1>
                <p className="sub">Manage your reviews, tutoring offerings, and help requests</p>
            </div>

            {/* Reviews */}
            <div className="dash-section">
                <h2> My Reviews <span className="count">{myReviews.length}</span></h2>
                {myReviews.length === 0 ? (
                    <p style={{ color: 'var(--text-muted)', fontSize: '0.86rem' }}>No reviews yet.</p>
                ) : myReviews.map((r) => (
                    <div className="dash-item" key={r.id}>
                        <div className="di-info">
                            <div className="di-title">{'★'.repeat(r.rating)} {r.title}</div>
                            <div className="di-sub">{r.module_code} · {r.date?.split('T')[0]}</div>
                        </div>
                        <div className="di-actions">
                            <button className="btn btn-secondary btn-sm" onClick={() => navigate(`/modules/${r.module_code}`)}>
                                View
                            </button>
                        </div>
                    </div>
                ))}
            </div>

            {/* Tutor listings */}
            <div className="dash-section">
                <h2>My Tutor Listings <span className="count">{myTutors.length}/5</span></h2>
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
                <h2> My Help Requests <span className="count">{openHelp.length}/5 open</span></h2>
                {myHelp.length === 0 ? (
                    <p style={{ color: 'var(--text-muted)', fontSize: '0.86rem' }}>No help requests.</p>
                ) : myHelp.map((h) => (
                    <div className="dash-item" key={h.id}>
                        <div className="di-info">
                            <div className="di-title">
                                {h.title}{' '}{h.status === 'solved' && <SolvedTag />}
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

            {/* Change Password — always last */}
            <ChangePasswordSection />
        </div>
    );
}
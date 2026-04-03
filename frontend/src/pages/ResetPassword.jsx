import { useState, useEffect } from 'react';
import { useSearchParams, useNavigate } from 'react-router';
import { getPasswordVerify, postResetPassword } from '../api/auth';
import { validatePassword } from '../utils/sanitise';
import toast from 'react-hot-toast';

export default function ResetPassword() {
    const [searchParams] = useSearchParams();
    const navigate = useNavigate();
    const token = searchParams.get('token');

    const [pageState, setPageState] = useState('verifying');
    const [newPw, setNewPw] = useState('');
    const [confirmPw, setConfirmPw] = useState('');
    const [error, setError] = useState('');
    const [loading, setLoading] = useState(false);

    useEffect(() => {
        if (!token) {
            setPageState('invalid');
            return;
        }
        getPasswordVerify(token)
            .then(() => setPageState('ready'))
            .catch(() => setPageState('invalid'));
    }, [token]);

    const handleSubmit = async () => {
        setError('');
        const pwErr = validatePassword(newPw);
        if (pwErr) { setError(pwErr); return; }
        if (newPw !== confirmPw) { setError("Passwords don't match."); return; }
        setLoading(true);
        try {
            await postResetPassword(token, newPw);
            setPageState('success');
            toast.success('Password reset! You can now sign in.');
        } catch (e) {
            setError(e.response?.data?.message || 'Failed to reset password. The link may have expired.');
        } finally { setLoading(false); }
    };

    return (
        <div style={{
            minHeight: '100vh',
            background: 'var(--bg-primary)',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
            padding: '24px',
            fontFamily: 'var(--font)',
            position: 'relative',
            overflow: 'hidden',
        }}>
            <div style={{
                position: 'absolute',
                top: '-200px',
                left: '50%',
                transform: 'translateX(-50%)',
                width: '700px',
                height: '700px',
                background: 'radial-gradient(circle, var(--accent-glow) 0%, transparent 65%)',
                pointerEvents: 'none',
                opacity: 0.4,
            }} />

            <div style={{
                background: 'var(--bg-sidebar)',
                border: '1px solid var(--border)',
                borderRadius: 'var(--radius-lg)',
                width: '100%',
                maxWidth: 400,
                position: 'relative',
                zIndex: 1,
                animation: 'modalIn 0.25s ease forwards',
            }}>
                {/* Brand header */}
                <div style={{
                    padding: '22px 24px 16px',
                    borderBottom: '1px solid var(--border)',
                    display: 'flex',
                    alignItems: 'center',
                    gap: 10,
                }}>
                    <div style={{
                        width: 32, height: 32, borderRadius: 8,
                        background: 'var(--accent)',
                        display: 'flex', alignItems: 'center', justifyContent: 'center',
                        fontFamily: 'var(--mono)', fontSize: '0.7rem', fontWeight: 700, color: '#fff',
                        flexShrink: 0,
                    }}>SM</div>
                    <span style={{ fontWeight: 700, fontSize: '1.05rem' }}>
            SIT<em style={{ fontStyle: 'normal', color: 'var(--accent)' }}>Mods</em>
          </span>
                </div>

                <div style={{ padding: '24px' }}>

                    {/* Verifying */}
                    {pageState === 'verifying' && (
                        <div style={{ textAlign: 'center', padding: '16px 0' }}>
                            <div style={{
                                display: 'inline-block', width: 32, height: 32,
                                border: '3px solid var(--border)', borderTopColor: 'var(--accent)',
                                borderRadius: '50%', animation: 'spin 0.7s linear infinite',
                                marginBottom: 14,
                            }} />
                            <p style={{ color: 'var(--text-secondary)', fontSize: '0.88rem' }}>
                                Verifying your reset link...
                            </p>
                        </div>
                    )}

                    {/* Invalid token */}
                    {pageState === 'invalid' && (
                        <div style={{ textAlign: 'center' }}>
                            <div style={{ fontSize: '2.2rem', marginBottom: 12 }}>⛔</div>
                            <h3 style={{ marginBottom: 8, fontSize: '1.1rem' }}>Link invalid or expired</h3>
                            <p style={{
                                color: 'var(--text-secondary)', fontSize: '0.86rem',
                                lineHeight: 1.6, marginBottom: 24,
                            }}>
                                This password reset link has expired or already been used.
                                Request a new one from the Sign In page.
                            </p>
                            <button
                                className="btn btn-primary"
                                style={{ width: '100%', justifyContent: 'center' }}
                                onClick={() => navigate('/')}
                            >
                                Back to Sign In
                            </button>
                        </div>
                    )}

                    {/* Ready — password form */}
                    {pageState === 'ready' && (
                        <>
                            <h3 style={{ marginBottom: 4, fontSize: '1.1rem' }}>Set a new password</h3>
                            <p style={{
                                color: 'var(--text-secondary)', fontSize: '0.86rem',
                                lineHeight: 1.5, marginBottom: 20,
                            }}>
                                Choose a strong password for your SIT account.
                            </p>
                            <div className="form-group">
                                <label htmlFor="rp-new">New Password</label>
                                <input
                                    id="rp-new"
                                    type="password"
                                    placeholder="Min 8 characters"
                                    value={newPw}
                                    onChange={(e) => setNewPw(e.target.value)}
                                    onKeyDown={(e) => e.key === 'Enter' && handleSubmit()}
                                    autoComplete="new-password"
                                    maxLength={128}
                                    autoFocus
                                />
                            </div>
                            <div className="form-group">
                                <label htmlFor="rp-confirm">Confirm New Password</label>
                                <input
                                    id="rp-confirm"
                                    type="password"
                                    placeholder="Confirm new password"
                                    value={confirmPw}
                                    onChange={(e) => setConfirmPw(e.target.value)}
                                    onKeyDown={(e) => e.key === 'Enter' && handleSubmit()}
                                    autoComplete="new-password"
                                    maxLength={128}
                                />
                            </div>
                            {error && (
                                <div className="form-error" style={{ marginBottom: 12 }}>{error}</div>
                            )}
                            <button
                                className="btn btn-primary"
                                style={{ width: '100%', justifyContent: 'center', marginTop: 6 }}
                                onClick={handleSubmit}
                                disabled={loading}
                            >
                                {loading ? 'Saving...' : 'Reset Password'}
                            </button>
                        </>
                    )}

                    {/* Success */}
                    {pageState === 'success' && (
                        <div style={{ textAlign: 'center' }}>
                            <div style={{ fontSize: '2.2rem', marginBottom: 12 }}>✅</div>
                            <h3 style={{ marginBottom: 8, fontSize: '1.1rem' }}>Password reset!</h3>
                            <p style={{
                                color: 'var(--text-secondary)', fontSize: '0.86rem',
                                lineHeight: 1.6, marginBottom: 24,
                            }}>
                                Your password has been updated. You can now sign in with your new password.
                            </p>
                            <button
                                className="btn btn-primary"
                                style={{ width: '100%', justifyContent: 'center' }}
                                onClick={() => navigate('/')}
                            >
                                Go to Sign In
                            </button>
                        </div>
                    )}

                </div>
            </div>
        </div>
    );
}
import { useState } from 'react';
import Modal from './Modal';
import { useAuth } from '../context/AuthContext';
import { sanitiseEmail, validatePassword } from '../utils/sanitise';
import { postForgotPassword } from '../api/auth';
import toast from 'react-hot-toast';

// ─── Forgot Password sub-view ───────────────────────────────────────────────
function ForgotPasswordView({ onBack }) {
    const [email, setEmail] = useState('');
    const [sent, setSent] = useState(false);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState('');

    const handleSubmit = async () => {
        setError('');
        const cleanEmail = sanitiseEmail(email);
        if (!cleanEmail) { setError('Enter a valid SIT email.'); return; }
        setLoading(true);
        try {
            await postForgotPassword(cleanEmail);
            setSent(true);
        } catch (e) {
            setError(e.response?.data?.message || 'Failed to send reset email.');
        } finally { setLoading(false); }
    };

    if (sent) {
        return (
            <div style={{ textAlign: 'center', padding: '32px 22px' }}>
                <div style={{ fontSize: '2.2rem', marginBottom: 12 }}></div>
                <h3 style={{ marginBottom: 8 }}>Check your email</h3>
                <p style={{ color: 'var(--text-secondary)', fontSize: '0.88rem', lineHeight: 1.6, marginBottom: 20 }}>
                    If <strong>{email}</strong> is registered, a password reset link has been sent. Check your inbox.
                </p>
                <button className="btn btn-secondary" style={{ width: '100%', justifyContent: 'center' }} onClick={onBack}>
                    ← Back to Sign In
                </button>
            </div>
        );
    }

    return (
        <div style={{ padding: '20px 22px' }}>
            <button
                onClick={onBack}
                style={{ background: 'none', border: 'none', color: 'var(--text-muted)', fontSize: '0.82rem', cursor: 'pointer', marginBottom: 16, padding: 0, display: 'flex', alignItems: 'center', gap: 5 }}
            >
                ← Back to Sign In
            </button>
            <h3 style={{ marginBottom: 6 }}>Forgot Password</h3>
            <p style={{ color: 'var(--text-secondary)', fontSize: '0.86rem', marginBottom: 18, lineHeight: 1.5 }}>
                Enter your SIT email and we'll send you a reset link.
            </p>
            <div className="form-group">
                <label htmlFor="fp-email">SIT Email</label>
                <input
                    id="fp-email"
                    type="email"
                    placeholder="student@sit.singaporetech.edu.sg"
                    value={email}
                    onChange={(e) => setEmail(e.target.value)}
                    onKeyDown={(e) => e.key === 'Enter' && handleSubmit()}
                    autoComplete="email"
                    maxLength={254}
                />
            </div>
            {error && <div className="form-error" style={{ marginBottom: 10 }}>{error}</div>}
            <button
                className="btn btn-primary"
                style={{ width: '100%', justifyContent: 'center', marginTop: 6 }}
                onClick={handleSubmit}
                disabled={loading}
            >
                {loading ? 'Sending...' : 'Send Reset Link'}
            </button>
        </div>
    );
}

// ─── Main AuthModal ──────────────────────────────────────────────────────────
export default function AuthModal({ onClose }) {
    const { login, requestRegister } = useAuth();

    // 'login' | 'register' | 'forgot'
    const [tab, setTab] = useState('login');
    // 'form' | 'otp'
    const [step, setStep] = useState('form');
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState('');

    const [loginEmail, setLoginEmail] = useState('');
    const [loginPw, setLoginPw] = useState('');

    const [regEmail, setRegEmail] = useState('');
    const [inviteSent, setInviteSent] = useState(false);

    const clearError = () => setError('');

    const switchTab = (t) => { setTab(t); setStep('form'); clearError(); };

    const handleLogin = async () => {
        clearError();
        const cleanEmail = sanitiseEmail(loginEmail);
        if (!cleanEmail) { setError('Only SIT emails allowed (@sit.singaporetech.edu.sg).'); return; }
        const pwErr = validatePassword(loginPw);
        if (pwErr) { setError(pwErr); return; }
        setLoading(true);
        try {
            const u = await login(cleanEmail, loginPw);
            toast.success(`Welcome, ${u.name}!`);
            onClose();
        } catch (e) {
            setError(e.response?.data?.message || 'Invalid credentials.');
        } finally { setLoading(false); }
    };

    const handleRegister = async () => {
        clearError();
        const cleanEmail = sanitiseEmail(regEmail);
        if (!cleanEmail) { setError('Only SIT emails allowed (@sit.singaporetech.edu.sg).'); return; }
        setLoading(true);
        try {
            await requestRegister(cleanEmail);
            setInviteSent(true);
        } catch (e) {
            setError(e.response?.data?.message || 'Registration failed.');
        } finally { setLoading(false); }
    };

    // ── Forgot Password step ──────────────────────────────────────────────────
    if (tab === 'forgot') {
        return (
            <Modal onClose={onClose} className="modal-sm">
                <ForgotPasswordView onBack={() => switchTab('login')} />
            </Modal>
        );
    }

    // ── Login / Register tabs ─────────────────────────────────────────────────
    return (
        <Modal onClose={onClose} className="modal-sm">
            <div className="auth-tabs">
                <button className={`auth-tab${tab === 'login' ? ' active' : ''}`} onClick={() => switchTab('login')}>
                    Sign In
                </button>
                <button className={`auth-tab${tab === 'register' ? ' active' : ''}`} onClick={() => switchTab('register')}>
                    Register
                </button>
            </div>

            {tab === 'login' ? (
                <div style={{ padding: '20px 22px' }}>
                    <div className="demo-box">
                        <strong>Demo:</strong> demo@sit.singaporetech.edu.sg / demo123<br />
                        <strong>Admin:</strong> admin@sit.singaporetech.edu.sg / admin123
                    </div>
                    <div className="form-group">
                        <label htmlFor="login-email">SIT Email</label>
                        <input
                            id="login-email"
                            type="email"
                            placeholder="student@sit.singaporetech.edu.sg"
                            value={loginEmail}
                            onChange={(e) => setLoginEmail(e.target.value)}
                            onKeyDown={(e) => e.key === 'Enter' && handleLogin()}
                            autoComplete="email"
                            maxLength={254}
                        />
                    </div>
                    <div className="form-group">
                        <label htmlFor="login-pw">Password</label>
                        <input
                            id="login-pw"
                            type="password"
                            placeholder="Enter password"
                            value={loginPw}
                            onChange={(e) => setLoginPw(e.target.value)}
                            onKeyDown={(e) => e.key === 'Enter' && handleLogin()}
                            autoComplete="current-password"
                            maxLength={128}
                        />
                    </div>
                    {error && <div className="form-error">{error}</div>}
                    <button className="btn btn-primary" style={{ width: '100%', justifyContent: 'center', marginTop: 6 }} onClick={handleLogin} disabled={loading}>
                        {loading ? 'Signing in...' : 'Sign In'}
                    </button>
                    {/* Forgot password link */}
                    <p style={{ textAlign: 'center', marginTop: 14, fontSize: '0.8rem', color: 'var(--text-muted)' }}>
                        <a
                            href="#"
                            style={{ color: 'var(--accent)' }}
                            onClick={(e) => { e.preventDefault(); switchTab('forgot'); }}
                        >
                            Forgot your password?
                        </a>
                    </p>
                </div>
            ) : inviteSent ? (
                <div style={{ textAlign: 'center', padding: '32px 22px' }}>
                    <div style={{ fontSize: '2.2rem', marginBottom: 12 }}></div>
                    <h3 style={{ marginBottom: 8 }}>Check your email</h3>
                    <p style={{ color: 'var(--text-secondary)', fontSize: '0.88rem', lineHeight: 1.6, marginBottom: 20 }}>
                        A registration link has been sent to <strong>{regEmail}</strong>. Click the link to set up your account.
                    </p>
                    <button className="btn btn-secondary" style={{ width: '100%', justifyContent: 'center' }} onClick={() => switchTab('login')}>
                        Back to Sign In
                    </button>
                </div>
            ) : (
                <div style={{ padding: '20px 22px' }}>
                    <p style={{ color: 'var(--text-secondary)', fontSize: '0.86rem', marginBottom: 18, lineHeight: 1.5 }}>
                        Enter your SIT email to receive a registration invite link.
                    </p>
                    <div className="form-group">
                        <label htmlFor="reg-email">SIT Email</label>
                        <input id="reg-email" type="email" placeholder="student@sit.singaporetech.edu.sg" value={regEmail} onChange={(e) => setRegEmail(e.target.value)} onKeyDown={(e) => e.key === 'Enter' && handleRegister()} autoComplete="email" maxLength={254} />
                        <div className="form-hint">Only @sit.singaporetech.edu.sg emails</div>
                    </div>
                    {error && <div className="form-error">{error}</div>}
                    <button className="btn btn-primary" style={{ width: '100%', justifyContent: 'center', marginTop: 6 }} onClick={handleRegister} disabled={loading}>
                        {loading ? 'Sending...' : 'Send Registration Link'}
                    </button>
                </div>
            )}
        </Modal>
    );
}
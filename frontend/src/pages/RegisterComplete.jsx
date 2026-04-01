import { useState, useEffect } from 'react';
import { useSearchParams, useNavigate } from 'react-router';
import { getRegisterVerify } from '../api/auth';
import { useAuth } from '../context/AuthContext';
import { validatePassword } from '../utils/sanitise';
import toast from 'react-hot-toast';

export default function RegisterComplete() {
    const [searchParams] = useSearchParams();
    const navigate = useNavigate();
    const { completeRegister } = useAuth();

    const token = searchParams.get('token') || '';

    const [maskedEmail, setMaskedEmail] = useState('');
    const [tokenValid, setTokenValid] = useState(null); // null=checking, true, false

    const [name, setName] = useState('');
    const [password, setPassword] = useState('');
    const [confirm, setConfirm] = useState('');
    const [error, setError] = useState('');
    const [loading, setLoading] = useState(false);

    useEffect(() => {
        if (!token) { setTokenValid(false); return; }
        getRegisterVerify(token)
            .then((data) => { setMaskedEmail(data.email || ''); setTokenValid(true); })
            .catch(() => setTokenValid(false));
    }, [token]);

    const handleSubmit = async () => {
        setError('');
        if (!name.trim()) { setError('Name is required.'); return; }
        const pwErr = validatePassword(password);
        if (pwErr) { setError(pwErr); return; }
        if (password !== confirm) { setError("Passwords don't match."); return; }
        setLoading(true);
        try {
            const u = await completeRegister(token, name.trim(), password);
            if (u) {
                toast.success(`Welcome, ${u.name}!`);
                navigate('/');
            }
        } catch (e) {
            setError(e.response?.data?.message || 'Registration failed. The link may have expired.');
        } finally { setLoading(false); }
    };

    if (tokenValid === null) {
        return (
            <div style={{ minHeight: '100vh', display: 'flex', alignItems: 'center', justifyContent: 'center', background: 'var(--bg-base)' }}>
                <p style={{ color: 'var(--text-secondary)' }}>Verifying link...</p>
            </div>
        );
    }

    if (!tokenValid) {
        return (
            <div style={{ minHeight: '100vh', display: 'flex', alignItems: 'center', justifyContent: 'center', background: 'var(--bg-base)' }}>
                <div style={{ textAlign: 'center', maxWidth: 380, padding: '0 24px' }}>
                    <div style={{ fontSize: '2.5rem', marginBottom: 16 }}>❌</div>
                    <h2 style={{ marginBottom: 8, color: 'var(--text-primary)' }}>Invalid or expired link</h2>
                    <p style={{ color: 'var(--text-secondary)', fontSize: '0.9rem', marginBottom: 24 }}>
                        This registration link is invalid or has expired. Please request a new one.
                    </p>
                    <button className="btn btn-primary" onClick={() => navigate('/')}>Go to Home</button>
                </div>
            </div>
        );
    }

    return (
        <div style={{ minHeight: '100vh', display: 'flex', alignItems: 'center', justifyContent: 'center', background: 'var(--bg-base)' }}>
            <div style={{ width: '100%', maxWidth: 400, padding: '32px 24px', background: 'var(--bg-surface)', borderRadius: 'var(--radius)', border: '1px solid var(--border)' }}>
                <h2 style={{ marginBottom: 4, fontSize: '1.3rem' }}>Complete Registration</h2>
                <p style={{ color: 'var(--text-secondary)', fontSize: '0.86rem', marginBottom: 24 }}>
                    Registering as <strong>{maskedEmail}</strong>
                </p>
                <div className="form-group">
                    <label htmlFor="rc-name">Full Name</label>
                    <input
                        id="rc-name"
                        type="text"
                        placeholder="Your full name"
                        value={name}
                        onChange={(e) => setName(e.target.value)}
                        autoComplete="name"
                        maxLength={100}
                    />
                </div>
                <div className="form-group">
                    <label htmlFor="rc-pw">Password</label>
                    <input
                        id="rc-pw"
                        type="password"
                        placeholder="Min 6 characters"
                        value={password}
                        onChange={(e) => setPassword(e.target.value)}
                        autoComplete="new-password"
                        maxLength={128}
                    />
                </div>
                <div className="form-group">
                    <label htmlFor="rc-confirm">Confirm Password</label>
                    <input
                        id="rc-confirm"
                        type="password"
                        placeholder="Confirm password"
                        value={confirm}
                        onChange={(e) => setConfirm(e.target.value)}
                        onKeyDown={(e) => e.key === 'Enter' && handleSubmit()}
                        autoComplete="new-password"
                        maxLength={128}
                    />
                </div>
                {error && <div className="form-error" style={{ marginBottom: 10 }}>{error}</div>}
                <button
                    className="btn btn-primary"
                    style={{ width: '100%', justifyContent: 'center', marginTop: 6 }}
                    onClick={handleSubmit}
                    disabled={loading}
                >
                    {loading ? 'Creating account...' : 'Create Account'}
                </button>
            </div>
        </div>
    );
}

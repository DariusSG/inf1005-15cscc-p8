import { useState, useRef } from 'react';
import Modal from './Modal';
import { useAuth } from '../context/AuthContext';
import { sanitiseEmail, validatePassword, sanitiseField } from '../utils/sanitise';
import toast from 'react-hot-toast';

export default function AuthModal({ onClose }) {
  const { login, requestRegister, completeRegister } = useAuth();
  const [tab, setTab] = useState('login');
  const [step, setStep] = useState('form'); // 'form' | 'otp'
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  const [loginEmail, setLoginEmail] = useState('');
  const [loginPw, setLoginPw] = useState('');

  const [regName, setRegName] = useState('');
  const [regEmail, setRegEmail] = useState('');
  const [regPw, setRegPw] = useState('');
  const [regCpw, setRegCpw] = useState('');

  const [otpValues, setOtpValues] = useState(['', '', '', '', '', '']);
  const [inviteToken, setInviteToken] = useState('');
  const otpRefs = useRef([]);

  const clearError = () => setError('');

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
    const cleanName = sanitiseField(regName, 'name');
    if (!cleanName) { setError('Name required.'); return; }
    const cleanEmail = sanitiseEmail(regEmail);
    if (!cleanEmail) { setError('Only SIT emails allowed.'); return; }
    const pwErr = validatePassword(regPw);
    if (pwErr) { setError(pwErr); return; }
    if (regPw !== regCpw) { setError("Passwords don't match."); return; }
    setLoading(true);
    try {
      const res = await requestRegister(cleanEmail);
      if (res?.token) setInviteToken(res.token);
      setStep('otp');
      toast.info('Verification email sent!');
    } catch (e) {
      setError(e.response?.data?.message || 'Registration failed.');
    } finally { setLoading(false); }
  };

  const handleOtpChange = (i, val) => {
    if (!/^\d?$/.test(val)) return;
    const next = [...otpValues];
    next[i] = val;
    setOtpValues(next);
    if (val && i < 5) otpRefs.current[i + 1]?.focus();
  };

  const handleOtpKey = (i, e) => {
    if (e.key === 'Backspace' && !otpValues[i] && i > 0) {
      otpRefs.current[i - 1]?.focus();
    }
  };

  const handleVerifyOtp = async () => {
    const code = otpValues.join('');
    if (code.length !== 6) { setError('Enter 6 digits.'); return; }
    setLoading(true);
    try {
      const cleanName = sanitiseField(regName, 'name');
      const u = await completeRegister(inviteToken || code, cleanName, regPw);
      if (u) { toast.success(`Welcome, ${u.name}!`); onClose(); }
    } catch (e) {
      setError(e.response?.data?.message || 'Invalid code.');
    } finally { setLoading(false); }
  };

  return (
    <Modal onClose={onClose} className="modal-sm">
      {step === 'otp' ? (
        <div style={{ textAlign: 'center', padding: '24px 22px' }}>
          <div style={{ fontSize: '2.2rem', marginBottom: 10 }}>📧</div>
          <h3 style={{ marginBottom: 4 }}>Verify Your Email</h3>
          <p style={{ color: 'var(--text-secondary)', fontSize: '0.88rem', marginBottom: 16 }}>
            Code sent to <strong>{sanitiseEmail(regEmail) || regEmail}</strong>
          </p>
          <div className="otp-inputs">
            {otpValues.map((v, i) => (
              <input
                key={i}
                ref={(el) => (otpRefs.current[i] = el)}
                type="text"
                inputMode="numeric"
                maxLength={1}
                value={v}
                onChange={(e) => handleOtpChange(i, e.target.value)}
                onKeyDown={(e) => handleOtpKey(i, e)}
                autoComplete="one-time-code"
              />
            ))}
          </div>
          {error && <div className="form-error" style={{ textAlign: 'center', marginBottom: 10 }}>{error}</div>}
          <button
            className="btn btn-primary"
            style={{ width: '100%', justifyContent: 'center' }}
            onClick={handleVerifyOtp}
            disabled={loading}
          >
            {loading ? 'Verifying...' : 'Verify'}
          </button>
          <p style={{ marginTop: 10, fontSize: '0.8rem', color: 'var(--text-muted)' }}>
            Didn't get it?{' '}
            <a href="#" onClick={(e) => { e.preventDefault(); requestRegister(sanitiseEmail(regEmail) || regEmail); toast.info('Resent!'); }}>
              Resend
            </a>
          </p>
        </div>
      ) : (
        <>
          <div className="auth-tabs">
            <button className={`auth-tab${tab === 'login' ? ' active' : ''}`} onClick={() => { setTab('login'); clearError(); }}>
              Sign In
            </button>
            <button className={`auth-tab${tab === 'register' ? ' active' : ''}`} onClick={() => { setTab('register'); clearError(); }}>
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
              <button
                className="btn btn-primary"
                style={{ width: '100%', justifyContent: 'center', marginTop: 6 }}
                onClick={handleLogin}
                disabled={loading}
              >
                {loading ? 'Signing in...' : 'Sign In'}
              </button>
            </div>
          ) : (
            <div style={{ padding: '20px 22px' }}>
              <div className="form-group">
                <label htmlFor="reg-name">Full Name</label>
                <input
                  id="reg-name"
                  type="text"
                  placeholder="Your full name"
                  value={regName}
                  onChange={(e) => setRegName(e.target.value)}
                  autoComplete="name"
                  maxLength={100}
                />
              </div>
              <div className="form-group">
                <label htmlFor="reg-email">SIT Email</label>
                <input
                  id="reg-email"
                  type="email"
                  placeholder="student@sit.singaporetech.edu.sg"
                  value={regEmail}
                  onChange={(e) => setRegEmail(e.target.value)}
                  autoComplete="email"
                  maxLength={254}
                />
                <div className="form-hint">Only @sit.singaporetech.edu.sg emails</div>
              </div>
              <div className="form-group">
                <label htmlFor="reg-pw">Password</label>
                <input
                  id="reg-pw"
                  type="password"
                  placeholder="Min 6 characters"
                  value={regPw}
                  onChange={(e) => setRegPw(e.target.value)}
                  autoComplete="new-password"
                  maxLength={128}
                />
              </div>
              <div className="form-group">
                <label htmlFor="reg-cpw">Confirm Password</label>
                <input
                  id="reg-cpw"
                  type="password"
                  placeholder="Confirm password"
                  value={regCpw}
                  onChange={(e) => setRegCpw(e.target.value)}
                  autoComplete="new-password"
                  maxLength={128}
                />
              </div>
              {error && <div className="form-error">{error}</div>}
              <button
                className="btn btn-primary"
                style={{ width: '100%', justifyContent: 'center', marginTop: 6 }}
                onClick={handleRegister}
                disabled={loading}
              >
                {loading ? 'Sending...' : 'Create Account'}
              </button>
            </div>
          )}
        </>
      )}
    </Modal>
  );
}

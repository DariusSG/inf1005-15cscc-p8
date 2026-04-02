import { useState, useEffect } from 'react';
import { getTutors, postTutor, getModules } from '../api/index';
import { useAuth } from '../context/AuthContext';
import { sanitiseField, sanitiseEmail, sanitiseModuleCodes, requireField } from '../utils/sanitise';
import { Loading, Empty, Avatar } from '../components/Shared';
import Modal from '../components/Modal';
import toast from 'react-hot-toast';

const ALLOWED_RATES = ['$15/hr', '$20/hr', '$25/hr', '$30/hr', 'Free'];
const RATE_TO_NUM = { '$15/hr': 15, '$20/hr': 20, '$25/hr': 25, '$30/hr': 30, 'Free': 0 };

function ContactModal({ tutor, onClose }) {
  return (
    <Modal onClose={onClose} className="modal-md">
      <div className="modal-head">
        <h3>Tutor Information</h3>
        <p style={{ color: 'var(--text-secondary)', fontSize: '0.88rem' }}>Contact details for {tutor.name || tutor.user?.name}</p>
      </div>
      <div className="modal-body">
        <div style={{ display: 'flex', gap: 14, alignItems: 'center', marginBottom: 16 }}>
          <Avatar name={tutor.name || tutor.user?.name || '?'} variant="green" size="lg" />
          <div>
            <div style={{ fontWeight: 600, fontSize: '1.05rem' }}>{tutor.name || tutor.user?.name}</div>
            <div style={{ fontSize: '0.82rem', color: 'var(--text-secondary)' }}>{tutor.bio}</div>
          </div>
        </div>
        <div className="info-row"><span className="info-label">Modules</span><span className="info-value">{(tutor.modules || []).join(', ')}</span></div>
        <div className="info-row"><span className="info-label">Rate</span><span className="info-value" style={{ color: 'var(--green)' }}>{tutor.rate}</span></div>
        <div className="info-row"><span className="info-label">Contact Email</span><span className="info-value"><a href={`mailto:${tutor.contactEmail}`}>{tutor.contactEmail}</a></span></div>
        <p style={{ fontSize: '0.78rem', color: 'var(--text-muted)', marginTop: 16, textAlign: 'center' }}>
          Reach out via the email above to arrange a session.
        </p>
      </div>
    </Modal>
  );
}

function OfferTutorModal({ onClose, onSaved }) {
  const { user } = useAuth();
  const userName = user?.name || '';
  const [modules, setModules] = useState([]);
  const [selectedMods, setSelectedMods] = useState([]);
  const [rate, setRate] = useState('$20/hr');
  const [bio, setBio] = useState('');
  const [handle, setHandle] = useState(user?.email?.split('@')[0] || '');
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    getModules().then((data) => setModules(Array.isArray(data) ? data : data.data || []));
  }, []);

  const toggleMod = (code) => {
    setSelectedMods((prev) =>
      prev.includes(code) ? prev.filter((m) => m !== code) : prev.length < 3 ? [...prev, code] : prev
    );
  };

  const submit = async () => {
    const cleanMods = sanitiseModuleCodes(selectedMods, 3);
    if (!cleanMods.length) { toast.error('Select at least one module'); return; }
    const { value: cleanBio, error: bioErr } = requireField(bio, 'Bio', 'bio');
    if (bioErr) { toast.error(bioErr); return; }
    const { value: cleanHandle, error: handleErr } = requireField(handle, 'Email', 'name');
    if (handleErr) { toast.error(handleErr); return; }
    // Validate the email handle — no @ allowed, alphanumeric + dots/dashes only
    if (!/^[a-zA-Z0-9._-]+$/.test(cleanHandle)) { toast.error('Invalid email handle'); return; }
    const cleanRate = ALLOWED_RATES.includes(rate) ? rate : '$20/hr';

    setLoading(true);
    try {
      await postTutor({
        name: userName,
        module_codes: cleanMods,
        rate: RATE_TO_NUM[cleanRate] ?? 0,
        bio: cleanBio,
        contact_email: cleanHandle + '@sit.singaporetech.edu.sg',
      });
      toast.success('Listing published!');
      onSaved();
      onClose();
    } catch (e) {
      toast.error(e.response?.data?.message || 'Failed to publish');
    } finally { setLoading(false); }
  };

  return (
    <Modal onClose={onClose} className="modal-md">
      <div className="modal-head">
        <h3>Offer Tutoring</h3>
        <p style={{ color: 'var(--text-secondary)', fontSize: '0.88rem' }}>List yourself as a peer tutor (max 5 active)</p>
      </div>
      <div className="modal-body">
        <div className="form-group">
          <label>Modules (up to 3 — click to select)</label>
          <div style={{ display: 'flex', flexWrap: 'wrap', gap: 5, marginTop: 5 }}>
            {modules.map((m) => (
              <button
                key={m.code}
                className={`pill${selectedMods.includes(m.code) ? ' active' : ''}`}
                onClick={() => toggleMod(m.code)}
                style={{ fontSize: '0.72rem' }}
              >
                {m.code}
              </button>
            ))}
          </div>
        </div>
        <div className="form-group">
          <label htmlFor="offer-rate">Hourly Rate</label>
          <select id="offer-rate" value={rate} onChange={(e) => setRate(e.target.value)}>
            {ALLOWED_RATES.map((r) => <option key={r}>{r}</option>)}
          </select>
        </div>
        <div className="form-group">
          <label htmlFor="offer-bio">Short Bio</label>
          <textarea id="offer-bio" placeholder="Why you're a great tutor..." value={bio} onChange={(e) => setBio(e.target.value)} rows={2} maxLength={500} />
        </div>
        <div className="form-group">
          <label htmlFor="offer-email">Contact Email</label>
          <div style={{ display: 'flex', alignItems: 'center' }}>
            <input
              id="offer-email"
              type="text"
              value={handle}
              onChange={(e) => setHandle(e.target.value)}
              style={{ borderRadius: 'var(--radius-sm) 0 0 var(--radius-sm)', borderRight: 'none' }}
              maxLength={64}
            />
            <span style={{ background: 'var(--bg-elevated)', border: '1px solid var(--border)', padding: '9px 10px', fontSize: '0.82rem', color: 'var(--text-muted)', borderRadius: '0 var(--radius-sm) var(--radius-sm) 0', whiteSpace: 'nowrap' }}>
              @sit.singaporetech.edu.sg
            </span>
          </div>
        </div>
        <button className="btn btn-primary" style={{ width: '100%', justifyContent: 'center', marginTop: 6 }} onClick={submit} disabled={loading}>
          {loading ? 'Publishing...' : 'Publish Listing'}
        </button>
      </div>
    </Modal>
  );
}

export default function Tutors() {
  const { user } = useAuth();
  const [tutors, setTutors] = useState([]);
  const [loading, setLoading] = useState(true);
  const [search, setSearch] = useState('');
  const [contactTutor, setContactTutor] = useState(null);
  const [offerOpen, setOfferOpen] = useState(false);

  const load = () => {
    setLoading(true);
    getTutors()
      .then((data) => setTutors(Array.isArray(data) ? data : data.data || []))
      .finally(() => setLoading(false));
  };

  useEffect(() => { load(); }, []);

  const filtered = tutors.filter((t) => {
    const q = search.toLowerCase();
    return !q || (t.name || t.user?.name || '').toLowerCase().includes(q) || (t.modules || []).some((m) => m.toLowerCase().includes(q));
  });

  return (
    <div className="page-section">
      {contactTutor && <ContactModal tutor={contactTutor} onClose={() => setContactTutor(null)} />}
      {offerOpen && <OfferTutorModal onClose={() => setOfferOpen(false)} onSaved={load} />}
      <div className="section-header">
        <h2>Tutor Finder</h2>
        <p className="sub">Find peer tutors or offer your tutoring services</p>
      </div>
      <div className="controls-row">
        <div className="search-input" style={{ flex: 1 }}>
          <span className="si-icon"></span>
          <input type="text" placeholder="Search tutors by module or name..." value={search} onChange={(e) => setSearch(e.target.value)} maxLength={100} />
        </div>
        <button className="btn btn-primary btn-sm" onClick={() => { if (!user) { toast.error('Sign in first'); return; } setOfferOpen(true); }}>
          + Offer Tutoring
        </button>
      </div>
      {loading ? <Loading /> : filtered.length === 0 ? <Empty icon="" title="No tutors found" /> : (
        filtered.map((t) => (
          <div className="tutor-card" key={t.id}>
            <Avatar name={t.name || t.user?.name || '?'} variant="green" size="lg" />
            <div className="tutor-info">
              <div className="tname">{t.name || t.user?.name}</div>
              <div className="tmods">
                Teaches: {(t.modules || []).map((m) => (
                  <span key={m} className="tag-prereq" style={{ fontSize: '0.72rem' }}>{m}</span>
                ))}
              </div>
              <div className="tbio">{t.bio}</div>
              <span className="trate">{t.rate}</span>
            </div>
            <div style={{ alignSelf: 'center' }}>
              <button className="btn btn-secondary btn-sm" onClick={() => setContactTutor(t)}>View Info</button>
            </div>
          </div>
        ))
      )}
    </div>
  );
}

import { useState, useEffect } from 'react';
import { getReportedReviews } from '../api/index';
import client from '../api/client';
import { useAuth } from '../context/AuthContext';
import { sanitiseField, requireField } from '../utils/sanitise';
import { Loading, Empty } from '../components/Shared';
import toast from 'react-hot-toast';

const ALLOWED_FACULTIES = ['ICT', 'Business', 'Engineering', 'HSS'];

export default function Admin() {
  const { user, isAdmin } = useAuth();
  const [reported, setReported] = useState([]);
  const [loading, setLoading] = useState(true);

  const [aCode, setACode] = useState('');
  const [aName, setAName] = useState('');
  const [aFac, setAFac] = useState('ICT');
  const [aCred, setACred] = useState(5);
  const [aSem, setASem] = useState('1');
  const [aDesc, setADesc] = useState('');
  const [aPrereq, setAPrereq] = useState('');
  const [creating, setCreating] = useState(false);

  const loadReported = () => {
    setLoading(true);
    getReportedReviews()
      .then((data) => setReported(Array.isArray(data) ? data : data.data || []))
      .catch(() => setReported([]))
      .finally(() => setLoading(false));
  };

  useEffect(() => { if (isAdmin) loadReported(); }, [isAdmin]);

  const handleDismissReports = async (reviewId, userIds) => {
    try {
      await Promise.all(
        userIds.map((uid) =>
          client.delete('/admin/reviews/report', { params: { review_id: reviewId, user_id: uid } })
        )
      );
      toast.info('Reports dismissed');
      loadReported();
    } catch {
      toast.error('Failed to dismiss');
    }
  };

  const handleCreateModule = async () => {
    // Sanitise all fields
    const cleanCode = sanitiseField(aCode, 'code').toUpperCase();
    const { value: cleanName, error: nameErr } = requireField(aName, 'Module name', 'short');
    if (!cleanCode) { toast.error('Module code required'); return; }
    // Validate code format e.g. ICT1001
    if (!/^[A-Z]{2,4}\d{4}$/.test(cleanCode)) { toast.error('Invalid module code format (e.g. ICT1001)'); return; }
    if (nameErr) { toast.error(nameErr); return; }
    const cleanFac = ALLOWED_FACULTIES.includes(aFac) ? aFac : 'ICT';
    const cleanCred = Math.min(Math.max(1, Math.round(Number(aCred))), 20);
    const cleanSems = aSem.split(',').map((s) => Math.round(Number(s.trim()))).filter((n) => n === 1 || n === 2);
    const cleanDesc = sanitiseField(aDesc, 'content');
    const cleanPrereqs = aPrereq.split(',').map((s) => sanitiseField(s, 'code').toUpperCase()).filter((s) => /^[A-Z]{2,4}\d{4}$/.test(s));

    setCreating(true);
    try {
      await client.post('/admin/modules', {
        code: cleanCode,
        name: cleanName,
        faculty: cleanFac,
        credits: cleanCred,
        semesters: cleanSems.length ? cleanSems : [1],
        description: cleanDesc,
        prerequisites: cleanPrereqs,
      });
      toast.success(`${cleanCode} created!`);
      setACode(''); setAName(''); setADesc(''); setAPrereq(''); setASem('1'); setACred(5);
    } catch (e) {
      toast.error(e.response?.data?.message || 'Failed to create');
    } finally { setCreating(false); }
  };

  if (!isAdmin) return <div className="page-section"><Empty icon="" title="Admin access required" /></div>;

  return (
    <div className="page-section">
      <div className="section-header">
        <h2>Admin Panel</h2>
        <p className="sub">Manage modules, review reports, and platform settings</p>
      </div>

      {/* Create Module */}
      <div className="admin-box">
        <h3> Create Module</h3>
        <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 10 }}>
          <div className="form-group">
            <label htmlFor="a-code">Code</label>
            <input id="a-code" type="text" placeholder="ICT3001" value={aCode} onChange={(e) => setACode(e.target.value)} maxLength={10} />
          </div>
          <div className="form-group">
            <label htmlFor="a-fac">Faculty</label>
            <select id="a-fac" value={aFac} onChange={(e) => setAFac(e.target.value)}>
              {ALLOWED_FACULTIES.map((f) => <option key={f}>{f}</option>)}
            </select>
          </div>
          <div className="form-group" style={{ gridColumn: '1 / -1' }}>
            <label htmlFor="a-name">Name</label>
            <input id="a-name" type="text" placeholder="Module name" value={aName} onChange={(e) => setAName(e.target.value)} maxLength={200} />
          </div>
          <div className="form-group">
            <label htmlFor="a-cred">Credits</label>
            <input id="a-cred" type="number" min={1} max={20} value={aCred} onChange={(e) => setACred(+e.target.value)} />
          </div>
          <div className="form-group">
            <label htmlFor="a-sem">Semesters</label>
            <input id="a-sem" type="text" placeholder="1,2" value={aSem} onChange={(e) => setASem(e.target.value)} maxLength={5} />
          </div>
          <div className="form-group" style={{ gridColumn: '1 / -1' }}>
            <label htmlFor="a-desc">Description</label>
            <textarea id="a-desc" rows={2} value={aDesc} onChange={(e) => setADesc(e.target.value)} maxLength={1000} />
          </div>
          <div className="form-group" style={{ gridColumn: '1 / -1' }}>
            <label htmlFor="a-prereq">Prerequisites</label>
            <input id="a-prereq" type="text" placeholder="ICT1001,ICT1002" value={aPrereq} onChange={(e) => setAPrereq(e.target.value)} maxLength={100} />
          </div>
        </div>
        <button className="btn btn-primary" onClick={handleCreateModule} disabled={creating} style={{ marginTop: 6 }}>
          {creating ? 'Creating...' : 'Create Module'}
        </button>
      </div>

      {/* Reported Reviews */}
      <div className="admin-box">
        <h3> Reported Reviews</h3>
        {loading ? <Loading /> : (
          <table className="adm-table">
            <thead>
              <tr><th>Module</th><th>Review</th><th>Reports</th><th>Action</th></tr>
            </thead>
            <tbody>
              {reported.length === 0 ? (
                <tr><td colSpan={4} style={{ textAlign: 'center', color: 'var(--text-muted)', padding: 20 }}>No reports</td></tr>
              ) : reported.map((r) => (
                <tr key={r.id}>
                  <td style={{ fontFamily: 'var(--mono)', fontSize: '0.8rem' }}>{r.module_code}</td>
                  <td>{r.title}</td>
                  <td><span className="report-badge"> {r.report_count}</span></td>
                  <td>
                    <button className="btn btn-secondary btn-sm" onClick={() => handleDismissReports(r.id, r.users || [])}>Dismiss</button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        )}
      </div>
    </div>
  );
}

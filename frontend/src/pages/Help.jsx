import { useState, useEffect } from 'react';
import { getHelpRequests, postHelpRequest, putHelpRequest, postHelpSolve, deleteHelpRequest, getModules } from '../api/index';
import { useAuth } from '../context/AuthContext';
import { sanitiseField, sanitiseBounty, requireField } from '../utils/sanitise';
import { Loading, Empty, UrgencyBadge, BountyTag, SolvedTag } from '../components/Shared';
import Modal from '../components/Modal';
import toast from 'react-hot-toast';

const ALLOWED_URGENCY = ['low', 'medium', 'high'];

function HelpDetailModal({ help, onClose }) {
  return (
    <Modal onClose={onClose} className="modal-md">
      <div className="modal-head">
        <h3>{help.title}</h3>
        <p style={{ color: 'var(--text-secondary)', fontSize: '0.88rem' }}>Help request details</p>
      </div>
      <div className="modal-body">
        <div className="info-row"><span className="info-label">Module</span><span className="info-value">{help.module || help.moduleCode}</span></div>
        <div className="info-row"><span className="info-label">Urgency</span><span className="info-value"><UrgencyBadge urgency={help.urgency} /></span></div>
        <div className="info-row"><span className="info-label">Bounty</span><span className="info-value">{help.hasBounty ? <BountyTag amount={help.bountyAmount} /> : 'None'}</span></div>
        <div className="info-row"><span className="info-label">Status</span><span className="info-value">{help.status === 'solved' ? <SolvedTag /> : <span style={{ color: 'var(--blue)' }}>Open</span>}</span></div>
        <div className="info-row"><span className="info-label">Contact</span><span className="info-value"><a href={`mailto:${help.contactEmail}`}>{help.contactEmail}</a></span></div>
        <div className="info-row" style={{ borderBottom: 'none' }}><span className="info-label">Description</span></div>
        <p style={{ fontSize: '0.88rem', color: 'var(--text-secondary)', padding: '4px 0 16px' }}>{help.description || help.desc}</p>
        <p style={{ fontSize: '0.78rem', color: 'var(--text-muted)', textAlign: 'center' }}>Reach out via email if you can help.</p>
      </div>
    </Modal>
  );
}

export function EditHelpModal({ help, onClose, onSaved }) {
  const [title, setTitle] = useState(help.title || '');
  const [desc, setDesc] = useState(help.desc || '');
  const [urgency, setUrgency] = useState(help.urgency || 'medium');
  const [handle, setHandle] = useState((help.contactEmail || '').split('@')[0]);
  const [bountyOn, setBountyOn] = useState(help.hasBounty || false);
  const [bountyAmount, setBountyAmount] = useState(help.bountyAmount || 10);
  const [loading, setLoading] = useState(false);

  const submit = async () => {
    if (!title.trim()) { toast.error('Title required'); return; }
    if (!desc.trim()) { toast.error('Description required'); return; }
    if (!/^[a-zA-Z0-9._-]+$/.test(handle)) { toast.error('Invalid email handle'); return; }
    const cleanUrgency = ALLOWED_URGENCY.includes(urgency) ? urgency : 'medium';

    setLoading(true);
    try {
      await putHelpRequest(help.id, {
        title: title.trim(),
        description: desc.trim(),
        urgency: cleanUrgency,
        contact_email: handle + '@sit.singaporetech.edu.sg',
        has_bounty: bountyOn,
        bounty_amount: bountyOn ? bountyAmount : 0,
      });
      toast.success('Request updated!');
      onSaved();
      onClose();
    } catch (e) {
      toast.error(e.response?.data?.message || 'Failed to update');
    } finally { setLoading(false); }
  };

  return (
    <Modal onClose={onClose} className="modal-md">
      <div className="modal-head">
        <h3>Edit Help Request</h3>
        <p style={{ color: 'var(--text-secondary)', fontSize: '0.88rem' }}>Update your request details</p>
      </div>
      <div className="modal-body">
        <div className="form-group">
          <label htmlFor="edit-help-title">Title</label>
          <input id="edit-help-title" type="text" value={title} onChange={(e) => setTitle(e.target.value)} maxLength={200} />
        </div>
        <div className="form-group">
          <label htmlFor="edit-help-desc">Description</label>
          <textarea id="edit-help-desc" value={desc} onChange={(e) => setDesc(e.target.value)} rows={3} maxLength={2000} />
        </div>
        <div className="form-group">
          <label htmlFor="edit-help-urgency">Urgency</label>
          <select id="edit-help-urgency" value={urgency} onChange={(e) => setUrgency(e.target.value)}>
            <option value="low">Low — No rush</option>
            <option value="medium">Medium — This week</option>
            <option value="high">High — ASAP</option>
          </select>
        </div>
        <div className="form-group">
          <label htmlFor="edit-help-email">Contact Email</label>
          <div style={{ display: 'flex', alignItems: 'center' }}>
            <input id="edit-help-email" type="text" value={handle} onChange={(e) => setHandle(e.target.value)} style={{ borderRadius: 'var(--radius-sm) 0 0 var(--radius-sm)', borderRight: 'none' }} maxLength={64} />
            <span style={{ background: 'var(--bg-elevated)', border: '1px solid var(--border)', padding: '9px 10px', fontSize: '0.82rem', color: 'var(--text-muted)', borderRadius: '0 var(--radius-sm) var(--radius-sm) 0', whiteSpace: 'nowrap' }}>
              @sit.singaporetech.edu.sg
            </span>
          </div>
        </div>
        <div className="form-group">
          <label>Offer a Bounty?</label>
          <div className="bounty-toggle">
            <div className={`toggle${bountyOn ? ' on' : ''}`} onClick={() => setBountyOn((b) => !b)} role="switch" aria-checked={bountyOn} tabIndex={0} aria-label="Offer a bounty" onKeyDown={(e) => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); setBountyOn((b) => !b); } }}>
              <div className="knob" />
            </div>
            <span style={{ fontSize: '0.85rem', color: 'var(--text-muted)' }}>{bountyOn ? 'Bounty enabled' : 'No bounty'}</span>
          </div>
          {bountyOn && (
            <div style={{ marginTop: 10 }}>
              <label htmlFor="edit-bounty-amount">Bounty Amount (SGD)</label>
              <select id="edit-bounty-amount" value={bountyAmount} onChange={(e) => setBountyAmount(+e.target.value)}>
                {[5, 10, 20, 30, 50].map((n) => <option key={n} value={n}>${n}</option>)}
              </select>
            </div>
          )}
        </div>
        <button className="btn btn-primary" style={{ width: '100%', justifyContent: 'center', marginTop: 6 }} onClick={submit} disabled={loading}>
          {loading ? 'Saving...' : 'Save Changes'}
        </button>
      </div>
    </Modal>
  );
}

function CreateHelpModal({ onClose, onSaved }) {
  const { user } = useAuth();
  const [modules, setModules] = useState([]);
  const [title, setTitle] = useState('');
  const [moduleCode, setModuleCode] = useState('');
  const [desc, setDesc] = useState('');
  const [urgency, setUrgency] = useState('medium');
  const [handle, setHandle] = useState(user?.email?.split('@')[0] || '');
  const [bountyOn, setBountyOn] = useState(false);
  const [bountyAmount, setBountyAmount] = useState(10);
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    getModules().then((data) => {
      const arr = Array.isArray(data) ? data : data.data || [];
      setModules(arr);
      if (arr.length) setModuleCode(arr[0].code);
    });
  }, []);

  const submit = async () => {
    const { value: cleanTitle, error: titleErr } = requireField(title, 'Title', 'title');
    if (titleErr) { toast.error(titleErr); return; }
    if (!moduleCode) { toast.error('Select a module'); return; }
    const { value: cleanDesc, error: descErr } = requireField(desc, 'Description', 'content');
    if (descErr) { toast.error(descErr); return; }
    const { value: cleanHandle, error: handleErr } = requireField(handle, 'Email', 'name');
    if (handleErr) { toast.error(handleErr); return; }
    if (!/^[a-zA-Z0-9._-]+$/.test(cleanHandle)) { toast.error('Invalid email handle'); return; }
    const cleanUrgency = ALLOWED_URGENCY.includes(urgency) ? urgency : 'medium';
    const cleanBounty = sanitiseBounty(bountyAmount);

    setLoading(true);
    try {
      await postHelpRequest({
        title: cleanTitle,
        module_code: moduleCode,
        description: cleanDesc,
        urgency: cleanUrgency,
        contact_email: cleanHandle + '@sit.singaporetech.edu.sg',
        has_bounty: bountyOn,
        bounty_amount: bountyOn ? cleanBounty : 0,
      });
      toast.success('Help request posted!');
      onSaved();
      onClose();
    } catch (e) {
      toast.error(e.response?.data?.message || 'Failed to post');
    } finally { setLoading(false); }
  };

  return (
    <Modal onClose={onClose} className="modal-md">
      <div className="modal-head">
        <h3>Request Help</h3>
        <p style={{ color: 'var(--text-secondary)', fontSize: '0.88rem' }}>Post a help request and connect with students</p>
      </div>
      <div className="modal-body">
        <div className="form-group">
          <label htmlFor="help-title">Title</label>
          <input id="help-title" type="text" placeholder="e.g. Need help with SQL JOINs" value={title} onChange={(e) => setTitle(e.target.value)} maxLength={200} />
        </div>
        <div className="form-group">
          <label htmlFor="help-mod">Module</label>
          <select id="help-mod" value={moduleCode} onChange={(e) => setModuleCode(e.target.value)}>
            {modules.map((m) => <option key={m.code} value={m.code}>{m.code} — {m.name}</option>)}
          </select>
        </div>
        <div className="form-group">
          <label htmlFor="help-desc">Description</label>
          <textarea id="help-desc" placeholder="Describe what you need help with..." value={desc} onChange={(e) => setDesc(e.target.value)} rows={3} maxLength={2000} />
        </div>
        <div className="form-group">
          <label htmlFor="help-urgency">Urgency</label>
          <select id="help-urgency" value={urgency} onChange={(e) => setUrgency(e.target.value)}>
            <option value="low">Low — No rush</option>
            <option value="medium">Medium — This week</option>
            <option value="high">High — ASAP</option>
          </select>
        </div>
        <div className="form-group">
          <label htmlFor="help-email">Contact Email</label>
          <div style={{ display: 'flex', alignItems: 'center' }}>
            <input id="help-email" type="text" value={handle} onChange={(e) => setHandle(e.target.value)} style={{ borderRadius: 'var(--radius-sm) 0 0 var(--radius-sm)', borderRight: 'none' }} maxLength={64} />
            <span style={{ background: 'var(--bg-elevated)', border: '1px solid var(--border)', padding: '9px 10px', fontSize: '0.82rem', color: 'var(--text-muted)', borderRadius: '0 var(--radius-sm) var(--radius-sm) 0', whiteSpace: 'nowrap' }}>
              @sit.singaporetech.edu.sg
            </span>
          </div>
        </div>
        <div className="form-group">
          <label>Offer a Bounty?</label>
          <div className="form-hint" style={{ marginTop: 0, marginBottom: 6 }}>Willing to offer a small reward?</div>
          <div className="bounty-toggle">
            <div className={`toggle${bountyOn ? ' on' : ''}`} onClick={() => setBountyOn((b) => !b)} role="switch" aria-checked={bountyOn} tabIndex={0} aria-label="Offer a bounty" onKeyDown={(e) => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); setBountyOn((b) => !b); } }}>
              <div className="knob" />
            </div>
            <span style={{ fontSize: '0.85rem', color: 'var(--text-muted)' }}>{bountyOn ? 'Bounty enabled' : 'No bounty'}</span>
          </div>
          {bountyOn && (
            <div style={{ marginTop: 10 }}>
              <label htmlFor="bounty-amount">Bounty Amount (SGD)</label>
              <select id="bounty-amount" value={bountyAmount} onChange={(e) => setBountyAmount(+e.target.value)}>
                {[5,10,20,30,50].map((n) => <option key={n} value={n}>${n}</option>)}
              </select>
            </div>
          )}
        </div>
        <button className="btn btn-primary" style={{ width: '100%', justifyContent: 'center', marginTop: 6 }} onClick={submit} disabled={loading}>
          {loading ? 'Posting...' : 'Post Help Request'}
        </button>
      </div>
    </Modal>
  );
}

export default function Help() {
  const { user } = useAuth();
  const [helpReqs, setHelpReqs] = useState([]);
  const [loading, setLoading] = useState(true);
  const [search, setSearch] = useState('');
  const [detailItem, setDetailItem] = useState(null);
  const [editItem, setEditItem] = useState(null);
  const [createOpen, setCreateOpen] = useState(false);

  const load = () => {
    setLoading(true);
    getHelpRequests()
      .then((data) => setHelpReqs(Array.isArray(data) ? data : data.data || []))
      .finally(() => setLoading(false));
  };

  useEffect(() => { load(); }, []);

  const handleSolve = async (id) => {
    try { await postHelpSolve(id); load(); toast.success('Marked as solved!'); }
    catch { toast.error('Failed to update'); }
  };

  const handleDelete = async (id) => {
    if (!window.confirm('Delete this request? This cannot be undone.')) return;
    try {
      await deleteHelpRequest(id);
      toast.success('Help request deleted');
      load();
    } catch (e) {
      toast.error(e.response?.data?.message || 'Failed to delete');
    }
  };

  const filtered = helpReqs.filter((h) => {
    const q = search.toLowerCase();
    return !q || h.title?.toLowerCase().includes(q) || (h.module || h.moduleCode || '').toLowerCase().includes(q);
  });

  return (
    <div className="page-section">
      {detailItem && <HelpDetailModal help={detailItem} onClose={() => setDetailItem(null)} />}
      {editItem && <EditHelpModal help={editItem} onClose={() => setEditItem(null)} onSaved={load} />}
      {createOpen && <CreateHelpModal onClose={() => setCreateOpen(false)} onSaved={load} />}
      <div className="section-header">
        <h1>Help Finder</h1>
        <p className="sub">Need help with a module? Post a request and connect with students who can help</p>
      </div>
      <div className="controls-row">
        <div className="search-input" style={{ flex: 1 }}>
          <span className="si-icon" aria-hidden="true"></span>
          <input type="text" placeholder="Search help requests..." value={search} onChange={(e) => setSearch(e.target.value)} maxLength={100} />
        </div>
        <button className="btn btn-primary btn-sm" onClick={() => { if (!user) { toast.error('Sign in first'); return; } setCreateOpen(true); }}>
          + Request Help
        </button>
      </div>
      {loading ? <Loading /> : filtered.length === 0 ? <Empty icon="" title="No help requests found" /> : (
        filtered.map((h) => {
          const isOwn = user && user.email === h.userEmail;
          const solved = h.status === 'solved';
          return (
            <div className={`help-card${solved ? ' solved' : ''}`} key={h.id}>
              <div className="help-head">
                <div>
                  <div className="help-title">{h.title} {solved && <SolvedTag />}</div>
                  <div className="help-mod">{h.module || h.moduleCode}{h.hasBounty && <> <BountyTag amount={h.bountyAmount} /></>}</div>
                </div>
                <UrgencyBadge urgency={h.urgency} />
              </div>
              <div className="help-desc">{h.description || h.desc}</div>
              <div className="help-footer">
                <span className="help-meta">{h.author}</span>
                <div className="help-actions">
                  {isOwn && !solved && <button className="btn btn-secondary btn-sm" onClick={() => setEditItem(h)}>Edit</button>}
                  {isOwn && !solved && <button className="btn btn-success btn-sm" onClick={() => handleSolve(h.id)}>✓ Solved</button>}
                  {isOwn && <button className="btn btn-danger btn-sm" onClick={() => handleDelete(h.id)}>Delete</button>}
                  {!isOwn && <button className="btn btn-secondary btn-sm" onClick={() => setDetailItem(h)}>View Details</button>}
                </div>
              </div>
            </div>
          );
        })
      )}
    </div>
  );
}

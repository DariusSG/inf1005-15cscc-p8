import { useState, useEffect } from 'react';
import { getStudyGroups, postStudyGroup, putStudyGroup, getModules } from '../api/index';
import { useAuth } from '../context/AuthContext';
import { Loading, Empty } from '../components/Shared';
import Modal from '../components/Modal';
import toast from 'react-hot-toast';

function EditGroupModal({ group, onClose, onSaved }) {
  const [name, setName] = useState(group.name || '');
  const [desc, setDesc] = useState(group.description || '');
  const [meetingTime, setMeetingTime] = useState(group.meeting_time || '');
  const [location, setLocation] = useState(group.location || '');
  const [loading, setLoading] = useState(false);

  const submit = async () => {
    if (!name.trim()) { toast.error('Group name required'); return; }
    setLoading(true);
    try {
      await putStudyGroup(group.id, { name, description: desc, meeting_time: meetingTime, location });
      toast.success('Group updated!');
      onSaved();
      onClose();
    } catch (e) {
      toast.error(e.response?.data?.message || 'Failed to update');
    } finally { setLoading(false); }
  };

  return (
    <Modal onClose={onClose} className="modal-md">
      <div className="modal-head">
        <h3>Edit Study Group</h3>
        <p style={{ color: 'var(--text-secondary)', fontSize: '0.88rem' }}>Update group details</p>
      </div>
      <div className="modal-body">
        <div className="form-group">
          <label>Group Name</label>
          <input type="text" value={name} onChange={(e) => setName(e.target.value)} maxLength={150} />
        </div>
        <div className="form-group">
          <label>Description</label>
          <textarea value={desc} onChange={(e) => setDesc(e.target.value)} rows={2} maxLength={500} />
        </div>
        <div className="form-group">
          <label>Meeting Time</label>
          <input type="text" placeholder="e.g. Wednesdays 6pm" value={meetingTime} onChange={(e) => setMeetingTime(e.target.value)} maxLength={100} />
        </div>
        <div className="form-group">
          <label>Location</label>
          <input type="text" placeholder="e.g. Library Level 3" value={location} onChange={(e) => setLocation(e.target.value)} maxLength={150} />
        </div>
        <button className="btn btn-primary" style={{ width: '100%', justifyContent: 'center', marginTop: 6 }} onClick={submit} disabled={loading}>
          {loading ? 'Saving...' : 'Save Changes'}
        </button>
      </div>
    </Modal>
  );
}

function CreateGroupModal({ onClose, onSaved }) {
  const [modules, setModules] = useState([]);
  const [name, setName] = useState('');
  const [moduleCode, setModuleCode] = useState('');
  const [desc, setDesc] = useState('');
  const [maxSize, setMaxSize] = useState(5);
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    getModules().then((data) => {
      const arr = Array.isArray(data) ? data : data.data || [];
      setModules(arr);
      if (arr.length) setModuleCode(arr[0].code);
    });
  }, []);

  const submit = async () => {
    if (!name.trim()) { toast.error('Group name required'); return; }
    setLoading(true);
    try {
      await postStudyGroup({ name, module_code: moduleCode, description: desc, maxSize });
      toast.success('Study group created!');
      onSaved();
      onClose();
    } catch (e) {
      toast.error(e.response?.data?.message || 'Failed to create group');
    } finally { setLoading(false); }
  };

  return (
    <Modal onClose={onClose} className="modal-md">
      <div className="modal-head">
        <h3>Create Study Group</h3>
        <p style={{ color: 'var(--text-secondary)', fontSize: '0.88rem' }}>Find students to study with</p>
      </div>
      <div className="modal-body">
        <div className="form-group">
          <label>Group Name</label>
          <input type="text" placeholder="e.g. ICT2001 Study Buddies" value={name} onChange={(e) => setName(e.target.value)} />
        </div>
        <div className="form-group">
          <label>Module</label>
          <select value={moduleCode} onChange={(e) => setModuleCode(e.target.value)}>
            {modules.map((m) => (
              <option key={m.code} value={m.code}>{m.code} — {m.name}</option>
            ))}
          </select>
        </div>
        <div className="form-group">
          <label>Description</label>
          <textarea placeholder="What will you study, when do you meet..." value={desc} onChange={(e) => setDesc(e.target.value)} rows={2} />
        </div>
        <div className="form-group">
          <label>Max Group Size</label>
          <select value={maxSize} onChange={(e) => setMaxSize(+e.target.value)}>
            {[2, 3, 4, 5, 6, 8, 10].map((n) => <option key={n} value={n}>{n} people</option>)}
          </select>
        </div>
        <button
          className="btn btn-primary"
          style={{ width: '100%', justifyContent: 'center', marginTop: 6 }}
          onClick={submit}
          disabled={loading}
        >
          {loading ? 'Creating...' : 'Create Group'}
        </button>
      </div>
    </Modal>
  );
}

export default function StudyGroups() {
  const { user } = useAuth();
  const [groups, setGroups] = useState([]);
  const [loading, setLoading] = useState(true);
  const [search, setSearch] = useState('');
  const [createOpen, setCreateOpen] = useState(false);
  const [editGroup, setEditGroup] = useState(null);

  const load = () => {
    setLoading(true);
    getStudyGroups()
      .then((data) => setGroups(Array.isArray(data) ? data : data.data || []))
      .finally(() => setLoading(false));
  };

  useEffect(() => { load(); }, []);

  const filtered = groups.filter((g) => {
    const q = search.toLowerCase();
    return !q || g.name?.toLowerCase().includes(q) || (g.module_code || '').toLowerCase().includes(q);
  });

  return (
    <div className="page-section">
      {createOpen && <CreateGroupModal onClose={() => setCreateOpen(false)} onSaved={load} />}
      {editGroup && <EditGroupModal group={editGroup} onClose={() => setEditGroup(null)} onSaved={load} />}

      <div className="section-header">
        <h1>Study Groups</h1>
        <p className="sub">Find or create study groups for your modules</p>
      </div>
      <div className="controls-row">
        <div className="search-input" style={{ flex: 1 }}>
          <span className="si-icon"></span>
          <input
            type="text"
            placeholder="Search groups by their name..."
            value={search}
            onChange={(e) => setSearch(e.target.value)}
          />
        </div>
        <button
          className="btn btn-primary btn-sm"
          onClick={() => { if (!user) { toast.error('Sign in first'); return; } setCreateOpen(true); }}
        >
          + Create Group
        </button>
      </div>

      {loading ? <Loading /> : filtered.length === 0 ? (
        <Empty icon="👥" title="No study groups yet" sub="Be the first to create one!" />
      ) : (
        filtered.map((g) => (
          <div className="help-card" key={g.id}>
            <div className="help-head">
              <div>
                <div className="help-title">{g.name}</div>
                <div className="help-mod">{g.module_code}</div>
              </div>
              <span style={{ fontFamily: 'var(--mono)', fontSize: '0.76rem', color: 'var(--text-muted)' }}>
                {g.memberCount || 1}/{g.maxSize} members
              </span>
            </div>
            {g.description && <div className="help-desc">{g.description}</div>}
            <div className="help-footer">
              <span className="help-meta">
                Created by {g.creator?.name || 'Unknown'} · {g.createdAt?.split('T')[0]}
              </span>
              <div className="help-actions">
                {user && g.user_id === user.id && (
                  <button className="btn btn-secondary btn-sm" onClick={() => setEditGroup(g)}>Edit</button>
                )}
                {g.memberCount < g.maxSize && (
                  <button className="btn btn-primary btn-sm">Join Group</button>
                )}
              </div>
            </div>
          </div>
        ))
      )}
    </div>
  );
}

import { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router';
import { getModule, postReview, putReview, postReviewVote, postReviewReport, postReviewComment } from '../api/index';
import { useAuth } from '../context/AuthContext';
import { sanitiseField, sanitiseRating, sanitiseSlider, requireField } from '../utils/sanitise';
import { Loading, Empty, FacTag, Stars, Avatar } from '../components/Shared';
import Modal from '../components/Modal';
import toast from 'react-hot-toast';

function WriteReviewModal({ module, review, onClose, onSaved }) {
  const [rating, setRating] = useState(review?.rating || 0);
  const [hovered, setHovered] = useState(0);
  const [title, setTitle] = useState(review?.title || '');
  const [content, setContent] = useState(review?.content || '');
  const [workload, setWorkload] = useState(review?.workload || 5);
  const [difficulty, setDifficulty] = useState(review?.difficulty || 5);
  const [usefulness, setUsefulness] = useState(review?.usefulness || 5);
  const [loading, setLoading] = useState(false);

  const submit = async () => {
    const cleanRating = sanitiseRating(rating);
    if (!cleanRating) { toast.error('Select a rating'); return; }
    const { value: cleanTitle, error: titleErr } = requireField(title, 'Title', 'title');
    if (titleErr) { toast.error(titleErr); return; }
    const { value: cleanContent, error: contentErr } = requireField(content, 'Review', 'content');
    if (contentErr) { toast.error(contentErr); return; }

    const payload = {
      moduleCode: module.code,
      rating: cleanRating,
      title: cleanTitle,
      content: cleanContent,
      workload: sanitiseSlider(workload),
      difficulty: sanitiseSlider(difficulty),
      usefulness: sanitiseSlider(usefulness),
    };
    setLoading(true);
    try {
      if (review) {
        await putReview(review.id, { ...payload });
        toast.success('Review updated!');
      } else {
        await postReview(payload);
        toast.success('Review submitted!');
      }
      onSaved();
      onClose();
    } catch (e) {
      toast.error(e.response?.data?.message || 'Failed to submit');
    } finally { setLoading(false); }
  };

  const display = hovered || rating;

  return (
    <Modal onClose={onClose} className="modal-md">
      <div className="modal-head">
        <h3>{review ? 'Edit Review' : 'Write a Review'}</h3>
        <p style={{ color: 'var(--text-secondary)', fontSize: '0.88rem' }}>{module.code} — {module.name}</p>
      </div>
      <div className="modal-body">
        <div className="form-group">
          <label>Overall Rating</label>
          <div className="star-picker">
            {[1,2,3,4,5].map((v) => (
              <span
                key={v}
                className={`star${v <= display ? ' filled' : ''}`}
                onClick={() => setRating(v)}
                onMouseEnter={() => setHovered(v)}
                onMouseLeave={() => setHovered(0)}
                role="button"
                aria-label={`${v} star`}
              >★</span>
            ))}
          </div>
        </div>
        <div className="form-group">
          <label htmlFor="w-title">Review Title</label>
          <input
            id="w-title"
            type="text"
            placeholder="Summarize your experience"
            value={title}
            onChange={(e) => setTitle(e.target.value)}
            maxLength={200}
          />
        </div>
        <div className="form-group">
          <label htmlFor="w-content">Review Content</label>
          <textarea
            id="w-content"
            placeholder="Share your detailed thoughts..."
            value={content}
            onChange={(e) => setContent(e.target.value)}
            rows={3}
            maxLength={5000}
          />
        </div>
        {[
          { label: 'Workload', val: workload, set: setWorkload },
          { label: 'Difficulty', val: difficulty, set: setDifficulty },
          { label: 'Usefulness', val: usefulness, set: setUsefulness },
        ].map(({ label, val, set }) => (
          <div className="slider-grp" key={label}>
            <label>{label} <span>{val}/10</span></label>
            <input type="range" min={1} max={10} value={val} onChange={(e) => set(+e.target.value)} />
          </div>
        ))}
        <button
          className="btn btn-primary"
          style={{ width: '100%', justifyContent: 'center', marginTop: 6 }}
          onClick={submit}
          disabled={loading}
        >
          {loading ? 'Submitting...' : 'Submit Review'}
        </button>
      </div>
    </Modal>
  );
}

function ReviewCard({ review, moduleCode, onRefresh }) {
  const { user } = useAuth();
  const [showComments, setShowComments] = useState(false);
  const [cmtText, setCmtText] = useState('');
  const [editOpen, setEditOpen] = useState(false);

  const isOwn = user && user.id === review.user_id;
  const myVote = review.user_vote;

  const handleVote = async (dir) => {
    if (!user) { toast.error('Sign in to vote'); return; }
    try { await postReviewVote(review.id, dir); onRefresh(); }
    catch { toast.error('Failed to vote'); }
  };

  const handleReport = async () => {
    if (!user) return;
    try { await postReviewReport(review.id); toast.info('Reported — admin will review'); }
    catch { toast.error('Failed to report'); }
  };

  const handleComment = async () => {
    const { value: cleanText, error } = requireField(cmtText, 'Comment', 'short');
    if (error) return;
    try {
      await postReviewComment(review.id, cleanText);
      setCmtText('');
      onRefresh();
      toast.success('Comment added!');
    } catch { toast.error('Failed to comment'); }
  };

  return (
    <>
      {editOpen && (
        <WriteReviewModal
          module={{ code: moduleCode, name: '' }}
          review={review}
          onClose={() => setEditOpen(false)}
          onSaved={onRefresh}
        />
      )}
      <div className="rev-card">
        <div className="rev-top">
          <div className="rev-author">
            <Avatar name={review.author || '?'} variant="accent" size="sm" />
            <div>
              <div style={{ fontWeight: 600, fontSize: '0.88rem' }}>{review.author}</div>
              <div style={{ fontSize: '0.72rem', color: 'var(--text-muted)' }}>{review.date?.split?.('T')?.[0] || review.date}</div>
            </div>
          </div>
          <Stars rating={review.rating} />
        </div>
        <div className="rev-title">{review.title}</div>
        <div className="rev-content">{review.content}</div>
        <div className="rev-metrics">
          <span className="rev-metric">Workload: <span>{review.workload}/10</span></span>
          <span className="rev-metric">Difficulty: <span>{review.difficulty}/10</span></span>
          <span className="rev-metric">Usefulness: <span>{review.usefulness}/10</span></span>
        </div>
        <div className="rev-actions">
          <button className={`vote-btn${myVote === 'up' ? ' voted' : ''}`} onClick={() => handleVote('up')}>▲ {review.upvotes || 0}</button>
          <button className={`vote-btn${myVote === 'down' ? ' voted' : ''}`} onClick={() => handleVote('down')}>▼ {review.downvotes || 0}</button>
          <button className="btn-ghost btn-sm" onClick={() => setShowComments((s) => !s)}>
             {(review.comments || []).length}
          </button>
          {isOwn && <button className="btn-ghost btn-sm" onClick={() => setEditOpen(true)}>️ Edit</button>}
          {!isOwn && user && <button className="btn-ghost btn-sm" onClick={handleReport}></button>}
        </div>
        {showComments && (
          <div className="comments-area">
            {(review.comments || []).map((c, i) => (
              <div className="cmt" key={i}>
                <Avatar name={c.a || '?'} variant="blue" size="sm" />
                <div>
                  <span className="cmt-author">{c.a}</span>{' '}
                  <span className="cmt-text">{c.t}</span>
                  <div className="cmt-time">{c.time?.split?.('T')?.[0] || c.time}</div>
                </div>
              </div>
            ))}
            {user && (
              <div className="cmt-input-row">
                <input
                  type="text"
                  placeholder="Add comment..."
                  value={cmtText}
                  onChange={(e) => setCmtText(e.target.value)}
                  onKeyDown={(e) => e.key === 'Enter' && handleComment()}
                  maxLength={300}
                />
                <button className="btn btn-primary btn-sm" onClick={handleComment}>Post</button>
              </div>
            )}
          </div>
        )}
      </div>
    </>
  );
}

export default function ModuleDetail() {
  const { code } = useParams();
  const navigate = useNavigate();
  const { user } = useAuth();
  const [mod, setMod] = useState(null);
  const [loading, setLoading] = useState(true);
  const [writeOpen, setWriteOpen] = useState(false);

  const load = () => {
    setLoading(true);
    getModule(code)
      .then(setMod)
      .catch(() => toast.error('Module not found'))
      .finally(() => setLoading(false));
  };

  useEffect(() => { load(); }, [code]);

  if (loading) return <div className="page-section"><Loading /></div>;
  if (!mod) return <div className="page-section"><Empty icon="" title="Module not found" /></div>;

  const reviews = mod.reviews || [];
  const avg = reviews.length ? (reviews.reduce((s, r) => s + r.rating, 0) / reviews.length).toFixed(1) : null;
  const aW = reviews.length ? (reviews.reduce((s, r) => s + r.workload, 0) / reviews.length).toFixed(1) : '—';
  const aD = reviews.length ? (reviews.reduce((s, r) => s + r.difficulty, 0) / reviews.length).toFixed(1) : '—';
  const aU = reviews.length ? (reviews.reduce((s, r) => s + r.usefulness, 0) / reviews.length).toFixed(1) : '—';

  return (
    <div className="page-section">
      {writeOpen && <WriteReviewModal module={mod} onClose={() => setWriteOpen(false)} onSaved={load} />}
      <button className="btn btn-ghost btn-sm" style={{ marginBottom: 16 }} onClick={() => navigate('/modules')}>
        ← Back
      </button>
      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', gap: 16, marginBottom: 20 }}>
        <div>
          <FacTag faculty={mod.faculty} />
          <div style={{ fontFamily: 'var(--mono)', fontSize: '0.82rem', color: 'var(--text-muted)', margin: '5px 0 3px' }}>{mod.code}</div>
          <h1 style={{ fontSize: '1.3rem', fontWeight: 700 }}>{mod.name}</h1>
        </div>
        <div style={{ textAlign: 'right' }}>
          <div style={{ fontSize: '1.8rem', fontFamily: 'var(--mono)', fontWeight: 700, color: 'var(--orange)' }}>{avg || '—'}</div>
          <div style={{ fontSize: '0.76rem', color: 'var(--text-muted)' }}>{reviews.length} reviews</div>
        </div>
      </div>
      <div className="detail-stats">
        <div className="stat-box"><div className="sv" style={{ color: 'var(--orange)' }}>{aW}</div><div className="sl">Workload</div></div>
        <div className="stat-box"><div className="sv" style={{ color: 'var(--red)' }}>{aD}</div><div className="sl">Difficulty</div></div>
        <div className="stat-box"><div className="sv" style={{ color: 'var(--green)' }}>{aU}</div><div className="sl">Usefulness</div></div>
      </div>
      <div className="detail-block">
        <h4>Description</h4>
        <p style={{ fontSize: '0.88rem', color: 'var(--text-secondary)' }}>{mod.description || mod.desc}</p>
      </div>
      <div className="detail-block">
        <h4>Prerequisites</h4>
        {(mod.prerequisites || mod.prereqs || []).length
          ? (mod.prerequisites || mod.prereqs).map((p) => <span key={p} className="tag-prereq">{p}</span>)
          : <span style={{ color: 'var(--text-muted)' }}>None</span>}
      </div>
      <div className="detail-block">
        <h4>Semesters</h4>
        {(mod.semesters || []).map((s) => <span key={s} className="tag-sem">Sem {s}</span>)}
      </div>
      <div className="detail-block">
        <div className="reviews-head">
          <h4 style={{ margin: 0 }}>Reviews ({reviews.length})</h4>
          {user && user.role !== 'admin' && (
            <button className="btn btn-primary btn-sm" onClick={() => setWriteOpen(true)}>+ Write Review</button>
          )}
        </div>
        {reviews.length
          ? reviews.map((r) => <ReviewCard key={r.id} review={r} moduleCode={mod.code} onRefresh={load} />)
          : <Empty icon="" title="No reviews yet" />}
      </div>
    </div>
  );
}

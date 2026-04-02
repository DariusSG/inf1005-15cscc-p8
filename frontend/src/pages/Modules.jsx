import { useState, useEffect } from 'react';
import { useNavigate, useSearchParams } from 'react-router';
import { getModules } from '../api/index';
import { Loading, Empty, FacTag } from '../components/Shared';

const FACULTIES = ['All', 'ICT', 'Business', 'Engineering', 'HSS'];

export default function Modules() {
  const [modules, setModules] = useState([]);
  const [loading, setLoading] = useState(true);
  const [faculty, setFaculty] = useState('All');
  const [search, setSearch] = useState('');
  const [searchParams] = useSearchParams();
  const navigate = useNavigate();

  useEffect(() => {
    const q = searchParams.get('q') || '';
    if (q) setSearch(q);
    getModules()
      .then((data) => setModules(Array.isArray(data) ? data : data.data || []))
      .finally(() => setLoading(false));
  }, []);

  const filtered = modules.filter((m) => {
    const matchFac = faculty === 'All' || m.faculty === faculty;
    const q = search.toLowerCase();
    const matchQ = !q || m.code?.toLowerCase().includes(q) || m.name?.toLowerCase().includes(q) || m.description?.toLowerCase().includes(q);
    return matchFac && matchQ;
  });

  return (
    <div className="page-section">
      <div className="section-header">
        <h1>Modules</h1>
        <p className="sub">Browse and review SIT modules across all faculties</p>
      </div>

      <div className="controls-row">
        <div className="search-input">
          <span className="si-icon"></span>
          <input
            type="text"
            placeholder="Search by code, name, or description..."
            value={search}
            onChange={(e) => setSearch(e.target.value)}
          />
        </div>
        <div className="filter-pills">
          {FACULTIES.map((f) => (
            <button
              key={f}
              className={`pill${faculty === f ? ' active' : ''}`}
              data-f={f}
              onClick={() => setFaculty(f)}
            >
              {f}
            </button>
          ))}
        </div>
      </div>

      {loading ? (
        <Loading text="Loading modules..." />
      ) : filtered.length === 0 ? (
        <Empty icon="" title="No modules found" />
      ) : (
        <div className="mod-grid">
          {filtered.map((m, i) => {
            const avg = m.reviews_avg_rating ? parseFloat(m.reviews_avg_rating).toFixed(1) : null;
            const count = m.reviews_count || 0;
            return (
              <div
                key={m.code}
                className="mod-card"
                style={{ animation: `fadeUp .35s ${i * 0.04}s both` }}
                onClick={() => navigate(`/modules/${m.code}`)}
              >
                <FacTag faculty={m.faculty} />
                <div className="code">{m.code}</div>
                <div className="name">{m.name}</div>
                <div className="desc">{m.description || m.desc}</div>
                <div className="meta">
                  <span className="rating">{avg ? `★ ${avg}` : 'No ratings'}</span>
                  <span>{count} review{count !== 1 ? 's' : ''}</span>
                  <span className="cu">{m.credits} CU</span>
                </div>
              </div>
            );
          })}
        </div>
      )}
    </div>
  );
}

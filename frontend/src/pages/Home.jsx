import { useState, useEffect } from 'react';
import { useNavigate } from 'react-router';
import { getStats } from '../api/index';

export default function Home() {
  const navigate = useNavigate();
  const [stats, setStats] = useState({ modules: 0, reviews: 0, avg: '—', tutors: 0, openHelp: 0 });

  useEffect(() => {
    getStats()
      .then((data) => setStats({
        modules: data.modules || 0,
        reviews: data.reviews || 0,
        avg: data.avg_rating ?? '—',
        tutors: data.tutors || 0,
        openHelp: data.open_help || 0,
      }))
      .catch(() => {});
  }, []);

  return (
    <div className="welcome-page">
      <div className="welcome-hero">
        <div className="welcome-logo">SM</div>
        <h1>Rate your <span className="highlight">SIT modules</span><br />honestly.</h1>
        <p className="tagline">Real reviews from real students. Find the best modules, dodge the worst, and plan your semesters smarter.</p>
      </div>

      <div className="home-cards">
        <div className="home-card c-mod" onClick={() => navigate('/modules')}>
          <span className="hc-icon">📘</span>
          <div className="hc-title">Module Reviews</div>
          <div className="hc-desc">Browse and review modules across all faculties. See ratings, workload, and difficulty.</div>
          <div className="hc-stat"><span className="dot" /><span>{stats.modules}</span> modules</div>
        </div>
        <div className="home-card c-tut" onClick={() => navigate('/tutors')}>
          <span className="hc-icon">🎓</span>
          <div className="hc-title">Tutor Finder</div>
          <div className="hc-desc">Find peer tutors or offer your own tutoring services to fellow students.</div>
          <div className="hc-stat"><span className="dot" /><span>{stats.tutors}</span> tutors</div>
        </div>
        <div className="home-card c-hlp" onClick={() => navigate('/help')}>
          <span className="hc-icon">🆘</span>
          <div className="hc-title">Help Finder</div>
          <div className="hc-desc">Post help requests or lend a hand to students who need assistance.</div>
          <div className="hc-stat"><span className="dot" /><span>{stats.openHelp}</span> open requests</div>
        </div>
      </div>

      <div className="welcome-footer">
        <div className="welcome-stat"><div className="num">{stats.modules}</div><div className="label">Modules</div></div>
        <div className="welcome-stat"><div className="num">{stats.reviews}</div><div className="label">Reviews</div></div>
        <div className="welcome-stat"><div className="num">{stats.avg}</div><div className="label">Avg Rating</div></div>
      </div>
    </div>
  );
}

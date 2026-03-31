import { useState, useEffect } from 'react';
import { useNavigate } from 'react-router';
import { getModules } from '../api/index';
import { getHelpRequests } from '../api/index';
import { getTutors } from '../api/index';

export default function Home() {
  const navigate = useNavigate();
  const [stats, setStats] = useState({ modules: 0, reviews: 0, avg: '—', tutors: 0, openHelp: 0 });

  useEffect(() => {
    Promise.allSettled([
      getModules(),
      getTutors(),
      getHelpRequests({ status: 'open' }),
    ]).then(([modsRes, tutRes, helpRes]) => {
      const mods = modsRes.status === 'fulfilled' ? (modsRes.value?.data || modsRes.value || []) : [];
      const tutors = tutRes.status === 'fulfilled' ? (tutRes.value?.data || tutRes.value || []) : [];
      const helpOpen = helpRes.status === 'fulfilled' ? (helpRes.value?.data || helpRes.value || []) : [];
      const allReviews = mods.flatMap((m) => m.reviews || []);
      const avg = allReviews.length
        ? (allReviews.reduce((s, r) => s + (r.rating || 0), 0) / allReviews.length).toFixed(1)
        : '—';
      setStats({
        modules: mods.length,
        reviews: allReviews.length,
        avg,
        tutors: Array.isArray(tutors) ? tutors.length : tutors.total || 0,
        openHelp: Array.isArray(helpOpen) ? helpOpen.length : helpOpen.total || 0,
      });
    });
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

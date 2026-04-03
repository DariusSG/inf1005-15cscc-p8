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
      .catch(() => { });
  }, []);

  return (
    <div className="welcome-page">
      <div className="welcome-layout">

        <div className="welcome-left">
          <div className="welcome-hero">
            <div className="welcome-eyebrow">Welcome to SITizens!</div>
            <h1>
              Be Smarter!
              <br />
              Choose Wisely!
              <br />
              Study Better!
            </h1>
            <p className="tagline">
              Ever needed some help knowing which modules to take, or how hard they are? You're not alone!
              <br />
            </p>
            <p className="tagline-mobile">
              With sitizens, you can read honest reviews from students who've taken the modules, find peer tutors, get help when you're stuck, and connect with study groups as well. Be a 
            </p>
            <p className="subtitle-mobile">
              Facing issues navigating the site? Email iloveinf1005@sit.singaporetech.edu.sg for any queries.
            </p>
          </div>

          <div className="welcome-footer">
            <div className="welcome-stat">
              <div className="num">{stats.modules}</div>
              <div className="label">Modules</div>
            </div>
            <div className="welcome-stat">
              <div className="num">{stats.reviews}</div>
              <div className="label">Reviews</div>
            </div>
            <div className="welcome-stat">
              <div className="num">{stats.avg}</div>
              <div className="label">Avg Rating</div>
            </div>
            <div className="welcome-stat">
              <div className="num">{stats.openHelp}</div>
              <div className="label">Open Help</div>
            </div>
          </div>
        </div>

        {/* Right — stacked cards */}
        <div className="home-cards">
          <div className="home-card c-mod" onClick={() => navigate('/modules')}>
            <div className="hc-title">Module Reviews</div>
            <div className="hc-desc">Browse ratings, workload, difficulty and usefulness scores across all SIT faculties. See what other students really think before you enrol.</div>
            <div className="hc-stat"><span className="dot" /><span>{stats.modules}</span> modules</div>
          </div>
          <div className="home-card c-tut" onClick={() => navigate('/tutors')}>
            <div className="hc-title">Tutor Finder</div>
            <div className="hc-desc">Struggling with a module? Find a peer tutor who's already aced it, or list yourself and help others while earning on the side.</div>
            <div className="hc-stat"><span className="dot" /><span>{stats.tutors}</span> tutors listed</div>
          </div>
          <div className="home-card c-hlp" onClick={() => navigate('/help')}>
            <div className="hc-title">Help Requests</div>
            <div className="hc-desc">Post a quick help request when you're stuck on an assignment or concept. Other students can respond — optionally with a bounty reward.</div>
            <div className="hc-stat"><span className="dot" /><span>{stats.openHelp}</span> open requests</div>
          </div>
        </div>

      </div>
    </div>
  );
}

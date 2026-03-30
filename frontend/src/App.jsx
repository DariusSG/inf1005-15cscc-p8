import { useState } from 'react';
import { BrowserRouter, Routes, Route } from 'react-router-dom';
import { Toaster } from 'react-hot-toast';
import { AuthProvider } from './context/AuthContext';
import Sidebar from './components/Sidebar';
import Topbar from './components/Topbar';
import AuthModal from './components/AuthModal';
import Home from './pages/Home';
import Modules from './pages/Modules';
import ModuleDetail from './pages/ModuleDetail';
import Tutors from './pages/Tutors';
import StudyGroups from './pages/StudyGroups';
import Help from './pages/Help';
import Dashboard from './pages/Dashboard';
import Admin from './pages/Admin';

function AppLayout() {
  const [authOpen, setAuthOpen] = useState(false);
  return (
    <div className="app-layout">
      <Sidebar />
      <div className="right-area">
        <Topbar onSignIn={() => setAuthOpen(true)} />
        <div className="content">
          <Routes>
            <Route path="/" element={<Home />} />
            <Route path="/modules" element={<Modules />} />
            <Route path="/modules/:code" element={<ModuleDetail />} />
            <Route path="/tutors" element={<Tutors />} />
            <Route path="/study-groups" element={<StudyGroups />} />
            <Route path="/help" element={<Help />} />
            <Route path="/dashboard" element={<Dashboard />} />
            <Route path="/admin" element={<Admin />} />
          </Routes>
        </div>
      </div>
      {authOpen && <AuthModal onClose={() => setAuthOpen(false)} />}
    </div>
  );
}

export default function App() {
  return (
    // basename="/frontend" matches the Vite base and the /frontend folder on the server
    <BrowserRouter>
      <AuthProvider>
        <AppLayout />
        <Toaster
          position="bottom-right"
          toastOptions={{
            style: {
              background: '#28282f',
              color: '#e4e2ec',
              border: '1px solid rgba(255,255,255,0.06)',
              fontFamily: "'Outfit', sans-serif",
              fontSize: '0.85rem',
            },
            success: { iconTheme: { primary: '#34c77b', secondary: '#28282f' } },
            error: { iconTheme: { primary: '#e05a5a', secondary: '#28282f' } },
          }}
        />
      </AuthProvider>
    </BrowserRouter>
  );
}

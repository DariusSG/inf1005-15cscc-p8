import { useState } from "react";
import { createBrowserRouter } from "react-router";
import { RouterProvider } from "react-router/dom";
import { Toaster } from "react-hot-toast";
import { AuthProvider } from "./context/AuthContext";
import Sidebar from "./components/Sidebar";
import Topbar from "./components/Topbar";
import AuthModal from "./components/AuthModal";
import Home from "./pages/Home";
import Modules from "./pages/Modules";
import ModuleDetail from "./pages/ModuleDetail";
import Tutors from "./pages/Tutors";
import StudyGroups from "./pages/StudyGroups";
import Help from "./pages/Help";
import Dashboard from "./pages/Dashboard";
import Admin from "./pages/Admin";
import {NotFound, ErrorBoundaryPage} from "./pages/Error";

function AppLayout({ children }) {
  const [authOpen, setAuthOpen] = useState(false);

  return (
    <div className="app-layout">
      <Sidebar />
      <div className="right-area">
        <Topbar onSignIn={() => setAuthOpen(true)} />
        <div className="content">{children}</div>
      </div>
      {authOpen && <AuthModal onClose={() => setAuthOpen(false)} />}
    </div>
  );
}

const routes = [
  {
    path: "/",
    element: <AppLayout><Home /></AppLayout>,
    errorElement: <AppLayout><ErrorBoundaryPage /></AppLayout>,
  },
  {
    path: "/modules",
    element: <AppLayout><Modules /></AppLayout>,
    errorElement: <AppLayout><ErrorBoundaryPage /></AppLayout>,
  },
  {
    path: "/modules/:code",
    element: <AppLayout><ModuleDetail /></AppLayout>,
    errorElement: <AppLayout><ErrorBoundaryPage /></AppLayout>,
  },
  {
    path: "/tutors",
    element: <AppLayout><Tutors /></AppLayout>,
  },
  {
    path: "/study-groups",
    element: <AppLayout><StudyGroups /></AppLayout>,
  },
  {
    path: "/help",
    element: <AppLayout><Help /></AppLayout>,
  },
  {
    path: "/dashboard",
    element: <AppLayout><Dashboard /></AppLayout>,
  },
  {
    path: "/admin",
    element: <AppLayout><Admin /></AppLayout>,
  },
  {
    path: "*",
    element: <AppLayout><NotFound /></AppLayout>,
  },
];

export default function App() {
  const router = createBrowserRouter(routes, {
    future: {
      v7_relativeSplatPath: true,
      v7_startTransition: true,
    },
  });

  return (
    <AuthProvider>
      <RouterProvider router={router} />
      <Toaster
        position="bottom-right"
        toastOptions={{
          style: {
            background: "#28282f",
            color: "#e4e2ec",
            border: "1px solid rgba(255,255,255,0.06)",
            fontFamily: "'Outfit', sans-serif",
            fontSize: "0.85rem",
          },
          success: { iconTheme: { primary: "#34c77b", secondary: "#28282f" } },
          error: { iconTheme: { primary: "#e05a5a", secondary: "#28282f" } },
        }}
      />
    </AuthProvider>
  );
}
import { createContext, useContext, useState, useEffect, useCallback } from 'react';
import { getMe, postLogin, postLogout, postRegisterRequest, postRegisterComplete } from '../api/auth';
import { setAccessToken, clearAccessToken } from '../api/client';
import toast from 'react-hot-toast';

const AuthContext = createContext(null);

export function AuthProvider({ children }) {
  const [user, setUser] = useState(null);
  const [authLoading, setAuthLoading] = useState(true);

  // On mount — try to restore session via /auth/me (uses httpOnly cookie refresh)
  useEffect(() => {
    getMe()
      .then((u) => setUser(u))
      .catch(() => setUser(null))
      .finally(() => setAuthLoading(false));
  }, []);

  const login = useCallback(async (email, password) => {
    const data = await postLogin(email, password);
    setAccessToken(data.access_token);
    const me = await getMe();
    setUser(me);
    return me;
  }, []);

  const logout = useCallback(async () => {
    try { await postLogout(); } catch (_) {}
    clearAccessToken();
    setUser(null);
    toast.success('Signed out');
  }, []);

  const requestRegister = useCallback((email) => postRegisterRequest(email), []);

  const completeRegister = useCallback(async (token, name, password) => {
    const data = await postRegisterComplete(token, name, password);
    if (data.access_token) {
      setAccessToken(data.access_token);
      const me = await getMe();
      setUser(me);
      return me;
    }
    return null;
  }, []);

  return (
    <AuthContext.Provider value={{
      user,
      authLoading,
      isAdmin: user?.role === 'admin',
      login,
      logout,
      requestRegister,
      completeRegister,
    }}>
      {children}
    </AuthContext.Provider>
  );
}

export const useAuth = () => useContext(AuthContext);

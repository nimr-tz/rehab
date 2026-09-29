import React, { createContext, useCallback, useContext, useEffect, useMemo, useState } from 'react';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { login as apiLogin, logout as apiLogout, getCurrentUser } from '../api/auth';
import { setAuthErrorHandler } from '../api/http';

const TOKEN_KEY = '@auth_token';
const STAFF_ROLES = ['admin', 'registration_officer'];

const AuthContext = createContext(null);

export const isStaffUser = (user) =>
  !!user && (
    STAFF_ROLES.includes(user.role) ||
    (Array.isArray(user.roles) && user.roles.some((role) => STAFF_ROLES.includes(role)))
  );

export function AuthProvider({ children }) {
  const [user, setUser] = useState(null);
  const [isLoading, setIsLoading] = useState(true);

  const clearSession = useCallback(async () => {
    try { await AsyncStorage.removeItem(TOKEN_KEY); } catch (_) {}
    setUser(null);
  }, []);

  useEffect(() => {
    setAuthErrorHandler(() => setUser(null));

    (async () => {
      try {
        const token = await AsyncStorage.getItem(TOKEN_KEY);
        if (token) {
          setUser(await getCurrentUser());
        }
      } catch (_) {
        await clearSession();
      } finally {
        setIsLoading(false);
      }
    })();

    return () => setAuthErrorHandler(null);
  }, [clearSession]);

  const signIn = useCallback(async (email, password) => {
    const { token, user: signedInUser } = await apiLogin(email, password);

    if (!isStaffUser(signedInUser)) {
      // Only registration staff may use this app; do not keep the token.
      try { await AsyncStorage.setItem(TOKEN_KEY, token); await apiLogout(); } catch (_) {}
      await clearSession();
      const error = new Error('This app is for registration staff only.');
      error.code = 'NOT_STAFF';
      throw error;
    }

    await AsyncStorage.setItem(TOKEN_KEY, token);
    setUser(signedInUser);
    return signedInUser;
  }, [clearSession]);

  const signOut = useCallback(async () => {
    try { await apiLogout(); } catch (_) {}
    await clearSession();
  }, [clearSession]);

  const value = useMemo(() => ({ user, isLoading, signIn, signOut }), [user, isLoading, signIn, signOut]);

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export const useAuth = () => {
  const context = useContext(AuthContext);
  if (!context) throw new Error('useAuth must be used inside AuthProvider');
  return context;
};

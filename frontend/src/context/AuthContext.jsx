// ============================================
// context/AuthContext.jsx - Gestion Auth
// ============================================

import { createContext, useContext, useState, useEffect } from 'react';
import authService from '../services/authService';

const AuthContext = createContext(null);

export const AuthProvider = ({ children }) => {
  const [user, setUser] = useState(null);
  const [loading, setLoading] = useState(true);

  // Charger user au montage
  useEffect(() => {
    const currentUser = authService.getCurrentUser();
    setUser(currentUser);
    setLoading(false);
  }, []);

  /**
   * Connexion
   */
  const login = async (username, password) => {
    setLoading(true);
    
    // UTILISER loginMock pour test, login pour production

    const result = await authService.login(username, password);
    
    if (result.success) {
      console.log(result.user);
      setUser(result.user);
    }
    
    setLoading(false);
    return result;
  };

  /**
   * Déconnexion
   */
  const logout = async () => {
    setLoading(true);
    await authService.logout();
    setUser(null);
    setLoading(false);
  };

  /**
   * Vérifier authentification
   */
  const isAuthenticated = () => {
    return !!user && authService.isAuthenticated();
  };

  /**
   * Vérifier rôle
   */
  const hasRole = (role) => {
    return user?.role === role;
  };

  const value = {
    user,
    loading,
    login,
    logout,
    isAuthenticated,
    hasRole,
    isAdmin: () => hasRole('admin'),
  };

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
};

// Hook personnalisé
export const useAuth = () => {
  const context = useContext(AuthContext);
  if (!context) {
    throw new Error('useAuth doit être utilisé dans AuthProvider');
  }
  return context;
};



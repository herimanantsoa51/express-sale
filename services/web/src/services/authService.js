// ============================================
// services/authService.js - Service Auth
// ============================================

import api from './api';

const authService = {
  /**
   * Connexion utilisateur
   * @param {string} username
   * @param {string} password 
   * @returns {Promise}
   */
  async login(username, password) {
    try {
      const response = await api.post('/auth/login', {
        username,
        password,
      });
      
      const { token, user } = response.data;
      
      console.log("token", token);
      // Stocker token et user
      localStorage.setItem('auth_token', token);
      localStorage.setItem('user', JSON.stringify(user));
      
      return { success: true, user, token };
    } catch (error) {
      const message = error.response?.data?.message || 'Erreur de connexion';
      return { success: false, message };
    }
  },

  /**
   * Déconnexion utilisateur
   * @returns {Promise}
   */
  async logout() {
    try {
      await api.post('/auth/logout');
    } catch (error) {
      console.error('Erreur logout:', error);
    } finally {
      // Toujours nettoyer le localStorage
      localStorage.removeItem('auth_token');
      localStorage.removeItem('user');
    }
  },

  /**
   * Récupérer utilisateur connecté
   * @returns {Object|null}
   */
  getCurrentUser() {
    const userStr = localStorage.getItem('user');
    if (userStr) {
      try {
        return JSON.parse(userStr);
      } catch (error) {
        return null;
      }
    }
    return null;
  },

  /**
   * Vérifier si utilisateur connecté
   * @returns {boolean}
   */
  isAuthenticated() {
    return !!localStorage.getItem('auth_token');
  },

  /**
   * Vérifier le rôle utilisateur
   * @param {string} role 
   * @returns {boolean}
   */
  hasRole(role) {
    const user = this.getCurrentUser();
    return user?.role === role;
  },

  /**
   * Vérifier si admin
   * @returns {boolean}
   */
  isAdmin() {
    return this.hasRole('admin');
  },

  /**
   * Récupérer le token
   * @returns {string|null}
   */
  getToken() {
    return localStorage.getItem('auth_token');
  },


 
};

export default authService;
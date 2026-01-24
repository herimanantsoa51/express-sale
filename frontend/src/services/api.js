// ============================================
// services/api.js - Configuration Axios
// ============================================

import axios from 'axios';


/**
 * const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL,
  timeout: 10000,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
});

 */
// Configuration de base
const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL || 'http://localhost:8000/api',
  timeout: 10000,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
});
console.log('API URL:', api.defaults.baseURL);
// Intercepteur requête : ajouter token
api.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('auth_token');
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  (error) => {
    return Promise.reject(error);
  }
);

// Intercepteur réponse : gérer erreurs
api.interceptors.response.use(
  (response) => {
    return response;
  },
  (error) => {
    if (error.response) {
      // Erreur 401 : token invalide ou expiré
      if (error.response.status === 401) {
        localStorage.removeItem('auth_token');
        localStorage.removeItem('user');
        window.location.href = '/login';
      }
      
      // Erreur 403 : accès interdit
      if (error.response.status === 403) {
        console.error('Accès refusé');
      }
      
      // Erreur 500 : erreur serveur
      if (error.response.status >= 500) {
        console.error('Erreur serveur');
      }
    } else if (error.request) {
      // Pas de réponse du serveur
      console.error('Serveur inaccessible');
    }
    
    return Promise.reject(error);
  }
);

export default api;



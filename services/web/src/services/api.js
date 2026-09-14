// ============================================
// services/api.js - Configuration Axios (Simplifié)
// ============================================

import axios from 'axios';

/**
 * Détecter l'URL de l'API basée sur l'hôte actuel
 * Si on est sur 192.168.0.128:3000, l'API sera sur 192.168.0.128:8000
 */
const getApiUrl = () => {
  const currentHost = window.location.hostname;
  const apiPort = '8000'; // Port API fixe
  
  // Si localhost, utiliser localhost
  if (currentHost === 'localhost' || currentHost === '127.0.0.1') {
    return 'http://localhost:8000/api';
  }
  
  // Sinon, utiliser l'IP actuelle
  return `http://${currentHost}:${apiPort}/api`;
};

// Configuration de base
const api = axios.create({
  baseURL: getApiUrl(),
  timeout: 10000,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
});

console.log('🌐 API URL:', api.defaults.baseURL);

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
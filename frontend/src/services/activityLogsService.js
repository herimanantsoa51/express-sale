import api from './api';

const API_URL = '/activity-logs';

const activityLogsService = {
  /**
   * Récupère la liste paginée des logs avec filtres
   */
  getLogs: async (params = {}) => {
    const response = await api.get(API_URL, { params });
    return response.data;
  },

  /**
   * Récupère le détail d'un log spécifique
   */
  getLogById: async (id) => {
    const response = await api.get(`${API_URL}/${id}`);
    return response.data;
  },

  /**
   * Récupère les logs liés à un modèle spécifique
   */
  getLogsByModel: async (params) => {
    const response = await api.get(`${API_URL}/by-model`, { params });
    return response.data;
  },

  /**
   * Récupère uniquement les logs d'échecs
   */
  getFailures: async (params = {}) => {
    const response = await api.get(`${API_URL}/failures`, { params });
    return response.data;
  },

  /**
   * Récupère les statistiques globales
   */
  getStatistics: async (params = {}) => {
    const response = await api.get(`${API_URL}/statistics`, { params });
    return response.data;
  },

  /**
   * Récupère les actions groupées par catégorie
   */
  getActionsByCategory: async () => {
    const response = await api.get(`${API_URL}/actions-by-category`);
    return response.data;
  },

  /**
   * Supprime les logs entre deux dates
   */
  deleteBetweenDates: async (data) => {
    const response = await api.post(`${API_URL}/delete-between-dates`, data);
    return response.data;
  },

  /**
   * Supprime les logs plus anciens qu'une date
   */
  deleteOlderThan: async (data) => {
    const response = await api.post(`${API_URL}/delete-older-than`, data);
    return response.data;
  },

  /**
   * Supprime les logs par statut
   */
  deleteByStatus: async (data) => {
    const response = await api.post(`${API_URL}/delete-by-status`, data);
    return response.data;
  },

  /**
   * Nettoie automatiquement les vieux logs
   */
  autoCleanup: async (data = {}) => {
    const response = await api.post(`${API_URL}/auto-cleanup`, data);
    return response.data;
  }
};

export default activityLogsService;
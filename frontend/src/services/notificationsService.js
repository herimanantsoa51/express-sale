// ============================================
// services/notificationsService.js
// ============================================

import api from './api';

const notificationsService = {
  /**
   * Récupère la liste des notifications
   */
  getNotifications: async (params = {}) => {
    const queryParams = new URLSearchParams(params).toString();
    const response = await api.get(`/notifications${queryParams ? `?${queryParams}` : ''}`);
    return response.data;
  },

  /**
   * Récupère les compteurs de notifications
   */
  getCount: async () => {
    const response = await api.get('/notifications/count');
    return response.data;
  },

  /**
   * Marque une notification comme lue
   */
  markAsRead: async (id) => {
    const response = await api.patch(`/notifications/${id}/read`);
    return response.data;
  },

  /**
   * Marque toutes les notifications comme lues
   */
  markAllAsRead: async () => {
    const response = await api.post('/notifications/mark-all-read');
    return response.data;
  },

  /**
   * Supprime (dismiss) une notification
   */
  dismiss: async (id) => {
    const response = await api.delete(`/notifications/${id}/dismiss`);
    return response.data;
  },

  /**
   * Supprime toutes les notifications d'un type
   */
  dismissByType: async (type) => {
    const response = await api.post('/notifications/dismiss-by-type', { type });
    return response.data;
  },

  /**
   * Récupère les préférences de notifications
   */
  getPreferences: async () => {
    const response = await api.get('/notifications/preferences');
    return response.data;
  },

  /**
   * Met à jour une préférence de notification
   */
  updatePreference: async (id, data) => {
    const response = await api.put(`/notifications/preferences/${id}`, data);
    return response.data;
  },

  /**
   * Génère les notifications (admin uniquement)
   */
  generate: async (type = null) => {
    const response = await api.post('/notifications/generate', { type });
    return response.data;
  }
};

export default notificationsService;
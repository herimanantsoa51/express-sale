import api from './api';

const reservationsService = {
  /**
   * Récupère la liste des réservations avec filtres
   */
  getReservations: async (params = {}) => {
    const response = await api.get('/reservations', { params });
    return response.data;
  },

  /**
   * Récupère une réservation par son ID
   */
  getReservationById: async (id) => {
    const response = await api.get(`/reservations/${id}`);
    return response.data;
  },

  /**
   * Complète une réservation (paiement final)
   */
  completeReservation: async (id, data) => {
    const response = await api.post(`/reservations/${id}/complete`, data);
    return response.data;
  },

  /**
   * Annule une réservation
   */
  cancelReservation: async (id, reason) => {
    const response = await api.post(`/reservations/${id}/cancel`, { reason });
    return response.data;
  },

  /**
   * Exporte les réservations au format CSV
   */
  exportCSV: async (params = {}) => {
    const response = await api.get('/reservations/export/csv', { 
      params,
      responseType: 'blob'
    });
    return response.data;
  },

  /**
   * Exporte les réservations au format PDF
   */
  exportPDF: async (params = {}) => {
    const response = await api.get('/reservations/export/pdf', { 
      params,
      responseType: 'blob'
    });
    return response.data;
  }
};

export default reservationsService;

import api from './api';

const locationService = {
  /**
   * Obtenir toutes les locations avec statistiques
   * GET /api/locations
   */
  getAll: async (filters = {}) => {
    const response = await api.get('/locations', { params: filters });
    return response.data;
  },

  /**
   * Obtenir une location par son ID avec détails
   * GET /api/locations/{id}
   */
  getById: async (id) => {
    const response = await api.get(`/locations/${id}`);
    return response.data;
  },

  /**
   * Créer une nouvelle location
   * POST /api/locations
   */
  create: async (data) => {
    const response = await api.post('/locations', data);
    return response.data;
  },

  /**
   * Mettre à jour une location
   * PUT /api/locations/{id}
   */
  update: async (id, data) => {
    const response = await api.put(`/locations/${id}`, data);
    return response.data;
  },

  /**
   * Supprimer une location
   * DELETE /api/locations/{id}
   */
  delete: async (id) => {
    await api.delete(`/locations/${id}`);
  },

  /**
   * Vérifier si une location peut être supprimée
   * GET /api/locations/{id}/can-delete
   */
  canDelete: async (id) => {
    const response = await api.get(`/locations/${id}/can-delete`);
    return response.data;
  },

  /**
   * Obtenir les variantes d'une location avec pagination
   * GET /api/locations/{id}/variants-detail
   */
  getVariantsDetail: async (id, params = {}) => {
    const response = await api.get(`/locations/${id}/variants-detail`, { params });
    return response.data;
  },

  /**
   * Obtenir les mouvements de stock d'une location
   * GET /api/locations/{id}/stock-movements
   */
  getStockMovements: async (id, params = {}) => {
    const response = await api.get(`/locations/${id}/stock-movements`, { params });
    return response.data;
  },

  /**
   * Obtenir les statistiques détaillées d'une location
   * GET /api/locations/{id}/statistics
   */
  getStatistics: async (id) => {
    const response = await api.get(`/locations/${id}/statistics`);
    return response.data;
  },

  /**
   * Obtenir uniquement les locations actives
   * GET /api/locations-active
   */
  getActive: async () => {
    const response = await api.get('/locations-active');
    return response.data;
  },

  /**
   * Obtenir la liste des entrepôts distincts
   * GET /api/locations-warehouses
   */
  getWarehouses: async () => {
    const response = await api.get('/locations-warehouses');
    return response.data;
  },

  /**
   * Obtenir les réservations actives d'une location
   * GET /api/locations/{id}/reservations
   */
  getActiveReservations: async (id, params = {}) => {
    const response = await api.get(`/locations/${id}/reservations`, { params });
    return response.data;
  }
};

export default locationService;
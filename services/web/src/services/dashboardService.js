// ============================================
// src/services/dashboardService.js
// ============================================

import api from './api';

const dashboardService = {
  /**
   * Récupère les données du dashboard
   * @param {Object} params - Paramètres de la requête
   * @param {string} params.date - Date au format YYYY-MM-DD
   * @param {string} params.period - Période (7days, 1month, 2months, 3months)
   * @returns {Promise} Données du dashboard
   */
    // src/services/dashboardService.js
  getDashboardData: async (params) => {
    const queryParams = new URLSearchParams({
      date: params.date,
      period: params.period,
      top_products_sort: params.top_products_sort || 'revenue'
    });
    
    const response = await api.get(`/dashboard?${queryParams}`);
    return response.data;
  }
};

export default dashboardService;
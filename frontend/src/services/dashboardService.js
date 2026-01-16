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
  getDashboardData: async (params) => {
    console.log('Dashboard Service - Fetching with params:', params);
    
    const response = await api.get("/dashboard?date=" + params.date + "&period=" + params.period);
    
    console.log('Dashboard Service - Response received:', response.data);
    
    return response.data;
  }
};

export default dashboardService;
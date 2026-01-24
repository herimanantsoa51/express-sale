// ============================================
// src/services/immediateSaleService.js
// Service dédié aux ventes immédiates
// ============================================

import api from './api';

const immediateSaleService = {
  /**
   * Récupère la liste des ventes immédiates avec filtres et pagination
   * @param {Object} params - Paramètres de recherche
   * @param {string} params.from_date - Date de début (format: YYYY-MM-DD HH:mm:ss)
   * @param {string} params.to_date - Date de fin (format: YYYY-MM-DD HH:mm:ss)
   * @param {number} params.user_id - ID du vendeur (optionnel)
   * @param {number} params.per_page - Nombre d'éléments par page
   * @param {string} params.search - Terme de recherche (optionnel)
   * @param {number} params.page - Numéro de page (optionnel)
   * @returns {Promise<Object>} Liste des ventes avec métadonnées de pagination
   */
  getList: async (params = {}) => {
    const response = await api.get('/sales/immediate', { params });
    return response.data;
  },

  /**
   * Récupère le détail d'une vente immédiate
   * @param {number} id - ID de la vente
   * @returns {Promise<Object>} Détails complets de la vente
   */
  getDetail: async (id) => {
    const response = await api.get(`/sales/immediate/${id}`);
    return response.data;
  },
  cancelImmediateSale: async(saleId)=>{
    const response = await api.post(`/sales/immediate/cancel/${saleId}`);
    return response.data;
  }
};

export default immediateSaleService;
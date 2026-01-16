// services/creditService.js
import api from './api';

const creditService = {
  /**
   * Récupère la liste des crédits avec filtres
   * @param {Object} params - Paramètres de filtrage
   * @returns {Promise} Liste paginée des crédits
   */
  getCredits: async (params = {}) => {
    try {
      const response = await api.get('/credits', { params });
      return response.data;
    } catch (error) {
      console.error('Erreur lors de la récupération des crédits:', error);
      throw error;
    }
  },

  /**
   * Récupère le détail d'un crédit avec ses échéances
   * @param {number} id - ID du crédit
   * @returns {Promise} Détail du crédit
   */
  getCreditDetail: async (id) => {
    try {
      const response = await api.get(`/credits/${id}`);
      return response.data;
    } catch (error) {
      console.error(`Erreur lors de la récupération du crédit ${id}:`, error);
      throw error;
    }
  },

  /**
   * Enregistre un paiement pour une échéance
   * @param {number} creditId - ID du crédit
   * @param {number} installmentId - ID de l'échéance
   * @param {Object} data - Données du paiement (amount, account_id, notes)
   * @returns {Promise} Échéance mise à jour
   */
  payInstallment: async (creditId, installmentId, data) => {
    try {
      const response = await api.post(
        `/credits/${creditId}/installments/${installmentId}/pay`,
        data
      );
      return response.data;
    } catch (error) {
      console.error('Erreur lors du paiement:', error);
      throw error;
    }
  }
};

export default creditService;
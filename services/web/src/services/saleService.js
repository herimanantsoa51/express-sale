/* ============================================
   SALE SERVICE - Gestion des ventes
   ============================================ */

import api from './api';

const saleService = {
  /**
   * Crée une vente immédiate
   * @param {Object} data - Données de la vente
   * @returns {Promise<Object>} Vente créée
   */
  createImmediate: async (data) => {
    const response = await api.post('/sales/immediate', data);
    return response.data;
  },

  /**
   * Crée une vente à crédit
   * @param {Object} data - Données de la vente à crédit
   * @returns {Promise<Object>} Vente à crédit créée
   */
  createCredit: async (data) => {
    try {
      console.log("Creating credit sale with data:", data);
      const response = await api.post('/sales/credit', data);
      console.log("Response from creating credit sale:", response.data);
      return response.data;
    } catch (error) {
        console.log(error);
      
    }
   
  },

  /**
   * Crée une réservation
   * @param {Object} data - Données de la réservation
   * @returns {Promise<Object>} Réservation créée
   */
  createReservation: async (data) => {
    try {
      console.log("Creating reservation with data:", data);
      const response = await api.post('/sales/reservation', data);
      return response.data;
    } catch (error) {
      console.log(error);
    }
    
  },

  /**
   * Recherche de clients pour la vente
   * @param {string} search - Terme de recherche
   * @returns {Promise<Object>} Liste de clients
   */
  searchCustomers: async (search) => {
    const response = await api.get('/customers/search-for-sale', {
      params: { search }
    });
    return response.data;
  },

  /**
   * Récupère les comptes cash et mobile money
   * @returns {Promise<Object>} Liste des comptes
   */
  getCashAccounts: async () => {
    const response = await api.get('/accounts/cash-mobile-money');
    return response.data;
  },

  
};

export default saleService;

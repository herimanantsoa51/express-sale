/* ============================================
   CUSTOMER SERVICE - Encapsulation API
   ============================================ */

   import api from './api';

   const customerService = {
     /**
      * Récupère la liste des clients avec filtres optionnels
      * @param {Object} params - Paramètres de recherche (search, status, reliability, page)
      * @returns {Promise<Object>} Liste paginée de clients
      */
     getAll: async (params = {}) => {
       const response = await api.get('/customers', { params });
       return response.data;
     },
   
     /**
      * Récupère un client par son ID
      * @param {number} id - ID du client
      * @param {Object} params - Paramètres optionnels (with_credit_info)
      * @returns {Promise<Object>} Détails du client
      */
     getById: async (id, params = {}) => {
       const response = await api.get(`/customers/${id}`, { params });
       return response.data;
     },
   
     /**
      * Crée un nouveau client
      * @param {Object} data - Données du client
      * @returns {Promise<Object>} Client créé
      */
     create: async (data) => {
       const response = await api.post('/customers', data);
       return response.data;
     },
   
     /**
      * Met à jour un client existant
      * @param {number} id - ID du client
      * @param {Object} data - Données à mettre à jour
      * @returns {Promise<Object>} Client mis à jour
      */
     update: async (id, data) => {
       const response = await api.put(`/customers/${id}`, data);
       return response.data;
     },
   
     /**
      * Récupère les ventes immédiates d'un client
      * @param {number} id - ID du client
      * @param {Object} params - Paramètres de pagination
      * @returns {Promise<Object>} Liste paginée de ventes
      */
     getSalesImmediate: async (id, params = {}) => {
       const response = await api.get(`/customers/${id}/sales-immediate`, { params });
       return response.data;
     },
   
     /**
      * Récupère les crédits d'un client
      * @param {number} id - ID du client
      * @param {Object} params - Paramètres de pagination
      * @returns {Promise<Object>} Liste paginée de crédits
      */
     getCredits: async (id, params = {}) => {
       const response = await api.get(`/customers/${id}/credits`, { params });
       return response.data;
     },
   
     /**
      * Récupère les réservations d'un client
      * @param {number} id - ID du client
      * @param {Object} params - Paramètres de pagination
      * @returns {Promise<Object>} Liste paginée de réservations
      */
     getReservations: async (id, params = {}) => {
       const response = await api.get(`/customers/${id}/reservations`, { params });
       return response.data;
     }
   };
   
   export default customerService;
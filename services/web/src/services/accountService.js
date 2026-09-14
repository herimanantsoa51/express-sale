import api from './api';

const accountService = {
  // Obtenir tous les comptes
  getAll: async (params = {}) => {
    const response = await api.get('/accounts', { params });
    return response.data;
  },

  // Obtenir un compte par ID
  getById: async (id) => {
    const response = await api.get(`/accounts/${id}`);
    return response.data;
  },
  getAccountTransactions: async (id, params = {}) => {
    const response = await api.get(`/accounts/${id}/transactions`, { params });
    return response.data;
  },
  getTypes: async ()=>{
    const response  =  await api.get('/account-types');
    return response.data;
  },
  storeAccount: async (data)=>{
    const response = await api.post('/accounts',data);
    return response.data;
  },
  updateAccount: async (id,data)=>{
    const response =await api.put(`/accounts/${id}`,data);
    return response.data;
  },
  transferBeetweenAccounts: async (data)=>{
    const response = await api.post('/transactions/transfer',data);
    return response.data;
  },
  storeOperationnalTransaction: async (data) => {
    const response = await api.post('/transactions/expense-operational', data);
    return response.data;
  },


   /**
   * Récupère la liste des comptes cash et mobile money
   * @returns {Promise} Liste des comptes disponibles
   */
   getCashAccounts: async () => {
    try {
      const response = await api.get('/accounts/cash-mobile-money');
      return response.data;
    } catch (error) {
      console.error('Erreur lors de la récupération des comptes:', error);
      throw error;
    }
  }
};

export default accountService;
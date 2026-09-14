import api from './api';

const cashCountService = {
  /**
   * Récupère la liste des cash counts
   */
  getCashCounts: async (data) => {
    const response = await api.get('/cash-counts');
    return response.data;
  },
    /**
     * Récupère un cash count par son ID
     */
    getCashCountById: async (id) => {
        const response = await api.get(`/cash-counts/${id}`);
        return response.data;
        },

    storeCashCount: async (cashCountData) => {
        const response = await api.post('/cash-counts', cashCountData);
        return response.data;
    },

    updateCashCount: async (id, cashCountData) => {
        const response = await api.put(`/cash-counts/${id}`, cashCountData);
        return response.data;
    },
};

export default cashCountService;
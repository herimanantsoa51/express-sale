// services/plannedExpenseService.js
import api from './api';

const plannedExpenseService = {
    getAll: async (params = {}) => {
        const response = await api.get('/planned-expenses', { params });
        return response.data;
    },

    getStats: async () => {
        const response = await api.get('/planned-expenses/stats');
        return response.data;
    },

    getById: async (id) => {
        const response = await api.get(`/planned-expenses/${id}`);
        return response.data;
    },

    create: async (data) => {
        const response = await api.post('/planned-expenses', data);
        return response.data;
    },

    update: async (id, data) => {
        const response = await api.put(`/planned-expenses/${id}`, data);
        return response.data;
    },

    markPaid: async (id) => {
        const response = await api.post(`/planned-expenses/${id}/mark-paid`);
        return response.data;
    },

    delete: async (id) => {
        const response = await api.delete(`/planned-expenses/${id}`);
        return response.data;
    },
    // Dans plannedExpenseService.js, ajouter:

    getTransactions: async (id, params = {}) => {
        const response = await api.get(`/planned-expenses/${id}/transactions`, { params });
        return response.data;
    }
};

export default plannedExpenseService;
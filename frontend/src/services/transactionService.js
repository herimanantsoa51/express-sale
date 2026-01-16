import api from './api'


const transactionService = {
    getTransaction: async (id) => {
        const response = await api.get(`/transactions/${id}`);
        return response.data;
    },
    cancelTransaction: async (id) => {
        const response = await api.post(`/transactions/${id}/cancel`);
        return response.data;
    }
}

export default transactionService;
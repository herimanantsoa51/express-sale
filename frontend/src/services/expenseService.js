import api from './api' 


const expenseService = {
    getExpenseCategories: async () => {
        const response = await api.get('/expense-categories');
        return response.data;
    },
    geExpenseTransactions: async (params = {}) => {
        const response = await api.get('/transactions/expense-operational', { params });
        return response.data;
    },
    storeExpenseCatgory: async (data) => {
        const response = await api.post('/expense-categories', data);
        return response.data;
    },
    storeOperationalTransaction: async (data) => {
        const response = await api.post('/transactions/expense-operational', data);
        return response.data;
    }
}

export default expenseService;
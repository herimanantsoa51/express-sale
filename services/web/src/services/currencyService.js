import api from './api';

const currencyService = {
  // Obtenir le taux actuel
  getCurrent: async () => {
    const response = await api.get('/currency-rates/current');
    return response.data;
  },

  // Convertir un montant
  convert: async (data) => {
    const response = await api.post('/currency-rates/convert', data);
    return response.data;
  },

  getAll: async (params = {}) => {
    const response = await api.get('/currency-rates', { params });
    return response.data;
  },
  storeCurrency:async (data)=>{
    const response = await api.post('/currency-rates',data);
    return response.data;
  },
  updateCurrency: async (id,data)=>{
    console.log("updating:",id,data)
    const response = await api.put(`/currency-rates/${id}`,data);
    return response.data;
  }
};

export default currencyService;
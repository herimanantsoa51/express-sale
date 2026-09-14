import api from './api';

const stockPaymentService = {
  // Payer le fournisseur
  paySupplier: async (data) => {
    const response = await api.post('/stock-payments/supplier', data);
    return response.data;
  },

  // Payer le transitaire
  payFreight: async (data) => {
    const response = await api.post('/stock-payments/freight', data);
    return response.data;
  },

  // Paiement complet (fournisseur + transitaire)
  payComplete: async (data) => {
    const response = await api.post('/stock-payments/complete', data);
    return response.data;
  },

  // Obtenir les transactions d'une réception
  getReceiptTransactions: async (receiptId) => {
    const response = await api.get(`/stock-payments/receipt/${receiptId}`);
    return response.data;
  }
};

export default stockPaymentService;
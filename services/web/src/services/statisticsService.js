// src/services/statisticsService.js - VERSION CORRIGÉE
import api from './api';

const statisticsService = {
  overviewSale: async (params = {}) => {
    const response = await api.get('/statistics/sales/overview', { params });
    return response.data;
  },

  timeline: async (startDate, endDate, grouping = 'day') => {
    const response = await api.get('/statistics/sales/timeline', {
      params: { 
        start_date: startDate, 
        end_date: endDate, 
        grouping 
      }
    });
    return response.data;
  },

  topProducts: async (params = {}) => {
    // Le backend attend start_date/end_date, pas period
    const cleanParams = cleanDateParams(params);
    const response = await api.get('/statistics/sales/top-products', { params: cleanParams });
    return response.data;
  },

  byCategory: async (params = {}) => {
    const cleanParams = cleanDateParams(params);
    const response = await api.get('/statistics/sales/by-category', { params: cleanParams });
    return response.data;
  },

  bySeller: async (params = {}) => {
    const cleanParams = cleanDateParams(params);
    const response = await api.get('/statistics/sales/by-seller', { params: cleanParams });
    return response.data;
  },

  byPaymentMethod: async (params = {}) => {
    const cleanParams = cleanDateParams(params);
    const response = await api.get('/statistics/sales/by-payment-method', { params: cleanParams });
    return response.data;
  },

  discounts: async (params = {}) => {
    const cleanParams = cleanDateParams(params);
    const response = await api.get('/statistics/sales/discounts', { params: cleanParams });
    return response.data;
  },

  credits: async (params = {}) => {
    const cleanParams = cleanDateParams(params);
    const response = await api.get('/statistics/sales/credits', { params: cleanParams });
    return response.data;
  },

  reservations: async (params = {}) => {
    const cleanParams = cleanDateParams(params);
    const response = await api.get('/statistics/sales/reservations', { params: cleanParams });
    return response.data;
  },
   // ========== FINANCIAL STATISTICS (Nouvelles) ==========
  
   financialDashboard: async (params) => {
    const response = await api.get('/statistics/financial/dashboard', { params });
    return response.data;
  },

  financialTimeline: async (startDate, endDate, grouping) => {
    const response = await api.get('/statistics/financial/timeline', {
      params: { start_date: startDate, end_date: endDate, grouping }
    });
    return response.data;
  },

  profitsOverview: async (params) => {
    const response = await api.get('/statistics/financial/profits', { params });
    return response.data;
  },

  expensesOverview: async (params) => {
    const response = await api.get('/statistics/financial/expenses', { params });
    return response.data;
  },

  lossesOverview: async (params) => {
    const response = await api.get('/statistics/financial/losses', { params });
    return response.data;
  },
};



// Helper pour nettoyer les paramètres de date
const cleanDateParams = (params) => {
  const { period, ...otherParams } = params;
  
  // Si period est fourni, on ne doit PAS envoyer start_date/end_date pour overview
  // Mais pour les autres endpoints, on doit convertir period en dates réelles
  if (period && !otherParams.start_date && !otherParams.end_date) {
    // Calculer les dates basées sur le period
    const dates = calculateDatesFromPeriod(period);
    return {
      ...otherParams,
      start_date: dates.startDate,
      end_date: dates.endDate
    };
  }
  
  return params;
};

// Calculer les dates à partir d'un period
const calculateDatesFromPeriod = (period) => {
  const today = new Date();
  const startDate = new Date();
  
  switch(period) {
    case 'today':
      startDate.setHours(0, 0, 0, 0);
      break;
    case 'week':
      startDate.setDate(startDate.getDate() - 7);
      break;
    case 'month':
      startDate.setMonth(startDate.getMonth() - 1);
      break;
    case 'year':
      startDate.setFullYear(startDate.getFullYear() - 1);
      break;
    default:
      // Par défaut: 1 mois
      startDate.setMonth(startDate.getMonth() - 1);
  }
  
  return {
    startDate: startDate.toISOString().split('T')[0],
    endDate: today.toISOString().split('T')[0]
  };
};

export default statisticsService;
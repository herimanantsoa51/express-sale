// ============================================
// services/freightForwarderService.js
// ============================================

import api from './api';

const freightForwarderService = {
  // ========== LISTE TRANSITAIRES ==========
  
  async getFreightForwarders(params = {}) {
    const cleanParams = Object.fromEntries(
      Object.entries(params).filter(([_, value]) => {
        if (typeof value === 'boolean') return true;
        return value !== '' && value !== null && value !== undefined;
      })
    );
    
    const response = await api.get('/freight-forwarders', { params: cleanParams });
    return response.data;
  },

  // ========== DÉTAILS TRANSITAIRE ==========
  
  async getFreightForwarder(id) {
    const response = await api.get(`/freight-forwarders/${id}`);
    return response.data;
  },

  // ========== CRÉER TRANSITAIRE ==========
  
  async createFreightForwarder(data) {
    // Enlever service_score - calculé par le système
    const { service_score, ...cleanData } = data;
    const response = await api.post('/freight-forwarders', cleanData);
    return response.data;
  },

  // ========== MODIFIER TRANSITAIRE ==========
  
  async updateFreightForwarder(id, data) {
    // Enlever service_score - calculé par le système
    const { service_score, ...cleanData } = data;
    const response = await api.put(`/freight-forwarders/${id}`, cleanData);
    return response.data;
  },

  // ========== SUPPRIMER TRANSITAIRE ==========
  
  async deleteFreightForwarder(id) {
    const response = await api.delete(`/freight-forwarders/${id}`);
    return response.data;
  },

  // ========== STATISTIQUES TRANSITAIRE ==========
  
  async getFreightForwarderStatistics(id) {
    try {
      const response = await api.get(`/freight-forwarders/${id}/statistics`);
      return response.data;
    } catch (error) {
      // Si l'endpoint n'existe pas encore, retourner des données par défaut
      return {
        total_receipts: 0,
        total_value: 0,
        last_receipt_date: null,
        active_receipts: 0
      };
    }
  }
};

export default freightForwarderService;
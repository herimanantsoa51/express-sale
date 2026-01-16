// ============================================
// services/supplierService.js
// ============================================

import api from './api';

const supplierService = {
  // ========== LISTE FOURNISSEURS ==========
  
  async getSuppliers(params = {}) {
    const cleanParams = Object.fromEntries(
      Object.entries(params).filter(([_, value]) => {
        if (typeof value === 'boolean') return true;
        return value !== '' && value !== null && value !== undefined;
      })
    );
    
    const response = await api.get('/suppliers', { params: cleanParams });
    return response.data;
  },

  // ========== DÉTAILS FOURNISSEUR ==========
  
  async getSupplier(id) {
    const response = await api.get(`/suppliers/${id}`);
     console.log("Fetched supplier data:", response.data);
    return response.data;
  },

  // ========== CRÉER FOURNISSEUR ==========
  
  async createSupplier(data) {
    // Enlever reliability_score - calculé par le système
    console.log("Creating supplier with data:", data);
    const { reliability_score, ...cleanData } = data;
    const response = await api.post('/suppliers', cleanData);
    return response.data;
  },

  // ========== MODIFIER FOURNISSEUR ==========
  
  async updateSupplier(id, data) {
    // Enlever reliability_score - calculé par le système
    const { reliability_score, ...cleanData } = data;
    const response = await api.put(`/suppliers/${id}`, cleanData);
    return response.data;
  },

  // ========== SUPPRIMER FOURNISSEUR ==========
  
  async deleteSupplier(id) {
    const response = await api.delete(`/suppliers/${id}`);
    return response.data;
  },

  // ========== STATISTIQUES FOURNISSEUR ==========
  
  async getSupplierStatistics(id) {
    try {
      const response = await api.get(`/suppliers/${id}/statistics`);
      return response.data;
    } catch (error) {
      // Si l'endpoint n'existe pas encore, retourner des données par défaut
      return {
        total_products: 0,
        total_receipts: 0,
        total_spent: 0,
        last_receipt_date: null
      };
    }
  }
};

export default supplierService;
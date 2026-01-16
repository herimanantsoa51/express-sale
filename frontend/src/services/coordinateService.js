// ============================================
// services/coordinateService.js
// ============================================

import api from './api';

const coordinateService = {
  // ========== LISTE COORDONNÉES ==========
  
  async getCoordinates(params = {}) {
    const cleanParams = Object.fromEntries(
      Object.entries(params).filter(([_, value]) => {
        if (typeof value === 'boolean') return true;
        return value !== '' && value !== null && value !== undefined;
      })
    );
    
    const response = await api.get('/coordinates', { params: cleanParams });
    return response.data;
  },

  // ========== LISTE PAYS ==========
  
  async getCountries() {
    const response = await api.get('/coordinates/countries');
    return response.data;
  },

  // ========== LISTE VILLES PAR PAYS ==========
  
  async getCitiesByCountry(country) {
    const response = await api.get(`/coordinates/cities/${encodeURIComponent(country)}`);
    return response.data;
  },

  // ========== DÉTAILS COORDONNÉE ==========
  
  async getCoordinate(id) {
    const response = await api.get(`/coordinates/${id}`);
    return response.data;
  },

  // ========== CRÉER COORDONNÉE ==========
  
  async createCoordinate(data) {
    const response = await api.post('/coordinates', data);
    return response.data;
  },

  // ========== MODIFIER COORDONNÉE ==========
  
  async updateCoordinate(id, data) {
    const response = await api.put(`/coordinates/${id}`, data);
    return response.data;
  },

  // ========== SUPPRIMER COORDONNÉE ==========
  
  async deleteCoordinate(id) {
    const response = await api.delete(`/coordinates/${id}`);
    return response.data;
  },

  // ========== STATISTIQUES D'UTILISATION ==========
  
  async getCoordinateUsage(id) {
    try {
      const response = await api.get(`/coordinates/${id}/usage`);
      return response.data;
    } catch (error) {
      return {
        suppliers_count: 0,
        forwarders_count: 0,
        suppliers: [],
        forwarders: []
      };
    }
  }
};

export default coordinateService;
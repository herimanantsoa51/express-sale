// ============================================
// services/productService.js
// ============================================

import api from './api';

const productService = {
  // ========== CATÉGORIES ==========
  
  async getCategories(params = {}) {
    const response = await api.get('/categories', { params });
    return response.data;
  },

  /**
   * Récupère uniquement les catégories racines (sans parent)
   */
  async getRootCategories() {
    const response = await api.get('/categories', { params: { only_roots: true } });
    return response.data;
  },

  /**
   * Récupère les sous-catégories d'une catégorie parente
   */
  async getSubcategories(parentId) {
    const response = await api.get('/categories', { params: { parent_id: parentId } });
    return response.data;
  },

  async getCategory(id) {
    const response = await api.get(`/categories/${id}`);
    return response.data;
  },

  async createCategory(data) {
    const response = await api.post('/categories', data);
    return response.data;
  },

  async updateCategory(id, data) {
    const response = await api.put(`/categories/${id}`, data);
    return response.data;
  },

  async deleteCategory(id) {
    const response = await api.delete(`/categories/${id}`);
    return response.data;
  },

  // ========== PRODUITS ==========
  
  async getProducts(params = {}) {
    // Nettoyer les paramètres vides
    console.log('Fetching products with params:', params);
    const cleanParams = Object.fromEntries(
      Object.entries(params).filter(([_, value]) => {
        if (typeof value === 'boolean') return true;
        return value !== '' && value !== null && value !== undefined;
      })
    );
    
    const response = await api.get('/products', { params: cleanParams });
    console.log('Products fetched:', response.data);
    return response.data;
  },

  async getProduct(id) {
    const response = await api.get(`/products/${id}`);
    return response.data;
  },
  async getProductWithVariants(id) {
    const response = await api.get(`/products/with-variants/${id}`);
    return response.data;
  },

  async createProduct(data) {
    const response = await api.post('/products', data);
    return response.data;
  },

  async updateProduct(id, data) {
    const response = await api.put(`/products/${id}`, data);
    return response.data;
  },

  async deleteProduct(id) {
    const response = await api.delete(`/products/${id}`);
    return response.data;
  },

  async getProductStatistics(id) {
    const response = await api.get(`/products/${id}/statistics`);
    return response.data;
  },

  // ========== VARIANTES ==========
  
  async getVariants(productId, params = {}) {
    const response = await api.get(`/products/${productId}/variants`, { params });
    console.log('Variants fetched:', response.data);  
    return response;
  },

  async createVariant(productId, data) {
    const response = await api.post(`/products/${productId}/variants`, data);
    return response.data;
  },

  async updateVariant(productId, variantId, data) {
    console.log('Updating variant:', productId, variantId, data);
    const response = await api.put(`/products/${productId}/variants/${variantId}`, data);
    return response.data;
  },

  async deleteVariant(productId, variantId) {
    const response = await api.delete(`/products/${productId}/variants/${variantId}`);
    return response.data;
  },

  async getVariant(productId, variantId) {
    const response = await api.get(`/products/${productId}/variants/${variantId}`);
    return response.data;
  },

  // ========== TYPES D'ATTRIBUTS ==========
  
  async getAttributeTypes() {
    const response = await api.get('/attribute-types');
    return response.data;
  },

  async getProductAttributeTypes(productId) {
    const res = await api.get(`/products/${productId}/attribute-types`);
    return res.data;
  },
  

  async getAttributeType(id) {
    const response = await api.get(`/attribute-types/${id}`);
    return response.data;
  },

  async createAttributeType(data) {
    const response = await api.post('/attribute-types', data);
    return response.data;
  },

  async updateAttributeType(id, data) {
    const response = await api.put(`/attribute-types/${id}`, data);
    return response.data;
  },

  async deleteAttributeType(id) {
    const response = await api.delete(`/attribute-types/${id}`);
    return response.data;
  },

  // ========== PRODUITS POUR VENTE ==========
  
  /**
   * Récupère les produits pour la vente avec pagination et filtres
   * @param {Object} params - Paramètres de recherche/filtrage
   * @returns {Promise<Object>} Liste paginée de produits
   */
  async getForSale(params = {}) {
    const response = await api.get('/products/for-sale', { params });
    console.log('Products for sale fetched:', response.data);
    return response.data;
  },

  /**
   * Récupère les catégories pour la vente
   * @returns {Promise<Object>} Liste des catégories avec sous-catégories
   */
  async getCategoriesForSale() {
    const response = await api.get('/categories/for-sale');
    return response.data;
  },
  async updateBasePrices(data){
    const response = await api.post('/products/update-base-prices', data);
    return response.data;
  }
};

export default productService;
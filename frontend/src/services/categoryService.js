import api from './api';

const categoryService = {
  /**
   * Récupérer toutes les catégories
   */
  getAll: async (params = {}) => {
    const response = await api.get('/categories', { params });
    return response.data;
  },

  /**
   * Récupérer uniquement les catégories racines (sans parent)
   */
  getRoots: async () => {
    const response = await api.get('/categories', { params: { only_roots: true } });
    return response.data;
  },

  /**
   * Récupérer les sous-catégories d'une catégorie parente
   */
  getSubcategories: async (parentId) => {
    const response = await api.get('/categories', { params: { parent_id: parentId } });
    return response.data;
  },

  /**
   * Récupérer une catégorie par ID
   */
  getById: async (id) => {
    const response = await api.get(`/categories/${id}`);
    return response.data;
  },

  /**
   * Créer une nouvelle catégorie
   */
  create: async (data) => {
    const response = await api.post('/categories', data);
    return response.data;
  },

  /**
   * Mettre à jour une catégorie
   */
  update: async (id, data) => {
    const response = await api.put(`/categories/${id}`, data);
    return response.data;
  },

  /**
   * Supprimer une catégorie
   */
  delete: async (id) => {
    const response = await api.delete(`/categories/${id}`);
    return response.data;
  }
};

export default categoryService;

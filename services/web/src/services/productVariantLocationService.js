import api from './api';

const productVariantLocationService = {
  /**
   * Obtenir toutes les localisations de variantes
   * GET /api/product-variant-locations
   * 
   * @returns {Promise<Array>} Liste des localisations
   */
  getAll: async () => {
    const response = await api.get('/product-variant-locations');
    return response.data;
  },

  /**
   * Obtenir une localisation par son ID
   * GET /api/product-variant-locations/{id}
   * 
   * @param {number} id - ID de la localisation
   * @returns {Promise<Object>} Localisation avec détails
   */
  getById: async (id) => {
    const response = await api.get(`/product-variant-locations/${id}`);
    return response.data;
  },

  /**
   * Obtenir toutes les variantes dans une location spécifique
   * GET /api/locations/{locationId}/variants
   * 
   * @param {number} locationId - ID de la location
   * @returns {Promise<Array>} Liste des variantes dans cette location
   */
  // services/productVariantLocationService.js (UPDATE)

  getByLocation: async (locationId, params = {}) => {
    const response = await api.get(`/locations/${locationId}/variants`, { params });
    return response.data; // Retourne directement l'objet paginé
  },
  // services/stockMovementService.js (UPDATE)

  /**
   * Déclarer une perte de stock
   */
  declareLoss: async (data) => {
    const response = await api.post('/stock-movements/loss', data);
    return response.data;
  },

  /**
   * Obtenir toutes les locations d'une variante spécifique
   * GET /api/variants/{variantId}/locations
   * 
   * @param {number} variantId - ID de la variante
   * @returns {Promise<Array>} Liste des locations de cette variante
   */
  getByVariant: async (variantId) => {
    const response = await api.get(`/variants/${variantId}/locations`);
    return response.data;
  },

  /**
   * Créer une nouvelle localisation de variante
   * POST /api/product-variant-locations
   * 
   * @param {Object} data
   * @param {number} data.variant_id - ID de la variante (requis)
   * @param {number} data.location_id - ID de la location (requis)
   * @param {number} data.quantity - Quantité (requis, défaut: 0)
   * @param {string|null} data.notes - Notes
   * 
   * @returns {Promise<Object>} Localisation créée
   */
  create: async (data) => {
    const response = await api.post('/product-variant-locations', data);
    return response.data;
  },

  /**
   * Mettre à jour une localisation de variante
   * PUT /api/product-variant-locations/{id}
   * 
   * @param {number} id - ID de la localisation
   * @param {Object} data - Données à mettre à jour
   * 
   * @returns {Promise<Object>} Localisation mise à jour
   */
  update: async (id, data) => {
    const response = await api.put(`/product-variant-locations/${id}`, data);
    return response.data;
  },

  /**
   * Supprimer une localisation de variante
   * DELETE /api/product-variant-locations/{id}
   * 
   * @param {number} id - ID de la localisation
   * @returns {Promise<void>}
   */
  delete: async (id) => {
    await api.delete(`/product-variant-locations/${id}`);
  },

  /**
   * Vérifier si une localisation peut être supprimée
   * GET /api/product-variant-locations/{id}/can-delete
   * 
   * @param {number} id - ID de la localisation
   * @returns {Promise<Object>} {canDelete: boolean, reason: string}
   */
  canDelete: async (id) => {
    const response = await api.get(`/product-variant-locations/${id}/can-delete`);
    return response.data;
  }
};

export default productVariantLocationService;
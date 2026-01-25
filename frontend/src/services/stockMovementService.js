import api from './api';

const stockMovementService = {
  /**
   * Récupérer tous les mouvements avec filtres et pagination
   */
  getAll: async (params = {}) => {
    const response = await api.get('/stock-movements', { params });
    return response.data;
  },

  /**
   * Récupérer les mouvements groupés par batch
   */
  getGrouped: async (params = {}) => {
    const response = await api.get('/stock-movements/grouped', { params });
    return response.data;
  },

  /**
   * Récupérer les détails d'un batch
   */
  getBatchDetails: async (batchId) => {
    const response = await api.get(`/stock-movements/batch/${batchId}`);
    return response.data;
  },

  /**
   * Récupérer un mouvement par ID
   */
  getById: async (id) => {
    const response = await api.get(`/stock-movements/${id}`);
    return response.data;
  },

  /**
   * Récupérer les mouvements d'une variante
   */
  getByVariant: async (variantId, params = {}) => {
    const response = await api.get(`/variants/${variantId}/stock-movements`, { params });
    return response.data;
  },

  /**
   * Récupérer les mouvements d'une location
   */
  getByLocation: async (locationId, params = {}) => {
    const response = await api.get(`/locations/${locationId}/stock-movements`, { params });
    return response.data;
  },

  /**
   * Effectuer un transfert simple (une seule variante)
   */
  transfer: async (data) => {
    const response = await api.post('/stock-movements/transfer', data);
    return response.data;
  },

  /**
   * Effectuer un transfert multiple (plusieurs variantes en même temps)
   */
  bulkTransfer: async (data) => {
    const response = await api.post('/stock-movements/bulk-transfer', data);
    return response.data;
  },

  /**
   * Effectuer un ajustement simple
   */
  adjustment: async (data) => {
    const response = await api.post('/stock-movements/adjustment', data);
    return response.data;
  },

  /**
   * Effectuer un ajustement multiple (plusieurs variantes)
   */
  bulkAdjustment: async (data) => {
    const response = await api.post('/stock-movements/bulk-adjustment', data);
    return response.data;
  },

  /**
   * Récupérer les statistiques des mouvements
   */
  getStatistics: async (params = {}) => {
    const response = await api.get('/stock-movements/statistics', { params });
    return response.data;
  },

  /**
   * Récupérer les types de mouvements disponibles
   */
  getMovementTypes: () => {
    return [
      { value: 'transfer', label: 'Transfert', color: 'primary' },
      { value: 'receipt', label: 'Réception', color: 'success' },
      { value: 'sale', label: 'Vente', color: 'danger' },
      { value: 'adjustment', label: 'Ajustement', color: 'warning' },
      { value: 'return', label: 'Retour', color: 'info' }
    ];
  },

  /**
   * Récupérer les raisons d'ajustement prédéfinies
   */
  getAdjustmentReasons: () => {
    return [
      { value: 'inventory_count', label: 'Inventaire physique' },
      { value: 'damaged', label: 'Produit endommagé' },
      { value: 'lost', label: 'Produit perdu' },
      { value: 'data_entry_error', label: 'Erreur de saisie' },
      { value: 'supplier_return', label: 'Retour fournisseur' },
      { value: 'sample', label: 'Échantillon' },
      { value: 'donation', label: 'Don' },
      { value: 'theft', label: 'Vol' },
      { value: 'expiry', label: 'Péremption' },
      { value: 'other', label: 'Autre' }
    ];
  },
  declareLoss: async (data) => {
    const response = await api.post('/stock-movements/loss', data);
    return response.data;
  },

  reconcileInventory: async (data) => {
    const response = await api.post('/stock-movements/reconcile-inventory', data);
    return response.data;
  },

  /**
   * Récupérer les types de perte disponibles
   */
  getLossTypes: () => {
    return [
      { value: 'breakage', label: 'Casse', icon: '💥', color: 'danger' },
      { value: 'theft', label: 'Vol', icon: '🚨', color: 'danger' },
      { value: 'expiry', label: 'Péremption', icon: '📅', color: 'warning' },
      { value: 'damage', label: 'Dommage', icon: '⚠️', color: 'warning' },
      { value: 'inventory_shortage', label: 'Écart inventaire', icon: '📊', color: 'info' },
      { value: 'other', label: 'Autre', icon: '📝', color: 'default' }
    ];
  },

};

export default stockMovementService;
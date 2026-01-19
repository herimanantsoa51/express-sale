import { data } from 'react-router-dom';
import api from './api';

const stockReceiptService = {
  /**
   * Obtenir les statistiques globales des réceptions
   * GET /api/stock-receipts/global-statistics
   * 
   * @returns {Promise<Object>} Réponse API
   * @returns {string} return.status - 'success'
   * @returns {Object} return.data
   * @returns {number} return.data.total - Nombre total de réceptions
   * @returns {Object} return.data.by_status - Compteur par statut
   * @returns {number} return.data.by_status.pending
   * @returns {number} return.data.by_status.sent
   * @returns {number} return.data.by_status.in_transit
   * @returns {number} return.data.by_status.arrived
   * @returns {number} return.data.by_status.validated
   * @returns {number} return.data.by_status.cancelled
   * @returns {number} return.data.not_arrived - Commandes pas encore arrivées
   * @returns {number} return.data.delayed - Commandes en retard
   * @returns {number} return.data.this_month - Commandes ce mois
   * @returns {number} return.data.total_value_pending - Valeur en cours
   * @returns {number} return.data.total_value_validated - Valeur validée
   */
  getGlobalStatistics: async () => {
    const response = await api.get('/stock-receipts/global-statistics');
    return response.data;
  },

  /**
   * Créer une réception de stock
   * POST /api/stock-receipts
   * 
   * @param {Object} data
   * @param {number} data.supplier_id - ID du fournisseur (requis)
   * @param {number|null} data.freight_forwarder_id - ID du transitaire (optionnel)
   * @param {string|null} data.expected_delivery_date - Date de livraison prévue (format: YYYY-MM-DD)
   * @param {string|null} data.notes - Notes (max 1000 caractères)
   * @param {Array} data.items - Liste des articles (min 1)
   * @param {number} data.items[].variant_id - ID de la variante produit (requis, unique)
   * @param {number} data.items[].quantity_ordered - Quantité commandée (requis, min 1)
   * @param {number} data.items[].quantity_received - Quantité reçue (min 0)
   * @param {number} data.items[].unit_cost_ariary - Coût unitaire en Ariary (requis, min 0)
   * @param {string|null} data.items[].notes - Notes article (max 500 caractères)
   * 
   * @returns {Promise<Object>} Réponse API
   * @returns {string} return.status - 'success' | 'error'
   * @returns {string} return.message - Message de confirmation
   * @returns {Object} return.data - StockReceiptResource
   * @returns {number} return.data.id
   * @returns {string} return.data.receipt_number
   * @returns {Object} return.data.supplier - {id, name, reliability_score}
   * @returns {Object|null} return.data.freight_forwarder - {id, name, service_score}
   * @returns {string|null} return.data.expected_delivery_date
   * @returns {number} return.data.total_cost_ariary
   * @returns {string} return.data.status - 'pending' | 'validated' | 'cancelled'
   * @returns {string} return.data.status_label
   * @returns {Array} return.data.items
   * @returns {Object} return.data.created_by - {id, name}
   * @returns {string} return.data.created_at
   * @returns {string} return.data.updated_at
   */
  create: async (data) => {
    console.log('Creating stock receipt with data:', data);
    const response = await api.post('/stock-receipts', data);
    return response.data;
  },

  /**
   * Obtenir toutes les réceptions avec pagination et filtres
   * GET /api/stock-receipts
   * 
   * @param {Object} params - Paramètres de requête
   * @param {number} [params.supplier_id] - Filtrer par fournisseur
   * @param {number} [params.freight_forwarder_id] - Filtrer par transitaire
   * @param {string} [params.status] - Filtrer par statut ('pending' | 'validated' | 'cancelled')
   * @param {string} [params.delivery_status] - Filtrer par statut livraison
   * @param {string} [params.start_date] - Date début (format: YYYY-MM-DD)
   * @param {string} [params.end_date] - Date fin (format: YYYY-MM-DD)
   * @param {string} [params.delayed] - 'true' pour les réceptions en retard
   * @param {string} [params.search] - Recherche par numéro ou notes
   * @param {string} [params.sort_by='created_at'] - Champ de tri
   * @param {string} [params.sort_order='desc'] - Ordre de tri ('asc' | 'desc')
   * @param {number} [params.per_page=20] - Nombre par page
   * 
   * @returns {Promise<Object>} Réponse paginée
   * @returns {Array} return.data - Liste de StockReceiptResource
   * @returns {Object} return.meta - Métadonnées pagination
   * @returns {number} return.meta.current_page
   * @returns {number} return.meta.last_page
   * @returns {number} return.meta.per_page
   * @returns {number} return.meta.total
   * @returns {number} return.meta.from
   * @returns {number} return.meta.to
   * @returns {Object} return.summary - Résumé
   * @returns {number} return.summary.total_receipts
   * @returns {number} return.summary.total_cost
   * @returns {number} return.summary.pending_count
   * @returns {number} return.summary.validated_count
   */
  getAll: async (params = {}) => {
    const response = await api.get('/stock-receipts', { params });
    return response.data;
  },

  /**
   * Obtenir une réception par son ID
   * GET /api/stock-receipts/{id}
   * 
   * @param {number} id - ID de la réception
   * 
   * @returns {Promise<Object>} Réponse API
   * @returns {string} return.status - 'success'
   * @returns {Object} return.data - StockReceiptResource complet avec relations
   * @returns {Array} return.data.items - Items avec variant, product, category, locations
   * @returns {Array} return.data.transactions - Transactions liées
   * @returns {number} return.data.total_paid - Total payé
   */
  getById: async (id) => {
    const response = await api.get(`/stock-receipts/${id}`);
    return response.data;
  },

  /**
   * Mettre à jour une réception (uniquement si status = 'pending')
   * PUT /api/stock-receipts/{id}
   * 
   * @param {number} id - ID de la réception
   * @param {Object} data - Données à mettre à jour
   * @param {number} [data.supplier_id] - ID du fournisseur
   * @param {number|null} [data.freight_forwarder_id] - ID du transitaire
   * @param {string|null} [data.expected_delivery_date] - Date de livraison prévue
   * @param {string|null} [data.notes] - Notes
   * @param {Array} [data.items] - Nouvelle liste d'articles (remplace les existants)
   * 
   * @returns {Promise<Object>} Réponse API
   * @returns {string} return.status - 'success' | 'error'
   * @returns {string} return.message
   * @returns {Object} return.data - StockReceiptResource mis à jour
   */
  update: async (id, data) => {
    const response = await api.put(`/stock-receipts/${id}`, data);
    return response.data;
  },

  /**
   * Marquer comme envoyé
   * POST /api/stock-receipts/{id}/mark-shipped
   * 
   * @param {number} id - ID de la réception
   * @param {Object} [data]
   * @param {string|null} [data.notes] - Notes (max 1000 caractères)
   * 
   * @returns {Promise<Object>} Réponse API
   * @returns {string} return.status - 'success' | 'error'
   * @returns {string} return.message
   * @returns {Object} return.data - StockReceiptResource
   */
  markAsShipped: async (id, data = {}) => {
    const response = await api.post(`/stock-receipts/${id}/mark-shipped`, data);
    return response.data;
  },

  /**
   * Marquer comme en transit
   * POST /api/stock-receipts/{id}/mark-in-transit
   * 
   * @param {number} id - ID de la réception
   * @param {Object} [data]
   * @param {string|null} [data.notes] - Notes
   * 
   * @returns {Promise<Object>} Réponse API
   * @returns {string} return.status - 'success' | 'error'
   * @returns {string} return.message
   * @returns {Object} return.data - StockReceiptResource
   */
  markAsInTransit: async (id, data = {}) => {
    const response = await api.post(`/stock-receipts/${id}/mark-in-transit`, data);
    return response.data;
  },

  markAsRated: async (id, data = {}) => {
    const response = await api.post(`/stock-receipts/${id}/mark-rated`, data);
    return response.data;
  },

  /**
   * Marquer comme arrivé et assigner les emplacements
   * POST /api/stock-receipts/{id}/mark-arrived
   * 
   * @param {number} id - ID de la réception
   * @param {Object} data
   * @param {Array} data.items - Liste des items à réceptionner (min 1)
   * @param {number} data.items[].item_id - ID de l'item (stock_receipt_items.id)
   * @param {number} data.items[].quantity_received - Quantité reçue (min 0)
   * @param {number} data.items[].location_id - ID de l'emplacement de stockage
   * @param {string|null} [data.items[].notes] - Notes (max 500 caractères)
   * 
   * @returns {Promise<Object>} Réponse API
   * @returns {string} return.status - 'success' | 'error'
   * @returns {string} return.message
   * @returns {Object} return.data - StockReceiptResource avec items et locations
   */
  markAsArrived: async (id, data) => {
    const response = await api.post(`/stock-receipts/${id}/mark-arrived`, data);
    return response.data;
  },

  /**
   * Valider la réception et mettre à jour les scores fournisseur/transitaire
   * POST /api/stock-receipts/{id}/validate
   * 
   * @param {number} id - ID de la réception
   * 
   * @returns {Promise<Object>} Réponse API
   * @returns {string} return.status - 'success' | 'error'
   * @returns {string} return.message
   * @returns {Object} return.data - StockReceiptResource
   */
  validate: async (id,data) => {
    const response = await api.post(`/stock-receipts/${id}/validate`,data);
    return response.data;
  },

  /**
   * Annuler une réception
   * POST /api/stock-receipts/{id}/cancel
   * 
   * @param {number} id - ID de la réception
   * 
   * @returns {Promise<Object>} Réponse API
   * @returns {string} return.status - 'success' | 'error'
   * @returns {string} return.message
   */
  cancel: async (id) => {
    const response = await api.post(`/stock-receipts/${id}/cancel`);
    return response.data;
  },

  /**
   * Évaluer la qualité d'un item
   * POST /api/stock-receipts/{id}/items/{itemId}/rate
   * 
   * @param {number} id - ID de la réception
   * @param {number} itemId - ID de l'item
   * @param {Object} data
   * @param {Array} data.ratings - Liste des évaluations
   * @param {number} data.ratings[].attribute_type_id - ID du type d'attribut
   * @param {number} data.ratings[].rating - Note (1-10)
   * @param {string|null} [data.ratings[].notes] - Commentaire
   * 
   * @returns {Promise<Object>} Réponse API
   * @returns {string} return.status - 'success' | 'error'
   * @returns {string} return.message
   * @returns {Object} return.data.item - Item avec ratings
   * @returns {Object} return.data.quality_summary - Résumé qualité
   * @returns {Object} return.data.conformity - Taux de conformité
   */
  rateItem: async (id, itemId, data) => {
    const response = await api.post(`/stock-receipts/${id}/items/${itemId}/rate`, data);
    return response.data;
  },

  /**
   * Enregistrer un paiement pour cette réception
   * POST /api/stock-receipts/{id}/payment
   * 
   * @param {number} id - ID de la réception
   * @param {Object} data
   * @param {number} data.account_id - ID du compte (requis)
   * @param {number} data.amount - Montant (requis, min 0)
   * @param {string} data.payment_type - Type: 'supplier' | 'freight' (requis)|other
   * @param {string|null} [data.notes] - Notes
   * @param {string|null} [data.reference_number] - Numéro de référence (max 255)
   * 
   * @returns {Promise<Object>} Réponse API
   * @returns {string} return.status - 'success' | 'error'
   * @returns {string} return.message
   * @returns {Object} return.data - Transaction créée
   */
  recordPayment: async (id, data) => {
    console.log('Recording payment for stock receipt', id, 'with data:', data);
    const response = await api.post(`/stock-receipts/${id}/payment`, data);
    return response.data;
  },

  /**
   * Obtenir les statistiques d'une réception
   * GET /api/stock-receipts/{id}/statistics
   * 
   * @param {number} id - ID de la réception
   * 
   * @returns {Promise<Object>} Réponse API
   * @returns {string} return.status - 'success'
   * @returns {Object} return.data.receipt_info - Infos générales
   * @returns {string} return.data.receipt_info.receipt_number
   * @returns {string} return.data.receipt_info.status
   * @returns {string} return.data.receipt_info.delivery_status
   * @returns {boolean} return.data.receipt_info.is_delayed
   * @returns {number} return.data.receipt_info.days_delayed
   * @returns {Object} return.data.quantities - Quantités
   * @returns {number} return.data.quantities.total_ordered
   * @returns {number} return.data.quantities.total_received
   * @returns {number} return.data.quantities.fulfillment_rate
   * @returns {Object} return.data.costs - Coûts
   * @returns {number} return.data.costs.total_cost_ariary
   * @returns {number} return.data.costs.average_unit_cost
   * @returns {Object} return.data.quality - Qualité
   * @returns {number} return.data.quality.average_quality_rating
   * @returns {number} return.data.quality.average_conformity_rate
   * @returns {Object} return.data.payments - Paiements
   * @returns {number} return.data.payments.total_paid
   * @returns {number} return.data.payments.remaining
   * @returns {number} return.data.payments.transactions_count
   */
  getStatistics: async (id) => {
    const response = await api.get(`/stock-receipts/${id}/statistics`);
    return response.data;
  },
  getCostRecommendations:async (id,method)=>{
    const response = await api.get(`/stock-receipts/${id}/cost-recommendations?method=${method}`);
    return response.data;
  },
  applyCosts: async (id,dataCosts)=>{
    const response = await api.post(`/stock-receipts/${id}/apply-costs`,dataCosts);
    return response.data;
  },
  validateCosts:async(id)=>{
    const response = await api.post(`/stock-receipts/${id}/validate-costs`);
    return response.data;
  },
  moveReceivedVariant:async(id,data)=>{
    const response = await api.post(`/stock-receipts/${id}/move-received-variant`,data);
    return response.data;
  },
  getAllocatedCosts: async(id)=>{
    const response = await api.get(`/stock-receipts/${id}/cost-allocations`);
    return response.data;
  }



};

export default stockReceiptService;
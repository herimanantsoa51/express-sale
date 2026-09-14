import { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { 
  ArrowRight, 
  ArrowLeft,
  Package, 
  MapPin, 
  X, 
  AlertCircle, 
  CheckCircle,
  Plus,
  Minus,
  Trash2,
  Search,
  Warehouse,
  Image as ImageIcon,
  ShoppingBag,
  Info,
  Loader2,
  Check,
  ArrowUpDown,
  Grid,
  List,
  ChevronLeft,
  ChevronRight
} from 'lucide-react';
import stockMovementService from '../../services/stockMovementService';
import locationService from '../../services/locationService';
import productVariantLocationService from '../../services/productVariantLocationService';
import categoryService from '../../services/categoryService';
import './StockTransfer.css';

const StockTransferForm = () => {
  const navigate = useNavigate();
  
  // États principaux
  const [step, setStep] = useState(1);
  const [loading, setLoading] = useState(false);
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState(null);
  const [success, setSuccess] = useState(false);

  // Données de référence
  const [locations, setLocations] = useState([]);
  const [categories, setCategories] = useState([]);
  
  // Sélection source
  const [fromLocationId, setFromLocationId] = useState('');
  const [sourceVariants, setSourceVariants] = useState([]);
  const [loadingVariants, setLoadingVariants] = useState(false);
  const [pagination, setPagination] = useState({
    current_page: 1,
    last_page: 1,
    per_page: 20,
    total: 0
  });

  // Sélection destination
  const [toLocationId, setToLocationId] = useState('');

  // Produits à transférer
  const [transferItems, setTransferItems] = useState([]);
  
  // Recherche et filtres produits
  const [searchTerm, setSearchTerm] = useState('');
  const [selectedCategory, setSelectedCategory] = useState('');
  const [viewMode, setViewMode] = useState('grid');
  const [showLowStockOnly, setShowLowStockOnly] = useState(false);

  // Raison et notes
  const [reason, setReason] = useState('');
  const [notes, setNotes] = useState('');

  // Charger les données initiales
  useEffect(() => {
    loadInitialData();
  }, []);

  // Charger les variantes quand on sélectionne une source
  useEffect(() => {
    if (fromLocationId) {
      loadSourceVariants();
    } else {
      setSourceVariants([]);
      setTransferItems([]);
      setPagination({
        current_page: 1,
        last_page: 1,
        per_page: 20,
        total: 0
      });
    }
  }, [fromLocationId]);

  // Charger les variantes quand on change de page, recherche ou filtre
  useEffect(() => {
    if (fromLocationId && step === 2) {
      loadSourceVariants();
    }
  }, [pagination.current_page, searchTerm, selectedCategory, fromLocationId, step]);

  const loadInitialData = async () => {
    try {
      setLoading(true);
      const [locationsData, categoriesData] = await Promise.all([
        locationService.getActive(),
        categoryService.getAll()
      ]);
      setLocations(locationsData);
      setCategories(categoriesData);
    } catch (err) {
      console.error('Erreur chargement données:', err);
      setError('Erreur lors du chargement des données');
    } finally {
      setLoading(false);
    }
  };

  const loadSourceVariants = async () => {
    try {
      setLoadingVariants(true);
      const params = {
        page: pagination.current_page,
        per_page: pagination.per_page,
        search: searchTerm,
        available_only: true
      };
      
      const response = await productVariantLocationService.getByLocation(fromLocationId, params);
      
      // Accéder aux données paginées correctement
      const variantsData = response.data || []; // Les données sont dans response.data
      const paginationData = {
        current_page: response.current_page || 1,
        last_page: response.last_page || 1,
        per_page: response.per_page || 20,
        total: response.total || 0
      };
      
      // Filtrer uniquement les variantes avec available_quantity > 0
      const variantsWithStock = variantsData.filter(vl => vl.available_quantity > 0);
      setSourceVariants(variantsWithStock);
      setPagination(paginationData);
      
    } catch (err) {
      console.error('Erreur chargement variantes:', err);
      setSourceVariants([]);
      setPagination({
        current_page: 1,
        last_page: 1,
        per_page: 20,
        total: 0
      });
    } finally {
      setLoadingVariants(false);
    }
  };

  // Gestion de la pagination
  const handlePageChange = (newPage) => {
    if (newPage >= 1 && newPage <= pagination.last_page) {
      setPagination(prev => ({ ...prev, current_page: newPage }));
    }
  };

  // Filtrer les variantes selon recherche et catégorie
  const filteredVariants = sourceVariants.filter(vl => {
    const searchLower = searchTerm.toLowerCase();
    const matchesSearch = !searchTerm || 
      vl.variant?.product?.name?.toLowerCase().includes(searchLower) ||
      vl.variant?.sku?.toLowerCase().includes(searchLower);
    
    const matchesCategory = !selectedCategory || 
      vl.variant?.product?.category_id === parseInt(selectedCategory);

    const matchesLowStock = !showLowStockOnly || vl.available_quantity <= 10;

    // Exclure les variantes déjà ajoutées
    const notAlreadyAdded = !transferItems.some(item => item.variantLocationId === vl.id);

    return matchesSearch && matchesCategory && matchesLowStock && notAlreadyAdded;
  });

  // Ajouter une variante au transfert
  const addTransferItem = (variantLocation) => {
    const newItem = {
      id: Date.now(),
      variantLocationId: variantLocation.id,
      variantId: variantLocation.variant_id,
      variant: variantLocation.variant,
      availableQuantity: variantLocation.available_quantity,
      quantity: 1
    };
    setTransferItems(prev => [...prev, newItem]);
  };

  // Supprimer une variante du transfert
  const removeTransferItem = (itemId) => {
    setTransferItems(prev => prev.filter(item => item.id !== itemId));
  };

  // Modifier la quantité d'un item
  const updateItemQuantity = (itemId, newQuantity) => {
    setTransferItems(prev => prev.map(item => {
      if (item.id === itemId) {
        const qty = Math.max(1, Math.min(newQuantity, item.availableQuantity));
        return { ...item, quantity: qty };
      }
      return item;
    }));
  };

  // Mettre toute la quantité disponible
  const setMaxQuantity = (itemId) => {
    setTransferItems(prev => prev.map(item => {
      if (item.id === itemId) {
        return { ...item, quantity: item.availableQuantity };
      }
      return item;
    }));
  };

  // Soumettre le transfert
  const handleSubmit = async () => {
    if (transferItems.length === 0) {
      setError('Veuillez sélectionner au moins un produit à transférer');
      return;
    }

    if (!toLocationId) {
      setError('Veuillez sélectionner une location de destination');
      return;
    }

    try {
      setSubmitting(true);
      setError(null);

      const transferData = {
        from_location_id: parseInt(fromLocationId),
        to_location_id: parseInt(toLocationId),
        reason: reason || null,
        notes: notes || null,
        items: transferItems.map(item => ({
          variant_id: item.variantId,
          quantity: item.quantity
        }))
      };

      await stockMovementService.bulkTransfer(transferData);

      setSuccess(true);
      setTimeout(() => {
        navigate('/mouvements-stock');
      }, 2000);

    } catch (err) {
      setError(err.response?.data?.message || 'Erreur lors du transfert');
      console.error('Erreur transfert:', err);
    } finally {
      setSubmitting(false);
    }
  };

  // Helpers
  const getSourceLocation = () => locations.find(l => l.id === parseInt(fromLocationId));
  const getDestinationLocation = () => locations.find(l => l.id === parseInt(toLocationId));
  
  const getTotalItems = () => transferItems.reduce((sum, item) => sum + item.quantity, 0);
  const getTotalVariants = () => transferItems.length;

  const getProductImage = (variant) => {
    return variant?.product?.image_url || null;
  };

  const getVariantAttributes = (variant) => {
    if (!variant?.attribute_values || variant.attribute_values.length === 0) return null;
    return variant.attribute_values.map((attr, idx) => (
      <span key={idx} className="attribute-tag small">
        {attr.attribute_value?.attribute_type?.display_name || attr.attribute_type?.display_name || 'Attr'}: {attr.attribute_value?.value || attr.value}
      </span>
    ));
  };

  // Catégories parentes uniquement
  const rootCategories = categories.filter(cat => !cat.parent_id);

  // Écran de succès
  if (success) {
    return (
      <div className="stock-transfer-page">
        <div className="success-screen">
          <div className="success-content">
            <div className="success-icon">
              <CheckCircle size={64} />
            </div>
            <h2>Transfert effectué avec succès !</h2>
            <p>{getTotalVariants()} variante{getTotalVariants() > 1 ? 's' : ''} transférée{getTotalVariants() > 1 ? 's' : ''}</p>
            <p className="success-detail">
              {getTotalItems()} unité{getTotalItems() > 1 ? 's' : ''} de {getSourceLocation()?.name} vers {getDestinationLocation()?.name}
            </p>
            <p className="redirect-text">Redirection en cours...</p>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="stock-transfer-page">
      {/* Header */}
      <div className="page-header">
        <div className="header-content">
          <button className="btn-back" onClick={() => navigate('/mouvements-stock')}>
            <ArrowLeft size={20} />
          </button>
          <div className="header-icon transfer">
            <ArrowUpDown size={28} />
          </div>
          <div>
            <h1 className="page-title">Nouveau Transfert</h1>
            <p className="page-subtitle">
              Transférer des produits entre emplacements
            </p>
          </div>
        </div>
      </div>

      {/* Erreur */}
      {error && (
        <div className="alert alert-danger">
          <AlertCircle size={20} />
          <span>{error}</span>
          <button className="alert-close" onClick={() => setError(null)}>
            <X size={16} />
          </button>
        </div>
      )}

      {/* Indicateur d'étapes */}
      <div className="steps-indicator">
        <div className={`step ${step >= 1 ? 'active' : ''} ${step > 1 ? 'completed' : ''}`}>
          <div className="step-number">{step > 1 ? <Check size={16} /> : '1'}</div>
          <span className="step-label">Source</span>
        </div>
        <div className="step-line"></div>
        <div className={`step ${step >= 2 ? 'active' : ''} ${step > 2 ? 'completed' : ''}`}>
          <div className="step-number">{step > 2 ? <Check size={16} /> : '2'}</div>
          <span className="step-label">Produits</span>
        </div>
        <div className="step-line"></div>
        <div className={`step ${step >= 3 ? 'active' : ''}`}>
          <div className="step-number">3</div>
          <span className="step-label">Destination</span>
        </div>
      </div>

      {/* Contenu principal */}
      <div className="transfer-content">
        {/* Étape 1: Sélection de la source */}
        {step === 1 && (
          <div className="transfer-step">
            <div className="step-card">
              <div className="step-card-header">
                <Warehouse size={24} />
                <div>
                  <h2>Sélectionnez la location source</h2>
                  <p>Choisissez l'emplacement d'où proviennent les produits</p>
                </div>
              </div>

              {loading ? (
                <div className="loading-products">
                  <Loader2 size={32} className="spinning" />
                  <span>Chargement des locations...</span>
                </div>
              ) : (
                <div className="locations-grid">
                  {locations.map(location => (
                    <div
                      key={location.id}
                      className={`location-card selectable ${fromLocationId === String(location.id) ? 'selected' : ''}`}
                      onClick={() => setFromLocationId(String(location.id))}
                    >
                      <div className="location-card-header">
                        <div className="location-icon">
                          <MapPin size={20} />
                        </div>
                        {fromLocationId === String(location.id) && (
                          <div className="selected-badge">
                            <Check size={14} />
                          </div>
                        )}
                      </div>
                      <div className="location-card-body">
                        <h4>{location.name}</h4>
                        <p className="location-warehouse">{location.warehouse}</p>
                        <p className="location-code">Code: {location.code}</p>
                      </div>
                    </div>
                  ))}
                </div>
              )}

              {fromLocationId && (
                <div className="selected-summary">
                  <div className="summary-icon">
                    <MapPin size={20} />
                  </div>
                  <div className="summary-content">
                    <strong>{getSourceLocation()?.name}</strong>
                    <span>{getSourceLocation()?.warehouse} • {pagination.total} produit(s) en stock</span>
                  </div>
                </div>
              )}

              <div className="step-actions">
                <button 
                  className="btn-secondary"
                  onClick={() => navigate('/mouvements-stock')}
                >
                  Annuler
                </button>
                <button 
                  className="btn-primary"
                  onClick={() => setStep(2)}
                  disabled={!fromLocationId}
                >
                  Continuer
                  <ArrowRight size={18} />
                </button>
              </div>
            </div>
          </div>
        )}

        {/* Étape 2: Sélection des produits */}
        {step === 2 && (
          <div className="transfer-step">
            <div className="step-card products-step">
              <div className="step-card-header">
                <Package size={24} />
                <div>
                  <h2>Sélectionnez les produits à transférer</h2>
                  <p>Depuis: <strong>{getSourceLocation()?.name}</strong> ({getSourceLocation()?.warehouse})</p>
                  <p className="stock-total">{pagination.total} produit(s) disponible(s) dans cette location</p>
                </div>
              </div>

              {/* Barre de recherche et filtres */}
              <div className="products-toolbar">
                <div className="search-filter-row">
                  <div className="search-box">
                    <Search size={18} />
                    <input
                      type="text"
                      placeholder="Rechercher par nom ou SKU..."
                      value={searchTerm}
                      onChange={(e) => {
                        setSearchTerm(e.target.value);
                        setPagination(prev => ({ ...prev, current_page: 1 }));
                      }}
                    />
                    {searchTerm && (
                      <button className="clear-btn" onClick={() => {
                        setSearchTerm('');
                        setPagination(prev => ({ ...prev, current_page: 1 }));
                      }}>
                        <X size={14} />
                      </button>
                    )}
                  </div>
                  {/* <select
                    value={selectedCategory}
                    onChange={(e) => {
                      setSelectedCategory(e.target.value);
                      setPagination(prev => ({ ...prev, current_page: 1 }));
                    }}
                    className="category-select"
                  >
                    <option value="">Toutes catégories</option>
                    {rootCategories.map(cat => (
                      <option key={cat.id} value={cat.id}>{cat.name}</option>
                    ))}
                  </select> */}
                  <div className="view-toggle">
                    <button 
                      className={viewMode === 'grid' ? 'active' : ''}
                      onClick={() => setViewMode('grid')}
                      title="Vue grille"
                    >
                      <Grid size={18} />
                    </button>
                    <button 
                      className={viewMode === 'list' ? 'active' : ''}
                      onClick={() => setViewMode('list')}
                      title="Vue liste"
                    >
                      <List size={18} />
                    </button>
                  </div>
                </div>
              </div>

              {/* Liste des produits disponibles */}
              {loadingVariants ? (
                <div className="loading-products">
                  <Loader2 size={32} className="spinning" />
                  <span>Chargement des produits...</span>
                </div>
              ) : (
                <div className="available-products">
                  <div className="section-header">
                    <h3 className="section-title">
                      <Package size={18} />
                      Produits disponibles ({filteredVariants.length})
                    </h3>
                    {pagination.total > 0 && (
                      <div className="pagination-info">
                        Page {pagination.current_page} sur {pagination.last_page} 
                        ({pagination.total} résultats)
                      </div>
                    )}
                  </div>
                  
                  {filteredVariants.length === 0 ? (
                    <div className="no-products">
                      <Package size={48} />
                      <p>
                        {pagination.total === 0 
                          ? 'Aucun produit en stock dans cette location'
                          : searchTerm || selectedCategory
                            ? 'Aucun produit ne correspond à vos critères'
                            : 'Tous les produits ont été ajoutés au transfert'
                        }
                      </p>
                    </div>
                  ) : viewMode === 'grid' ? (
                    <>
                      <div className="products-grid compact">
                        {filteredVariants.map(vl => {
                          const image = getProductImage(vl.variant);
                          return (
                            <div 
                              key={vl.id} 
                              className="product-card compact"
                              onClick={() => addTransferItem(vl)}
                            >
                              <div className="product-image">
                                {image ? (
                                  <img src={image} alt={vl.variant?.product?.name} />
                                ) : (
                                  <div className="no-image">
                                    <ImageIcon size={20} />
                                  </div>
                                )}
                                <div className="add-overlay">
                                  <Plus size={24} />
                                </div>
                              </div>
                              <div className="product-info">
                                <h5>{vl.variant?.product?.name}</h5>
                                <span className="sku">{vl.variant?.sku}</span>
                                <div className="product-attributes">
                                  {getVariantAttributes(vl.variant)}
                                </div>
                                <div className="stock-info">
                                  <span className="stock-badge">{vl.available_quantity} disponible(s)</span>
                                </div>
                              </div>
                            </div>
                          );
                        })}
                      </div>
                      
                      {/* Pagination */}
                      {pagination.last_page > 1 && (
                        <div className="pagination-controls">
                          <button 
                            className="pagination-btn"
                            onClick={() => handlePageChange(pagination.current_page - 1)}
                            disabled={pagination.current_page === 1}
                          >
                            <ChevronLeft size={18} />
                            Précédent
                          </button>
                          <div className="pagination-numbers">
                            {Array.from({ length: Math.min(5, pagination.last_page) }, (_, i) => {
                              let pageNum;
                              if (pagination.last_page <= 5) {
                                pageNum = i + 1;
                              } else if (pagination.current_page <= 3) {
                                pageNum = i + 1;
                              } else if (pagination.current_page >= pagination.last_page - 2) {
                                pageNum = pagination.last_page - 4 + i;
                              } else {
                                pageNum = pagination.current_page - 2 + i;
                              }
                              
                              return (
                                <button
                                  key={pageNum}
                                  className={`pagination-number ${pagination.current_page === pageNum ? 'active' : ''}`}
                                  onClick={() => handlePageChange(pageNum)}
                                >
                                  {pageNum}
                                </button>
                              );
                            })}
                          </div>
                          <button 
                            className="pagination-btn"
                            onClick={() => handlePageChange(pagination.current_page + 1)}
                            disabled={pagination.current_page === pagination.last_page}
                          >
                            Suivant
                            <ChevronRight size={18} />
                          </button>
                        </div>
                      )}
                    </>
                  ) : (
                    <div className="products-list">
                      {filteredVariants.map(vl => {
                        const image = getProductImage(vl.variant);
                        return (
                          <div 
                            key={vl.id} 
                            className="transfer-item"
                            onClick={() => addTransferItem(vl)}
                            style={{ cursor: 'pointer' }}
                          >
                            <div className="item-image">
                              {image ? (
                                <img src={image} alt={vl.variant?.product?.name} />
                              ) : (
                                <div className="no-image">
                                  <ImageIcon size={16} />
                                </div>
                              )}
                            </div>
                            <div className="item-info">
                              <h5>{vl.variant?.product?.name}</h5>
                              <span className="sku">{vl.variant?.sku}</span>
                              <div className="item-attributes">
                                {getVariantAttributes(vl.variant)}
                              </div>
                            </div>
                            <div className="stock-info">
                              <span className="stock-badge">{vl.available_quantity} disponible(s)</span>
                            </div>
                            <button className="btn-add-item">
                              <Plus size={18} />
                            </button>
                          </div>
                        );
                      })}
                      
                      {/* Pagination pour la vue liste */}
                      {pagination.last_page > 1 && (
                        <div className="pagination-controls">
                          <button 
                            className="pagination-btn"
                            onClick={() => handlePageChange(pagination.current_page - 1)}
                            disabled={pagination.current_page === 1}
                          >
                            <ChevronLeft size={18} />
                            Précédent
                          </button>
                          <span className="pagination-info">
                            Page {pagination.current_page} sur {pagination.last_page}
                          </span>
                          <button 
                            className="pagination-btn"
                            onClick={() => handlePageChange(pagination.current_page + 1)}
                            disabled={pagination.current_page === pagination.last_page}
                          >
                            Suivant
                            <ChevronRight size={18} />
                          </button>
                        </div>
                      )}
                    </div>
                  )}
                </div>
              )}

              {/* Produits sélectionnés pour le transfert */}
              <div className="transfer-items-section">
                <h3 className="section-title">
                  <ShoppingBag size={18} />
                  Produits à transférer ({transferItems.length})
                </h3>

                {transferItems.length === 0 ? (
                  <div className="no-items-selected">
                    <Info size={20} />
                    <p>Cliquez sur un produit ci-dessus pour l'ajouter au transfert</p>
                  </div>
                ) : (
                  <>
                    <div className="transfer-items-list">
                      {transferItems.map(item => {
                        const image = getProductImage(item.variant);
                        return (
                          <div key={item.id} className="transfer-item">
                            <div className="item-image">
                              {image ? (
                                <img src={image} alt={item.variant?.product?.name} />
                              ) : (
                                <div className="no-image">
                                  <ImageIcon size={16} />
                                </div>
                              )}
                            </div>
                            <div className="item-info">
                              <h5>{item.variant?.product?.name}</h5>
                              <span className="sku">{item.variant?.sku}</span>
                              <div className="item-attributes">
                                {getVariantAttributes(item.variant)}
                              </div>
                            </div>
                            <div className="item-quantity">
                              <div className="quantity-control">
                                <button 
                                  className="qty-btn"
                                  onClick={() => updateItemQuantity(item.id, item.quantity - 1)}
                                  disabled={item.quantity <= 1}
                                >
                                  <Minus size={14} />
                                </button>
                                <input
                                  type="number"
                                  value={item.quantity}
                                  onChange={(e) => updateItemQuantity(item.id, parseInt(e.target.value) || 1)}
                                  min="1"
                                  max={item.availableQuantity}
                                />
                                <button 
                                  className="qty-btn"
                                  onClick={() => updateItemQuantity(item.id, item.quantity + 1)}
                                  disabled={item.quantity >= item.availableQuantity}
                                >
                                  <Plus size={14} />
                                </button>
                              </div>
                              <button 
                                className="max-qty-btn"
                                onClick={() => setMaxQuantity(item.id)}
                                title="Quantité maximum"
                              >
                                Max: {item.availableQuantity}
                              </button>
                            </div>
                            <button 
                              className="remove-item-btn"
                              onClick={() => removeTransferItem(item.id)}
                            >
                              <Trash2 size={16} />
                            </button>
                          </div>
                        );
                      })}
                    </div>

                    <div className="transfer-summary-bar">
                      <span>{getTotalVariants()} variante(s)</span>
                      <span className="separator">•</span>
                      <span><strong>{getTotalItems()}</strong> unités au total</span>
                    </div>
                  </>
                )}
              </div>

              <div className="step-actions">
                <button 
                  className="btn-secondary"
                  onClick={() => setStep(1)}
                >
                  <ArrowLeft size={18} />
                  Retour
                </button>
                <button 
                  className="btn-primary"
                  onClick={() => setStep(3)}
                  disabled={transferItems.length === 0}
                >
                  Continuer
                  <ArrowRight size={18} />
                </button>
              </div>
            </div>
          </div>
        )}

        {/* Étape 3: Destination et confirmation */}
        {step === 3 && (
          <div className="transfer-step">
            <div className="step-card">
              <div className="step-card-header">
                <MapPin size={24} />
                <div>
                  <h2>Destination et confirmation</h2>
                  <p>Choisissez la destination et validez le transfert</p>
                </div>
              </div>

              {/* Sélection destination */}
              <div className="destination-section">
                <h3 className="section-title">
                  <Warehouse size={18} />
                  Location de destination
                </h3>
                <div className="locations-grid">
                  {locations
                    .filter(l => l.id !== parseInt(fromLocationId))
                    .map(location => (
                      <div
                        key={location.id}
                        className={`location-card selectable ${toLocationId === String(location.id) ? 'selected' : ''}`}
                        onClick={() => setToLocationId(String(location.id))}
                      >
                        <div className="location-card-header">
                          <div className="location-icon">
                            <MapPin size={20} />
                          </div>
                          {toLocationId === String(location.id) && (
                            <div className="selected-badge">
                              <Check size={14} />
                            </div>
                          )}
                        </div>
                        <div className="location-card-body">
                          <h4>{location.name}</h4>
                          <p className="location-warehouse">{location.warehouse}</p>
                          <p className="location-code">Code: {location.code}</p>
                        </div>
                      </div>
                    ))}
                </div>
              </div>

              {/* Raison et notes */}
              <div className="additional-info-section">
                <div className="form-group">
                  <label>Raison du transfert (optionnel)</label>
                  <input
                    type="text"
                    value={reason}
                    onChange={(e) => setReason(e.target.value)}
                    placeholder="Ex: Réorganisation, Rapprochement client, Inventaire..."
                  />
                </div>
                <div className="form-group">
                  <label>Notes (optionnel)</label>
                  <textarea
                    value={notes}
                    onChange={(e) => setNotes(e.target.value)}
                    rows="3"
                    placeholder="Informations complémentaires..."
                  />
                </div>
              </div>

              {/* Récapitulatif */}
              <div className="transfer-recap">
                <h3 className="section-title">Récapitulatif du transfert</h3>
                
                <div className="recap-flow">
                  <div className="recap-location source">
                    <span className="label">Source</span>
                    <div className="location-badge">
                      <Warehouse size={18} />
                      <span>{getSourceLocation()?.name}</span>
                    </div>
                  </div>
                  <div className="recap-arrow">
                    <ArrowRight size={24} />
                    <span className="items-count">{getTotalItems()} unités</span>
                  </div>
                  <div className="recap-location destination">
                    <span className="label">Destination</span>
                    <div className={`location-badge ${!toLocationId ? 'empty' : ''}`}>
                      <Warehouse size={18} />
                      <span>{getDestinationLocation()?.name || 'Non sélectionnée'}</span>
                    </div>
                  </div>
                </div>

                <div className="recap-items">
                  <h4>{transferItems.length} produit(s) à transférer:</h4>
                  <ul>
                    {transferItems.map(item => (
                      <li key={item.id}>
                        <span className="item-name">{item.variant?.product?.name}</span>
                        <span className="item-sku">({item.variant?.sku})</span>
                        <span className="item-qty">× {item.quantity}</span>
                      </li>
                    ))}
                  </ul>
                </div>
              </div>

              <div className="step-actions">
                <button 
                  className="btn-secondary"
                  onClick={() => setStep(2)}
                >
                  <ArrowLeft size={18} />
                  Retour
                </button>
                <button 
                  className="btn-primary"
                  onClick={handleSubmit}
                  disabled={submitting || !toLocationId || transferItems.length === 0}
                >
                  {submitting ? (
                    <>
                      <Loader2 size={18} className="spinning" />
                      Transfert en cours...
                    </>
                  ) : (
                    <>
                      <Check size={18} />
                      Confirmer le transfert
                    </>
                  )}
                </button>
              </div>
            </div>
          </div>
        )}
      </div>
    </div>
  );
};

export default StockTransferForm;
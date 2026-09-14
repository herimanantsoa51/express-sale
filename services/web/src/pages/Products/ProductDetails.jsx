import React, { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { motion, AnimatePresence } from 'framer-motion';
import productService from '../../services/productService';
import '../../styles/ProductDetails.css';

// ProductHeader Component
const ProductHeader = ({ product }) => {
  const navigate = useNavigate();
  
  return (
    <div className="pd-header">
      <div className="pd-header-content">
        <div className="pd-header-info">
          <div className="pd-category-path">
            <span className="pd-category-text">{product.category?.name}</span>
            {product.subcategory && (
              <>
                <span className="pd-category-separator">/</span>
                <span className="pd-category-text">{product.subcategory.name}</span>
              </>
            )}
          </div>
          
          <h1 className="pd-product-name">{product.name}</h1>
          
          <div className="pd-header-meta">
            <div className="pd-price-container">
              <span className="pd-price-label">Prix de base</span>
              <span className="pd-price-value">
                {new Intl.NumberFormat('fr-FR', {
                  style: 'currency',
                  currency: 'MGA',
                  minimumFractionDigits: 0
                }).format(product.base_price)}
              </span>
            </div>
            
            <span className={`pd-status-badge ${product.is_active ? 'pd-status-active' : 'pd-status-inactive'}`}>
              {product.is_active ? 'Actif' : 'Inactif'}
            </span>
          </div>
          
          <button 
            className="pd-btn-primary"
            onClick={() => navigate(`/produits/${product.id}/modifier`)}
          >
            Modifier le produit
          </button>
        </div>
        
        <div className="pd-header-image">
          <div className="pd-image-wrapper">
            {product.image_url ? (
              <img src={product.image_url} alt={product.name} className="pd-product-image" />
            ) : (
              <div className="pd-image-placeholder">
                <svg width="64" height="64" viewBox="0 0 64 64" fill="none">
                  <rect width="64" height="64" rx="8" fill="var(--bg-tertiary)" />
                  <path d="M32 24v16M24 32h16" stroke="var(--text-tertiary)" strokeWidth="2" strokeLinecap="round" />
                </svg>
              </div>
            )}
          </div>
        </div>
      </div>
      
      {product.description && (
        <p className="pd-description">{product.description}</p>
      )}
    </div>
  );
};

// ProductStats Component
const ProductStats = ({ stats }) => {
  const formatCurrency = (value) => {
    return new Intl.NumberFormat('fr-FR', {
      style: 'currency',
      currency: 'MGA',
      minimumFractionDigits: 0
    }).format(value);
  };
  
  return (
    <div className="pd-section">
      <h2 className="pd-section-title">Statistiques globales</h2>
      
      <div className="pd-stats-grid">
        <div className="pd-stat-card">
          <span className="pd-stat-label">Variantes totales</span>
          <span className="pd-stat-value">{stats.total_variants}</span>
        </div>
        
        <div className="pd-stat-card">
          <span className="pd-stat-label">Variantes actives</span>
          <span className="pd-stat-value pd-stat-success">{stats.active_variants}</span>
        </div>
        
        <div className="pd-stat-card">
          <span className="pd-stat-label">Stock total</span>
          <span className="pd-stat-value">{stats.total_stock_all_variants}</span>
        </div>
        
        <div className="pd-stat-card">
          <span className="pd-stat-label">Total vendu</span>
          <span className="pd-stat-value">{stats.total_sold}</span>
        </div>
        
        <div className="pd-stat-card pd-stat-card-highlight">
          <span className="pd-stat-label">Chiffre d'affaires net</span>
          <span className="pd-stat-value">{formatCurrency(stats.net_revenue || 0)}</span>
        </div>
        
        <div className="pd-stat-card">
          <span className="pd-stat-label">Coût total</span>
          <span className="pd-stat-value pd-stat-cost">{formatCurrency(stats.total_cost || 0)}</span>
        </div>
        
        <div className="pd-stat-card pd-stat-card-profit">
          <span className="pd-stat-label">Bénéfice total</span>
          <span className="pd-stat-value pd-stat-profit">{formatCurrency(stats.total_profit || 0)}</span>
        </div>
        
        <div className="pd-stat-card">
          <span className="pd-stat-label">Marge bénéficiaire</span>
          <span className="pd-stat-value pd-stat-margin">{stats.profit_margin_percent?.toFixed(1) || 0}%</span>
        </div>
      </div>
    </div>
  );
};

// ProductAttributes Component
const ProductAttributes = ({ attributes }) => {
  if (!attributes || attributes.length === 0) return null;
  
  return (
    <div className="pd-section">
      <h2 className="pd-section-title">Attributs configurables</h2>
      
      <div className="pd-attributes-grid">
        {attributes.map((attr) => (
          <div key={attr.attribute_type_id} className="pd-attribute-card">
            <div className="pd-attribute-header">
              <span className="pd-attribute-name">{attr.attribute_type.display_name}</span>
              {attr.is_required && (
                <span className="pd-attribute-required">Obligatoire</span>
              )}
            </div>
            
            {attr.attribute_type.input_type === 'color' ? (
              <div className="pd-color-swatches">
                {attr.attribute_type.values.map((value) => (
                  <div key={value.id} className="pd-color-swatch-wrapper">
                    <div 
                      className="pd-color-swatch"
                      style={{ backgroundColor: value.value }}
                      title={value.value}
                    />
                  </div>
                ))}
              </div>
            ) : (
              <div className="pd-select-values">
                {attr.attribute_type.values.map((value) => (
                  <span key={value.id} className="pd-select-chip">
                    {value.value}
                  </span>
                ))}
              </div>
            )}
          </div>
        ))}
      </div>
    </div>
  );
};

// VariantStockTable Component
const VariantStockTable = ({ locations }) => {
  if (!locations || locations.length === 0) {
    return <p className="pd-empty-text">Aucun stock enregistré</p>;
  }
  
  return (
    <div className="pd-stock-table-wrapper">
      <table className="pd-stock-table">
        <thead>
          <tr>
            <th>Emplacement</th>
            <th>Quantité</th>
            <th>Notes</th>
          </tr>
        </thead>
        <tbody>
          {locations.map((location) => (
            <tr key={location.location_id}>
              <td className="pd-table-location">{location.location_name}</td>
              <td className="pd-table-quantity">{location.quantity}</td>
              <td className="pd-table-notes">{location.notes || '—'}</td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
};

// BatchesTable Component
const BatchesTable = ({ batches, pagination, onNavigateToReceipt, onPageChange }) => {
  const formatCurrency = (value) => {
    return new Intl.NumberFormat('fr-FR', {
      style: 'currency',
      currency: 'MGA',
      minimumFractionDigits: 0
    }).format(value);
  };

  const formatDate = (date) => {
    if (!date) return '—';
    return new Date(date).toLocaleDateString('fr-FR', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric'
    });
  };

  if (!batches || batches.length === 0) {
    return <p className="pd-empty-text">Aucun batch disponible</p>;
  }

  return (
    <>
      <div className="pd-batches-table-wrapper">
        <table className="pd-batches-table">
          <thead>
            <tr>
              <th>Numéro</th>
              <th>Réception</th>
              <th>Fournisseur</th>
              <th>Qté initiale</th>
              <th>Qté restante</th>
              <th>Qté disponible</th>
              <th>Qté vendue</th>
              <th>Coût unitaire</th>
              <th>Valeur stock</th>
              <th>Date réception</th>
              <th>Statut</th>
            </tr>
          </thead>
          <tbody>
            {batches.map((batch) => (
              <tr key={batch.id}>
                <td className="pd-batch-number">{batch.batch_number}</td>
                <td className="pd-batch-receipt">
                  <button 
                    className="pd-batch-link"
                    onClick={() => onNavigateToReceipt(batch.stock_receipt_id)}
                  >
                    {batch.receipt_number}
                  </button>
                </td>
                <td className="pd-batch-supplier">{batch.supplier_name}</td>
                <td className="pd-batch-qty">{batch.quantities.initial}</td>
                <td className="pd-batch-qty">{batch.quantities.remaining}</td>
                <td className="pd-batch-qty pd-batch-qty-available">{batch.quantities.available}</td>
                <td className="pd-batch-qty pd-batch-qty-sold">{batch.quantities.sold}</td>
                <td className="pd-batch-cost">{formatCurrency(batch.costs.total_unit_cost)}</td>
                <td className="pd-batch-value">{formatCurrency(batch.costs.total_batch_value)}</td>
                <td className="pd-batch-date">{formatDate(batch.dates.received_date)}</td>
                <td>
                  <span className={`pd-batch-status pd-batch-status-${batch.cost_status}`}>
                    {batch.cost_status === 'validated' ? 'Validé' : 'En attente'}
                  </span>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
      
      {/* Pagination */}
      {pagination && pagination.last_page > 1 && (
        <div className="pd-pagination">
          <div className="pd-pagination-info">
            Affichage de {pagination.from} à {pagination.to} sur {pagination.total} batches
          </div>
          
          <div className="pd-pagination-controls">
            <button
              className="pd-pagination-btn"
              onClick={() => onPageChange(1)}
              disabled={pagination.current_page === 1}
            >
              <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                <path d="M11 12L7 8l4-4M9 12L5 8l4-4" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round"/>
              </svg>
            </button>
            
            <button
              className="pd-pagination-btn"
              onClick={() => onPageChange(pagination.current_page - 1)}
              disabled={pagination.current_page === 1}
            >
              <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                <path d="M10 12L6 8l4-4" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round"/>
              </svg>
            </button>
            
            <span className="pd-pagination-current">
              Page {pagination.current_page} sur {pagination.last_page}
            </span>
            
            <button
              className="pd-pagination-btn"
              onClick={() => onPageChange(pagination.current_page + 1)}
              disabled={pagination.current_page === pagination.last_page}
            >
              <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                <path d="M6 12l4-4-4-4" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round"/>
              </svg>
            </button>
            
            <button
              className="pd-pagination-btn"
              onClick={() => onPageChange(pagination.last_page)}
              disabled={pagination.current_page === pagination.last_page}
            >
              <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                <path d="M5 12l4-4-4-4M7 12l4-4-4-4" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round"/>
              </svg>
            </button>
          </div>
        </div>
      )}
    </>
  );
};

// VariantCard Component - RÉORGANISÉ
const VariantCard = ({ variant, productId }) => {
  const navigate = useNavigate();
  const [batches, setBatches] = useState(null);
  const [loadingBatches, setLoadingBatches] = useState(false);
  const [activeTab, setActiveTab] = useState('stock');
  const [currentPage, setCurrentPage] = useState(1);
  const [pagination, setPagination] = useState(null);
  
  const formatPrice = (price) => {
    return new Intl.NumberFormat('fr-FR', {
      style: 'currency',
      currency: 'MGA',
      minimumFractionDigits: 0
    }).format(price);
  };

  const loadBatches = async (page = 1) => {
    setLoadingBatches(true);
    try {
      const data = await productService.getVariantBatches(productId, variant.id, page);
      setBatches(data);
      setPagination(data.pagination);
      setCurrentPage(page);
    } catch (error) {
      console.error('Erreur lors du chargement des batches:', error);
    } finally {
      setLoadingBatches(false);
    }
  };

  const handlePageChange = (page) => {
    loadBatches(page);
  };

  const handleNavigateToReceipt = (receiptId) => {
    navigate(`/reapprovisionnements/${receiptId}`);
  };
  
  return (
    <motion.div 
      className="pd-variant-card"
      initial={{ opacity: 0, y: 20 }}
      animate={{ opacity: 1, y: 0 }}
      transition={{ duration: 0.3 }}
    >
      {/* Top Section: Image + Header Info */}
      <div className="pd-variant-top">
        <div className="pd-variant-image-wrapper">
          {variant.image_path ? (
            <img src={variant.image_path} alt={variant.sku} className="pd-variant-image" />
          ) : (
            <div className="pd-variant-placeholder">
              <svg width="48" height="48" viewBox="0 0 48 48" fill="none">
                <rect width="48" height="48" rx="6" fill="var(--bg-tertiary)" />
                <path d="M24 18v12M18 24h12" stroke="var(--text-tertiary)" strokeWidth="1.5" strokeLinecap="round" />
              </svg>
            </div>
          )}
        </div>

        <div className="pd-variant-info">
          <div className="pd-variant-header">
            <div>
              <span className="pd-variant-sku">{variant.sku}</span>
              <div className="pd-variant-attributes">
                {variant.attributes.map((attr, idx) => (
                  <span key={idx} className="pd-variant-attr">
                    {attr.attribute_type_display}: {attr.attribute_type_name === 'couleur' ? (
                      <span 
                        className="pd-variant-color-indicator"
                        style={{ backgroundColor: attr.value }}
                      />
                    ) : (
                      <span className="pd-variant-attr-value">{attr.value}</span>
                    )}
                  </span>
                ))}
              </div>
            </div>
            
            <div className="pd-variant-price-status">
              <span className="pd-variant-price">{formatPrice(variant.final_price)}</span>
              <span className={`pd-variant-status ${variant.is_active ? 'pd-variant-active' : 'pd-variant-inactive'}`}>
                {variant.is_active ? 'Actif' : 'Inactif'}
              </span>
            </div>
          </div>

          {/* Financials Summary */}
          {variant.financials && variant.financials.units_sold > 0 && (
            <div className="pd-variant-financials">
              <div className="pd-financial-item">
                <span className="pd-financial-label">CA net</span>
                <span className="pd-financial-value">{formatPrice(variant.financials.net_revenue)}</span>
              </div>
              <div className="pd-financial-item">
                <span className="pd-financial-label">Coût</span>
                <span className="pd-financial-value pd-financial-cost">{formatPrice(variant.financials.total_cost)}</span>
              </div>
              <div className="pd-financial-item pd-financial-profit-item">
                <span className="pd-financial-label">Bénéfice</span>
                <span className="pd-financial-value pd-financial-profit">{formatPrice(variant.financials.total_profit)}</span>
              </div>
              <div className="pd-financial-item">
                <span className="pd-financial-label">Marge</span>
                <span className="pd-financial-value pd-financial-margin">{variant.financials.profit_margin_percent.toFixed(1)}%</span>
              </div>
            </div>
          )}
        </div>
      </div>

      {/* Tabs */}
      <div className="pd-variant-tabs">
        <button 
          className={`pd-tab ${activeTab === 'stock' ? 'pd-tab-active' : ''}`}
          onClick={() => setActiveTab('stock')}
        >
          Stock & Ventes
        </button>
        <button 
          className={`pd-tab ${activeTab === 'batches' ? 'pd-tab-active' : ''}`}
          onClick={() => {
            setActiveTab('batches');
            if (!batches && !loadingBatches) {
              loadBatches();
            }
          }}
        >
          Batches ({loadingBatches ? '...' : batches?.batches?.length || '0'})
        </button>
      </div>

      {/* Tab Content */}
      <AnimatePresence mode="wait">
        {activeTab === 'stock' && (
          <motion.div
            key="stock"
            initial={{ opacity: 0, y: 10 }}
            animate={{ opacity: 1, y: 0 }}
            exit={{ opacity: 0, y: -10 }}
            transition={{ duration: 0.2 }}
            className="pd-tab-content"
          >
            <div className="pd-variant-grid">
              <div className="pd-variant-section">
                <h4 className="pd-variant-section-title">Stock</h4>
                <div className="pd-stock-summary">
                  <div className="pd-stock-item">
                    <span className="pd-stock-label">Total</span>
                    <span className="pd-stock-value pd-stock-total">{variant.stock.total}</span>
                  </div>
                  <div className="pd-stock-item">
                    <span className="pd-stock-label">Disponible</span>
                    <span className="pd-stock-value pd-stock-available">{variant.stock.available}</span>
                  </div>
                  <div className="pd-stock-item">
                    <span className="pd-stock-label">Réservé</span>
                    <span className="pd-stock-value">{variant.stock.reserved_in_reservations}</span>
                  </div>
                  <div className="pd-stock-item">
                    <span className="pd-stock-label">Crédit</span>
                    <span className="pd-stock-value">{variant.stock.locked_in_active_credits}</span>
                  </div>
                </div>
                
                <VariantStockTable locations={variant.stock.by_location} />
              </div>
              
              <div className="pd-variant-section">
                <h4 className="pd-variant-section-title">Ventes</h4>
                <div className="pd-sales-grid">
                  <div className="pd-sales-item">
                    <span className="pd-sales-label">Ventes immédiates</span>
                    <span className="pd-sales-value">{variant.sales.immediate_sales}</span>
                  </div>
                  <div className="pd-sales-item">
                    <span className="pd-sales-label">Réservations</span>
                    <span className="pd-sales-value">{variant.sales.completed_reservations}</span>
                  </div>
                  <div className="pd-sales-item">
                    <span className="pd-sales-label">Crédits</span>
                    <span className="pd-sales-value">{variant.sales.completed_credits}</span>
                  </div>
                  <div className="pd-sales-item pd-sales-total">
                    <span className="pd-sales-label">Total vendu</span>
                    <span className="pd-sales-value">{variant.sales.total_sold}</span>
                  </div>
                </div>
              </div>
            </div>
          </motion.div>
        )}

        {activeTab === 'batches' && (
          <motion.div
            key="batches"
            initial={{ opacity: 0, y: 10 }}
            animate={{ opacity: 1, y: 0 }}
            exit={{ opacity: 0, y: -10 }}
            transition={{ duration: 0.2 }}
            className="pd-tab-content"
          >
            {loadingBatches ? (
              <div className="pd-batches-loading">
                <div className="pd-spinner"></div>
                <p>Chargement des batches...</p>
              </div>
            ) : batches ? (
              <>
                {batches.summary && (
                  <div className="pd-batches-summary">
                    <div className="pd-batch-summary-item">
                      <span className="pd-batch-summary-label">Total batches</span>
                      <span className="pd-batch-summary-value">{batches.summary.total_batches}</span>
                    </div>
                    <div className="pd-batch-summary-item">
                      <span className="pd-batch-summary-label">Stock restant</span>
                      <span className="pd-batch-summary-value">{batches.summary.total_remaining_stock}</span>
                    </div>
                    <div className="pd-batch-summary-item">
                      <span className="pd-batch-summary-label">Stock disponible</span>
                      <span className="pd-batch-summary-value">{batches.summary.total_available_stock}</span>
                    </div>
                    <div className="pd-batch-summary-item pd-batch-summary-highlight">
                      <span className="pd-batch-summary-label">Valeur inventaire</span>
                      <span className="pd-batch-summary-value">{formatPrice(batches.summary.total_inventory_value)}</span>
                    </div>
                  </div>
                )}
                <BatchesTable 
                  batches={batches.batches} 
                  onNavigateToReceipt={handleNavigateToReceipt}
                />
              </>
            ) : (
              <p className="pd-empty-text">Impossible de charger les batches</p>
            )}
          </motion.div>
        )}
      </AnimatePresence>
      
      {/* Alerts */}
      {(variant.alerts.is_low_stock || variant.alerts.has_pending_reservations || variant.alerts.has_active_credits) && (
        <div className="pd-variant-alerts">
          {variant.alerts.is_low_stock && (
            <div className="pd-alert pd-alert-warning">Stock faible</div>
          )}
          {variant.alerts.has_pending_reservations && (
            <div className="pd-alert pd-alert-info">Réservations en cours</div>
          )}
          {variant.alerts.has_active_credits && (
            <div className="pd-alert pd-alert-info">Crédits actifs</div>
          )}
        </div>
      )}
      
      {/* Actions */}
      <div className="pd-variant-actions">
        <button 
          className="pd-btn-secondary"
          onClick={() => navigate(`/produits/${productId}/variante/${variant.id}/modifier`)}
        >
          Modifier la variante
        </button>
      </div>
    </motion.div>
  );
};

// SkeletonProductDetail Component
const SkeletonProductDetail = () => {
  return (
    <div className="pd-container">
      <div className="pd-skeleton-header">
        <div className="pd-skeleton-block pd-skeleton-title" />
        <div className="pd-skeleton-block pd-skeleton-text" />
      </div>
      
      <div className="pd-skeleton-cards">
        {[1, 2, 3].map((i) => (
          <div key={i} className="pd-skeleton-card">
            <div className="pd-skeleton-block pd-skeleton-image" />
            <div className="pd-skeleton-block pd-skeleton-text" />
          </div>
        ))}
      </div>
    </div>
  );
};

// Main ProductDetailPage Component
const ProductDetails = () => {
  const { id } = useParams();
  const navigate = useNavigate();
  const [product, setProduct] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  
  useEffect(() => {
    const fetchProduct = async () => {
      try {
        setLoading(true);
        const data = await productService.getProduct(id);
        setProduct(data);
      } catch (err) {
        setError(err.message);
      } finally {
        setLoading(false);
      }
    };
    
    fetchProduct();
  }, [id]);
  
  if (loading) return <SkeletonProductDetail />;
  
  if (error) {
    return (
      <div className="pd-error-container">
        <div className="pd-error-content">
          <h2>Erreur de chargement</h2>
          <p>{error}</p>
          <button className="pd-btn-primary" onClick={() => navigate('/produits')}>
            Retour aux produits
          </button>
        </div>
      </div>
    );
  }
  
  if (!product) return null;
  
  return (
    <div className="pd-container">
      <ProductHeader product={product} />
      
      {product.product_stats && (
        <ProductStats stats={product.product_stats} />
      )}
      
      <ProductAttributes attributes={product.attributes} />
      
      <div className="pd-section">
        <div className="pd-section-header">
          <h2 className="pd-section-title">Variantes</h2>
          <button 
            className="pd-btn-primary"
            onClick={() => navigate(`/produits/${product.id}/variante/nouvelle`)}
          >
            Ajouter une variante
          </button>
        </div>
        
        <div className="pd-variants-list">
          {product.variants && product.variants.length > 0 ? (
            product.variants.map((variant) => (
              <VariantCard key={variant.id} variant={variant} productId={product.id} />
            ))
          ) : (
            <div className="pd-empty-state">
              <p className="pd-empty-text">Aucune variante disponible</p>
              <button 
                className="pd-btn-secondary"
                onClick={() => navigate(`/produits/${product.id}/variante/nouvelle`)}
              >
                Créer la première variante
              </button>
            </div>
          )}
        </div>
      </div>
    </div>
  );
};

export default ProductDetails;
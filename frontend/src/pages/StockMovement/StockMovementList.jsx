import { useState, useEffect } from 'react';
import { useNavigate, Link } from 'react-router-dom';
import { 
  ArrowRight, 
  Package, 
  MapPin, 
  Search, 
  Filter, 
  TrendingUp, 
  TrendingDown, 
  RefreshCw, 
  Plus, 
  User,
  ExternalLink,
  ShoppingCart,
  Truck,
  Image as ImageIcon,
  ChevronLeft,
  ChevronRight,
  ChevronsLeft,
  ChevronsRight,
  ArrowUpDown,
  ClipboardCheck,
  Eye,
  X,
  Clock,
  Warehouse,
  Layers,
  Calendar,
  AlertTriangle
} from 'lucide-react';
import stockMovementService from '../../services/stockMovementService';
import './StockMovementList.css';

const StockMovementList = () => {
  const navigate = useNavigate();
  
  // États principaux
  const [viewMode, setViewMode] = useState('individual'); // 'grouped' ou 'individual'
  const [loading, setLoading] = useState(true);
  const [statistics, setStatistics] = useState(null);
  
  // Données groupées
  const [groupedMovements, setGroupedMovements] = useState([]);
  const [groupedPagination, setGroupedPagination] = useState({ current_page: 1, last_page: 1, total: 0 });
  
  // Données individuelles
  const [movements, setMovements] = useState([]);
  const [individualPagination, setIndividualPagination] = useState({ current_page: 1, last_page: 1, total: 0 });
  
  // Filtres
  const [searchTerm, setSearchTerm] = useState('');
  const [typeFilter, setTypeFilter] = useState('');
  const [daysFilter, setDaysFilter] = useState(30);
  
  // Pagination
  const [currentPage, setCurrentPage] = useState(1);
  const [perPage] = useState(20);

  // Modal détail batch
  const [selectedBatch, setSelectedBatch] = useState(null);
  const [batchDetails, setBatchDetails] = useState(null);
  const [loadingBatchDetails, setLoadingBatchDetails] = useState(false);

  // Modal détail mouvement individuel
  const [selectedMovement, setSelectedMovement] = useState(null);

  useEffect(() => {
    if (viewMode === 'grouped') {
      loadGroupedMovements();
    } else {
      loadMovements();
    }
    loadStatistics();
  }, [typeFilter, daysFilter, currentPage, viewMode]);

  useEffect(() => {
    const timer = setTimeout(() => {
      setCurrentPage(1);
      if (viewMode === 'grouped') {
        loadGroupedMovements();
      } else {
        loadMovements();
      }
    }, 300);
    return () => clearTimeout(timer);
  }, [searchTerm]);

  const loadGroupedMovements = async () => {
    try {
      setLoading(true);
      const params = {
        page: currentPage,
        per_page: perPage,
        movement_type: typeFilter || undefined,
        days: daysFilter || undefined
      };

      Object.keys(params).forEach(key => {
        if (params[key] === undefined || params[key] === '') delete params[key];
      });

      const response = await stockMovementService.getGrouped(params);
      setGroupedMovements(response.data || []);
      setGroupedPagination({
        current_page: response.current_page || 1,
        last_page: response.last_page || 1,
        total: response.total || 0
      });
    } catch (err) {
      console.error('Erreur chargement mouvements groupés:', err);
      setGroupedMovements([]);
    } finally {
      setLoading(false);
    }
  };

  const loadMovements = async () => {
    try {
      setLoading(true);
      const params = {
        page: currentPage,
        per_page: perPage,
        search: searchTerm || undefined,
        movement_type: typeFilter || undefined,
        days: daysFilter || undefined
      };

      Object.keys(params).forEach(key => {
        if (params[key] === undefined || params[key] === '') delete params[key];
      });

      const response = await stockMovementService.getAll(params);
      setMovements(response.data || []);
      setIndividualPagination({
        current_page: response.current_page || 1,
        last_page: response.last_page || 1,
        total: response.total || 0
      });
    } catch (err) {
      console.error('Erreur chargement mouvements:', err);
      setMovements([]);
    } finally {
      setLoading(false);
    }
  };

  const loadStatistics = async () => {
    try {
      const stats = await stockMovementService.getStatistics({ days: daysFilter || 30 });
      setStatistics(stats);
    } catch (err) {
      console.error('Erreur chargement statistiques:', err);
    }
  };

  const loadBatchDetails = async (batchId) => {
    try {
      setLoadingBatchDetails(true);
      const details = await stockMovementService.getBatchDetails(batchId);
      setBatchDetails(details);
    } catch (err) {
      console.error('Erreur chargement détails batch:', err);
    } finally {
      setLoadingBatchDetails(false);
    }
  };

  const handleBatchClick = (batch) => {
    setSelectedBatch(batch);
    loadBatchDetails(batch.batch_id);
  };

  // Helpers
  const getMovementIcon = (type) => {
    const icons = {
      transfer: <ArrowUpDown size={18} />,
      receipt: <TrendingUp size={18} />,
      sale: <ShoppingCart size={18} />,
      loss: <AlertTriangle size={18} />,  // NOUVEAU
      reconciliation: <ClipboardCheck size={18} />,  // NOUVEAU
      return: <RefreshCw size={18} />,
      restock:<RefreshCw size={18} />
    };
    return icons[type] || <Package size={18} />;
  };

  const getMovementTypeName = (type) => {
    const types = {
      transfer: 'Transfert',
      receipt: 'Réception',
      sale: 'Vente',
      loss: 'Perte',  // NOUVEAU
      return: 'Retour',
      restock:'Annulation Vente',
      reconciliation: 'Réconciliation',
    };
    return types[type] || type;
  };

  const getMovementBadgeClass = (type) => {
    const classes = {
      transfer: 'badge-primary',
      receipt: 'badge-success',
      sale: 'badge-danger',
      loss: 'badge-warning',  // NOUVEAU
      return: 'badge-info',
      restock:'badge-info',
      reconciliation: 'badge-info'
    };
    return classes[type] || 'badge-default';
  };
  const formatDate = (dateString) => {
    const date = new Date(dateString);
    return date.toLocaleString('fr-FR', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  };

  const formatDateShort = (dateString) => {
    const date = new Date(dateString);
    return date.toLocaleDateString('fr-FR', {
      day: '2-digit',
      month: 'short',
      hour: '2-digit',
      minute: '2-digit'
    });
  };

  const getProductImage = (movement) => {
    return movement.variant?.image_path || movement.variant?.product?.image_url || null;
  };

  const getVariantAttributes = (variant) => {
    if (!variant?.attribute_values || variant.attribute_values.length === 0) return null;
    return variant.attribute_values.map((attr, idx) => (
      <span key={idx} className="attribute-mini-tag">
        {attr.attribute_value?.value || attr.value}
      </span>
    ));
  };

  const getReferenceLink = (movement) => {
    console.log('movement',movement);
    if (movement.movement_type === 'receipt' && movement.stock_receipt_id) {
      return (
        <Link 
          to={`/reapprovisionnements/${movement.stock_receipt_id}`}
          className="reference-link receipt"
          onClick={(e) => e.stopPropagation()}
        >
          <Truck size={14} />
          <span>Réappro #{movement.stock_receipt_id}</span>
          <ExternalLink size={12} />
        </Link>
      );
    }
    if ((movement.movement_type === 'sale') && movement.sale_id) {
      if (movement.sale?.sale_type == 'immediate') {
        return (
          <Link 
            to={`/ventes/immediates/${movement.sale_id}`}
            className="reference-link sale"
            onClick={(e) => e.stopPropagation()}
          >
            <ShoppingCart size={14} />
            <span>Vente #{movement.sale.sale_number}</span>
            <ExternalLink size={12} />
          </Link>
        );
      }
      else if(movement.sale?.sale_type == 'reservation'){
        return (
          <Link 
            to={`/ventes/reservations/${movement.sale.reservation.id}`}
            className="reference-link sale"
            onClick={(e) => e.stopPropagation()}
          >
            <ShoppingCart size={14} />
            <span>Réservation #{movement.sale.sale_number}</span>
            <ExternalLink size={12} />
          </Link>
        );
      }
      else if(movement.sale?.sale_type == 'credit'){
        return (
          <Link 
            to={`/ventes/credits/${movement.sale.credit.id}`}
            className="reference-link sale"
            onClick={(e) => e.stopPropagation()}
          >
            <ShoppingCart size={14} />
            <span>Credit #{movement.sale.sale_number}</span>
            <ExternalLink size={12} />
          </Link>
        );
      }
      
    }
    else if (movement.movement_type =='reservation' )
    {
      return (
        <Link 
          to={`/ventes/reservations/${movement.sale.reservation.id}`}
          className="reference-link reservation"
          onClick={(e) => e.stopPropagation()}
        >
          <Warehouse size={14} />
          <span>Réservation #{movement.sale.sale_number}</span>
          <ExternalLink size={12} />
        </Link>
      );
    }
    else if (movement.movement_type =='restock')
    {
        if (movement.sale?.sale_type == 'immediate') {
          return (
            <Link 
              to={`/ventes/immediates/${movement.sale_id}`}
              className="reference-link sale"
              onClick={(e) => e.stopPropagation()}
            >
              <ShoppingCart size={14} />
              <span>Vente #{movement.sale.sale_number}</span>
              <ExternalLink size={12} />
            </Link>
          );
        }
        else if(movement.sale?.sale_type == 'reservation'){
          return (
            <Link 
              to={`/ventes/reservations/${movement.sale.reservation.id}`}
              className="reference-link sale"
              onClick={(e) => e.stopPropagation()}
            >
              <ShoppingCart size={14} />
              <span>Réservation #{movement.sale.sale_number}</span>
              <ExternalLink size={12} />
            </Link>
          );
        }
        else if(movement.sale?.sale_type == 'credit'){
          return (
            <Link 
              to={`/ventes/credits/${movement.sale.credit.id}`}
              className="reference-link sale"
              onClick={(e) => e.stopPropagation()}
            >
              <ShoppingCart size={14} />
              <span>Credit #{movement.sale.sale_number}</span>
              <ExternalLink size={12} />
            </Link>
          );
        }
      }
      return null;
  };

  // Pagination
  const pagination = viewMode === 'grouped' ? groupedPagination : individualPagination;
  const totalPages = pagination.last_page;

  const goToPage = (page) => {
    if (page >= 1 && page <= totalPages) {
      setCurrentPage(page);
    }
  };

  const getPageNumbers = () => {
    const pages = [];
    const maxVisible = 5;
    let start = Math.max(1, currentPage - Math.floor(maxVisible / 2));
    let end = Math.min(totalPages, start + maxVisible - 1);
    if (end - start + 1 < maxVisible) {
      start = Math.max(1, end - maxVisible + 1);
    }
    for (let i = start; i <= end; i++) {
      pages.push(i);
    }
    return pages;
  };

  const handleRefresh = () => {
    if (viewMode === 'grouped') {
      loadGroupedMovements();
    } else {
      loadMovements();
    }
    loadStatistics();
  };

  return (
    <div className="stock-movement-page">
      {/* Header */}
      <div className="page-header">
        <div className="header-content">
          <div className="header-icon">
            <ArrowUpDown size={28} />
          </div>
          <div>
            <h1 className="page-title">Mouvements de Stock</h1>
            <p className="page-subtitle">
              Historique et traçabilité des mouvements de stock
            </p>
          </div>
        </div>
        <div className="header-actions">
          {/* <button
            className="btn-secondary"
            onClick={() => navigate('/mouvements-stock/reconciliation')}
          >
            <ClipboardCheck size={18} />
            Réconciliation
          </button> */}
          <button
            className="btn-secondary"
            onClick={() => navigate('/mouvements-stock/perte')}
          >
            <AlertTriangle size={18} />
            Déclarer une perte
          </button>
          <button
            className="btn-primary"
            onClick={() => navigate('/mouvements-stock/transfert')}
          >
            <Plus size={18} />
            Nouveau Transfert
          </button>
        </div>
      </div>

      {/* Statistiques */}
      {statistics && (
        <div className="stats-grid">
          <div className="stat-card">
            <div className="stat-icon primary">
              <ArrowUpDown size={24} />
            </div>
            <div className="stat-content">
              <div className="stat-value">{statistics.total_movements || 0}</div>
              <div className="stat-label">Mouvements ({daysFilter}j)</div>
            </div>
          </div>
          <div className="stat-card">
            <div className="stat-icon success">
              <TrendingUp size={24} />
            </div>
            <div className="stat-content">
              <div className="stat-value">{statistics.total_incoming || 0}</div>
              <div className="stat-label">Entrées</div>
            </div>
          </div>
          <div className="stat-card">
            <div className="stat-icon danger">
              <TrendingDown size={24} />
            </div>
            <div className="stat-content">
              <div className="stat-value">{statistics.total_outgoing || 0}</div>
              <div className="stat-label">Sorties</div>
            </div>
          </div>
          <div className="stat-card">
            <div className="stat-icon info">
              <RefreshCw size={24} />
            </div>
            <div className="stat-content">
              <div className="stat-value">{statistics.total_transfers || 0}</div>
              <div className="stat-label">Transferts</div>
            </div>
          </div>
        </div>
      )}

      {/* Filtres */}
      <div className="filters-section">
        <div className="search-bar">
          <Search className="search-icon" size={20} />
          <input
            type="text"
            placeholder="Rechercher par produit, SKU, location..."
            value={searchTerm}
            onChange={(e) => setSearchTerm(e.target.value)}
            className="search-input"
            disabled={viewMode === 'grouped'}
          />
          {searchTerm && (
            <button className="search-clear" onClick={() => setSearchTerm('')}>
              <X size={16} />
            </button>
          )}
        </div>

        <div className="filter-controls">
        <select
            value={typeFilter}
            onChange={(e) => { setTypeFilter(e.target.value); setCurrentPage(1); }}
            className="filter-select"
          >
            <option value="">Tous les types</option>
            <option value="transfer">Transferts</option>
            <option value="receipt">Réceptions</option>
            <option value="sale">Ventes</option>
            <option value="loss">Pertes</option>  {/* NOUVEAU */}
            <option value="restock">Retours</option>
          </select>

          <select
            value={daysFilter}
            onChange={(e) => { setDaysFilter(parseInt(e.target.value) || ''); setCurrentPage(1); }}
            className="filter-select"
          >
            <option value="7">7 derniers jours</option>
            <option value="30">30 derniers jours</option>
            <option value="90">3 derniers mois</option>
            <option value="365">Dernière année</option>
            <option value="">Tout l'historique</option>
          </select>

          <div className="view-toggle">
            <button 
              className={viewMode === 'grouped' ? 'active' : ''}
              onClick={() => { setViewMode('grouped'); setCurrentPage(1); }}
              title="Vue groupée"
            >
              <Layers size={18} />
            </button>
            <button 
              className={viewMode === 'individual' ? 'active' : ''}
              onClick={() => { setViewMode('individual'); setCurrentPage(1); }}
              title="Vue détaillée"
            >
              <Package size={18} />
            </button>
          </div>

          <button 
            className="btn-icon" 
            onClick={handleRefresh}
            title="Actualiser"
          >
            <RefreshCw size={18} className={loading ? 'spinning' : ''} />
          </button>
        </div>
      </div>

      {/* Contenu principal */}
      <div className="movements-container">
        {loading ? (
          <div className="loading-state">
            <div className="loading-spinner large"></div>
            <p>Chargement des mouvements...</p>
          </div>
        ) : viewMode === 'grouped' ? (
          // Vue groupée
          groupedMovements.length === 0 ? (
            <div className="empty-state">
              <Package size={64} />
              <h3>Aucun mouvement trouvé</h3>
              <p>Aucun mouvement de stock ne correspond à vos critères.</p>
              <button className="btn-primary" onClick={() => navigate('/mouvements-stock/transfert')}>
                <Plus size={18} />
                Créer un transfert
              </button>
            </div>
          ) : (
            <>
              <div className="movements-header">
                <span className="results-count">
                  {groupedPagination.total} opération{groupedPagination.total > 1 ? 's' : ''} groupée{groupedPagination.total > 1 ? 's' : ''}
                </span>
              </div>

              <div className="grouped-movements-list">
                {groupedMovements.map((batch) => (
                  <div 
                    key={batch.batch_id} 
                    className="grouped-movement-card"
                    onClick={() => handleBatchClick(batch)}
                  >
                    <div className={`movement-type-indicator ${batch.movement_type}`}>
                      {getMovementIcon(batch.movement_type)}
                    </div>
                    
                    <div className="grouped-movement-content">
                      <div className="grouped-movement-header">
                        <div className="movement-title">
                          <h4>{getMovementTypeName(batch.movement_type)}</h4>
                          <span className="movement-batch-id">{batch.batch_id}</span>
                        </div>
                        <div className={`movement-badge ${getMovementBadgeClass(batch.movement_type)}`}>
                          {getMovementIcon(batch.movement_type)}
                          <span>{getMovementTypeName(batch.movement_type)}</span>
                        </div>
                      </div>

                      <div className="movement-flow">
                        {batch.from_location ? (
                          <div className="flow-location from">
                            <MapPin size={14} />
                            <span>{batch.from_location.name}</span>
                          </div>
                        ) : (
                          <div className="flow-location empty">
                            <span>—</span>
                          </div>
                        )}
                        
                        <div className="flow-arrow">
                          <ArrowRight size={18} />
                        </div>

                        {batch.to_location ? (
                          <div className="flow-location to">
                            <MapPin size={14} />
                            <span>{batch.to_location.name}</span>
                          </div>
                        ) : (
                          <div className="flow-location empty">
                            <span>—</span>
                          </div>
                        )}
                      </div>

                      <div className="movement-footer">
                        <div className="movement-meta">
                          <span className="meta-item">
                            <Clock size={14} />
                            {formatDateShort(batch.created_at)}
                          </span>
                          {batch.performed_by && (
                            <span className="meta-item">
                              <User size={14} />
                              {batch.performed_by.name}
                            </span>
                          )}
                        </div>
                        {batch.reason && (
                          <span className="reason-tag" title={batch.reason}>
                            {batch.reason}
                          </span>
                        )}
                      </div>
                    </div>

                    <div className="grouped-movement-stats">
                      <div className="items-count-badge">
                        <Package size={14} />
                        <strong>{batch.items_count}</strong> produit{batch.items_count > 1 ? 's' : ''}
                      </div>
                      <div className="total-quantity-badge">
                        {batch.total_quantity} unités
                      </div>
                    </div>
                  </div>
                ))}
              </div>
            </>
          )
        ) : (
          // Vue individuelle
          movements.length === 0 ? (
            <div className="empty-state">
              <Package size={64} />
              <h3>Aucun mouvement trouvé</h3>
              <p>Aucun mouvement de stock ne correspond à vos critères.</p>
              <button className="btn-primary" onClick={() => navigate('/mouvements-stock/transfert')}>
                <Plus size={18} />
                Créer un transfert
              </button>
            </div>
          ) : (
            <>
              <div className="movements-header">
                <span className="results-count">
                  {individualPagination.total} mouvement{individualPagination.total > 1 ? 's' : ''}
                </span>
              </div>

              <div className="movements-list">
                {movements.map((movement) => {
                  const productImage = getProductImage(movement);
                  const referenceLink = getReferenceLink(movement);

                  return (
                    <div 
                      key={movement.id} 
                      className="movement-card"
                      onClick={() => setSelectedMovement(movement)}
                    >
                      <div className="movement-product-image">
                        {productImage ? (
                          <img src={productImage} alt={movement.variant?.product?.name} />
                        ) : (
                          <div className="no-image">
                            <ImageIcon size={24} />
                          </div>
                        )}
                        <div className={`movement-type-indicator ${movement.movement_type}`}>
                          {getMovementIcon(movement.movement_type)}
                        </div>
                      </div>

                      <div className="movement-content">
                        <div className="movement-header">
                          <div className="movement-product-info">
                            <h4>{movement.variant?.product?.name || 'Produit inconnu'}</h4>
                            <div className="product-meta">
                              <span className="sku">{movement.variant?.sku}</span>
                              <div className="attributes">
                                {getVariantAttributes(movement.variant)}
                              </div>
                            </div>
                          </div>
                          <div className={`movement-badge ${getMovementBadgeClass(movement.movement_type)}`}>
                            {getMovementIcon(movement.movement_type)}
                            <span>{getMovementTypeName(movement.movement_type)}</span>
                          </div>
                        </div>

                        <div className="movement-flow">
                          {movement.from_location ? (
                            <div className="flow-location from">
                              <MapPin size={14} />
                              <span>{movement.from_location.name}</span>
                            </div>
                          ) : (
                            <div className="flow-location empty">
                              <span>—</span>
                            </div>
                          )}
                          
                          <div className="flow-arrow">
                            <ArrowRight size={18} />
                          </div>

                          {movement.to_location ? (
                            <div className="flow-location to">
                              <MapPin size={14} />
                              <span>{movement.to_location.name}</span>
                            </div>
                          ) : (
                            <div className="flow-location empty">
                              <span>—</span>
                            </div>
                          )}
                        </div>

                        <div className="movement-footer">
                          <div className="movement-meta">
                            <span className="meta-item">
                              <Clock size={14} />
                              {formatDate(movement.created_at)}
                            </span>
                            {movement.performed_by && (
                              <span className="meta-item">
                                <User size={14} />
                                {movement.performed_by.name}
                              </span>
                            )}
                          </div>
                          
                          <div className="movement-actions">
                            {referenceLink}
                            {movement.reason && (
                              <span className="reason-tag" title={movement.reason}>
                                {movement.reason}
                              </span>
                            )}
                          </div>
                        </div>
                      </div>

                      <div className={`movement-quantity ${movement.to_location_id ? 'positive' : 'negative'}`}>
                        <span className="qty-value">
                          {movement.to_location_id ? '+' : '-'}{movement.quantity}
                        </span>
                        <span className="qty-label">unités</span>
                      </div>
                    </div>
                  );
                })}
              </div>
            </>
          )
        )}

        {/* Pagination */}
        {totalPages > 1 && (
          <div className="pagination-container">
            <div className="pagination">
              <button
                className="pagination-btn"
                onClick={() => goToPage(1)}
                disabled={currentPage === 1}
                title="Première page"
              >
                <ChevronsLeft size={16} />
              </button>
              <button
                className="pagination-btn"
                onClick={() => goToPage(currentPage - 1)}
                disabled={currentPage === 1}
                title="Page précédente"
              >
                <ChevronLeft size={16} />
              </button>

              <div className="pagination-pages">
                {getPageNumbers().map(page => (
                  <button
                    key={page}
                    className={`pagination-page ${currentPage === page ? 'active' : ''}`}
                    onClick={() => goToPage(page)}
                  >
                    {page}
                  </button>
                ))}
              </div>

              <button
                className="pagination-btn"
                onClick={() => goToPage(currentPage + 1)}
                disabled={currentPage === totalPages}
                title="Page suivante"
              >
                <ChevronRight size={16} />
              </button>
              <button
                className="pagination-btn"
                onClick={() => goToPage(totalPages)}
                disabled={currentPage === totalPages}
                title="Dernière page"
              >
                <ChevronsRight size={16} />
              </button>
            </div>
            <div className="pagination-info">
              Page {currentPage} sur {totalPages} • {pagination.total} résultats
            </div>
          </div>
        )}
      </div>

      {/* Modal détail batch */}
      {selectedBatch && (
        <div className="modal-overlay" onClick={() => { setSelectedBatch(null); setBatchDetails(null); }}>
          <div className="modal-content batch-detail-modal" onClick={(e) => e.stopPropagation()}>
            <div className="modal-header">
              <h2>Détail du mouvement groupé</h2>
              <button className="modal-close" onClick={() => { setSelectedBatch(null); setBatchDetails(null); }}>
                <X size={20} />
              </button>
            </div>
            
            <div className="modal-body">
              {loadingBatchDetails ? (
                <div className="loading-state">
                  <div className="loading-spinner"></div>
                  <p>Chargement des détails...</p>
                </div>
              ) : batchDetails ? (
                <>
                  {/* En-tête du batch */}
                  <div className="detail-row">
                    <div className="detail-item">
                      <label>Type de mouvement</label>
                      <div className={`movement-badge large ${getMovementBadgeClass(batchDetails.movement_type)}`}>
                        {getMovementIcon(batchDetails.movement_type)}
                        <span>{getMovementTypeName(batchDetails.movement_type)}</span>
                      </div>
                    </div>
                    <div className="detail-item">
                      <label>ID du batch</label>
                      <p style={{ fontFamily: 'var(--font-mono)' }}>{batchDetails.batch_id}</p>
                    </div>
                  </div>

                  {/* Flux de mouvement */}
                  <div className="detail-section">
                    <h3>Mouvement</h3>
                    <div className="detail-flow">
                      <div className="detail-location">
                        <label>Source</label>
                        {batchDetails.from_location ? (
                          <div className="location-info">
                            <Warehouse size={18} />
                            <div>
                              <strong>{batchDetails.from_location.name}</strong>
                              <span>{batchDetails.from_location.warehouse}</span>
                            </div>
                          </div>
                        ) : (
                          <span className="text-muted">Non applicable</span>
                        )}
                      </div>
                      <div className="detail-arrow">
                        <ArrowRight size={24} />
                      </div>
                      <div className="detail-location">
                        <label>Destination</label>
                        {batchDetails.to_location ? (
                          <div className="location-info">
                            <Warehouse size={18} />
                            <div>
                              <strong>{batchDetails.to_location.name}</strong>
                              <span>{batchDetails.to_location.warehouse}</span>
                            </div>
                          </div>
                        ) : (
                          <span className="text-muted">Non applicable</span>
                        )}
                      </div>
                    </div>
                  </div>

                  {/* Raison et notes */}
                  {(batchDetails.reason || batchDetails.notes) && (
                    <div className="detail-section">
                      <h3>Informations complémentaires</h3>
                      {batchDetails.reason && (
                        <div className="detail-item">
                          <label>Raison</label>
                          <p>{batchDetails.reason}</p>
                        </div>
                      )}
                      {batchDetails.notes && (
                        <div className="detail-item">
                          <label>Notes</label>
                          <p>{batchDetails.notes}</p>
                        </div>
                      )}
                    </div>
                  )}

                  {/* Liste des items */}
                  <div className="detail-section">
                    <h3>Produits ({batchDetails.items_count} • {batchDetails.total_quantity} unités)</h3>
                    <div className="batch-items-list">
                      {batchDetails.items?.map((item) => {
                        const image = getProductImage(item);
                        const refLink = getReferenceLink(item);
                        return (
                          <div key={item.id} className="batch-item">
                            <div className="batch-item-image">
                              {image ? (
                                <img src={image} alt={item.variant?.product?.name} />
                              ) : (
                                <div className="no-image">
                                  <ImageIcon size={16} />
                                </div>
                              )}
                            </div>
                            <div className="batch-item-info">
                              <h5>{item.variant?.product?.name}</h5>
                              <span className="sku">{item.variant?.sku}</span>
                              {refLink && <div style={{ marginTop: '4px' }}>{refLink}</div>}
                            </div>
                            <div className="batch-item-qty">
                              ×{item.quantity}
                            </div>
                          </div>
                        );
                      })}
                    </div>
                  </div>

                  {/* Métadonnées */}
                  <div className="detail-section">
                    <h3>Métadonnées</h3>
                    <div className="detail-meta-grid">
                      <div className="detail-meta-item">
                        <Clock size={16} />
                        <div>
                          <label>Date</label>
                          <span>{formatDate(batchDetails.created_at)}</span>
                        </div>
                      </div>
                      <div className="detail-meta-item">
                        <User size={16} />
                        <div>
                          <label>Effectué par</label>
                          <span>{batchDetails.performed_by?.name || 'N/A'}</span>
                        </div>
                      </div>
                    </div>
                  </div>
                </>
              ) : (
                <div className="empty-state">
                  <p>Impossible de charger les détails</p>
                </div>
              )}
            </div>
          </div>
        </div>
      )}

      {/* Modal détail mouvement individuel */}
      {selectedMovement && (
        <div className="modal-overlay" onClick={() => setSelectedMovement(null)}>
          <div className="modal-content movement-detail-modal" onClick={(e) => e.stopPropagation()}>
            <div className="modal-header">
              <h2>Détail du mouvement</h2>
              <button className="modal-close" onClick={() => setSelectedMovement(null)}>
                <X size={20} />
              </button>
            </div>
            
            <div className="modal-body">
              {/* Produit */}
              <div className="detail-section">
                <h3>Produit</h3>
                <div className="detail-product">
                  <div className="detail-product-image">
                    {getProductImage(selectedMovement) ? (
                      <img src={getProductImage(selectedMovement)} alt="" />
                    ) : (
                      <div className="no-image"><ImageIcon size={32} /></div>
                    )}
                  </div>
                  <div className="detail-product-info">
                    <h4>{selectedMovement.variant?.product?.name}</h4>
                    <p className="sku">SKU: {selectedMovement.variant?.sku}</p>
                    <div className="attributes">
                      {getVariantAttributes(selectedMovement.variant)}
                    </div>
                    <Link 
                      to={`/produits/${selectedMovement.variant?.product_id}`}
                      className="btn-link"
                    >
                      Voir le produit <ExternalLink size={14} />
                    </Link>
                  </div>
                </div>
              </div>

              {/* Type et quantité */}
              <div className="detail-row">
                <div className="detail-item">
                  <label>Type de mouvement</label>
                  <div className={`movement-badge large ${getMovementBadgeClass(selectedMovement.movement_type)}`}>
                    {getMovementIcon(selectedMovement.movement_type)}
                    <span>{getMovementTypeName(selectedMovement.movement_type)}</span>
                  </div>
                </div>
                <div className="detail-item">
                  <label>Quantité</label>
                  <div className={`detail-quantity ${selectedMovement.to_location_id ? 'positive' : 'negative'}`}>
                    {selectedMovement.to_location_id ? '+' : '-'}{selectedMovement.quantity} unités
                  </div>
                </div>
              </div>

              {/* Locations */}
              <div className="detail-section">
                <h3>Mouvement</h3>
                <div className="detail-flow">
                  <div className="detail-location">
                    <label>Source</label>
                    {selectedMovement.from_location ? (
                      <div className="location-info">
                        <Warehouse size={18} />
                        <div>
                          <strong>{selectedMovement.from_location.name}</strong>
                          <span>{selectedMovement.from_location.warehouse}</span>
                        </div>
                      </div>
                    ) : (
                      <span className="text-muted">Non applicable</span>
                    )}
                  </div>
                  <div className="detail-arrow">
                    <ArrowRight size={24} />
                  </div>
                  <div className="detail-location">
                    <label>Destination</label>
                    {selectedMovement.to_location ? (
                      <div className="location-info">
                        <Warehouse size={18} />
                        <div>
                          <strong>{selectedMovement.to_location.name}</strong>
                          <span>{selectedMovement.to_location.warehouse}</span>
                        </div>
                      </div>
                    ) : (
                      <span className="text-muted">Non applicable</span>
                    )}
                  </div>
                </div>
              </div>

              {/* Référence */}
              {getReferenceLink(selectedMovement) && (
                <div className="detail-section">
                  <h3>Document associé</h3>
                  <div className="detail-reference">
                    {getReferenceLink(selectedMovement)}
                  </div>
                </div>
              )}

              {/* Raison et notes */}
              {(selectedMovement.reason || selectedMovement.notes) && (
                <div className="detail-section">
                  <h3>Informations complémentaires</h3>
                  {selectedMovement.reason && (
                    <div className="detail-item">
                      <label>Raison</label>
                      <p>{selectedMovement.reason}</p>
                    </div>
                  )}
                  {selectedMovement.notes && (
                    <div className="detail-item">
                      <label>Notes</label>
                      <p>{selectedMovement.notes}</p>
                    </div>
                  )}
                </div>
              )}

              {/* Métadonnées */}
              <div className="detail-section">
                <h3>Métadonnées</h3>
                <div className="detail-meta-grid">
                  <div className="detail-meta-item">
                    <Clock size={16} />
                    <div>
                      <label>Date</label>
                      <span>{formatDate(selectedMovement.created_at)}</span>
                    </div>
                  </div>
                  <div className="detail-meta-item">
                    <User size={16} />
                    <div>
                      <label>Effectué par</label>
                      <span>{selectedMovement.performed_by?.name || 'N/A'}</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default StockMovementList;
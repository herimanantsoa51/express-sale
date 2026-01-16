import { useState, useEffect } from 'react';
import { useParams, useNavigate, Link } from 'react-router-dom';
import {
  ArrowLeft,
  Package,
  Truck,
  Building2,
  Calendar,
  Clock,
  FileText,
  CreditCard,
  DollarSign,
  User,
  Box,
  Layers,
  TrendingUp,
  AlertCircle,
  AlertTriangle,
  ExternalLink,
  Loader2,
  Send,
  Navigation,
  PackageCheck,
  BadgeCheck,
  Ban,
  ChevronDown,
  ChevronUp,
  Star,
  Hash,
  CheckSquare,
  Percent,
  ArrowUpRight,
  Wallet,
  Receipt,
  TrendingDown,
  MapPin,
  Zap,
  ShoppingBag
} from 'lucide-react';
import stockReceiptService from '../../services/stockReceiptService';
import locationService from '../../services/locationService';

import StatusBadge from './stockReceiptDetailsComponents/StatusBadge';
import StatusTimeline from './stockReceiptDetailsComponents/StatusTimeline';
import ArrivalModal from './stockReceiptDetailsComponents/ArrivalModal';
import RatingsModal from './stockReceiptDetailsComponents/RatingsModal'


import {
  formatCurrency,
  formatDate,
  formatDateTime,
  safeGet
} from './stockReceiptDetailsComponents/utils';

import './StockReceiptDetails.css';

const ColorSwatch = ({ color, size = 16 }) => {
  if (!color || !color.startsWith('#')) return null;
  return (
    <span 
      className="srd-swatch" 
      style={{ 
        backgroundColor: color, 
        width: size, 
        height: size
      }} 
      title={color}
    />
  );
};

const VariantAttributes = ({ attributes }) => {
  if (!attributes || attributes.length === 0) return null;
  
  return (
    <div className="srd-attrs">
      {attributes.map((attr, idx) => (
        <span key={attr.attribute_id || idx} className="srd-attr">
          {attr.value?.startsWith('#') ? (
            <>
              <ColorSwatch color={attr.value} size={12} />
              <span>{attr.type}</span>
            </>
          ) : (
            <span>{attr.type}: {attr.value}</span>
          )}
        </span>
      ))}
    </div>
  );
};

const QualityBadge = ({ qualitySummary }) => {
  if (!qualitySummary) return null;
  
  const rating = qualitySummary.average_quality_rating || 0;
  let level = 'low';
  if (rating >= 8) level = 'high';
  else if (rating >= 6) level = 'medium';
  
  return (
    <div className={`srd-quality-pill ${level}`}>
      <Star size={12} fill="currentColor" />
      <span>{rating}/10</span>
    </div>
  );
};

const StockReceiptDetails = () => {
  const { id } = useParams();
  const navigate = useNavigate();
  
  const [receipt, setReceipt] = useState(null);
  const [statistics, setStatistics] = useState(null);
  const [locations, setLocations] = useState([]);
  const [loading, setLoading] = useState(true);
  const [actionLoading, setActionLoading] = useState(false);
  const [error, setError] = useState(null);
  const [expandedItems, setExpandedItems] = useState({});
  const [showArrivalModal, setShowArrivalModal] = useState(false);
  const [showRatingsModal, setShowRatingsModal] = useState(false);
const [selectedItemForRatings, setSelectedItemForRatings] = useState(null);

  useEffect(() => {
    fetchData();
  }, [id]);

  const fetchData = async () => {
    try {
      setLoading(true);
      
      const [receiptRes, statsRes, locationsRes] = await Promise.all([
        stockReceiptService.getById(id).catch(err => {
          throw new Error(`Impossible de charger la réception: ${err.message}`);
        }),
        stockReceiptService.getStatistics(id).catch(() => ({ data: null })),
        locationService.getAll().catch(() => ({ data: [] }))
      ]);
      
      if (!receiptRes.data) {
        throw new Error('Aucune donnée reçue pour la réception');
      }
      
      setReceipt(receiptRes.data);
      setStatistics(statsRes?.data || null);
      setLocations(locationsRes?.data || locationsRes || []);
      setError(null);
    } catch (err) {
      setError(err.message || 'Erreur lors du chargement des données');
      setReceipt(null);
      setStatistics(null);
      setLocations([]);
    } finally {
      setLoading(false);
    }
  };

  const handleStatusChange = async (action) => {
    if (!receipt) return;
    
    try {
      setActionLoading(true);
      
      switch (action) {
        case 'ship':
          await stockReceiptService.markAsShipped(id);
          break;
        case 'transit':
          await stockReceiptService.markAsInTransit(id);
          break;
        case 'arrive':
          setShowArrivalModal(true);
          setActionLoading(false);
          return;
        case 'validate':
          navigate(`/reapprovisionnements/${id}/evaluation`);
          setActionLoading(false);
          return;
        case 'cancel':
          if (window.confirm('Êtes-vous sûr de vouloir annuler cette réception ? Cette action est irréversible.')) {
            await stockReceiptService.cancel(id);
          } else {
            setActionLoading(false);
            return;
          }
          break;
        default:
          break;
      }
      
      await fetchData();
    } catch (err) {
      alert(err.response?.data?.message || 'Erreur lors de la mise à jour du statut');
    } finally {
      setActionLoading(false);
    }
  };
  // Ajoutez cette fonction pour ouvrir le modal des ratings
const handleViewRatings = (item, event) => {
  event.stopPropagation();
  setSelectedItemForRatings(item);
  setShowRatingsModal(true);
};
  const handleArrivalSubmit = async (data) => {
    try {
      setActionLoading(true);
      await stockReceiptService.markAsArrived(id, data);
      setShowArrivalModal(false);
      await fetchData();
    } catch (err) {
      alert(err.response?.data?.message || 'Erreur lors de la confirmation de réception');
    } finally {
      setActionLoading(false);
    }
  };

  const toggleItemExpand = (itemId) => {
    setExpandedItems(prev => ({
      ...prev,
      [itemId]: !prev[itemId]
    }));
  };

  const getAvailableActions = () => {
    if (!receipt || !receipt.status) return [];
    
    const actions = [];
    
    switch (receipt.status) {
      case 'pending':
        actions.push(
          { key: 'ship', label: 'Marquer envoyé', icon: Send, variant: 'primary' },
          { key: 'cancel', label: 'Annuler', icon: Ban, variant: 'danger' }
        );
        break;
      case 'sent':
        actions.push(
          { key: 'transit', label: 'Marquer en transit', icon: Navigation, variant: 'primary' },
          { key: 'cancel', label: 'Annuler', icon: Ban, variant: 'danger' }
        );
        break;
      case 'in_transit':
        actions.push(
          { key: 'arrive', label: 'Confirmer arrivée', icon: PackageCheck, variant: 'success' },
          { key: 'cancel', label: 'Annuler', icon: Ban, variant: 'danger' }
        );
        break;
      case 'arrived':
        actions.push(
          { key: 'validate', label: 'Évaluer et valider', icon: BadgeCheck, variant: 'success' }
        );
        break;
      default:
        break;
    }
    
    return actions;
  };

  const getTotalPaid = () => {
    if (receipt?.total_paid !== undefined) return receipt.total_paid;
    if (!receipt?.transactions || receipt.transactions.length === 0) return 0;
    return receipt.transactions.reduce((sum, t) => sum + (t.amount || 0), 0);
  };

  if (loading) {
    return (
      <div className="srd-loading-screen">
        <div className="srd-loading-box">
          <Loader2 className="srd-loading-spinner" size={56} />
          <h3>Chargement...</h3>
        </div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="srd-error-screen">
        <div className="srd-error-box">
          <div className="srd-error-icon-wrap">
            <AlertCircle size={72} />
          </div>
          <h2>Une erreur est survenue</h2>
          <p>{error}</p>
          <div className="srd-error-actions">
            <button className="srd-action-btn primary" onClick={() => navigate('/reapprovisionnements')}>
              <ArrowLeft size={18} />
              Retour
            </button>
            <button className="srd-action-btn secondary" onClick={fetchData}>
              Réessayer
            </button>
          </div>
        </div>
      </div>
    );
  }

  if (!receipt) {
    return (
      <div className="srd-error-screen">
        <div className="srd-error-box">
          <div className="srd-error-icon-wrap">
            <Package size={72} />
          </div>
          <h2>Réception introuvable</h2>
          <p>Cette réception n'existe pas ou a été supprimée.</p>
          <button className="srd-action-btn primary" onClick={() => navigate('/reapprovisionnements')}>
            <ArrowLeft size={18} />
            Retour à la liste
          </button>
        </div>
      </div>
    );
  }

  const actions = getAvailableActions();
  const totalPaid = getTotalPaid();

  return (
    <div className="srd-page">
      {/* Hero Header */}
      <div className="srd-hero">
        <div className="srd-hero-bg" />
        <div className="srd-hero-content">
          <button className="srd-back-link" onClick={() => navigate('/reapprovisionnements')}>
            <ArrowLeft size={20} />
            <span>Réceptions</span>
          </button>
          
          <div className="srd-hero-main">
            <div className="srd-hero-left">
              <div className="srd-hero-icon">
                <Package size={32} />
              </div>
              <div>
                <h1 className="srd-hero-title">{receipt.receipt_number || 'Sans référence'}</h1>
                <div className="srd-hero-badges">
                  <StatusBadge status={receipt.status} />
                  {receipt.is_delayed && (
                    <span className="srd-pill warning">
                      <AlertTriangle size={12} />
                      {safeGet(statistics, 'receipt_info.days_delayed', 0)}j de retard
                    </span>
                  )}
                  {receipt.fulfillment_rate !== undefined && receipt.status === 'validated' && (
                    <span className={`srd-pill ${receipt.fulfillment_rate === 100 ? 'success' : 'info'}`}>
                      <CheckSquare size={12} />
                      {receipt.fulfillment_rate}% reçu
                    </span>
                  )}
                </div>
              </div>
            </div>
            
            {actions.length > 0 && (
              <div className="srd-hero-actions">
                {actions.map(action => {
                  const Icon = action.icon;
                  return (
                    <button
                      key={action.key}
                      className={`srd-action-btn ${action.variant}`}
                      onClick={() => handleStatusChange(action.key)}
                      disabled={actionLoading}
                    >
                      {actionLoading ? <Loader2 className="srd-loading-spinner" size={18} /> : <Icon size={18} />}
                      {action.label}
                    </button>
                  );
                })}
              </div>
            )}
          </div>
          
          <div className="srd-hero-meta">
            <div className="srd-meta-item">
              <Calendar size={16} />
              <span>Créée le {formatDate(receipt.created_at)}</span>
            </div>
            {receipt.created_by && (
              <div className="srd-meta-item">
                <User size={16} />
                <span>{receipt.created_by.name}</span>
              </div>
            )}
            {receipt.items_count !== undefined && (
              <div className="srd-meta-item">
                <ShoppingBag size={16} />
                <span>{receipt.items_count} article{receipt.items_count > 1 ? 's' : ''}</span>
              </div>
            )}
          </div>
        </div>
      </div>

      {/* Main Content */}
      <div className="srd-content">
        {/* Timeline Card */}
        <div className="srd-timeline-card">
          <StatusTimeline
            currentStatus={receipt.status || 'pending'}
            expectedDate={receipt.expected_delivery_date}
            actualDate={receipt.actual_delivery_date}
            isDelayed={receipt.is_delayed || false}
          />
        </div>

        {/* Three Column Layout */}
        <div className="srd-grid">
          {/* Left: Partners */}
          <div className="srd-col">
            {/* Supplier */}
            {receipt.supplier && (
              <div className="srd-partner-card">
                <div className="srd-partner-header">
                  <Building2 size={18} />
                  <span>Fournisseur</span>
                </div>
                <Link to={`/fournisseurs/${receipt.supplier.id}`} className="srd-partner-body">
                  <div className="srd-partner-avatar">
                    {receipt.supplier.logo_url ? (
                      <img src={receipt.supplier.logo_url} alt={receipt.supplier.name} />
                    ) : (
                      <Building2 size={28} />
                    )}
                  </div>
                  <div className="srd-partner-info">
                    <h4>{receipt.supplier.name || 'Fournisseur inconnu'}</h4>
                    {receipt.supplier.reliability_score !== undefined && (
                      <div className="srd-partner-badge">
                        <Star size={12} fill="currentColor" />
                        <span>{parseFloat(receipt.supplier.reliability_score).toFixed(1)}/10 Fiabilité</span>
                      </div>
                    )}
                  </div>
                  <ExternalLink size={18} className="srd-partner-arrow" />
                </Link>
              </div>
            )}

            {/* Freight Forwarder */}
            {receipt.freight_forwarder && (
              <div className="srd-partner-card">
                <div className="srd-partner-header">
                  <Truck size={18} />
                  <span>Transitaire</span>
                </div>
                <Link to={`/transitaires/${receipt.freight_forwarder.id}`} className="srd-partner-body">
                  <div className="srd-partner-avatar">
                    {receipt.freight_forwarder.logo_url ? (
                      <img src={receipt.freight_forwarder.logo_url} alt={receipt.freight_forwarder.name} />
                    ) : (
                      <Truck size={28} />
                    )}
                  </div>
                  <div className="srd-partner-info">
                    <h4>{receipt.freight_forwarder.name || 'Transitaire inconnu'}</h4>
                    {receipt.freight_forwarder.service_score !== undefined && (
                      <div className="srd-partner-badge">
                        <Star size={12} fill="currentColor" />
                        <span>{parseFloat(receipt.freight_forwarder.service_score).toFixed(1)}/10 Service</span>
                      </div>
                    )}
                  </div>
                  <ExternalLink size={18} className="srd-partner-arrow" />
                </Link>
              </div>
            )}

            {/* Financial Summary */}
            <div className="srd-finance-card">
              <div className="srd-finance-header">
                <DollarSign size={18} />
                <span>Financier</span>
              </div>
              <div className="srd-finance-body">
                <div className="srd-finance-main">
                  <span className="srd-finance-label">Total commande</span>
                  <span className="srd-finance-value">{formatCurrency(receipt.total_cost_ariary)}</span>
                </div>
              </div>
            </div>

            {/* Dates */}
            <div className="srd-dates-card">
              <div className="srd-dates-header">
                <Clock size={18} />
                <span>Dates clés</span>
              </div>
              <div className="srd-dates-body">
                <div className="srd-date-row">
                  <span>Livraison prévue</span>
                  <strong>{receipt.expected_delivery_date ? formatDate(receipt.expected_delivery_date) : 'Non définie'}</strong>
                </div>
                {receipt.actual_delivery_date && (
                  <div className="srd-date-row">
                    <span>Livraison réelle</span>
                    <strong>{formatDate(receipt.actual_delivery_date)}</strong>
                  </div>
                )}
                {receipt.validated_at && (
                  <div className="srd-date-row">
                    <span>Validée le</span>
                    <strong>{formatDate(receipt.validated_at)}</strong>
                  </div>
                )}
              </div>
            </div>
          </div>

          {/* Center: Items */}
          <div className="srd-col srd-col-main">
            <div className="srd-items-wrapper">
              <div className="srd-items-header">
                <div className="srd-items-title">
                  <Package size={20} />
                  <h3>Articles ({receipt.items?.length || 0})</h3>
                </div>
              </div>
              
              <div className="srd-items-list">
                {receipt.items && receipt.items.length > 0 ? (
                  receipt.items.map((item) => {
                    const productId = item.variant?.product?.id;
                    const productName = item.variant?.product?.name || 'Article sans nom';
                    const variantSku = item.variant?.sku || 'N/A';
                    const attributes = item.variant?.attributes || [];
                    const itemTotalCost = item.total_cost || ((item.quantity_ordered || 0) * (item.unit_cost_ariary || 0));
                    const hasVariance = item.quantity_variance !== 0 && item.quantity_variance !== undefined;
                    
                    return (
                      <div key={item.id} className={`srd-item ${expandedItems[item.id] ? 'expanded' : ''}`}>
                        <div className="srd-item-main" onClick={() => toggleItemExpand(item.id)}>
                          <div className="srd-item-left">
                            <div className="srd-item-icon">
                              <Box size={20} />
                            </div>
                            <div className="srd-item-details">
                              <div className="srd-item-title-row">
                                <h4>{productName}</h4>
                                {productId && (
                                  <Link 
                                    to={`/produits/${productId}`} 
                                    className="srd-item-link"
                                    onClick={(e) => e.stopPropagation()}
                                  >
                                    <ArrowUpRight size={14} />
                                  </Link>
                                )}
                                {item.quality_summary && (
                                    <div className="srd-quality-section">
                                      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 'var(--spacing-md)' }}>
                                        <h5 style={{ margin: 0 }}>Résumé qualité</h5>
                                        {item.ratings && item.ratings.length > 0 && (
                                          <button 
                                            className="srd-quality-view-btn"
                                            onClick={(e) => handleViewRatings(item, e)}
                                          >
                                            <Star size={12} strokeWidth={2.5} fill="currentColor" />
                                            Voir les évaluations ({item.ratings.length})
                                          </button>
                                        )}
                                      </div>
                                      {/* <div className="srd-quality-stats">
                                        <div className="srd-quality-box">
                                          <Star size={16} strokeWidth={2} />
                                          <div>
                                            <strong>{item.quality_summary.average_quality_rating?.toFixed(1) || 0}/10</strong>
                                            <span>Qualité</span>
                                          </div>
                                        </div>
                                        <div className="srd-quality-box">
                                          <Percent size={16} strokeWidth={2} />
                                          <div>
                                            <strong>{item.quality_summary.quantity_fulfillment_rate || 0}%</strong>
                                            <span>Réception</span>
                                          </div>
                                        </div>
                                        <div className="srd-quality-box">
                                          <CheckSquare size={16} strokeWidth={2} />
                                          <div>
                                            <strong>{item.quality_summary.attribute_conformity_rate || 0}%</strong>
                                            <span>Conformité</span>
                                          </div>
                                        </div>
                                        <div className="srd-quality-box">
                                          <Zap size={16} strokeWidth={2} />
                                          <div>
                                            <strong>{item.quality_summary.overall_score?.toFixed(1) || 0}/10</strong>
                                            <span>Score global</span>
                                          </div>
                                        </div>
                                      </div> */}
                                    </div>
                                  )}
                              </div>
                              <div className="srd-item-sku">{variantSku}</div>
                              <VariantAttributes attributes={attributes} />
                            </div>
                          </div>
                          
                          <div className="srd-item-right">
                            <div className="srd-item-qty">
                              <div className="srd-qty-box ordered">
                                <span className="srd-qty-label">Commandé</span>
                                <span className="srd-qty-value">{item.quantity_ordered || 0}</span>
                              </div>
                              {(receipt.status === 'arrived' || receipt.status === 'validated') && (
                                <div className={`srd-qty-box ${item.quantity_received < item.quantity_ordered ? 'partial' : 'received'}`}>
                                  <span className="srd-qty-label">Reçu</span>
                                  <span className="srd-qty-value">{item.quantity_received || 0}</span>
                                </div>
                              )}
                            </div>
                            
                            <div className="srd-item-price">
                              <span className="srd-price-label">Prix unitaire</span>
                              <span className="srd-price-value">{formatCurrency(item.unit_cost_ariary)}</span>
                              <span className="srd-price-total">{formatCurrency(itemTotalCost)}</span>
                            </div>
                            
                            <button className="srd-item-expand" onClick={(e) => { e.stopPropagation(); toggleItemExpand(item.id); }}>
                              {expandedItems[item.id] ? <ChevronUp size={20} /> : <ChevronDown size={20} />}
                            </button>
                          </div>
                        </div>
                        
                        {expandedItems[item.id] && (
                          <div className="srd-item-expanded">
                            {item.quality_summary && (
                              <div className="srd-quality-section">
                                <h5>Résumé qualité</h5>
                                <div className="srd-quality-stats">
                                  <div className="srd-quality-box">
                                    <Star size={16} />
                                    <div>
                                      <strong>{item.quality_summary.average_quality_rating || 0}/10</strong>
                                      <span>Qualité</span>
                                    </div>
                                  </div>
                                  <div className="srd-quality-box">
                                    <Percent size={16} />
                                    <div>
                                      <strong>{item.quality_summary.quantity_fulfillment_rate || 0}%</strong>
                                      <span>Réception</span>
                                    </div>
                                  </div>
                                  <div className="srd-quality-box">
                                    <CheckSquare size={16} />
                                    <div>
                                      <strong>{item.quality_summary.attribute_conformity_rate || 0}%</strong>
                                      <span>Conformité</span>
                                    </div>
                                  </div>
                                  <div className="srd-quality-box">
                                    <Zap size={16} />
                                    <div>
                                      <strong>{item.quality_summary.overall_score || 0}/10</strong>
                                      <span>Score global</span>
                                    </div>
                                  </div>
                                </div>
                              </div>
                            )}
                            
                            {item.notes && (
                              <div className="srd-notes-section">
                                <FileText size={16} />
                                <p>{item.notes}</p>
                              </div>
                            )}
                          </div>
                        )}
                      </div>
                    );
                  })
                ) : (
                  <div className="srd-empty-state">
                    <Package size={56} />
                    <p>Aucun article</p>
                  </div>
                )}
              </div>
            </div>

            {receipt.notes && (
              <div className="srd-global-notes">
                <FileText size={18} />
                <div>
                  <strong>Notes</strong>
                  <p>{receipt.notes}</p>
                </div>
              </div>
            )}
          </div>

          {/* Right: Stats & Transactions */}
          <div className="srd-col">
            {/* Stats */}
            <div className="srd-stats-card">
              <div className="srd-stats-header">
                <TrendingUp size={18} />
                <span>Statistiques</span>
              </div>
              <div className="srd-stats-body">
                <div className="srd-stat-box">
                  <Layers size={20} />
                  <div>
                    <strong>{receipt.total_quantity_ordered || safeGet(statistics, 'quantities.total_ordered', 0)}</strong>
                    <span>Commandés</span>
                  </div>
                </div>
                {(receipt.total_quantity_received !== undefined || safeGet(statistics, 'quantities.total_received') !== undefined) && (
                  <div className="srd-stat-box success">
                    <PackageCheck size={20} />
                    <div>
                      <strong>{receipt.total_quantity_received || statistics?.quantities?.total_received || 0}</strong>
                      <span>Reçus</span>
                    </div>
                  </div>
                )}
                {(receipt.fulfillment_rate !== undefined || safeGet(statistics, 'quantities.fulfillment_rate') !== undefined) && (
                  <div className="srd-stat-box info">
                    <Percent size={20} />
                    <div>
                      <strong>{receipt.fulfillment_rate || statistics?.quantities?.fulfillment_rate || 0}%</strong>
                      <span>Taux</span>
                    </div>
                  </div>
                )}
              </div>
            </div>

            {/* Transactions */}
            {receipt.transactions && receipt.transactions.length > 0 && (
              <div className="srd-transactions-card">
                <div className="srd-transactions-header">
                  <CreditCard size={18} />
                  <span>Transactions ({receipt.transactions.length})</span>
                </div>
                <div className="srd-transactions-body">
                  {receipt.transactions.map(transaction => (
                    <Link
                      key={transaction.id}
                      to={`/transactions/${transaction.id}`}
                      className="srd-transaction-item"
                    >
                      <div className="srd-transaction-left">
                        <div className="srd-transaction-icon">
                          {transaction.transaction_type?.category === 'expense' ? (
                            <TrendingDown size={16} />
                          ) : (
                            <TrendingUp size={16} />
                          )}
                        </div>
                        <div>
                          <strong>{transaction.transaction_type?.display_name || 'Transaction'}</strong>
                          {transaction.account && (
                            <span className="srd-transaction-account">
                              <Wallet size={12} />
                              {transaction.account.name}
                            </span>
                          )}
                        </div>
                      </div>
                      <div className="srd-transaction-right">
                        <span className="srd-transaction-amount">
                          {transaction.formatted_amount || formatCurrency(transaction.amount)}
                        </span>
                        <ExternalLink size={14} />
                      </div>
                    </Link>
                  ))}
                  <div className="srd-transactions-total">
                    <span>Total paiements</span>
                    <strong>{formatCurrency(totalPaid)}</strong>
                  </div>
                </div>
              </div>
            )}
          </div>
        </div>
      </div>

      <ArrivalModal
        isOpen={showArrivalModal}
        onClose={() => setShowArrivalModal(false)}
        items={receipt.items || []}
        locations={locations}
        onSubmit={handleArrivalSubmit}
        isLoading={actionLoading}
      />
      <RatingsModal
      isOpen={showRatingsModal}
      onClose={() => setShowRatingsModal(false)}
      item={selectedItemForRatings}
    />
    </div>
  );
};

export default StockReceiptDetails;
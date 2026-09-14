import { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import {
  ArrowLeft,
  Edit2,
  Trash2,
  Package,
  DollarSign,
  TrendingUp,
  ShoppingCart,
  Calendar,
  Phone,
  MessageCircle,
  FileText,
  MapPin,
  AlertTriangle,
  Star,
  CheckCircle,
  XCircle,
  Loader2,
  ChevronRight
} from 'lucide-react';
import supplierService from '../../services/supplierService';
import StatusBadge from '../StockReceipt/stockReceiptDetailsComponents/StatusBadge';
import '../../styles/SupplierDetails.css';

const SupplierDetails = () => {
  const { id } = useParams();
  const navigate = useNavigate();
  
  const [supplier, setSupplier] = useState(null);
  const [statistics, setStatistics] = useState(null);
  const [receipts, setReceipts] = useState([]);
  const [receiptsPage, setReceiptsPage] = useState(1);
  const [receiptsPagination, setReceiptsPagination] = useState(null);
  const [loading, setLoading] = useState(true);
  const [receiptsLoading, setReceiptsLoading] = useState(false);
  const [error, setError] = useState(null);
  const [showDeleteConfirm, setShowDeleteConfirm] = useState(false);
  const [deleting, setDeleting] = useState(false);
  const [activeTab, setActiveTab] = useState('overview');

  useEffect(() => {
    loadSupplierData();
  }, [id]);

  useEffect(() => {
    if (activeTab === 'receipts') {
      loadReceipts();
    }
  }, [activeTab, receiptsPage]);

  const loadSupplierData = async () => {
    try {
      setLoading(true);
      setError(null);
      
      const response = await supplierService.getSupplier(id);
      setSupplier(response.data);
      setStatistics(response.statistics);
    } catch (err) {
      setError('Erreur lors du chargement des données');
      console.error(err);
    } finally {
      setLoading(false);
    }
  };

  const loadReceipts = async () => {
    try {
      setReceiptsLoading(true);
      const response = await supplierService.getSupplierReceipts(id, { page: receiptsPage });
      setReceipts(response.data || []);
      setReceiptsPagination(response.meta);
    } catch (err) {
      console.error('Erreur chargement réceptions:', err);
    } finally {
      setReceiptsLoading(false);
    }
  };

  const handleDelete = async () => {
    try {
      setDeleting(true);
      await supplierService.deleteSupplier(id);
      navigate('/fournisseurs');
    } catch (err) {
      alert(err.response?.data?.message || 'Erreur lors de la suppression');
      setDeleting(false);
      setShowDeleteConfirm(false);
    }
  };

  const formatCurrency = (amount) => {
    return new Intl.NumberFormat('fr-FR', {
      minimumFractionDigits: 0,
      maximumFractionDigits: 0
    }).format(amount) + ' Ar';
  };

  const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleDateString('fr-FR', {
      day: '2-digit',
      month: 'short',
      year: 'numeric'
    });
  };

  const getReliabilityColor = (score) => {
    if (score >= 8) return 'sd-reliability-excellent';
    if (score >= 6) return 'sd-reliability-good';
    if (score >= 4) return 'sd-reliability-average';
    return 'sd-reliability-poor';
  };

  const getReliabilityLabel = (score) => {
    if (score >= 8) return 'Excellent';
    if (score >= 6) return 'Bon';
    if (score >= 4) return 'Moyen';
    return 'Faible';
  };

  const renderOverviewTab = () => {
    if (!statistics) return null;

    return (
      <div className="sd-overview-content">
        {/* Stats principales */}
        <div className="sd-stats-grid">
          <div className="sd-stat-card sd-stat-primary">
            <div className="sd-stat-icon" style={{ background: 'var(--danger-light)', color: 'var(--danger)' }}>
              <DollarSign size={24} />
            </div>
            <div className="sd-stat-content">
              <div className="sd-stat-value">{formatCurrency(statistics.total_spent)}</div>
              <div className="sd-stat-label">Total dépensé</div>
            </div>
          </div>

          <div className="sd-stat-card sd-stat-success">
            <div className="sd-stat-icon" style={{ background: 'var(--success-light)', color: 'var(--success)' }}>
              <TrendingUp size={24} />
            </div>
            <div className="sd-stat-content">
              <div className="sd-stat-value">{formatCurrency(statistics.profit_data.total_profit)}</div>
              <div className="sd-stat-label">Bénéfices réalisés</div>
              <div className="sd-stat-subtitle">
                Marge: {statistics.profit_data.profit_margin_percent}%
              </div>
            </div>
          </div>

          <div className="sd-stat-card">
            <div className="sd-stat-icon" style={{ background: 'var(--info-light)', color: 'var(--info)' }}>
              <ShoppingCart size={24} />
            </div>
            <div className="sd-stat-content">
              <div className="sd-stat-value">{statistics.profit_data.total_units_sold}</div>
              <div className="sd-stat-label">Unités vendues</div>
              <div className="sd-stat-subtitle">
                Revenu: {formatCurrency(statistics.profit_data.total_revenue)}
              </div>
            </div>
          </div>

          <div className="sd-stat-card">
            <div className="sd-stat-icon" style={{ background: 'var(--warning-light)', color: 'var(--warning)' }}>
              <Package size={24} />
            </div>
            <div className="sd-stat-content">
              <div className="sd-stat-value">{statistics.total_products}</div>
              <div className="sd-stat-label">Produits</div>
              <div className="sd-stat-subtitle">
                {statistics.total_receipts} réceptions
              </div>
            </div>
          </div>
        </div>

        {/* Conformité par attribut */}
        {statistics.conformity_by_attribute && statistics.conformity_by_attribute.length > 0 && (
          <div className="sd-section">
            <h3 className="sd-section-title">
              <CheckCircle size={20} />
              Conformité par attribut
            </h3>
            <div className="sd-conformity-grid">
              {statistics.conformity_by_attribute.map((attr) => (
                <div key={attr.attribute_id} className="sd-conformity-card">
                  <div className="sd-conformity-header">
                    <span className="sd-conformity-name">{attr.attribute_name}</span>
                    <span className={`sd-conformity-rate ${attr.conformity_rate >= 70 ? 'sd-rate-good' : attr.conformity_rate >= 40 ? 'sd-rate-average' : 'sd-rate-poor'}`}>
                      {attr.conformity_rate}%
                    </span>
                  </div>
                  <div className="sd-conformity-progress">
                    <div 
                      className="sd-conformity-bar"
                      style={{ width: `${attr.conformity_rate}%` }}
                    />
                  </div>
                  <div className="sd-conformity-details">
                    {attr.conforming_count} / {attr.total_ratings} conformes
                  </div>
                </div>
              ))}
            </div>
          </div>
        )}

        {/* Informations générales */}
        <div className="sd-section">
          <h3 className="sd-section-title">
            <FileText size={20} />
            Informations générales
          </h3>
          <div className="sd-info-grid">
            {supplier.coordinate && (
              <div className="sd-info-item">
                <MapPin size={16} className="sd-info-icon" />
                <div className="sd-info-content">
                  <div className="sd-info-label">Localisation</div>
                  <div className="sd-info-value">{supplier.coordinate.full_location}</div>
                </div>
              </div>
            )}

            {supplier.contact && (
              <div className="sd-info-item">
                <Phone size={16} className="sd-info-icon" />
                <div className="sd-info-content">
                  <div className="sd-info-label">Contact</div>
                  <div className="sd-info-value">{supplier.contact}</div>
                </div>
              </div>
            )}

            {supplier.wechat && (
              <div className="sd-info-item">
                <MessageCircle size={16} className="sd-info-icon" />
                <div className="sd-info-content">
                  <div className="sd-info-label">WeChat</div>
                  <div className="sd-info-value">{supplier.wechat}</div>
                </div>
              </div>
            )}

            <div className="sd-info-item">
              <Calendar size={16} className="sd-info-icon" />
              <div className="sd-info-content">
                <div className="sd-info-label">Ajouté le</div>
                <div className="sd-info-value">{formatDate(supplier.created_at)}</div>
              </div>
            </div>
          </div>

          {supplier.profile && (
            <div className="sd-info-block">
              <div className="sd-info-label">Profil</div>
              <div className="sd-info-description">{supplier.profile}</div>
            </div>
          )}

          {supplier.accessibility_notes && (
            <div className="sd-info-block">
              <div className="sd-info-label">Notes d'accessibilité</div>
              <div className="sd-info-description">{supplier.accessibility_notes}</div>
            </div>
          )}
        </div>
      </div>
    );
  };

  const renderReceiptsTab = () => {
    if (receiptsLoading) {
      return (
        <div className="sd-tab-loading">
          <Loader2 className="sd-spinner" size={32} />
        </div>
      );
    }

    if (receipts.length === 0) {
      return (
        <div className="sd-empty-tab">
          <Package size={48} />
          <p>Aucune réception</p>
        </div>
      );
    }

    return (
      <>
        <div className="sd-receipts-list">
          {receipts.map((receipt) => (
            <div
              key={receipt.id}
              className="sd-receipt-card"
              onClick={() => navigate(`/reapprovisionnements/${receipt.id}`)}
            >
              <div className="sd-receipt-main">
                <h4 className="sd-receipt-number">{receipt.receipt_number}</h4>
                <StatusBadge status={receipt.status} />
              </div>
              <div className="sd-receipt-footer">
                <span className="sd-receipt-amount">{formatCurrency(receipt.total_cost_ariary)}</span>
                <span className="sd-receipt-date">{formatDate(receipt.created_at)}</span>
              </div>
              <ChevronRight className="sd-receipt-arrow" size={20} />
            </div>
          ))}
        </div>

        {receiptsPagination && receiptsPagination.last_page > 1 && (
          <div className="sd-pagination">
            <button
              className="sd-pagination-btn"
              onClick={() => setReceiptsPage(receiptsPage - 1)}
              disabled={receiptsPage === 1}
            >
              Précédent
            </button>
            <span className="sd-pagination-text">
              Page {receiptsPage} sur {receiptsPagination.last_page}
            </span>
            <button
              className="sd-pagination-btn"
              onClick={() => setReceiptsPage(receiptsPage + 1)}
              disabled={receiptsPage === receiptsPagination.last_page}
            >
              Suivant
            </button>
          </div>
        )}
      </>
    );
  };

  if (loading) {
    return (
      <div className="sd-page">
        <div className="sd-loading-screen">
          <Loader2 className="sd-spinner" size={56} />
          <p>Chargement...</p>
        </div>
      </div>
    );
  }

  if (error || !supplier) {
    return (
      <div className="sd-page">
        <div className="sd-error-screen">
          <AlertTriangle size={72} />
          <h3>Erreur</h3>
          <p>{error || 'Fournisseur introuvable'}</p>
          <button className="sd-btn-primary" onClick={() => navigate('/fournisseurs')}>
            <ArrowLeft size={18} /> Retour à la liste
          </button>
        </div>
      </div>
    );
  }

  return (
    <div className="sd-page">
      {/* Header */}
      <div className="sd-header">
        <button className="sd-back-btn" onClick={() => navigate('/fournisseurs')}>
          <ArrowLeft size={18} />
          Retour
        </button>

        <div className="sd-header-content">
          <div className="sd-header-main">
            <div className="sd-supplier-logo">
              {supplier.logo_url ? (
                <img src={supplier.logo_url} alt={supplier.name} />
              ) : (
                <div className="sd-logo-placeholder">
                  {supplier.name?.charAt(0).toUpperCase()}
                </div>
              )}
            </div>

            <div className="sd-header-info">
              <h1>{supplier.name}</h1>
              <div className="sd-header-badges">
                <span className={`sd-status-badge ${supplier.is_active ? 'sd-status-active' : 'sd-status-inactive'}`}>
                  {supplier.is_active ? 'Actif' : 'Inactif'}
                </span>
                <span className={`sd-reliability-badge ${getReliabilityColor(supplier.reliability_score || 0)}`}>
                  <Star size={14} fill="currentColor" />
                  {supplier.reliability_score || '0.0'}/10 - {getReliabilityLabel(supplier.reliability_score || 0)}
                </span>
              </div>
            </div>
          </div>

          <div className="sd-header-actions">
            <button className="sd-btn-secondary" onClick={() => navigate(`/fournisseurs/${id}/modifier`)}>
              <Edit2 size={18} /> Modifier
            </button>
            <button className="sd-btn-danger" onClick={() => setShowDeleteConfirm(true)}>
              <Trash2 size={18} /> Supprimer
            </button>
          </div>
        </div>
      </div>

      {/* Tabs */}
      <div className="sd-tabs-container">
        <div className="sd-tabs-header">
          <button
            className={`sd-tab-btn ${activeTab === 'overview' ? 'sd-tab-active' : ''}`}
            onClick={() => setActiveTab('overview')}
          >
            <TrendingUp size={18} />
            Vue d'ensemble
          </button>
          <button
            className={`sd-tab-btn ${activeTab === 'receipts' ? 'sd-tab-active' : ''}`}
            onClick={() => setActiveTab('receipts')}
          >
            <Package size={18} />
            Réceptions ({statistics?.total_receipts || 0})
          </button>
        </div>

        <div className="sd-tab-content">
          {activeTab === 'overview' && renderOverviewTab()}
          {activeTab === 'receipts' && renderReceiptsTab()}
        </div>
      </div>

      {/* Modal de suppression */}
      {showDeleteConfirm && (
        <div className="sd-modal-overlay" onClick={() => !deleting && setShowDeleteConfirm(false)}>
          <div className="sd-modal-content" onClick={(e) => e.stopPropagation()}>
            <div className="sd-modal-icon">
              <AlertTriangle size={40} />
            </div>
            <h3>Confirmer la suppression</h3>
            <p>
              Êtes-vous sûr de vouloir supprimer le fournisseur <strong>{supplier.name}</strong> ?
              <br />
              Cette action est irréversible.
            </p>
            <div className="sd-modal-actions">
              <button
                className="sd-btn-secondary"
                onClick={() => setShowDeleteConfirm(false)}
                disabled={deleting}
              >
                Annuler
              </button>
              <button
                className="sd-btn-danger"
                onClick={handleDelete}
                disabled={deleting}
              >
                {deleting ? (
                  <>
                    <Loader2 className="sd-spinner" size={18} />
                    Suppression...
                  </>
                ) : (
                  <>
                    <Trash2 size={18} /> Supprimer
                  </>
                )}
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default SupplierDetails;
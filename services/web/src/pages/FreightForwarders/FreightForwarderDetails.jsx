import { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import {
  ArrowLeft,
  Edit2,
  Trash2,
  Truck,
  DollarSign,
  Package,
  Calendar,
  Phone,
  MapPin,
  FileText,
  AlertTriangle,
  Loader2,
  ChevronRight,
  CheckCircle,
  TrendingUp,
  Plane,
  Ship
} from 'lucide-react';
import freightForwarderService from '../../services/freightForwarderService';
import StatusBadge from '../StockReceipt/stockReceiptDetailsComponents/StatusBadge';
import '../../styles/FreightForwarderDetails.css';

const FreightForwarderDetails = () => {
  const { id } = useParams();
  const navigate = useNavigate();
  
  const [forwarder, setForwarder] = useState(null);
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
    loadForwarderData();
  }, [id]);

  useEffect(() => {
    if (activeTab === 'receipts') {
      loadReceipts();
    }
  }, [activeTab, receiptsPage]);

  const loadForwarderData = async () => {
    try {
      setLoading(true);
      setError(null);
      
      const response = await freightForwarderService.getFreightForwarder(id);
      setForwarder(response.data);
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
      const response = await freightForwarderService.getFreightForwarderReceipts(id, { page: receiptsPage });
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
      await freightForwarderService.deleteFreightForwarder(id);
      navigate('/transitaires');
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

  const getScoreColor = (score) => {
    if (score >= 8) return 'ffd-score-excellent';
    if (score >= 6) return 'ffd-score-good';
    if (score >= 4) return 'ffd-score-average';
    return 'ffd-score-poor';
  };

  const getScoreLabel = (score) => {
    if (score >= 8) return 'Excellent';
    if (score >= 6) return 'Bon';
    if (score >= 4) return 'Moyen';
    return 'Faible';
  };

  const getTypeIcon = (type) => {
    if (type === 'aerien') return <Plane size={16} />;
    if (type === 'maritime') return <Ship size={16} />;
    return <Truck size={16} />;
  };

  const getTypeLabel = (type) => {
    if (type === 'aerien') return 'Aérien';
    if (type === 'maritime') return 'Maritime';
    return type;
  };

  const renderOverviewTab = () => {
    if (!statistics) return null;

    return (
      <div className="ffd-overview-content">
        {/* Stats principales */}
        <div className="ffd-stats-grid">
          <div className="ffd-stat-card ffd-stat-primary">
            <div className="ffd-stat-icon" style={{ background: 'var(--danger-light)', color: 'var(--danger)' }}>
              <DollarSign size={24} />
            </div>
            <div className="ffd-stat-content">
              <div className="ffd-stat-value">{formatCurrency(statistics.total_spent)}</div>
              <div className="ffd-stat-label">Total dépensé</div>
            </div>
          </div>

          <div className="ffd-stat-card ffd-stat-success">
            <div className="ffd-stat-icon" style={{ background: 'var(--primary-light)', color: 'var(--primary)' }}>
              <Package size={24} />
            </div>
            <div className="ffd-stat-content">
              <div className="ffd-stat-value">{statistics.total_receipts}</div>
              <div className="ffd-stat-label">Total réceptions</div>
              <div className="ffd-stat-subtitle">
                {statistics.validated_receipts} validées
              </div>
            </div>
          </div>

          <div className="ffd-stat-card">
            <div className="ffd-stat-icon" style={{ background: 'var(--success-light)', color: 'var(--success)' }}>
              <CheckCircle size={24} />
            </div>
            <div className="ffd-stat-content">
              <div className="ffd-stat-value">{formatCurrency(statistics.total_receipts_value)}</div>
              <div className="ffd-stat-label">Valeur totale réceptions</div>
            </div>
          </div>

          <div className="ffd-stat-card">
            <div className="ffd-stat-icon" style={{ background: 'var(--info-light)', color: 'var(--info)' }}>
              <TrendingUp size={24} />
            </div>
            <div className="ffd-stat-content">
              <div className="ffd-stat-value">{formatCurrency(statistics.average_per_receipt)}</div>
              <div className="ffd-stat-label">Moyenne par réception</div>
            </div>
          </div>
        </div>

        {/* Dernière réception */}
        {statistics.last_receipt_number && (
          <div className="ffd-section">
            <h3 className="ffd-section-title">
              <Package size={20} />
              Dernière réception
            </h3>
            <div className="ffd-last-receipt-card">
              <div className="ffd-receipt-info">
                <span className="ffd-receipt-number">{statistics.last_receipt_number}</span>
                <span className="ffd-receipt-date">{formatDate(statistics.last_receipt_date)}</span>
              </div>
            </div>
          </div>
        )}

        {/* Informations générales */}
        <div className="ffd-section">
          <h3 className="ffd-section-title">
            <FileText size={20} />
            Informations générales
          </h3>
          <div className="ffd-info-grid">
            <div className="ffd-info-item">
              <Truck size={16} className="ffd-info-icon" />
              <div className="ffd-info-content">
                <div className="ffd-info-label">Type</div>
                <div className="ffd-info-value ffd-type-badge">
                  {getTypeIcon(forwarder.type)}
                  {getTypeLabel(forwarder.type)}
                </div>
              </div>
            </div>

            {forwarder.coordinate && (
              <div className="ffd-info-item">
                <MapPin size={16} className="ffd-info-icon" />
                <div className="ffd-info-content">
                  <div className="ffd-info-label">Localisation</div>
                  <div className="ffd-info-value">{forwarder.coordinate.full_location}</div>
                </div>
              </div>
            )}

            {forwarder.contact && (
              <div className="ffd-info-item">
                <Phone size={16} className="ffd-info-icon" />
                <div className="ffd-info-content">
                  <div className="ffd-info-label">Contact</div>
                  <div className="ffd-info-value">{forwarder.contact}</div>
                </div>
              </div>
            )}

            <div className="ffd-info-item">
              <Calendar size={16} className="ffd-info-icon" />
              <div className="ffd-info-content">
                <div className="ffd-info-label">Ajouté le</div>
                <div className="ffd-info-value">{formatDate(forwarder.created_at)}</div>
              </div>
            </div>
          </div>

          {forwarder.notes && (
            <div className="ffd-info-block">
              <div className="ffd-info-label">Notes</div>
              <div className="ffd-info-description">{forwarder.notes}</div>
            </div>
          )}
        </div>
      </div>
    );
  };

  const renderReceiptsTab = () => {
    if (receiptsLoading) {
      return (
        <div className="ffd-tab-loading">
          <Loader2 className="ffd-spinner" size={32} />
        </div>
      );
    }

    if (receipts.length === 0) {
      return (
        <div className="ffd-empty-tab">
          <Package size={48} />
          <p>Aucune réception</p>
        </div>
      );
    }

    return (
      <>
        <div className="ffd-receipts-list">
          {receipts.map((receipt) => (
            <div
              key={receipt.id}
              className="ffd-receipt-card"
              onClick={() => navigate(`/reapprovisionnements/${receipt.id}`)}
            >
              <div className="ffd-receipt-main">
                <h4 className="ffd-receipt-number">{receipt.receipt_number}</h4>
                <StatusBadge status={receipt.status} />
              </div>
              <div className="ffd-receipt-footer">
                <span className="ffd-receipt-amount">{formatCurrency(receipt.total_cost_ariary)}</span>
                <span className="ffd-receipt-date">{formatDate(receipt.created_at)}</span>
              </div>
              <ChevronRight className="ffd-receipt-arrow" size={20} />
            </div>
          ))}
        </div>

        {receiptsPagination && receiptsPagination.last_page > 1 && (
          <div className="ffd-pagination">
            <button
              className="ffd-pagination-btn"
              onClick={() => setReceiptsPage(receiptsPage - 1)}
              disabled={receiptsPage === 1}
            >
              Précédent
            </button>
            <span className="ffd-pagination-text">
              Page {receiptsPage} sur {receiptsPagination.last_page}
            </span>
            <button
              className="ffd-pagination-btn"
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
      <div className="ffd-page">
        <div className="ffd-loading-screen">
          <Loader2 className="ffd-spinner" size={56} />
          <p>Chargement...</p>
        </div>
      </div>
    );
  }

  if (error || !forwarder) {
    return (
      <div className="ffd-page">
        <div className="ffd-error-screen">
          <AlertTriangle size={72} />
          <h3>Erreur</h3>
          <p>{error || 'Transitaire introuvable'}</p>
          <button className="ffd-btn-primary" onClick={() => navigate('/transitaires')}>
            <ArrowLeft size={18} /> Retour à la liste
          </button>
        </div>
      </div>
    );
  }

  return (
    <div className="ffd-page">
      {/* Header */}
      <div className="ffd-header">
        <button className="ffd-back-btn" onClick={() => navigate('/transitaires')}>
          <ArrowLeft size={18} />
          Retour
        </button>

        <div className="ffd-header-content">
          <div className="ffd-header-main">
            <div className="ffd-forwarder-logo">
              {forwarder.logo_url ? (
                <img src={forwarder.logo_url} alt={forwarder.name} />
              ) : (
                <div className="ffd-logo-placeholder">
                  {forwarder.name?.charAt(0).toUpperCase()}
                </div>
              )}
            </div>

            <div className="ffd-header-info">
              <h1>{forwarder.name}</h1>
              <div className="ffd-header-badges">
                <span className={`ffd-status-badge ${forwarder.is_active ? 'ffd-status-active' : 'ffd-status-inactive'}`}>
                  {forwarder.is_active ? 'Actif' : 'Inactif'}
                </span>
                <span className={`ffd-score-badge ${getScoreColor(forwarder.service_score || 0)}`}>
                  {forwarder.service_score || '0.0'}/10 - {getScoreLabel(forwarder.service_score || 0)}
                </span>
              </div>
            </div>
          </div>

          <div className="ffd-header-actions">
            <button className="ffd-btn-secondary" onClick={() => navigate(`/transitaires/${id}/modifier`)}>
              <Edit2 size={18} /> Modifier
            </button>
            <button className="ffd-btn-danger" onClick={() => setShowDeleteConfirm(true)}>
              <Trash2 size={18} /> Supprimer
            </button>
          </div>
        </div>
      </div>

      {/* Tabs */}
      <div className="ffd-tabs-container">
        <div className="ffd-tabs-header">
          <button
            className={`ffd-tab-btn ${activeTab === 'overview' ? 'ffd-tab-active' : ''}`}
            onClick={() => setActiveTab('overview')}
          >
            <TrendingUp size={18} />
            Vue d'ensemble
          </button>
          <button
            className={`ffd-tab-btn ${activeTab === 'receipts' ? 'ffd-tab-active' : ''}`}
            onClick={() => setActiveTab('receipts')}
          >
            <Package size={18} />
            Réceptions ({statistics?.total_receipts || 0})
          </button>
        </div>

        <div className="ffd-tab-content">
          {activeTab === 'overview' && renderOverviewTab()}
          {activeTab === 'receipts' && renderReceiptsTab()}
        </div>
      </div>

      {/* Modal de suppression */}
      {showDeleteConfirm && (
        <div className="ffd-modal-overlay" onClick={() => !deleting && setShowDeleteConfirm(false)}>
          <div className="ffd-modal-content" onClick={(e) => e.stopPropagation()}>
            <div className="ffd-modal-icon">
              <AlertTriangle size={40} />
            </div>
            <h3>Confirmer la suppression</h3>
            <p>
              Êtes-vous sûr de vouloir supprimer le transitaire <strong>{forwarder.name}</strong> ?
              <br />
              Cette action est irréversible.
            </p>
            <div className="ffd-modal-actions">
              <button
                className="ffd-btn-secondary"
                onClick={() => setShowDeleteConfirm(false)}
                disabled={deleting}
              >
                Annuler
              </button>
              <button
                className="ffd-btn-danger"
                onClick={handleDelete}
                disabled={deleting}
              >
                {deleting ? (
                  <>
                    <Loader2 className="ffd-spinner" size={18} />
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

export default FreightForwarderDetails;
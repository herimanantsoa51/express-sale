// ============================================
// pages/Suppliers/SupplierDetails.jsx
// ============================================

import { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { ArrowLeft, Edit2, Trash2, Package, Inbox, DollarSign, Calendar, Phone, MessageCircle, FileText, MapPin, AlertTriangle } from 'lucide-react';
import supplierService from '../../services/supplierService';
import '../../styles/SupplierDetails.css';

const SupplierDetails = () => {
  const { id } = useParams();
  const navigate = useNavigate();
  
  const [supplier, setSupplier] = useState(null);
  const [statistics, setStatistics] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [showDeleteConfirm, setShowDeleteConfirm] = useState(false);
  const [deleting, setDeleting] = useState(false);

  useEffect(() => {
    loadSupplierData();
  }, [id]);

  const loadSupplierData = async () => {
    try {
      setLoading(true);
      setError(null);
      
      const [supplierData, statsData] = await Promise.all([
        supplierService.getSupplier(id),
        supplierService.getSupplierStatistics(id)
      ]);
      
      setSupplier(supplierData);
      setStatistics(statsData);
    } catch (err) {
      setError('Erreur lors du chargement des données');
      console.error(err);
    } finally {
      setLoading(false);
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

  const getReliabilityColor = (score) => {
    if (score >= 8) return 'success';
    if (score >= 6) return 'warning';
    return 'danger';
  };

  const getReliabilityLabel = (score) => {
    if (score >= 8) return 'Excellent';
    if (score >= 6) return 'Bon';
    if (score >= 4) return 'Moyen';
    return 'Faible';
  };

  const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleDateString('fr-FR', {
      day: '2-digit',
      month: 'long',
      year: 'numeric'
    });
  };

  if (loading) {
    return (
      <div className="supplier-details-page">
        <div className="loading-container">
          <div className="loading-spinner large"></div>
          <p>Chargement des détails...</p>
        </div>
      </div>
    );
  }

  if (error || !supplier) {
    return (
      <div className="supplier-details-page">
        <div className="error-container">
          <AlertTriangle className="error-icon" size={64} />
          <h3>Erreur</h3>
          <p>{error || 'Fournisseur introuvable'}</p>
          <button 
            className="btn-primary"
            onClick={() => navigate('/fournisseurs')}
          >
            <ArrowLeft size={18} /> Retour à la liste
          </button>
        </div>
      </div>
    );
  }

  return (
    <div className="supplier-details-page">
      {/* Header */}
      <div className="details-header">
        <button 
          className="btn-back"
          onClick={() => navigate('/fournisseurs')}
        >
          <ArrowLeft size={18} /> Retour
        </button>

        <div className="header-content">
          <div className="header-main">
            <div className="supplier-logo-large">
              {supplier.logo_url ? (
                <img src={supplier.logo_url} alt={supplier.name} />
              ) : (
                <div className="logo-placeholder-large">
                  {supplier.name?.charAt(0).toUpperCase()}
                </div>
              )}
            </div>

            <div className="header-info">
              <h1 className="supplier-title">{supplier.name}</h1>
              
              <div className="header-badges">
                <span className={`status-badge ${supplier.is_active ? 'active' : 'inactive'}`}>
                  {supplier.is_active ? 'Actif' : 'Inactif'}
                </span>
                
                <span className={`reliability-badge ${getReliabilityColor(supplier.reliability_score || 0)}`}>
                  {supplier.reliability_score || '0.0'}/10 - {getReliabilityLabel(supplier.reliability_score || 0)}
                </span>
              </div>
            </div>
          </div>

          <div className="header-actions">
            <button 
              className="btn-secondary"
              onClick={() => navigate(`/fournisseurs/${id}/modifier`)}
            >
              <Edit2 size={18} /> Modifier
            </button>
            <button 
              className="btn-danger"
              onClick={() => setShowDeleteConfirm(true)}
            >
              <Trash2 size={18} /> Supprimer
            </button>
          </div>
        </div>
      </div>

      {/* Statistiques */}
      {statistics && (
        <div className="stats-section">
          <h2 className="section-title">
            <FileText className="section-icon" size={24} />
            Statistiques
          </h2>
          
          <div className="stats-grid">
            <div className="stat-card">
              <div className="stat-icon" style={{ background: 'var(--primary-light)', color: 'var(--primary)' }}>
                <Package size={24} />
              </div>
              <div className="stat-info">
                <div className="stat-value">{statistics.total_products || 0}</div>
                <div className="stat-label">Produits</div>
              </div>
            </div>

            <div className="stat-card">
              <div className="stat-icon" style={{ background: 'var(--success-light)', color: 'var(--success)' }}>
                <Inbox size={24} />
              </div>
              <div className="stat-info">
                <div className="stat-value">{statistics.total_receipts || 0}</div>
                <div className="stat-label">Réceptions</div>
              </div>
            </div>

            <div className="stat-card">
              <div className="stat-icon" style={{ background: 'var(--warning-light)', color: 'var(--warning)' }}>
                <DollarSign size={24} />
              </div>
              <div className="stat-info">
                <div className="stat-value">
                  {statistics.total_spent ? `${statistics.total_spent.toLocaleString()} Ar` : '0 Ar'}
                </div>
                <div className="stat-label">Total Dépensé</div>
              </div>
            </div>

            <div className="stat-card">
              <div className="stat-icon" style={{ background: 'var(--info-light)', color: 'var(--info)' }}>
                <Calendar size={24} />
              </div>
              <div className="stat-info">
                <div className="stat-value">{formatDate(statistics.last_receipt_date)}</div>
                <div className="stat-label">Dernière Réception</div>
              </div>
            </div>
          </div>
        </div>
      )}

      {/* Informations */}
      <div className="details-content">
        <div className="info-section">
          <h2 className="section-title">
            <FileText className="section-icon" size={24} />
            Informations Générales
          </h2>

          <div className="info-grid">
            {supplier.coordinate && (
              <div className="info-item">
                <div className="info-label"><MapPin size={16} /> Localisation</div>
                <div className="info-value">{supplier.coordinate.full_location}</div>
              </div>
            )}

            {supplier.contact && (
              <div className="info-item">
                <div className="info-label"><Phone size={16} /> Contact</div>
                <div className="info-value">{supplier.contact}</div>
              </div>
            )}

            {supplier.wechat && (
              <div className="info-item">
                <div className="info-label"><MessageCircle size={16} /> WeChat</div>
                <div className="info-value">{supplier.wechat}</div>
              </div>
            )}

            <div className="info-item">
              <div className="info-label"><Calendar size={16} /> Ajouté le</div>
              <div className="info-value">{formatDate(supplier.created_at)}</div>
            </div>

            <div className="info-item">
              <div className="info-label"><Calendar size={16} /> Modifié le</div>
              <div className="info-value">{formatDate(supplier.updated_at)}</div>
            </div>
          </div>

          {supplier.profile && (
            <div className="info-block">
              <div className="info-label"><FileText size={16} /> Profil</div>
              <div className="info-description">{supplier.profile}</div>
            </div>
          )}

          {supplier.accessibility_notes && (
            <div className="info-block">
              <div className="info-label"><MapPin size={16} /> Notes d'Accessibilité</div>
              <div className="info-description">{supplier.accessibility_notes}</div>
            </div>
          )}
        </div>
      </div>

      {/* Modal de confirmation de suppression */}
      {showDeleteConfirm && (
        <div className="modal-overlay" onClick={() => !deleting && setShowDeleteConfirm(false)}>
          <div className="modal-content" onClick={(e) => e.stopPropagation()}>
            <div className="modal-icon danger">
              <AlertTriangle size={40} />
            </div>
            <h3 className="modal-title">Confirmer la suppression</h3>
            <p className="modal-message">
              Êtes-vous sûr de vouloir supprimer le fournisseur <strong>{supplier.name}</strong> ?
              <br />
              Cette action est irréversible.
            </p>
            <div className="modal-actions">
              <button 
                className="btn-secondary"
                onClick={() => setShowDeleteConfirm(false)}
                disabled={deleting}
              >
                Annuler
              </button>
              <button 
                className="btn-danger"
                onClick={handleDelete}
                disabled={deleting}
              >
                {deleting ? (
                  <>
                    <div className="loading-spinner small"></div>
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
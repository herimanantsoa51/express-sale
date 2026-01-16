// ============================================
// pages/FreightForwarders/FreightForwarderDetails.jsx
// ============================================

import { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { ArrowLeft, Edit2, Trash2, Truck, Inbox, DollarSign, Calendar, Phone, MapPin, FileText, AlertTriangle } from 'lucide-react';
import freightForwarderService from '../../services/freightForwarderService';
import '../../styles/FreightForwarderDetails.css';

const FreightForwarderDetails = () => {
  const { id } = useParams();
  const navigate = useNavigate();
  
  const [forwarder, setForwarder] = useState(null);
  const [statistics, setStatistics] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [showDeleteConfirm, setShowDeleteConfirm] = useState(false);
  const [deleting, setDeleting] = useState(false);

  useEffect(() => {
    loadForwarderData();
  }, [id]);

  const loadForwarderData = async () => {
    try {
      setLoading(true);
      setError(null);
      console.log('Chargement des données pour ID:', id);
      
      const [forwarderData, statsData] = await Promise.all([
        freightForwarderService.getFreightForwarder(id),
        freightForwarderService.getFreightForwarderStatistics(id)
      ]);

      const forwardera =  forwarderData?.data;
      setForwarder(forwardera);
      setStatistics(statsData);
    } catch (err) {
      console.error('Erreur détaillée:', err);
      console.error('Erreur réponse:', err.response);
      setError('Erreur lors du chargement des données: ' + (err.message || 'Erreur inconnue'));
    } finally {
      setLoading(false);
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

  const getScoreColor = (score) => {
    if (score >= 8) return 'success';
    if (score >= 6) return 'warning';
    return 'danger';
  };

  const getScoreLabel = (score) => {
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
      <div className="freight-details-page">
        <div className="loading-container">
          <div className="loading-spinner large"></div>
          <p>Chargement des détails...</p>
        </div>
      </div>
    );
  }

  if (error || !forwarder) {
    return (
      <div className="freight-details-page">
        <div className="error-container">
          <AlertTriangle className="error-icon" size={64} />
          <h3>Erreur</h3>
          <p>{error || 'Transitaire introuvable'}</p>
          <button 
            className="btn-primary"
            onClick={() => navigate('/transitaires')}
          >
            <ArrowLeft size={18} /> Retour à la liste
          </button>
        </div>
      </div>
    );
  }

  return (
    <div className="freight-details-page">
      {/* Header */}
      <div className="details-header">
        <button 
          className="btn-back"
          onClick={() => navigate('/transitaires')}
        >
          <ArrowLeft size={18} /> Retour
        </button>

        <div className="header-content">
          <div className="header-main">
            <div className="freight-logo-large">
              {forwarder.logo_url ? (
                <img src={forwarder.logo_url} alt={forwarder.name} />
              ) : (
                <div className="logo-placeholder-large">
                  {forwarder.name?.charAt(0).toUpperCase()}
                </div>
              )}
            </div>

            <div className="header-info">
              <h1 className="freight-title">{forwarder.name}</h1>
              
              <div className="header-badges">
                <span className={`status-badge ${forwarder.is_active ? 'active' : 'inactive'}`}>
                  {forwarder.is_active ? 'Actif' : 'Inactif'}
                </span>
                
                <span className={`score-badge ${getScoreColor(forwarder.service_score || 0)}`}>
                  {forwarder.service_score || '0.0'}/10 - {getScoreLabel(forwarder.service_score || 0)}
                </span>
              </div>
            </div>
          </div>

          <div className="header-actions">
            <button 
              className="btn-secondary"
              onClick={() => navigate(`/transitaires/${id}/modifier`)}
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
                <Inbox size={24} />
              </div>
              <div className="stat-info">
                <div className="stat-value">{statistics.total_receipts || 0}</div>
                <div className="stat-label">Total Réceptions</div>
              </div>
            </div>

            <div className="stat-card">
              <div className="stat-icon" style={{ background: 'var(--success-light)', color: 'var(--success)' }}>
                <Truck size={24} />
              </div>
              <div className="stat-info">
                <div className="stat-value">{statistics.active_receipts || 0}</div>
                <div className="stat-label">Réceptions Validées</div>
              </div>
            </div>

            <div className="stat-card">
              <div className="stat-icon" style={{ background: 'var(--warning-light)', color: 'var(--warning)' }}>
                <DollarSign size={24} />
              </div>
              <div className="stat-info">
                <div className="stat-value">
                  {statistics.total_value ? `${statistics.total_value.toLocaleString()} Ar` : '0 Ar'}
                </div>
                <div className="stat-label">Valeur Totale</div>
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
            {forwarder.coordinate && (
              <div className="info-item">
                <div className="info-label"><MapPin size={16} /> Localisation</div>
                <div className="info-value">{forwarder.coordinate.full_location}</div>
              </div>
            )}

            {forwarder.contact && (
              <div className="info-item">
                <div className="info-label"><Phone size={16} /> Contact</div>
                <div className="info-value">{forwarder.contact}</div>
              </div>
            )}

            <div className="info-item">
              <div className="info-label"><Calendar size={16} /> Ajouté le</div>
              <div className="info-value">{formatDate(forwarder.created_at)}</div>
            </div>

            <div className="info-item">
              <div className="info-label"><Calendar size={16} /> Modifié le</div>
              <div className="info-value">{formatDate(forwarder.updated_at)}</div>
            </div>
          </div>

          {forwarder.notes && (
            <div className="info-block">
              <div className="info-label"><FileText size={16} /> Notes</div>
              <div className="info-description">{forwarder.notes}</div>
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
              Êtes-vous sûr de vouloir supprimer le transitaire <strong>{forwarder.name}</strong> ?
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

export default FreightForwarderDetails;
import React from 'react';
import { 
  XCircle, 
  Star, 
  CheckCircle2, 
  AlertCircle,
  User,
  Calendar,
  FileText,
  Award,
  TrendingUp,
  Percent,
  Hash
} from 'lucide-react';

const RatingsModal = ({ isOpen, onClose, item }) => {
  if (!isOpen || !item) return null;

  const { quality_summary, ratings, conformity } = item;
  const productName = item.variant?.product?.name || 'Article';
  const sku = item.variant?.sku || 'N/A';

  const getQualityLevel = (rating) => {
    if (rating >= 9) return { label: 'Excellent', color: 'success' };
    if (rating >= 7) return { label: 'Bon', color: 'info' };
    if (rating >= 5) return { label: 'Moyen', color: 'warning' };
    return { label: 'Faible', color: 'danger' };
  };

  const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('fr-FR', {
      day: '2-digit',
      month: 'short',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  };

  return (
    <div className="modal-overlay" onClick={onClose}>
      <div className="modal-content modal-ratings" onClick={e => e.stopPropagation()}>
        <div className="modal-header">
          <div className="modal-header-content">
            <Star size={24} className="modal-icon" strokeWidth={2} fill="var(--primary)" />
            <div>
              <h3 className="modal-title">Évaluations de qualité</h3>
              <p className="modal-subtitle">{productName} • {sku}</p>
            </div>
          </div>
          <button className="modal-close" onClick={onClose} aria-label="Fermer" type="button">
            <XCircle size={20} strokeWidth={2} />
          </button>
        </div>
        
        <div className="modal-body">
          {/* Résumé global */}
          {quality_summary && (
            <div className="ratings-summary-section">
              <h4 className="ratings-section-title">
                <Award size={16} strokeWidth={2} />
                Résumé général
              </h4>
              <div className="ratings-summary-grid">
                <div className="rating-summary-card">
                  <div className="rating-summary-icon success">
                    <Star size={20} strokeWidth={2} fill="currentColor" />
                  </div>
                  <div className="rating-summary-content">
                    <span className="rating-summary-value">{quality_summary.overall_score?.toFixed(1) || 0}/10</span>
                    <span className="rating-summary-label">Score global</span>
                  </div>
                </div>
                
                <div className="rating-summary-card">
                  <div className="rating-summary-icon info">
                    <TrendingUp size={20} strokeWidth={2} />
                  </div>
                  <div className="rating-summary-content">
                    <span className="rating-summary-value">{quality_summary.average_quality_rating?.toFixed(1) || 0}/10</span>
                    <span className="rating-summary-label">Qualité moyenne</span>
                  </div>
                </div>
                
                <div className="rating-summary-card">
                  <div className="rating-summary-icon warning">
                    <CheckCircle2 size={20} strokeWidth={2} />
                  </div>
                  <div className="rating-summary-content">
                    <span className="rating-summary-value">{quality_summary.attribute_conformity_rate || 0}%</span>
                    <span className="rating-summary-label">Conformité</span>
                  </div>
                </div>
                
                <div className="rating-summary-card">
                  <div className="rating-summary-icon primary">
                    <Percent size={20} strokeWidth={2} />
                  </div>
                  <div className="rating-summary-content">
                    <span className="rating-summary-value">{quality_summary.quantity_fulfillment_rate || 0}%</span>
                    <span className="rating-summary-label">Taux réception</span>
                  </div>
                </div>
              </div>

              {/* Quantités */}
              <div className="ratings-quantities">
                <div className="quantity-item">
                  <Hash size={14} strokeWidth={2} />
                  <span>Commandé: <strong>{quality_summary.quantity_ordered || 0}</strong></span>
                </div>
                <div className="quantity-item">
                  <Hash size={14} strokeWidth={2} />
                  <span>Reçu: <strong>{quality_summary.quantity_received || 0}</strong></span>
                </div>
                {quality_summary.quantity_variance !== 0 && (
                  <div className={`quantity-item ${quality_summary.quantity_variance < 0 ? 'negative' : 'positive'}`}>
                    <Hash size={14} strokeWidth={2} />
                    <span>Variance: <strong>{quality_summary.quantity_variance > 0 ? '+' : ''}{quality_summary.quantity_variance || 0}</strong></span>
                  </div>
                )}
              </div>
            </div>
          )}

          {/* Détails de conformité */}
          {conformity && conformity.details && conformity.details.length > 0 && (
            <div className="ratings-conformity-section">
              <h4 className="ratings-section-title">
                <CheckCircle2 size={16} strokeWidth={2} />
                Conformité des attributs
              </h4>
              <div className="conformity-list">
                {conformity.details.map((detail, index) => (
                  <div key={index} className="conformity-item">
                    <div className="conformity-icon">
                      {detail.is_conforming ? (
                        <CheckCircle2 size={16} strokeWidth={2} className="text-success" />
                      ) : (
                        <AlertCircle size={16} strokeWidth={2} className="text-danger" />
                      )}
                    </div>
                    <div className="conformity-content">
                      <span className="conformity-name">{detail.display_name}</span>
                      <div className="conformity-rating">
                        <span className={`conformity-badge ${detail.is_conforming ? 'success' : 'danger'}`}>
                          {parseFloat(detail.rating).toFixed(1)}/10
                        </span>
                        <span className="conformity-status">
                          {detail.is_conforming ? 'Conforme' : 'Non conforme'}
                        </span>
                      </div>
                    </div>
                  </div>
                ))}
              </div>
            </div>
          )}

          {/* Liste des évaluations détaillées */}
          {ratings && ratings.length > 0 && (
            <div className="ratings-details-section">
              <h4 className="ratings-section-title">
                <FileText size={16} strokeWidth={2} />
                Évaluations détaillées ({ratings.length})
              </h4>
              <div className="ratings-list">
                {ratings.map((rating) => {
                  const qualityLevel = getQualityLevel(rating.quality_rating);
                  
                  return (
                    <div key={rating.id} className="rating-detail-card">
                      <div className="rating-detail-header">
                        <div className="rating-detail-type">
                          {rating.attribute_type ? (
                            <>
                              <CheckCircle2 size={14} strokeWidth={2} />
                              <span>{rating.attribute_type.display_name}</span>
                            </>
                          ) : (
                            <>
                              <Star size={14} strokeWidth={2} />
                              <span>Évaluation générale</span>
                            </>
                          )}
                        </div>
                        <div className={`rating-detail-score ${qualityLevel.color}`}>
                          <Star size={12} strokeWidth={2} fill="currentColor" />
                          {rating.overall_rating?.toFixed(1) || 0}/10
                        </div>
                      </div>

                      <div className="rating-detail-metrics">
                        <div className="rating-metric">
                          <span className="rating-metric-label">Qualité</span>
                          <span className={`rating-metric-value ${qualityLevel.color}`}>
                            {rating.quality_rating}/10
                          </span>
                        </div>
                        
                        {rating.attribute_type && (
                          <div className="rating-metric">
                            <span className="rating-metric-label">Conformité</span>
                            <span className={`rating-metric-value ${rating.is_conforming ? 'success' : 'danger'}`}>
                              {rating.attribute_conformity_rating}/10
                            </span>
                          </div>
                        )}
                      </div>

                      {rating.quality_notes && (
                        <div className="rating-detail-notes">
                          <FileText size={12} strokeWidth={2} />
                          <p>{rating.quality_notes}</p>
                        </div>
                      )}

                      <div className="rating-detail-footer">
                        <div className="rating-detail-user">
                          <User size={12} strokeWidth={2} />
                          <span>{rating.rated_by?.name || 'Inconnu'}</span>
                        </div>
                        <div className="rating-detail-date">
                          <Calendar size={12} strokeWidth={2} />
                          <span>{formatDate(rating.created_at)}</span>
                        </div>
                      </div>
                    </div>
                  );
                })}
              </div>
            </div>
          )}

          {(!ratings || ratings.length === 0) && (
            <div className="ratings-empty-state">
              <Star size={48} strokeWidth={1.5} />
              <p>Aucune évaluation disponible</p>
            </div>
          )}
        </div>
        
        <div className="modal-footer">
          <button 
            type="button" 
            className="btn btn-secondary" 
            onClick={onClose}
          >
            Fermer
          </button>
        </div>
      </div>
    </div>
  );
};

export default RatingsModal;
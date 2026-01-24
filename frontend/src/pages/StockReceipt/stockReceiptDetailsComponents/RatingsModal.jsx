import React from 'react';
import { 
  XCircle, 
  Star, 
  CheckCircle2, 
  AlertCircle,
  User,
  Calendar,
  FileText,
  Package,
  TrendingUp,
  Percent
} from 'lucide-react';

const RatingsModal = ({ isOpen, onClose, item }) => {
  if (!isOpen || !item) return null;

  const { summary, conformity, ratings } = item;
  const productName = item.variant?.product?.name || 'Article';
  const sku = item.variant?.sku || 'N/A';

  const getQualityColor = (rating) => {
    if (!rating) return 'secondary';
    if (rating >= 9) return 'success';
    if (rating >= 7) return 'info';
    if (rating >= 5) return 'warning';
    return 'danger';
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
              <h3 className="modal-title">Évaluation de la réception</h3>
              <p className="modal-subtitle">{productName} • {sku}</p>
            </div>
          </div>
          <button className="modal-close" onClick={onClose} aria-label="Fermer" type="button">
            <XCircle size={20} strokeWidth={2} />
          </button>
        </div>
        
        <div className="modal-body">
          {/* Qualité globale et quantités */}
          {summary && (
            <div className="ratings-summary-section">
              <h4 className="ratings-section-title">
                <Package size={16} strokeWidth={2} />
                Informations générales
              </h4>
              
              <div className="ratings-summary-grid">
                {/* Qualité globale */}
                <div className="rating-summary-card highlight">
                  <div className={`rating-summary-icon ${getQualityColor(summary.quality_rating)}`}>
                    <Star size={20} strokeWidth={2} fill="currentColor" />
                  </div>
                  <div className="rating-summary-content">
                    <span className="rating-summary-value">
                      {summary.quality_rating ? `${summary.quality_rating}/10` : 'Non évalué'}
                    </span>
                    <span className="rating-summary-label">Qualité globale</span>
                    {summary.quality_level && (
                      <span className="rating-summary-sublabel">{summary.quality_level}</span>
                    )}
                  </div>
                </div>
                
                {/* Conformité */}
                <div className="rating-summary-card">
                  <div className={`rating-summary-icon ${summary.conformity_rate >= 80 ? 'success' : summary.conformity_rate >= 60 ? 'warning' : 'danger'}`}>
                    <CheckCircle2 size={20} strokeWidth={2} />
                  </div>
                  <div className="rating-summary-content">
                    <span className="rating-summary-value">{summary.conformity_rate}%</span>
                    <span className="rating-summary-label">Conformité</span>
                    <span className="rating-summary-sublabel">
                      {summary.conforming_attributes}/{summary.total_attributes} attributs
                    </span>
                  </div>
                </div>
                
                {/* Quantité reçue */}
                <div className="rating-summary-card">
                  <div className={`rating-summary-icon ${summary.quantity_rate >= 100 ? 'success' : summary.quantity_rate >= 95 ? 'info' : 'warning'}`}>
                    <TrendingUp size={20} strokeWidth={2} />
                  </div>
                  <div className="rating-summary-content">
                    <span className="rating-summary-value">{summary.quantity_rate}%</span>
                    <span className="rating-summary-label">Taux de réception</span>
                    <span className="rating-summary-sublabel">
                      {summary.quantity_received}/{summary.quantity_ordered} unités
                    </span>
                  </div>
                </div>
                
                {/* Coût total */}
                <div className="rating-summary-card">
                  <div className="rating-summary-icon primary">
                    <Percent size={20} strokeWidth={2} />
                  </div>
                  <div className="rating-summary-content">
                    <span className="rating-summary-value">
                      {summary.total_cost?.toLocaleString('fr-FR')} Ar
                    </span>
                    <span className="rating-summary-label">Coût total</span>
                  </div>
                </div>
              </div>

              {/* Notes sur la qualité */}
              {summary.quality_notes && (
                <div className="rating-detail-notes">
                  <FileText size={14} strokeWidth={2} />
                  <p><strong>Notes :</strong> {summary.quality_notes}</p>
                </div>
              )}
            </div>
          )}

          {/* Détails de conformité par attribut
          {conformity && conformity.details && conformity.details.length > 0 && (
            <div className="ratings-conformity-section">
              <h4 className="ratings-section-title">
                <CheckCircle2 size={16} strokeWidth={2} />
                Conformité des attributs ({conformity.conforming}/{conformity.total})
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
                        {detail.rating ? (
                          <>
                            <span className={`conformity-badge ${detail.is_conforming ? 'success' : 'danger'}`}>
                              {parseFloat(detail.rating).toFixed(1)}/10
                            </span>
                            <span className="conformity-status">{detail.conformity_level}</span>
                          </>
                        ) : (
                          <span className="conformity-badge secondary">Non évalué</span>
                        )}
                      </div>
                      {detail.notes && (
                        <p className="conformity-notes">{detail.notes}</p>
                      )}
                    </div>
                  </div>
                ))}
              </div>
            </div>
          )} */}

          {/* Historique des évaluations */}
          {ratings && ratings.length > 0 && (
            <div className="ratings-details-section">
              <h4 className="ratings-section-title">
                <FileText size={16} strokeWidth={2} />
                Historique des évaluations ({ratings.length})
              </h4>
              <div className="ratings-list">
                {ratings.map((rating) => (
                  <div key={rating.id} className="rating-detail-card">
                    <div className="rating-detail-header">
                      <div className="rating-detail-type">
                        <CheckCircle2 size={14} strokeWidth={2} />
                        <span>{rating.attribute_type.display_name}</span>
                      </div>
                      <div className={`rating-detail-score ${rating.is_conforming ? 'success' : 'danger'}`}>
                        {rating.conformity_rating}/10
                      </div>
                    </div>

                    <div className="rating-detail-metrics">
                      <span className={`rating-metric-badge ${rating.is_conforming ? 'success' : 'danger'}`}>
                        {rating.conformity_level}
                      </span>
                    </div>

                    {rating.notes && (
                      <div className="rating-detail-notes">
                        <FileText size={12} strokeWidth={2} />
                        <p>{rating.notes}</p>
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
                ))}
              </div>
            </div>
          )}

          {(!summary || !summary.has_ratings) && (
            <div className="ratings-empty-state">
              <Star size={48} strokeWidth={1.5} />
              <p>Aucune évaluation disponible pour cet article</p>
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
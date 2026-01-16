import { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import {
  ArrowLeft,
  Package,
  Star,
  CheckSquare,
  Loader2,
  AlertCircle,
  BadgeCheck,
  Save,
  Box,
  FileText,
  AlertTriangle,
  X,
  Check,
  Clock
} from 'lucide-react';
import stockReceiptService from '../../services/stockReceiptService';
import './StockReceiptRating.css';

// Composant pour afficher une couleur
const ColorSwatch = ({ color, size = 20 }) => {
  if (!color || !color.startsWith('#')) return null;
  return (
    <span 
      className="color-swatch-rating"
      style={{ 
        backgroundColor: color, 
        width: size, 
        height: size, 
        borderRadius: 4,
        display: 'inline-block',
        border: '2px solid var(--border-color)',
        verticalAlign: 'middle',
        boxShadow: '0 1px 3px rgba(0,0,0,0.2)'
      }} 
      title={color}
    />
  );
};

// Composant pour un slider de note
const RatingSlider = ({ label, icon: Icon, value, onChange, description, disabled, touched, required }) => {
  const getColorClass = (val) => {
    if (val >= 8) return 'excellent';
    if (val >= 6) return 'good';
    if (val >= 4) return 'average';
    return 'poor';
  };

  return (
    <div className={`rating-slider-field ${!touched && required ? 'untouched' : ''}`}>
      <div className="rating-slider-header">
        <label className="rating-slider-label">
          {Icon && <Icon size={18} />}
          <span>{label}</span>
          {!touched && required && <span className="required-badge">À évaluer</span>}
        </label>
        <span className={`rating-value-display ${getColorClass(value)}`}>
          {value}/10
        </span>
      </div>
      {description && <p className="rating-slider-description">{description}</p>}
      <div className="rating-slider-wrapper">
        <input
          type="range"
          min="0"
          max="10"
          step="1"
          value={value}
          onChange={(e) => onChange(parseInt(e.target.value))}
          className={`rating-slider-input ${getColorClass(value)}`}
          disabled={disabled}
        />
        <div className="rating-slider-marks">
          {[0, 2, 4, 6, 8, 10].map(mark => (
            <span key={mark} className="rating-mark">{mark}</span>
          ))}
        </div>
      </div>
    </div>
  );
};

// Indicateur de progression
const ProgressIndicator = ({ current, total }) => {
  const percentage = total > 0 ? (current / total) * 100 : 0;
  
  return (
    <div className="progress-indicator">
      <div className="progress-info">
        <span className="progress-label">
          <Clock size={16} />
          Progression
        </span>
        <span className="progress-count">
          {current} / {total} évaluations
        </span>
      </div>
      <div className="progress-bar-container">
        <div 
          className="progress-bar-fill" 
          style={{ width: `${percentage}%` }}
        />
      </div>
    </div>
  );
};

// Modal de confirmation
const ConfirmationModal = ({ isOpen, onConfirm, onCancel, itemsCount }) => {
  if (!isOpen) return null;

  return (
    <div className="modal-overlay-rating" onClick={onCancel}>
      <div className="confirmation-modal-rating" onClick={e => e.stopPropagation()}>
        <div className="modal-icon-rating warning">
          <AlertTriangle size={48} />
        </div>
        <h3 className="modal-title-rating">Confirmer l'évaluation</h3>
        <p className="modal-text-rating">
          Vous êtes sur le point d'enregistrer l'évaluation de <strong>{itemsCount} article{itemsCount > 1 ? 's' : ''}</strong>.
        </p>
        <p className="modal-warning-text">
          ⚠️ Une fois validée, cette évaluation ne pourra plus être modifiée.
        </p>
        <div className="modal-actions-rating">
          <button className="btn btn-secondary" onClick={onCancel}>
            <X size={16} />
            Annuler
          </button>
          <button className="btn btn-primary" onClick={onConfirm}>
            <Check size={16} />
            Confirmer l'évaluation
          </button>
        </div>
      </div>
    </div>
  );
};

// Toast de succès
const SuccessToast = ({ message, onClose }) => {
  useEffect(() => {
    const timer = setTimeout(onClose, 3000);
    return () => clearTimeout(timer);
  }, [onClose]);

  return (
    <div className="success-toast-rating">
      <div className="toast-icon-rating">
        <Check size={20} />
      </div>
      <span>{message}</span>
    </div>
  );
};

const StockReceiptRating = () => {
  const { id } = useParams();
  const navigate = useNavigate();

  const [receipt, setReceipt] = useState(null);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [validating, setValidating] = useState(false);
  const [error, setError] = useState(null);
  const [ratingsData, setRatingsData] = useState({});
  const [touchedRatings, setTouchedRatings] = useState({});
  const [isSaved, setIsSaved] = useState(false);
  const [showConfirmModal, setShowConfirmModal] = useState(false);
  const [showSuccessToast, setShowSuccessToast] = useState(false);

  useEffect(() => {
    fetchReceipt();
  }, [id]);

  const fetchReceipt = async () => {
    try {
      setLoading(true);
      const response = await stockReceiptService.getById(id);
      
      if (!response.data) {
        throw new Error('Réception non trouvée');
      }

      // Vérifier que la réception est au bon statut
      if (response.data.status !== 'arrived') {
        setError('Cette réception n\'est pas prête pour l\'évaluation. Elle doit être au statut "Arrivée".');
        setReceipt(null);
        return;
      }

      setReceipt(response.data);
      
      // Initialiser les données de rating
      const initialRatings = {};
      const initialTouched = {};
      response.data.items?.forEach(item => {
        const attributes = item.variant?.attributes || [];
        initialRatings[item.id] = {
          quality_rating: 7,
          quality_notes: '',
          attribute_ratings: attributes.map(attr => ({
            attribute_type_id: attr.attribute_id,
            attribute_type: attr.type,
            attribute_value: attr.value,
            conformity_rating: 7
          }))
        };
        
        // Initialiser les "touched" pour chaque rating
        initialTouched[item.id] = {
          quality: false,
          attributes: attributes.map(() => false)
        };
      });
      setRatingsData(initialRatings);
      setTouchedRatings(initialTouched);
      setError(null);
    } catch (err) {
      console.error('Error fetching receipt:', err);
      setError(err.message || 'Erreur lors du chargement de la réception');
    } finally {
      setLoading(false);
    }
  };

  const handleQualityRatingChange = (itemId, value) => {
    if (isSaved) return;
    setRatingsData(prev => ({
      ...prev,
      [itemId]: {
        ...prev[itemId],
        quality_rating: value
      }
    }));
    
    // Marquer comme touché
    setTouchedRatings(prev => ({
      ...prev,
      [itemId]: {
        ...prev[itemId],
        quality: true
      }
    }));
  };

  const handleAttributeRatingChange = (itemId, attrIndex, value) => {
    if (isSaved) return;
    setRatingsData(prev => ({
      ...prev,
      [itemId]: {
        ...prev[itemId],
        attribute_ratings: prev[itemId].attribute_ratings.map((attr, idx) =>
          idx === attrIndex ? { ...attr, conformity_rating: value } : attr
        )
      }
    }));
    
    // Marquer l'attribut comme touché
    setTouchedRatings(prev => ({
      ...prev,
      [itemId]: {
        ...prev[itemId],
        attributes: prev[itemId].attributes.map((touched, idx) =>
          idx === attrIndex ? true : touched
        )
      }
    }));
  };

  const handleNotesChange = (itemId, value) => {
    if (isSaved) return;
    setRatingsData(prev => ({
      ...prev,
      [itemId]: {
        ...prev[itemId],
        quality_notes: value
      }
    }));
  };

  const calculateAverageConformity = (attributeRatings) => {
    if (!attributeRatings || attributeRatings.length === 0) return 7;
    const sum = attributeRatings.reduce((acc, attr) => acc + attr.conformity_rating, 0);
    return Math.round(sum / attributeRatings.length);
  };

  // Calculer le nombre total de ratings requis et complétés
  const getTotalRatingsStats = () => {
    let total = 0;
    let completed = 0;

    receipt?.items.forEach(item => {
      const itemTouched = touchedRatings[item.id];
      if (!itemTouched) return;

      // Rating de qualité
      total++;
      if (itemTouched.quality) completed++;

      // Ratings d'attributs
      const attrCount = item.variant?.attributes?.length || 0;
      total += attrCount;
      completed += itemTouched.attributes.filter(t => t).length;
    });

    return { total, completed, isComplete: total > 0 && completed === total };
  };

  const handleSaveAllRatings = () => {
    const stats = getTotalRatingsStats();
    
    if (!stats.isComplete) {
      alert(`Veuillez compléter toutes les évaluations avant d'enregistrer. (${stats.completed}/${stats.total} complétées)`);
      return;
    }
    
    setShowConfirmModal(true);
  };

  const confirmSaveRatings = async () => {
    setShowConfirmModal(false);
    
    try {
      setSaving(true);
      
      // Pour chaque item, construire et envoyer les ratings
      for (const item of receipt.items) {
        const itemRating = ratingsData[item.id];
        if (!itemRating) continue;

        const ratings = [];
        
        // Rating général
        ratings.push({
          quality_rating: itemRating.quality_rating,
          attribute_conformity_rating: calculateAverageConformity(itemRating.attribute_ratings),
          quality_notes: itemRating.quality_notes || null
        });
        
        // Ratings par attribut
        itemRating.attribute_ratings.forEach(attrRating => {
          if (attrRating.attribute_type_id) {
            ratings.push({
              attribute_type_id: attrRating.attribute_type_id,
              attribute_conformity_rating: attrRating.conformity_rating,
              quality_rating: itemRating.quality_rating,
              quality_notes: null
            });
          }
        });

        await stockReceiptService.rateItem(id, item.id, { ratings });
      }
      
      setIsSaved(true);
      setShowSuccessToast(true);
      
    } catch (err) {
      console.error('Error saving ratings:', err);
      alert(err.response?.data?.message || 'Erreur lors de la sauvegarde des évaluations');
    } finally {
      setSaving(false);
    }
  };

  const handleValidateReceipt = async () => {
    if (!isSaved) {
      alert('Vous devez d\'abord enregistrer les évaluations avant de valider la réception.');
      return;
    }

    try {
      setValidating(true);
      await stockReceiptService.validate(id);
      navigate(`/reapprovisionnements/${id}`, { 
        state: { message: 'Réception validée avec succès' } 
      });
    } catch (err) {
      console.error('Error validating receipt:', err);
      alert(err.response?.data?.message || 'Erreur lors de la validation de la réception');
    } finally {
      setValidating(false);
    }
  };

  if (loading) {
    return (
      <div className="rating-page">
        <div className="loading-container">
          <div className="loading-content">
            <Loader2 className="spinner" size={48} />
            <h3>Chargement...</h3>
            <p>Récupération des informations de la réception</p>
          </div>
        </div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="rating-page">
        <div className="error-container">
          <div className="error-content">
            <AlertCircle size={64} className="error-icon" />
            <h3>Erreur</h3>
            <p className="error-message">{error}</p>
            <div className="error-actions">
              <button className="btn btn-primary" onClick={() => navigate(`/reapprovisionnements/${id}`)}>
                <ArrowLeft size={16} />
                Retour aux détails
              </button>
            </div>
          </div>
        </div>
      </div>
    );
  }

  if (!receipt || !receipt.items || receipt.items.length === 0) {
    return (
      <div className="rating-page">
        <div className="error-container">
          <div className="error-content">
            <Package size={64} className="error-icon" />
            <h3>Aucun article à évaluer</h3>
            <p>Cette réception ne contient aucun article.</p>
            <div className="error-actions">
              <button className="btn btn-primary" onClick={() => navigate(`/reapprovisionnements/${id}`)}>
                <ArrowLeft size={16} />
                Retour aux détails
              </button>
            </div>
          </div>
        </div>
      </div>
    );
  }

  const ratingsStats = getTotalRatingsStats();

  return (
    <div className="rating-page">
      {/* Header */}
      <header className="rating-header">
        <div className="rating-header-left">
          <button className="btn-back" onClick={() => navigate(`/reapprovisionnements/${id}`)}>
            <ArrowLeft size={20} />
            Retour
          </button>
          <div className="rating-title-section">
            <h1 className="rating-page-title">
              <Star size={24} className="title-icon" />
              Évaluation qualité
            </h1>
            <p className="rating-subtitle">
              {receipt.receipt_number} • {receipt.items.length} article{receipt.items.length > 1 ? 's' : ''}
            </p>
          </div>
        </div>
        <div className="rating-header-right">
          {!isSaved ? (
            <button 
              className="btn btn-primary"
              onClick={handleSaveAllRatings}
              disabled={saving || !ratingsStats.isComplete}
            >
              {saving ? (
                <>
                  <Loader2 className="spinner" size={16} />
                  Enregistrement...
                </>
              ) : (
                <>
                  <Save size={16} />
                  Enregistrer toutes les évaluations
                </>
              )}
            </button>
          ) : (
            <button 
              className="btn btn-success"
              onClick={handleValidateReceipt}
              disabled={validating}
            >
              {validating ? (
                <>
                  <Loader2 className="spinner" size={16} />
                  Validation...
                </>
              ) : (
                <>
                  <BadgeCheck size={16} />
                  Valider la réception
                </>
              )}
            </button>
          )}
        </div>
      </header>

      {/* Indicateur de progression */}
      {!isSaved && (
        <div className="progress-section">
          <ProgressIndicator 
            current={ratingsStats.completed} 
            total={ratingsStats.total} 
          />
          {!ratingsStats.isComplete && (
            <p className="progress-hint">
              <AlertCircle size={16} />
              Vous devez évaluer tous les critères avant de pouvoir enregistrer
            </p>
          )}
        </div>
      )}

      {/* Notice si déjà sauvegardé */}
      {isSaved && (
        <div className="locked-notice-rating">
          <CheckSquare size={20} />
          <div>
            <strong>Évaluations enregistrées</strong>
            <p className="locked-notice-text">
              Les évaluations ont été enregistrées et ne peuvent plus être modifiées. Vous pouvez maintenant valider la réception.
            </p>
          </div>
        </div>
      )}

      {/* Contenu principal */}
      <div className="rating-main-content">
        {/* Grille des items */}
        <div className="items-grid-rating">
          {receipt.items.map((item, itemIndex) => {
            const itemRating = ratingsData[item.id] || {};
            const itemTouched = touchedRatings[item.id] || { quality: false, attributes: [] };
            
            // Calculer si cet item est complet
            const itemComplete = itemTouched.quality && 
              itemTouched.attributes.every(t => t);
            
            return (
              <div key={item.id} className={`item-rating-card ${isSaved ? 'saved' : ''} ${itemComplete ? 'complete' : ''}`}>
                {/* Header de l'item */}
                <div className="item-header-rating">
                  <div className="item-icon-rating">
                    <Box size={24} />
                  </div>
                  <div className="item-info-rating">
                    <div className="item-number-badge">Article {itemIndex + 1}/{receipt.items.length}</div>
                    <h3 className="item-name-rating">
                      {item.variant?.product?.name || 'Article sans nom'}
                    </h3>
                    <div className="item-meta-rating">
                      <span className="item-sku">{item.variant?.sku || 'N/A'}</span>
                      <span className="item-quantity">
                        {item.quantity_received || 0} / {item.quantity_ordered || 0} reçus
                      </span>
                    </div>
                  </div>
                  {isSaved ? (
                    <span className="saved-badge">
                      <CheckSquare size={16} />
                      Évalué
                    </span>
                  ) : itemComplete ? (
                    <span className="complete-badge">
                      <Check size={16} />
                      Complet
                    </span>
                  ) : null}
                </div>

                {/* Attributs */}
                {item.variant?.attributes && item.variant.attributes.length > 0 && (
                  <div className="attributes-display-rating">
                    {item.variant.attributes.map((attr, idx) => (
                      <div key={idx} className="attribute-tag">
                        <span className="attr-label">{attr.type}:</span>
                        {attr.value?.startsWith('#') ? (
                          <ColorSwatch color={attr.value} />
                        ) : (
                          <span className="attr-value">{attr.value}</span>
                        )}
                      </div>
                    ))}
                  </div>
                )}

                {/* Note de qualité générale */}
                <RatingSlider
                  label="Qualité du produit"
                  icon={Star}
                  value={itemRating.quality_rating || 7}
                  onChange={(val) => handleQualityRatingChange(item.id, val)}
                  description="Évaluez la qualité globale du produit reçu"
                  disabled={isSaved}
                  touched={itemTouched.quality}
                  required={true}
                />

                {/* Ratings par attribut */}
                {itemRating.attribute_ratings && itemRating.attribute_ratings.length > 0 && (
                  <div className="attribute-ratings-section">
                    <h4 className="section-title-rating">
                      <CheckSquare size={18} />
                      Conformité des attributs
                    </h4>
                    <p className="section-description">
                      Évaluez la conformité de chaque attribut par rapport à la commande
                    </p>
                    
                    {itemRating.attribute_ratings.map((attrRating, idx) => (
                      <div key={idx} className="attribute-rating-item">
                        <div className="attribute-rating-header">
                          <span className="attribute-name">{attrRating.attribute_type}</span>
                          <span className="attribute-expected">
                            Attendu: {attrRating.attribute_value?.startsWith('#') ? (
                              <ColorSwatch color={attrRating.attribute_value} size={16} />
                            ) : (
                              <strong>{attrRating.attribute_value}</strong>
                            )}
                          </span>
                        </div>
                        <RatingSlider
                          label={`Conformité ${attrRating.attribute_type}`}
                          value={attrRating.conformity_rating}
                          onChange={(val) => handleAttributeRatingChange(item.id, idx, val)}
                          disabled={isSaved}
                          touched={itemTouched.attributes[idx]}
                          required={true}
                        />
                      </div>
                    ))}
                  </div>
                )}

                {/* Notes */}
                <div className="notes-section-rating">
                  <label className="notes-label">
                    <FileText size={18} />
                    Notes et commentaires (optionnel)
                  </label>
                  <textarea
                    value={itemRating.quality_notes || ''}
                    onChange={(e) => handleNotesChange(item.id, e.target.value)}
                    placeholder="Décrivez les défauts observés, remarques sur la qualité, etc."
                    className="notes-textarea-rating"
                    disabled={isSaved}
                  />
                </div>
              </div>
            );
          })}
        </div>

        {/* Boutons d'action */}
        <div className="action-buttons-rating">
          {!isSaved && (
            <button 
              className="btn btn-primary btn-large-rating"
              onClick={handleSaveAllRatings}
              disabled={saving || !ratingsStats.isComplete}
            >
              {saving ? (
                <>
                  <Loader2 className="spinner" size={20} />
                  Enregistrement en cours...
                </>
              ) : (
                <>
                  <Save size={20} />
                  Enregistrer toutes les évaluations ({ratingsStats.completed}/{ratingsStats.total})
                </>
              )}
            </button>
          )}
          {isSaved && (
            <button 
              className="btn btn-success btn-large-rating"
              onClick={handleValidateReceipt}
              disabled={validating}
            >
              {validating ? (
                <>
                  <Loader2 className="spinner" size={20} />
                  Validation en cours...
                </>
              ) : (
                <>
                  <BadgeCheck size={20} />
                  Valider la réception
                </>
              )}
            </button>
          )}
        </div>
      </div>

      {/* Modals et Toasts */}
      <ConfirmationModal
        isOpen={showConfirmModal}
        onConfirm={confirmSaveRatings}
        onCancel={() => setShowConfirmModal(false)}
        itemsCount={receipt.items.length}
      />
      
      {showSuccessToast && (
        <SuccessToast
          message="Évaluations enregistrées avec succès !"
          onClose={() => setShowSuccessToast(false)}
        />
      )}
    </div>
  );
};

export default StockReceiptRating;
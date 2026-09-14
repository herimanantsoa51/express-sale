import { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import {
  ArrowLeft,
  Package,
  Star,
  CheckSquare,
  Loader2,
  AlertCircle,
  Save,
  Box,
  FileText,
  AlertTriangle,
  X,
  Check,
  Clock,
  ArrowRightLeft
} from 'lucide-react';
import stockReceiptService from '../../services/stockReceiptService';
import VariantTransferModal from './stockReceiptFormComponents/VariantTransferModal';
import './StockReceiptRating.css';

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

const ConfirmationModal = ({ isOpen, onConfirm, onCancel, itemsCount }) => {
  if (!isOpen) return null;

  return (
    <div className="modal-overlay-rating" onClick={onCancel}>
      <div className="confirmation-modal-rating" onClick={e => e.stopPropagation()}>
        <div className="modal-icon-rating warning">
          <AlertTriangle size={48} />
        </div>
        <h3 className="modal-title-rating">Confirmer et valider la réception</h3>
        <p className="modal-text-rating">
          Vous êtes sur le point d'enregistrer l'évaluation de <strong>{itemsCount} article{itemsCount > 1 ? 's' : ''}</strong> et de <strong>valider la réception</strong>.
        </p>
        <p className="modal-warning-text">
          ⚠️ Cette action est irréversible. Les batches seront créés automatiquement.
        </p>
        <div className="modal-actions-rating">
          <button className="btn btn-secondary" onClick={onCancel}>
            <X size={16} />
            Annuler
          </button>
          <button className="btn btn-primary" onClick={onConfirm}>
            <Check size={16} />
            Confirmer et valider
          </button>
        </div>
      </div>
    </div>
  );
};

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
  const [error, setError] = useState(null);
  const [ratingsData, setRatingsData] = useState({});
  const [touchedRatings, setTouchedRatings] = useState({});
  const [showConfirmModal, setShowConfirmModal] = useState(false);
  const [showSuccessToast, setShowSuccessToast] = useState(false);
  const [showTransferModal, setShowTransferModal] = useState(false);
  const [selectedItemForTransfer, setSelectedItemForTransfer] = useState(null);

  const STORAGE_KEY = `receipt_rating_progress_${id}`;

  useEffect(() => {
    if (receipt) {
      const progressData = {
        ratingsData,
        touchedRatings,
        timestamp: Date.now(),
        receiptId: id
      };
      localStorage.setItem(STORAGE_KEY, JSON.stringify(progressData));
    }
  }, [ratingsData, touchedRatings, receipt, STORAGE_KEY, id]);

  const loadSavedProgress = () => {
    try {
      const savedData = localStorage.getItem(STORAGE_KEY);
      if (savedData) {
        const { ratingsData: saved, touchedRatings: savedTouched, timestamp } = JSON.parse(savedData);
        const isRecent = Date.now() - timestamp < 24 * 60 * 60 * 1000;
        if (isRecent && saved && savedTouched) {
          return { ratingsData: saved, touchedRatings: savedTouched };
        }
      }
    } catch (err) {
      console.error('Error loading saved progress:', err);
    }
    return null;
  };

  const clearSavedProgress = () => {
    localStorage.removeItem(STORAGE_KEY);
  };

  useEffect(() => {
    fetchReceipt();
  }, [id]);

  useEffect(() => {
    const params = new URLSearchParams(window.location.search);
    if (params.get('openTransferModal') === 'true' && receipt?.items?.length > 0) {
      const itemToTransfer = receipt.items.find(item => item.quantity_received > 0);
      if (itemToTransfer) {
        setSelectedItemForTransfer(itemToTransfer);
        setShowTransferModal(true);
        const newUrl = window.location.pathname;
        window.history.replaceState({}, '', newUrl);
      }
    }
  }, [receipt]);

  const fetchReceipt = async () => {
    try {
      setLoading(true);
      const response = await stockReceiptService.getById(id);
      
      if (!response.data) {
        throw new Error('Réception non trouvée');
      }

      if (response.data.status !== 'arrived') {
        setError('Cette réception n\'est pas prête pour l\'évaluation. Elle doit être au statut "Arrivée".');
        setReceipt(null);
        return;
      }

      setReceipt(response.data);
      
      const savedProgress = loadSavedProgress();
      
      if (savedProgress) {
        // Reprendre automatiquement la progression sauvegardée
        setRatingsData(savedProgress.ratingsData);
        setTouchedRatings(savedProgress.touchedRatings);
        setError(null);
        return;
      }

      // Initialiser de nouvelles évaluations
      const initialRatings = {};
      const initialTouched = {};
      (response.data.items || []).forEach(item => {
        const attributes = item.variant?.attributes || [];
        initialRatings[item.id] = {
          quality_rating: 7,
          quality_notes: '',
          attribute_ratings: attributes.map(attr => ({
            attribute_type_id: attr.attribute_id,
            attribute_type: attr.type,
            attribute_value: attr.value,
            conformity_rating: 7,
            notes: ''
          }))
        };
        
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
    setRatingsData(prev => ({
      ...prev,
      [itemId]: {
        ...prev[itemId],
        quality_rating: value
      }
    }));
    
    setTouchedRatings(prev => ({
      ...prev,
      [itemId]: {
        ...prev[itemId],
        quality: true
      }
    }));
  };

  const handleAttributeRatingChange = (itemId, attrIndex, value) => {
    setRatingsData(prev => ({
      ...prev,
      [itemId]: {
        ...prev[itemId],
        attribute_ratings: prev[itemId].attribute_ratings.map((attr, idx) =>
          idx === attrIndex ? { ...attr, conformity_rating: value } : attr
        )
      }
    }));
    
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
    setRatingsData(prev => ({
      ...prev,
      [itemId]: {
        ...prev[itemId],
        quality_notes: value
      }
    }));
  };

  const handleOpenTransferModal = (item) => {
    setSelectedItemForTransfer(item);
    setShowTransferModal(true);
  };

  const handleTransferSuccess = async (transferData) => {
    try {
      await stockReceiptService.moveReceivedVariant(id, transferData);
      setShowTransferModal(false);
      setSelectedItemForTransfer(null);
      
      // Recharger les données
      await fetchReceipt();
      
    } catch (err) {
      console.error('Error transferring variant:', err);
      alert(err.response?.data?.message || 'Erreur lors du transfert');
    }
  };

  const getTotalRatingsStats = () => {
    let total = 0;
    let completed = 0;

    receipt?.items.forEach(item => {
      const itemTouched = touchedRatings[item.id];
      if (!itemTouched) return;

      // Qualité obligatoire pour tous les items
      total++;
      if (itemTouched.quality) completed++;

      // Conformité des attributs obligatoire SEULEMENT si quantity_ordered > 0
      if (item.quantity_ordered > 0) {
        const attrCount = item.variant?.attributes?.length || 0;
        total += attrCount;
        
        if (itemTouched.attributes && Array.isArray(itemTouched.attributes)) {
          completed += itemTouched.attributes.filter(t => t).length;
        }
      }
    });

    return { total, completed, isComplete: total > 0 && completed === total };
  };

  const handleSaveAndValidate = () => {
    const stats = getTotalRatingsStats();
    
    if (!stats.isComplete) {
      alert(`Veuillez compléter toutes les évaluations avant de valider. (${stats.completed}/${stats.total} complétées)`);
      return;
    }
    
    // Ouvrir directement le modal de confirmation
    setShowConfirmModal(true);
  };

  const confirmSaveAndValidate = async () => {
    setShowConfirmModal(false);
    
    try {
      setSaving(true);
      
      // Préparer les données pour tous les items
      const items = receipt.items.map(item => {
        const itemRating = ratingsData[item.id];
        
        // Pour les items non commandés (transférés), mettre conformity à 5/10 par défaut
        const isUnordered = item.quantity_ordered === 0;
        
        return {
          item_id: item.id,
          quality_rating: itemRating?.quality_rating || 7,
          quality_notes: itemRating?.quality_notes || null,
          attribute_ratings: (itemRating?.attribute_ratings || []).map(ar => ({
            attribute_type_id: ar.attribute_type_id,
            conformity_rating: isUnordered ? 5.0 : ar.conformity_rating,
            notes: isUnordered ? 'Variant transféré - conformité non évaluée' : (ar.notes || null)
          }))
        };
      });

      // UN SEUL APPEL API : évalue + valide + crée batches
      await stockReceiptService.rateReceipt(id, { items });
      
      // Supprimer la progression du localStorage après succès
      clearSavedProgress();
      
      setShowSuccessToast(true);
      
      // Redirection après succès
      setTimeout(() => {
        navigate(`/reapprovisionnements/${id}`, { 
          state: { message: 'Réception évaluée et validée avec succès' } 
        });
      }, 1500);
      
    } catch (err) {
      console.error('Error saving ratings:', err);
      alert(err.response?.data?.message || 'Erreur lors de la sauvegarde des évaluations');
    } finally {
      setSaving(false);
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
          <button 
            className="btn btn-primary"
            onClick={handleSaveAndValidate}
            disabled={saving || !ratingsStats.isComplete}
          >
            {saving ? (
              <>
                <Loader2 className="spinner" size={16} />
                Validation...
              </>
            ) : (
              <>
                <Save size={16} />
                Enregistrer et valider
              </>
            )}
          </button>
        </div>
      </header>

      <div className="progress-section">
        <ProgressIndicator 
          current={ratingsStats.completed} 
          total={ratingsStats.total} 
        />
        {!ratingsStats.isComplete && (
          <p className="progress-hint">
            <AlertCircle size={16} />
            Vous devez évaluer tous les critères avant de pouvoir valider
          </p>
        )}
      </div>

      <div className="rating-main-content">
        <div className="items-grid-rating">
          {receipt.items.map((item, itemIndex) => {
            const itemRating = ratingsData[item.id] || {};
            const itemTouched = touchedRatings[item.id] || { quality: false, attributes: [] };
            
            const itemComplete = itemTouched.quality && 
                 itemTouched.attributes && 
                 Array.isArray(itemTouched.attributes) &&
                 itemTouched.attributes.every(t => t);
            
            return (
              <div key={item.id} className={`item-rating-card ${itemComplete ? 'complete' : ''}`}>
                <div className="item-header-rating">
                  <div className="item-icon-rating">
                    <Box size={24} />
                  </div>
                  <div className="item-info-rating">
                    <div className="item-number-badge">Article {itemIndex + 1}/{receipt.items.length}</div>
                    <h3 className="item-name-rating">
                      {item.variant?.product?.name || 'Article sans nom'}
                      {item.quantity_ordered === 0 && (
                        <span className="item-unordered-badge">
                          <AlertCircle size={12} />
                          Transféré
                        </span>
                      )}
                    </h3>
                    <div className="item-meta-rating">
                      <span className="item-sku">{item.variant?.sku || 'N/A'}</span>
                      <span className="item-quantity">
                        {item.quantity_received || 0} / {item.quantity_ordered || 0} reçus
                      </span>
                    </div>
                  </div>
                  <div className="item-badges-rating">
                    {itemComplete && (
                      <span className="complete-badge">
                        <Check size={16} />
                        Complet
                      </span>
                    )}
                  </div>
                </div>

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

                {item.quantity_ordered === 0 && (
                  <div className="unordered-variant-notice">
                    <AlertCircle size={16} />
                    <div>
                      <strong>Variant transféré</strong>
                      <p>Cet article a été reçu suite à un transfert. Seule la qualité globale doit être évaluée. La conformité des attributs sera automatiquement notée 5/10.</p>
                    </div>
                  </div>
                )}

                {item.quantity_received > 0 && item.quantity_ordered > 0 && (
                  <div className="transfer-section-rating">
                    <button 
                      className="btn-transfer-rating"
                      onClick={() => handleOpenTransferModal(item)}
                    >
                      <ArrowRightLeft size={16} />
                      Transférer vers un autre variant
                    </button>
                    <p className="transfer-hint">
                      Si certaines unités ne correspondent pas au variant commandé, transférez-les vers le bon variant avant d'évaluer.
                    </p>
                  </div>
                )}

                <RatingSlider
                  label="Qualité du produit"
                  icon={Star}
                  value={itemRating.quality_rating || 7}
                  onChange={(val) => handleQualityRatingChange(item.id, val)}
                  description="Évaluez la qualité globale du produit reçu"
                  touched={itemTouched.quality}
                  required={true}
                />

                {itemRating.attribute_ratings && itemRating.attribute_ratings.length > 0 && item.quantity_ordered > 0 && (
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
                          touched={itemTouched.attributes[idx]}
                          required={true}
                        />
                      </div>
                    ))}
                  </div>
                )}

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
                  />
                </div>
              </div>
            );
          })}
        </div>

        <div className="action-buttons-rating">
          <button 
            className="btn btn-primary btn-large-rating"
            onClick={handleSaveAndValidate}
            disabled={saving || !ratingsStats.isComplete}
          >
            {saving ? (
              <>
                <Loader2 className="spinner" size={20} />
                Validation en cours...
              </>
            ) : (
              <>
                <Save size={20} />
                Enregistrer et valider ({ratingsStats.completed}/{ratingsStats.total})
              </>
            )}
          </button>
        </div>
      </div>

      <ConfirmationModal
        isOpen={showConfirmModal}
        onConfirm={confirmSaveAndValidate}
        onCancel={() => setShowConfirmModal(false)}
        itemsCount={receipt.items.length}
      />
      
      {showSuccessToast && (
        <SuccessToast
          message="Réception validée avec succès !"
          onClose={() => setShowSuccessToast(false)}
        />
      )}

      {showTransferModal && selectedItemForTransfer && (
        <VariantTransferModal
          isOpen={showTransferModal}
          onClose={() => {
            setShowTransferModal(false);
            setSelectedItemForTransfer(null);
          }}
          sourceItem={selectedItemForTransfer}
          receiptId={id}
          onTransferSuccess={handleTransferSuccess}
        />
      )}
    </div>
  );
};

export default StockReceiptRating;
import React, { useState, useEffect } from 'react';
import { Star, CheckSquare, XCircle, Loader2, BadgeCheck } from 'lucide-react';

const RatingModal = ({ isOpen, onClose, items, onSubmit, isLoading }) => {
  console.log('RatingModal - isOpen:', isOpen, 'items:', items?.length);
  
  const [ratingsData, setRatingsData] = useState([]);

  useEffect(() => {
    console.log('RatingModal - useEffect triggered');
    if (items && isOpen) {
      const initialData = items.map(item => ({
        item_id: item.id,
        quality_rating: 8,
        conformity_rating: 8,
        notes: ''
      }));
      console.log('RatingModal - Initial ratings data:', initialData);
      setRatingsData(initialData);
    }
  }, [items, isOpen]);

  const handleRatingChange = (index, field, value) => {
    const newData = [...ratingsData];
    if (field === 'notes') {
      newData[index][field] = value;
    } else {
      newData[index][field] = Math.max(1, Math.min(10, parseInt(value) || 1));
    }
    console.log('RatingModal - Rating changed for item', index, field, 'to', value);
    setRatingsData(newData);
  };

  const handleSubmit = (e) => {
    e.preventDefault();
    console.log('RatingModal - Submitting ratings');
    onSubmit(ratingsData);
  };

  if (!isOpen) return null;

  return (
    <div className="modal-overlay" onClick={onClose}>
      <div className="modal-content modal-large" onClick={e => e.stopPropagation()}>
        <div className="modal-header">
          <div className="modal-header-content">
            <Star size={24} className="modal-icon" />
            <div>
              <h3 className="modal-title">Évaluation de la qualité</h3>
              <p className="modal-subtitle">
                Évaluez la qualité et la conformité de chaque article reçu
              </p>
            </div>
          </div>
          <button className="modal-close" onClick={onClose} aria-label="Fermer">
            <XCircle size={24} />
          </button>
        </div>
        
        <form onSubmit={handleSubmit}>
          <div className="modal-body">
            <div className="rating-items-grid">
              {items.map((item, index) => (
                <div key={item.id} className="rating-item-card">
                  <div className="rating-item-header">
                    <div className="rating-item-info">
                      <span className="rating-item-name">
                        {item.variant?.product?.name || 'Article sans nom'}
                      </span>
                      <span className="rating-item-sku">
                        {item.variant?.sku || 'N/A'}
                      </span>
                    </div>
                    <div className="rating-item-stats">
                      <span className="rating-item-quantity">
                        {item.quantity_received || 0} / {item.quantity_ordered || 0} unités reçues
                      </span>
                    </div>
                  </div>
                  
                  <div className="rating-fields">
                    <div className="rating-field">
                      <label className="rating-label">
                        <Star size={16} />
                        Qualité du produit
                      </label>
                      <div className="rating-slider-container">
                        <input
                          type="range"
                          min="1"
                          max="10"
                          step="1"
                          value={ratingsData[index]?.quality_rating || 8}
                          onChange={(e) => handleRatingChange(index, 'quality_rating', e.target.value)}
                          className="rating-slider"
                        />
                        <div className="rating-values">
                          <span className="rating-min">1</span>
                          <span className="rating-current">
                            {ratingsData[index]?.quality_rating || 8}/10
                          </span>
                          <span className="rating-max">10</span>
                        </div>
                      </div>
                    </div>
                    
                    <div className="rating-field">
                      <label className="rating-label">
                        <CheckSquare size={16} />
                        Conformité aux spécifications
                      </label>
                      <div className="rating-slider-container">
                        <input
                          type="range"
                          min="1"
                          max="10"
                          step="1"
                          value={ratingsData[index]?.conformity_rating || 8}
                          onChange={(e) => handleRatingChange(index, 'conformity_rating', e.target.value)}
                          className="rating-slider"
                        />
                        <div className="rating-values">
                          <span className="rating-min">1</span>
                          <span className="rating-current">
                            {ratingsData[index]?.conformity_rating || 8}/10
                          </span>
                          <span className="rating-max">10</span>
                        </div>
                      </div>
                    </div>
                    
                    <div className="rating-notes">
                      <label className="form-label">Notes et commentaires</label>
                      <textarea
                        value={ratingsData[index]?.notes || ''}
                        onChange={(e) => handleRatingChange(index, 'notes', e.target.value)}
                        placeholder="Commentaires sur la qualité, défauts observés, remarques..."
                        rows={3}
                        className="form-textarea"
                      />
                    </div>
                  </div>
                </div>
              ))}
            </div>
          </div>
          
          <div className="modal-footer">
            <button 
              type="button" 
              className="btn btn-secondary" 
              onClick={onClose}
              disabled={isLoading}
            >
              Annuler
            </button>
            <button 
              type="submit" 
              className="btn btn-success" 
              disabled={isLoading}
            >
              {isLoading ? (
                <>
                  <Loader2 className="spinner" size={16} />
                  Traitement...
                </>
              ) : (
                <>
                  <BadgeCheck size={16} />
                  Valider l'évaluation
                </>
              )}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
};

export default RatingModal;
import React, { useState, useEffect } from 'react';
import { MapPin, XCircle, Loader2, Package } from 'lucide-react';
import './LocationModal.css';

const LocationModal = ({ isOpen, onClose, items, locations, onSubmit, isLoading }) => {
  const [globalLocationId, setGlobalLocationId] = useState(null);
  const [notes, setNotes] = useState('');

  useEffect(() => {
    if (isOpen && items && items.length > 0) {
      // Initialiser avec le location_id du premier item s'il existe
      const firstLocationId = items[0]?.location_id || null;
      setGlobalLocationId(firstLocationId);
    }
  }, [items, isOpen]);

  const handleLocationChange = (locationId) => {
    setGlobalLocationId(locationId ? Number(locationId) : null);
  };

  const validateForm = () => {
    return globalLocationId !== null && globalLocationId !== '';
  };

  const handleSubmit = (e) => {
    e.preventDefault();
    
    if (!validateForm()) {
      alert('Veuillez sélectionner un emplacement');
      return;
    }
    
    // Créer un item pour chaque article avec le même emplacement
    const validItems = items
      .filter(item => item.quantity_received > 0)
      .map(item => ({
        item_id: item.id,
        quantity_received: item.quantity_received,
        location_id: globalLocationId,
        notes: notes || ''
      }));
    
    if (validItems.length === 0) {
      alert('Aucun article valide à valider');
      return;
    }
    
    onSubmit({ items: validItems });
  };

  if (!isOpen) return null;

  const totalItems = items.length;
  const hasLocation = globalLocationId !== null && globalLocationId !== '';

  return (
    <div className="lm-overlay" onClick={onClose}>
      <div className="lm-container" onClick={e => e.stopPropagation()}>
        {/* Header */}
        <div className="lm-header">
          <div className="lm-header-content">
            <div className="lm-header-icon">
              <MapPin size={24} strokeWidth={2} />
            </div>
            <div className="lm-header-text">
              <h3 className="lm-title">Validation de réception</h3>
              <p className="lm-subtitle">
                Sélectionnez l'emplacement de stockage pour tous les articles
              </p>
            </div>
          </div>
          <button className="lm-close" onClick={onClose} aria-label="Fermer" type="button">
            <XCircle size={20} strokeWidth={2} />
          </button>
        </div>
        
        <form onSubmit={handleSubmit}>
          <div className="lm-body">
            {/* Summary */}
            <div className="lm-summary">
              <div className="lm-summary-card">
                <div className="lm-summary-icon">
                  <Package size={20} strokeWidth={2} />
                </div>
                <div className="lm-summary-content">
                  <span className="lm-summary-label">Articles à valider</span>
                  <span className="lm-summary-value">{totalItems}</span>
                </div>
              </div>

              <div className="lm-summary-card">
                <div className={`lm-summary-icon ${hasLocation ? 'success' : ''}`}>
                  <MapPin size={20} strokeWidth={2} />
                </div>
                <div className="lm-summary-content">
                  <span className="lm-summary-label">Emplacement</span>
                  <span className="lm-summary-value">
                    {hasLocation ? '✓' : '—'}
                  </span>
                </div>
              </div>
            </div>

            {/* Location Selector */}
            <div className="lm-location-section">
              <div className="lm-section-header">
                <MapPin size={18} strokeWidth={2} />
                <h4>Emplacement de stockage</h4>
              </div>
              
              <div className="lm-input-wrapper">
                <label className="lm-input-label">
                  Tous les articles seront stockés au même emplacement *
                </label>
                <select
                  value={globalLocationId || ''}
                  onChange={(e) => handleLocationChange(e.target.value)}
                  className="lm-select"
                  required
                >
                  <option value="">Sélectionnez un emplacement</option>
                  {locations.map(location => (
                    <option key={location.id} value={location.id}>
                      {location.name || `Emplacement #${location.id}`}
                    </option>
                  ))}
                </select>
              </div>

              {/* Notes */}
              <div className="lm-input-wrapper">
                <label className="lm-input-label">
                  Notes (optionnel)
                </label>
                <textarea
                  value={notes}
                  onChange={(e) => setNotes(e.target.value)}
                  className="lm-textarea"
                  placeholder="Notes supplémentaires pour cette réception..."
                  rows="3"
                  maxLength="500"
                />
                <div className="lm-char-count">
                  {notes.length}/500 caractères
                </div>
              </div>
            </div>

            {/* Items List */}
            <div className="lm-items-container">
              <div className="lm-items-header">
                <Package size={18} strokeWidth={2} />
                <h4>Articles à réceptionner ({totalItems})</h4>
              </div>

              <div className="lm-items-list">
                {items.map((item) => {
                  const received = item.quantity_received || 0;
                  
                  return (
                    <div key={item.id} className="lm-item">
                      <div className="lm-item-info">
                        <div className="lm-item-icon">
                          <Package size={16} strokeWidth={2} />
                        </div>
                        <div className="lm-item-details">
                          <span className="lm-item-name">
                            {item.variant?.product?.name || 'Article sans nom'}
                          </span>
                          {item.variant?.sku && (
                            <span className="lm-item-sku">
                              {item.variant.sku}
                            </span>
                          )}
                        </div>
                      </div>

                      <div className="lm-item-qty">
                        <span className="lm-qty-label">Reçu</span>
                        <span className="lm-qty-value">{received}</span>
                      </div>
                    </div>
                  );
                })}
              </div>
            </div>
          </div>

          {/* Footer */}
          <div className="lm-footer">
            <div className="lm-footer-content">
              <button 
                type="button" 
                className="lm-btn lm-btn-secondary" 
                onClick={onClose}
                disabled={isLoading}
              >
                Annuler
              </button>
              <button 
                type="submit" 
                className="lm-btn lm-btn-primary" 
                disabled={isLoading || !validateForm()}
              >
                {isLoading ? (
                  <>
                    <Loader2 className="lm-spinner" size={16} strokeWidth={2.5} />
                    Validation en cours...
                  </>
                ) : (
                  <>
                    <MapPin size={16} strokeWidth={2.5} />
                    Valider la réception
                  </>
                )}
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>
  );
};

export default LocationModal;
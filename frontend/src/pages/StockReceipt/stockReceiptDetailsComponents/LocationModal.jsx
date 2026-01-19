import React, { useState, useEffect } from 'react';
import { MapPin, XCircle, Loader2, Package, AlertCircle } from 'lucide-react';
import './LocationModal.css';

const LocationModal = ({ isOpen, onClose, items, locations, onSubmit, isLoading }) => {
  const [itemsData, setItemsData] = useState([]);
  const [expandedItems, setExpandedItems] = useState({});

  useEffect(() => {
    if (items && isOpen) {
      const initialData = items.map(item => ({
        item_id: item.id,
        quantity_received: item.quantity_received || 0,
        location_id: item.location_id || null,
        notes: item.notes || ''
      }));
      setItemsData(initialData);
    }
  }, [items, isOpen]);

  const handleLocationChange = (index, locationId) => {
    const newData = [...itemsData];
    // Convertir en nombre pour être cohérent
    newData[index].location_id = locationId ? Number(locationId) : null;
    setItemsData(newData);
  };

  const handleNotesChange = (index, value) => {
    const newData = [...itemsData];
    newData[index].notes = value;
    setItemsData(newData);
  };

  const toggleItemExpand = (index) => {
    setExpandedItems(prev => ({
      ...prev,
      [index]: !prev[index]
    }));
  };

  // Fonction pour formater l'affichage d'un emplacement
  const formatLocationDisplay = (location) => {
    if (!location) return '';
    
    const parts = [];
    
    // Nom principal
    parts.push(location.name);
    
    // Code entre parenthèses
    if (location.code) {
      parts.push(`(${location.code})`);
    }
    
    // Entrepôt
    if (location.warehouse) {
      parts.push(`- ${location.warehouse}`);
    }
    
    return parts.join(' ');
  };

  const getLocationDisplay = (locationId) => {
    if (!locationId && locationId !== 0) return 'Non spécifié';
    
    // Chercher l'emplacement (les IDs sont des nombres)
    const location = locations.find(loc => {
      // Comparer en convertissant les deux en nombres
      const locId = Number(loc.id);
      const searchId = Number(locationId);
      return locId === searchId;
    });
    
    if (!location) {
      console.warn('Emplacement non trouvé pour ID:', locationId);
      return 'Emplacement inconnu';
    }
    
    return formatLocationDisplay(location);
  };

  // Fonction pour obtenir les statistiques d'un emplacement
  const getLocationStats = (locationId) => {
    if (!locationId && locationId !== 0) return null;
    
    const location = locations.find(loc => {
      const locId = Number(loc.id);
      const searchId = Number(locationId);
      return locId === searchId;
    });
    
    return location?.statistics || null;
  };

  const validateForm = () => {
    const invalidItems = itemsData.filter(item => 
      !item.location_id && item.location_id !== 0
    );
    return invalidItems.length === 0;
  };

  const handleSubmit = (e) => {
    e.preventDefault();
    
    if (!validateForm()) {
      alert('Veuillez sélectionner un emplacement pour tous les articles');
      return;
    }
    
    const validItems = itemsData.filter(item => 
      item.item_id && 
      (item.location_id || item.location_id === 0) && 
      item.quantity_received > 0
    );
    
    if (validItems.length === 0) {
      alert('Aucun article valide à valider');
      return;
    }
    
    onSubmit({ items: validItems });
  };

  if (!isOpen) return null;

  // Calcul du nombre d'articles configurés
  const configuredCount = itemsData.filter(item => 
    item.location_id || item.location_id === 0
  ).length;

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
              <h3 className="lm-title">Validation finale</h3>
              <p className="lm-subtitle">
                Sélectionnez les emplacements de stockage pour chaque article
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
                  <span className="lm-summary-value">{items.length}</span>
                </div>
              </div>

              <div className="lm-summary-card">
                <div className="lm-summary-icon success">
                  <MapPin size={20} strokeWidth={2} />
                </div>
                <div className="lm-summary-content">
                  <span className="lm-summary-label">Emplacements</span>
                  <span className="lm-summary-value">{locations.length}</span>
                </div>
              </div>
            </div>

            {/* Items List */}
            <div className="lm-items-container">
              <div className="lm-items-header">
                <Package size={18} strokeWidth={2} />
                <h4>Configuration des emplacements ({configuredCount}/{items.length})</h4>
              </div>

              <div className="lm-items-list">
                {items.map((item, index) => {
                  const itemData = itemsData[index] || {};
                  const received = itemData.quantity_received || 0;
                  const hasLocation = itemData.location_id || itemData.location_id === 0;
                  const isExpanded = expandedItems[index];
                  const locationStats = getLocationStats(itemData.location_id);
                  
                  return (
                    <div key={item.id} className={`lm-item ${isExpanded ? 'expanded' : ''}`}>
                      {/* Item Header */}
                      <div className="lm-item-header" onClick={() => toggleItemExpand(index)}>
                        <div className="lm-item-info">
                          <div className="lm-item-icon">
                            <Package size={16} strokeWidth={2} />
                          </div>
                          <div className="lm-item-details">
                            <span className="lm-item-name">
                              {item.variant?.product?.name || 'Article sans nom'}
                            </span>
                            <span className="lm-item-sku">
                              {item.variant?.sku || 'N/A'}
                            </span>
                          </div>
                        </div>

                        <div className="lm-item-status">
                          <div className="lm-item-qty">
                            <span className="lm-qty-label">Reçu</span>
                            <span className="lm-qty-value">{received}</span>
                          </div>
                          <div className={`lm-location-status ${hasLocation ? 'success' : 'warning'}`}>
                            <MapPin size={14} strokeWidth={2} />
                            <span>
                              {hasLocation 
                                ? getLocationDisplay(itemData.location_id)
                                : 'À configurer'
                              }
                            </span>
                          </div>
                          <button 
                            className="lm-item-expand"
                            onClick={(e) => { 
                              e.stopPropagation(); 
                              toggleItemExpand(index); 
                            }}
                            type="button"
                          >
                            {isExpanded ? '−' : '+'}
                          </button>
                        </div>
                      </div>

                      {/* Expanded Content */}
                      {isExpanded && (
                        <div className="lm-item-expanded">
                          {/* Location Selector */}
                          <div className="lm-input-section">
                            <div className="lm-input-wrapper">
                              <label className="lm-input-label">
                                <MapPin size={14} strokeWidth={2} />
                                Emplacement de stockage *
                              </label>
                              <select
                                value={itemData.location_id || ''}
                                onChange={(e) => handleLocationChange(index, e.target.value)}
                                className="lm-select"
                                required
                              >
                                <option value="">Sélectionnez un emplacement</option>
                                {locations.map(location => (
                                  <option key={location.id} value={location.id}>
                                    {formatLocationDisplay(location)}
                                    {location.statistics && (
                                      ` - ${location.statistics.occupancy_percentage}% occupé`
                                    )}
                                  </option>
                                ))}
                              </select>
                              
                              {/* Aide au choix */}
                              {hasLocation && locationStats && (
                                <div className="lm-location-hint">
                                  <small>
                                    📦 {locationStats.available_quantity || 0} unités disponibles • 
                                    📊 {locationStats.occupancy_percentage || 0}% rempli • 
                                    🚛 {locationStats.movement_count || 0} mouvements
                                  </small>
                                </div>
                              )}
                            </div>

                            {/* Notes */}
                            <div className="lm-input-wrapper">
                              <label className="lm-input-label">
                                Notes (optionnel)
                              </label>
                              <textarea
                                value={itemData.notes || ''}
                                onChange={(e) => handleNotesChange(index, e.target.value)}
                                className="lm-textarea"
                                placeholder="Notes supplémentaires..."
                                rows="2"
                                maxLength="500"
                              />
                              <div className="lm-char-count">
                                {itemData.notes?.length || 0}/500 caractères
                              </div>
                            </div>
                          </div>
                        </div>
                      )}
                    </div>
                  );
                })}
              </div>
            </div>
          </div>

          {/* Footer - FIXED POSITION */}
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
                    Valider la réception ({configuredCount}/{items.length})
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
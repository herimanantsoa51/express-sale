import React, { useState, useEffect } from 'react';
import { PackageCheck, XCircle, Loader2, MapPin, Package } from 'lucide-react';

const ArrivalModal = ({ isOpen, onClose, items, locations, onSubmit, isLoading }) => {
  const [itemsData, setItemsData] = useState([]);
  const [globalLocation, setGlobalLocation] = useState('');

  useEffect(() => {
    if (items && isOpen) {
      const initialData = items.map(item => ({
        item_id: item.id,
        quantity_received: item.quantity_ordered || 0,
        location_id: '',
        notes: ''
      }));
      setItemsData(initialData);
      setGlobalLocation('');
    }
  }, [items, isOpen]);

  const handleQuantityChange = (index, value) => {
    const newData = [...itemsData];
    const item = items[index];
    const quantity = Math.max(0, Math.min(parseInt(value) || 0, item.quantity_ordered || 0));
    newData[index].quantity_received = quantity;
    setItemsData(newData);
  };

  const handleLocationChange = (index, value) => {
    const newData = [...itemsData];
    newData[index].location_id = value;
    setItemsData(newData);
  };

  const handleGlobalLocationChange = (value) => {
    setGlobalLocation(value);
    if (value) {
      const newData = itemsData.map(item => ({
        ...item,
        location_id: value
      }));
      setItemsData(newData);
    }
  };

  const handleSubmit = (e) => {
    e.preventDefault();
    
    const validItems = itemsData.filter(item => item.quantity_received > 0 && item.location_id);
    
    if (validItems.length === 0) {
      alert('Veuillez sélectionner au moins un emplacement pour les produits reçus');
      return;
    }
    
    onSubmit({ items: itemsData });
  };

  if (!isOpen) return null;

  return (
    <div className="modal-overlay" onClick={onClose}>
      <div className="modal-content modal-large modal-arrival" onClick={e => e.stopPropagation()}>
        <div className="modal-header">
          <div className="modal-header-content">
            <PackageCheck size={24} className="modal-icon" strokeWidth={2} />
            <div>
              <h3 className="modal-title">Réception des articles</h3>
              <p className="modal-subtitle">
                Vérifiez les quantités et assignez les emplacements de stockage
              </p>
            </div>
          </div>
          <button className="modal-close" onClick={onClose} aria-label="Fermer" type="button">
            <XCircle size={20} strokeWidth={2} />
          </button>
        </div>
        
        <form onSubmit={handleSubmit}>
          <div className="modal-body">
            {/* Section d'emplacement global */}
            <div className="global-location-section">
              <div className="global-location-header">
                <MapPin size={18} strokeWidth={2} />
                <h4>Emplacement pour tous les articles</h4>
              </div>
              <p className="global-location-description">
                Sélectionnez un emplacement unique pour tous les articles, ou choisissez individuellement ci-dessous
              </p>
              <select
                value={globalLocation}
                onChange={(e) => handleGlobalLocationChange(e.target.value)}
                className="form-select"
              >
                <option value="">Choisir des emplacements individuels...</option>
                {locations.map(loc => (
                  <option key={loc.id} value={loc.id}>
                    {loc.name} ({loc.code}) - {loc.warehouse?.name || 'N/A'}
                  </option>
                ))}
              </select>
            </div>

            {/* Divider */}
            <div className="modal-divider">
              <span>Articles à réceptionner</span>
            </div>

            {/* Liste des articles */}
            <div className="arrival-items-grid">
              {items.map((item, index) => (
                <div key={item.id} className="arrival-item-card">
                  <div className="arrival-item-header">
                    <div className="arrival-item-icon">
                      <Package size={16} strokeWidth={2} />
                    </div>
                    <div className="arrival-item-info">
                      <span className="arrival-item-name">
                        {item.variant?.product?.name || 'Article sans nom'}
                      </span>
                      <span className="arrival-item-sku">
                        {item.variant?.sku || 'N/A'}
                      </span>
                    </div>
                    <div className="arrival-item-stats">
                      <span className="arrival-item-ordered">
                        {item.quantity_ordered || 0} unités
                      </span>
                    </div>
                  </div>
                  
                  <div className="arrival-item-fields">
                    <div className="form-field">
                      <label className="form-label">
                        Quantité reçue
                        <span className="form-hint">Max: {item.quantity_ordered || 0}</span>
                      </label>
                      <input
                        type="number"
                        min="0"
                        max={item.quantity_ordered || 0}
                        value={itemsData[index]?.quantity_received || 0}
                        onChange={(e) => handleQuantityChange(index, e.target.value)}
                        className="form-input"
                        placeholder="0"
                      />
                    </div>
                    
                    <div className="form-field">
                      <label className="form-label">
                        Emplacement
                        {globalLocation && <span className="form-hint">Emplacement global appliqué</span>}
                      </label>
                      <select
                        value={itemsData[index]?.location_id || ''}
                        onChange={(e) => handleLocationChange(index, e.target.value)}
                        className="form-select"
                        required={itemsData[index]?.quantity_received > 0}
                      >
                        <option value="">Sélectionner...</option>
                        {locations.map(loc => (
                          <option key={loc.id} value={loc.id}>
                            {loc.name} ({loc.code}) - {loc.warehouse?.name || 'N/A'}
                          </option>
                        ))}
                      </select>
                    </div>
                  </div>
                </div>
              ))}
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
              className="btn btn-primary" 
              disabled={isLoading}
            >
              {isLoading ? (
                <>
                  <Loader2 className="spinner" size={16} strokeWidth={2.5} />
                  Traitement...
                </>
              ) : (
                <>
                  <PackageCheck size={16} strokeWidth={2.5} />
                  Confirmer la réception
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

export default ArrivalModal;
import React, { useState, useEffect } from 'react';
import { PackageCheck, XCircle, Loader2, Package, TrendingUp, AlertCircle } from 'lucide-react';
import './ArrivalModal.css';

const ArrivalModal = ({ isOpen, onClose, items, onSubmit, isLoading }) => {
  const [itemsData, setItemsData] = useState([]);

  useEffect(() => {
    if (items && isOpen) {
      const initialData = items.map(item => ({
        item_id: item.id,
        quantity_received: item.quantity_ordered || 0
      }));
      setItemsData(initialData);
    }
  }, [items, isOpen]);

  const handleQuantityChange = (index, value) => {
    const newData = [...itemsData];
    const item = items[index];
    const quantity = Math.max(0, parseInt(value) || 0);
    newData[index].quantity_received = quantity;
    setItemsData(newData);
  };

  const getTotalOrdered = () => {
    return items.reduce((sum, item) => sum + (item.quantity_ordered || 0), 0);
  };

  const getTotalReceived = () => {
    return itemsData.reduce((sum, item) => sum + (item.quantity_received || 0), 0);
  };

  const getFulfillmentRate = () => {
    const ordered = getTotalOrdered();
    const received = getTotalReceived();
    return ordered > 0 ? Math.round((received / ordered) * 100) : 0;
  };

  const handleSubmit = (e) => {
    e.preventDefault();
    
    const totalReceived = getTotalReceived();
    if (totalReceived === 0) {
      alert('Veuillez saisir au moins une quantité reçue');
      return;
    }
    
    onSubmit({ items: itemsData });
  };

  if (!isOpen) return null;

  const totalOrdered = getTotalOrdered();
  const totalReceived = getTotalReceived();
  const fulfillmentRate = getFulfillmentRate();

  return (
    <div className="am-overlay" onClick={onClose}>
      <div className="am-container" onClick={e => e.stopPropagation()}>
        {/* Header */}
        <div className="am-header">
          <div className="am-header-content">
            <div className="am-header-icon">
              <PackageCheck size={24} strokeWidth={2} />
            </div>
            <div className="am-header-text">
              <h3 className="am-title">Réception des articles</h3>
              <p className="am-subtitle">
                Saisissez les quantités réellement reçues pour chaque article
              </p>
            </div>
          </div>
          <button className="am-close" onClick={onClose} aria-label="Fermer" type="button">
            <XCircle size={20} strokeWidth={2} />
          </button>
        </div>
        
        <form onSubmit={handleSubmit}>
          <div className="am-body">
            {/* Summary Stats */}
            <div className="am-summary">
              <div className="am-summary-card">
                <div className="am-summary-icon ordered">
                  <Package size={20} strokeWidth={2} />
                </div>
                <div className="am-summary-content">
                  <span className="am-summary-label">Total commandé</span>
                  <span className="am-summary-value">{totalOrdered}</span>
                </div>
              </div>

              <div className="am-summary-card">
                <div className="am-summary-icon received">
                  <PackageCheck size={20} strokeWidth={2} />
                </div>
                <div className="am-summary-content">
                  <span className="am-summary-label">Total reçu</span>
                  <span className="am-summary-value">{totalReceived}</span>
                </div>
              </div>

              <div className="am-summary-card">
                <div className={`am-summary-icon rate ${fulfillmentRate === 100 ? 'success' : fulfillmentRate >= 50 ? 'warning' : 'danger'}`}>
                  <TrendingUp size={20} strokeWidth={2} />
                </div>
                <div className="am-summary-content">
                  <span className="am-summary-label">Taux de réception</span>
                  <span className="am-summary-value">{fulfillmentRate}%</span>
                </div>
              </div>
            </div>

            {/* Items List */}
            <div className="am-items-container">
              <div className="am-items-header">
                <Package size={18} strokeWidth={2} />
                <h4>Articles à réceptionner ({items.length})</h4>
              </div>

              <div className="am-items-list">
                {items.map((item, index) => {
                  const received = itemsData[index]?.quantity_received || 0;
                  const ordered = item.quantity_ordered || 0;
                  const variance = received - ordered;
                  const hasVariance = variance !== 0;

                  return (
                    <div key={item.id} className="am-item">
                      <div className="am-item-header">
                        <div className="am-item-info">
                          <div className="am-item-icon">
                            <Package size={16} strokeWidth={2} />
                          </div>
                          <div className="am-item-details">
                            <span className="am-item-name">
                              {item.variant?.product?.name || 'Article sans nom'}
                            </span>
                            <span className="am-item-sku">
                              {item.variant?.sku || 'N/A'}
                            </span>
                            {item.variant?.attributes && item.variant.attributes.length > 0 && (
                              <div className="am-item-attrs">
                                {item.variant.attributes.map((attr, idx) => (
                                  <span key={idx} className="am-attr-tag">
                                    {attr.type}: {attr.value}
                                  </span>
                                ))}
                              </div>
                            )}
                          </div>
                        </div>

                        <div className="am-item-ordered">
                          <span className="am-ordered-label">Commandé</span>
                          <span className="am-ordered-value">{ordered}</span>
                        </div>
                      </div>

                      <div className="am-item-input-section">
                        <div className="am-input-wrapper">
                          <label className="am-input-label">
                            Quantité reçue
                          </label>
                          <input
                            type="number"
                            min="0"
                            value={received}
                            onChange={(e) => handleQuantityChange(index, e.target.value)}
                            className="am-input"
                            placeholder="0"
                          />
                        </div>

                        {hasVariance && (
                          <div className={`am-variance ${variance > 0 ? 'positive' : 'negative'}`}>
                            <AlertCircle size={14} strokeWidth={2} />
                            <span>
                              {variance > 0 ? '+' : ''}{variance} {Math.abs(variance) === 1 ? 'unité' : 'unités'}
                            </span>
                          </div>
                        )}
                      </div>
                    </div>
                  );
                })}
              </div>
            </div>
          </div>

          {/* Footer */}
          <div className="am-footer">
            <button 
              type="button" 
              className="am-btn am-btn-secondary" 
              onClick={onClose}
              disabled={isLoading}
            >
              Annuler
            </button>
            <button 
              type="submit" 
              className="am-btn am-btn-primary" 
              disabled={isLoading || totalReceived === 0}
            >
              {isLoading ? (
                <>
                  <Loader2 className="am-spinner" size={16} strokeWidth={2.5} />
                  Traitement en cours...
                </>
              ) : (
                <>
                  <PackageCheck size={16} strokeWidth={2.5} />
                  Confirmer la réception ({totalReceived} {totalReceived === 1 ? 'unité' : 'unités'})
                </>
              )}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
};

export default ArrivalModal;
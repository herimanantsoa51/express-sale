import React from 'react';
import { useNavigate } from 'react-router-dom';
import { Check, Plus, X, Package, Image as ImageIcon } from 'lucide-react';
import './VariantSelector.css';

const VariantSelector = ({ product, variants, selectedVariantIds, onVariantSelect, onClose }) => {
  const navigate = useNavigate();

  if (!product || variants.length === 0) {
    return null;
  }

  const getVariantImage = (variant) => {
    return variant.image_path || product.image_url || null;
  };

  return (
    <div className="variant-selector">
      <div className="variant-selector-header">
        <div className="variant-selector-title">
          <Package size={20} />
          <div>
            <h3>Sélectionner les variantes</h3>
            <p>{product.name}</p>
          </div>
        </div>
        <button 
          type="button" 
          className="variant-selector-close"
          onClick={onClose}
        >
          <X size={20} />
        </button>
      </div>

      <div className="variant-selector-content">
        <p className="variant-selector-hint">
          Cliquez sur une variante pour l'ajouter à votre commande. Les variantes déjà sélectionnées sont marquées d'une coche.
        </p>

        <div className="variants-grid">
          {variants.map(variant => {
            const isSelected = selectedVariantIds.includes(variant.id);
            const image = getVariantImage(variant);
            
            return (
              <div
                key={variant.id}
                className={`variant-card ${isSelected ? 'selected' : ''}`}
                onClick={() => onVariantSelect(variant)}
              >
                <div className="variant-card-image">
                  {image ? (
                    <img src={image} alt={variant.sku} />
                  ) : (
                    <div className="variant-card-no-image">
                      <ImageIcon size={24} />
                    </div>
                  )}
                  {isSelected && (
                    <div className="variant-card-selected-badge">
                      <Check size={16} />
                    </div>
                  )}
                </div>

                <div className="variant-card-content">
                  <div className="variant-header">
                    <span className="variant-sku">{variant.sku}</span>
                  </div>
                  
                  <div className="variant-attributes">
                    {variant.attribute_values?.map((attr, idx) => (
                      <span key={idx} className="attribute-badge">
                        {attr.attribute_type?.display_name || attr.attribute_type?.name}: {attr.value}
                      </span>
                    ))}
                  </div>
                  
                  <div className="variant-footer">
                    <span className="variant-stock">
                      Stock: {variant.stock_quantity || 0}
                    </span>
                  </div>
                </div>
              </div>
            );
          })}

          <button
            type="button"
            className="add-variant-btn"
            onClick={() => navigate(`/produits/${product.id}/variante/nouvelle`)}
          >
            <Plus size={20} />
            <span>Nouvelle variante</span>
          </button>
        </div>
      </div>

      <div className="variant-selector-footer">
        <span className="selected-count">
          {selectedVariantIds.length} variante{selectedVariantIds.length > 1 ? 's' : ''} sélectionnée{selectedVariantIds.length > 1 ? 's' : ''}
        </span>
        <button 
          type="button" 
          className="btn-done"
          onClick={onClose}
        >
          Terminé
        </button>
      </div>
    </div>
  );
};

export default VariantSelector;
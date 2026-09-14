// ============================================
// src/components/Credits/CreditProductCard.jsx
// Carte produit pour les crédits (style PDP)
// ============================================

import React from 'react';
import { useNavigate } from 'react-router-dom';
import { Package, Tag } from 'lucide-react';
import '../../styles/components/CreditProductCard.css';

const CreditProductCard = ({ item }) => {
  const navigate = useNavigate();

  const formatAmount = (amount) => {
    return new Intl.NumberFormat('fr-FR', {
      minimumFractionDigits: 0,
      maximumFractionDigits: 0
    }).format(amount);
  };

  const handleProductClick = (e) => {
    e.stopPropagation();
    navigate(`/produits/${item.product.id}`);
  };

  // Déterminer l'image à afficher
  const imageUrl = item.image_url || item.variant?.variant_image_url || item.product?.product_image_url;

  return (
    <div className="credit-product-card">
      {/* Image du produit */}
      <div className="credit-product-image">
        {imageUrl ? (
          <img src={imageUrl} alt={item.product?.name || 'Produit'} />
        ) : (
          <Package className="credit-product-placeholder" size={32} />
        )}
      </div>

      {/* Contenu */}
      <div className="credit-product-content">
        <div className="credit-product-header">
          <h4 
            className="credit-product-name"
            onClick={handleProductClick}
            title={item.product?.name}
          >
            {item.product?.name || 'Produit'}
          </h4>
        </div>

        {/* Variantes / Attributs */}
        {item.variant?.attributes && item.variant.attributes.length > 0 && (
          <div className="credit-product-attributes">
            {item.variant.attributes.map((attr, index) => (
              <div key={index} className="credit-product-attribute">
                <span className="attr-type">{attr.attribute_type}:</span>
                <span className="attr-value">{attr.attribute_value}</span>
              </div>
            ))}
          </div>
        )}

        {/* SKU */}
        {item.variant?.sku && (
          <div className="credit-product-sku">
            SKU: {item.variant.sku}
          </div>
        )}

        {/* Détails prix */}
        <div className="credit-product-details">
          <div className="credit-product-row">
            <span className="detail-label">Prix unitaire</span>
            <span className="detail-value">{formatAmount(item.unit_price)} Ar</span>
          </div>

          <div className="credit-product-row">
            <span className="detail-label">Quantité</span>
            <span className="detail-value quantity">× {item.quantity}</span>
          </div>

          <div className="credit-product-row total-row">
            <span className="detail-label">
              <Tag size={12} />
              Total
            </span>
            <span className="detail-value total-value">{formatAmount(item.line_total)} Ar</span>
          </div>
        </div>
      </div>
    </div>
  );
};

export default CreditProductCard;

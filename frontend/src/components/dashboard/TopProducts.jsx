// ============================================
// src/components/dashboard/TopProducts.jsx
// ============================================
import React from 'react';
import { Package } from 'lucide-react';
import { formatCurrency } from '../../utils/formatters';
import '../../styles/TopProducts.css';

const TopProducts = ({ products }) => {
  return (
    <div className="products">
      <h3 className="products__title">Top Produits</h3>
      <div className="products__list">
        {products.map((product) => (
          <div key={product.variant_id} className="products__item">
            <div className="products__item-image-wrapper">
              {product.image_url ? (
                <img 
                  src={product.image_url} 
                  alt={product.product_name}
                  className="products__item-image"
                />
              ) : (
                <Package size={24} className="products__item-placeholder" />
              )}
            </div>
            <div className="products__item-details">
              <div className="products__item-name">{product.product_name}</div>
              <div className="products__item-quantity">{product.quantity_sold} unités vendues</div>
            </div>
            <div className="products__item-value">{formatCurrency(product.total_value)}</div>
          </div>
        ))}
      </div>
    </div>
  );
};

export default TopProducts;

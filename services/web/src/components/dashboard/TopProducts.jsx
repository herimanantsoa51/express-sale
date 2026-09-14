// ============================================
// src/components/dashboard/TopProducts.jsx
// ============================================
import React, { useState } from 'react';
import { Package, TrendingUp, DollarSign, ShoppingCart } from 'lucide-react';
import { formatCurrency } from '../../utils/formatters';
import '../../styles/TopProducts.css';

const TopProducts = ({ products, sortBy, onSortChange }) => {
  const sortOptions = [
    { value: 'revenue', label: 'CA', icon: DollarSign },
    { value: 'profit', label: 'Bénéfice', icon: TrendingUp },
    { value: 'quantity', label: 'Quantité', icon: ShoppingCart }
  ];

  if (!products || products.length === 0) {
    return (
      <div className="products">
        <div className="products__header">
          <h3 className="products__title">Top 10 Produits</h3>
        </div>
        <div className="products__empty">Aucun produit vendu aujourd'hui</div>
      </div>
    );
  }

  return (
    <div className="products">
      <div className="products__header">
        <h3 className="products__title">Top 10 Produits</h3>
        <div className="products__sort">
          {sortOptions.map(option => {
            const Icon = option.icon;
            return (
              <button
                key={option.value}
                className={`products__sort-btn ${sortBy === option.value ? 'products__sort-btn--active' : ''}`}
                onClick={() => onSortChange?.(option.value)}
              >
                <Icon size={16} />
                {option.label}
              </button>
            );
          })}
        </div>
      </div>

      <div className="products__list">
        {products.map((product, index) => (
          <div key={product.product_id} className="products__item">
            <div className="products__item-rank">{index + 1}</div>
            
            <div className="products__item-image-wrapper">
              {product.image_url ? (
                <img 
                  src={product.image_url} 
                  alt={product.product_name}
                  className="products__item-image"
                  onError={(e) => {
                    e.target.style.display = 'none';
                    e.target.nextSibling.style.display = 'flex';
                  }}
                />
              ) : null}
              <Package size={24} className="products__item-placeholder" style={{ display: product.image_url ? 'none' : 'flex' }} />
            </div>

            <div className="products__item-details">
              <div className="products__item-name">{product.product_name}</div>
              <div className="products__item-stats">
                <span className="products__item-quantity">{product.quantity_sold} vendus</span>
                <span className="products__item-margin">Marge: {product.profit_margin}%</span>
              </div>
            </div>

            <div className="products__item-values">
              <div className="products__item-value products__item-value--primary">
                {sortBy === 'quantity' ? `${product.quantity_sold} unités` : formatCurrency(
                  sortBy === 'profit' ? product.total_profit : product.total_revenue
                )}
              </div>
              {sortBy !== 'quantity' && (
                <div className="products__item-value products__item-value--secondary">
                  {formatCurrency(sortBy === 'profit' ? product.total_revenue : product.total_profit)}
                </div>
              )}
            </div>
          </div>
        ))}
      </div>
    </div>
  );
};

export default TopProducts;
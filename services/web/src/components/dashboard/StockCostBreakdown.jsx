// ============================================
// src/components/dashboard/StockCostBreakdown.jsx
// ============================================
import React from 'react';
import { Package, Hash, DollarSign, Layers } from 'lucide-react';
import { formatCurrency } from '../../utils/formatters';
import '../../styles/StockCostBreakdown.css';

const StockCostBreakdown = ({ breakdown, totalValue }) => {
  if (!breakdown || breakdown.length === 0) {
    return (
      <div className="stock-cost">
        <div className="stock-cost__header">
          <h3 className="stock-cost__title">Détail Stock par Coût</h3>
          <div className="stock-cost__total">
            <DollarSign size={16} />
            <span>{formatCurrency(totalValue || 0)}</span>
          </div>
        </div>
        <div className="stock-cost__empty">Aucun stock disponible</div>
      </div>
    );
  }

  // Calculer le pourcentage de chaque produit
  const breakdownWithPercentages = breakdown.map(item => ({
    ...item,
    percentage: totalValue > 0 ? (item.total_cost / totalValue * 100) : 0
  }));

  return (
    <div className="stock-cost">
      <div className="stock-cost__header">
        <h3 className="stock-cost__title">Détail Stock par Coût</h3>
        <div className="stock-cost__total">
          <DollarSign size={16} />
          <span>{formatCurrency(totalValue || 0)}</span>
        </div>
      </div>

      <div className="stock-cost__list">
        {breakdownWithPercentages.map((item, index) => (
          <div key={`${item.product_id}-${index}`} className="stock-cost__item">
            <div className="stock-cost__item-main">
              <div className="stock-cost__item-header">
                <div className="stock-cost__item-rank">
                  <span>{index + 1}</span>
                </div>
                
                <div className="stock-cost__item-image-wrapper">
                  {item.image_url ? (
                    <img 
                      src={item.image_url} 
                      alt={item.product_name}
                      className="stock-cost__item-image"
                      onError={(e) => {
                        e.target.style.display = 'none';
                        e.target.nextSibling.style.display = 'flex';
                      }}
                    />
                  ) : null}
                  <Package size={20} className="stock-cost__item-placeholder" 
                    style={{ display: item.image_url ? 'none' : 'flex' }} 
                  />
                </div>

                <div className="stock-cost__item-info">
                  <div className="stock-cost__item-name">{item.product_name}</div>
                  <div className="stock-cost__item-sku">
                    <Hash size={12} />
                    <span>{item.main_sku}</span>
                    {item.variants_count > 1 && (
                      <span className="stock-cost__variants-count">
                        <Layers size={12} />
                        {item.variants_count} variants
                      </span>
                    )}
                  </div>
                </div>

                <div className="stock-cost__item-cost">
                  {formatCurrency(item.total_cost)}
                </div>
              </div>

              <div className="stock-cost__item-details">
                <div className="stock-cost__item-stats">
                  <div className="stock-cost__stat">
                    <Package size={14} />
                    <span>{item.total_quantity} unités total</span>
                  </div>
                  <div className="stock-cost__stat">
                    <DollarSign size={14} />
                    <span>Coût moyen: {formatCurrency(item.avg_unit_cost)}</span>
                  </div>
                </div>
                
                <div className="stock-cost__percentage-bar">
                  <div 
                    className="stock-cost__percentage-fill"
                    style={{ width: `${Math.min(item.percentage, 100)}%` }}
                  />
                  <span className="stock-cost__percentage-text">
                    {item.percentage.toFixed(1)}% du stock total
                  </span>
                </div>
              </div>
            </div>
          </div>
        ))}
      </div>
    </div>
  );
};

export default StockCostBreakdown;
// ============================================
// src/components/dashboard/StatCard.jsx
// ============================================

import React from 'react';
import { TrendingUp, TrendingDown } from 'lucide-react';
import { formatCurrency, parsePercentage } from '../../utils/formatters';
import '../../styles/StatCard.css';

const StatCard = ({ 
  title, 
  value, 
  count, 
  variation, 
  countVariation,
  formatValue = formatCurrency, 
  delay = 0 
}) => {
  const percentage = parsePercentage(variation);
  const isPositive = percentage > 0;
  const isNeutral = percentage === 0;

  const countPercentage = countVariation ? parsePercentage(countVariation) : null;
  const isCountPositive = countPercentage ? countPercentage > 0 : false;
  const isCountNeutral = countPercentage === 0;

  return (
    <div className="stat-card" style={{ animationDelay: `${delay}s` }}>
      <div className="stat-card__title">{title}</div>
      
      {/* Valeur principale */}
      <div className="stat-card__value">{formatValue(value)}</div>

      {/* Container pour count et variations */}
      <div className="stat-card__bottom">
        {/* Count avec variation si disponible */}
        {typeof count !== 'undefined' && (
          <div className="stat-card__count">
            <span className="stat-card__count-value">{count} {count > 1 ? 'transactions' : 'transaction'}</span>
            {countVariation && !isCountNeutral && (
              <span className={`stat-card__count-variation ${isCountPositive ? 'stat-card__count-variation--positive' : 'stat-card__count-variation--negative'}`}>
                {countVariation}
              </span>
            )}
          </div>
        )}

        {/* Variation de la valeur */}
        {!isNeutral && (
          <div className={`stat-card__variation ${isPositive ? 'stat-card__variation--positive' : 'stat-card__variation--negative'}`}>
            {isPositive ? <TrendingUp size={14} /> : <TrendingDown size={14} />}
            <span>{variation}</span>
          </div>
        )}
      </div>
    </div>
  );
};

export default StatCard;
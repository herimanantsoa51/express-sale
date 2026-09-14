// src/components/statistics/SummaryCard.jsx
import React from 'react';

const SummaryCard = ({ value, label, variant = 'default' }) => {
  return (
    <div className={`stats-summary-card ${variant !== 'default' ? `stats-summary-card-${variant}` : ''}`}>
      <div className="stats-summary-value">{value}</div>
      <div className="stats-summary-label">{label}</div>
    </div>
  );
};

export default SummaryCard;
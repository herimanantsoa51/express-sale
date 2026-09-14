// src/components/statistics/DiscountCard.jsx
import React from 'react';

const DiscountCard = ({ label, value, meta }) => {
  return (
    <div className="stats-discount-card">
      <div className="stats-discount-label">{label}</div>
      <div className="stats-discount-value">{value}</div>
      <div className="stats-discount-meta">{meta}</div>
    </div>
  );
};

export default DiscountCard;

// src/components/statistics/KPICard.jsx
import React from 'react';

const KPICard = ({ icon, label, value, meta, progress, color = 'default' }) => {
  return (
    <div className={`stats-card stats-card-${color}`}>
      <div className="stats-card-header">
        <div className="stats-card-icon">
          {icon}
        </div>
        <span className="stats-card-label">{label}</span>
      </div>
      <div className="stats-card-value">{value}</div>
      <div className="stats-card-footer">
        {progress && (
          <div className="stats-progress-bar">
            <div 
              className="stats-progress-fill" 
              style={{ width: `${progress}%` }}
            />
          </div>
        )}
        <span className="stats-card-meta">{meta}</span>
      </div>
    </div>
  );
};

export default KPICard;
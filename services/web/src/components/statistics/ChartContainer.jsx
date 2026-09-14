// src/components/statistics/ChartContainer.jsx
import React from 'react';

const ChartContainer = ({ children, className = '' }) => {
  return (
    <div className={`stats-chart-container ${className}`}>
      {children}
    </div>
  );
};

export default ChartContainer;
// src/components/statistics/Section.jsx
import React from 'react';

const Section = ({ icon, title, description, children }) => {
  return (
    <div className="stats-section">
      <h2 className="stats-section-title">
        {icon}
        {title}
      </h2>
      {description && (
        <p className="stats-section-description">{description}</p>
      )}
      {children}
    </div>
  );
};

export default Section;
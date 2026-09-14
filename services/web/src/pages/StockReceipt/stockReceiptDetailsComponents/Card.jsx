import React from 'react';

const Card = ({ children, className = '', title, icon: Icon, headerAction }) => {
  return (
    <div className={`srd-card ${className}`}>
      {(title || Icon) && (
        <div className="srd-card-header">
          {Icon && <Icon size={20} className="srd-card-icon" />}
          {title && <h3 className="srd-card-title">{title}</h3>}
          {headerAction && (
            <div className="srd-card-action">
              {headerAction}
            </div>
          )}
        </div>
      )}
      <div className="srd-card-body">
        {children}
      </div>
    </div>
  );
};

export default Card;
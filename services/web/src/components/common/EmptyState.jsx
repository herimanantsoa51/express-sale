import React from 'react';
import '../../styles/EmptyState.css';

const EmptyState = ({
  icon: Icon,
  title,
  description,
  action,
  className = ''
}) => {
  const baseClass = 'empty-state';
  const classes = [baseClass, className].filter(Boolean).join(' ');

  return (
    <div className={classes}>
      {Icon && (
        <div className="empty-state-icon">
          <Icon size={64} />
        </div>
      )}
      
      {title && (
        <h3 className="empty-state-title">
          {title}
        </h3>
      )}
      
      {description && (
        <p className="empty-state-description">
          {description}
        </p>
      )}
      
      {action && (
        <div className="empty-state-action">
          {action}
        </div>
      )}
    </div>
  );
};

export default EmptyState;